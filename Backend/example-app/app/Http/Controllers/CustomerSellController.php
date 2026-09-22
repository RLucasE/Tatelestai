<?php

namespace App\Http\Controllers;

use App\Actions\Offers\OfferIsFromFoodEstablishmentAction;
use App\Actions\Offers\ValidateOfferExpirationFromDTOAction;
use App\Actions\Offers\ValidateOfferIsActiveAction;
use App\Actions\Sell\getCustomerSellsAction;
use App\Actions\Sell\makeSellAction;
use App\Actions\Sell\VerifyPurchaseDataFreshnessAction;
use App\DTOs\PreparePurchaseDTO;
use App\Enums\CartState;
use App\Events\PurchaseCompleted;
use App\Http\Resources\SellResource;
use App\Models\FoodEstablishment;
use App\Models\Sell;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CustomerSellController extends Controller
{
    public function __construct(
        private readonly ValidateOfferExpirationFromDTOAction $validateOfferExpirationFromDTOAction,
        private readonly ValidateOfferIsActiveAction $validateOfferIsActiveAction,
        private readonly OfferIsFromFoodEstablishmentAction $offerIsFromFoodEstablishmentAction,
        private readonly getCustomerSellsAction $getCustomerSellsAction,
        private readonly makeSellAction $makeSellAction,
        private readonly VerifyPurchaseDataFreshnessAction $verifyPurchaseDataFreshnessAction,
        private readonly \App\Actions\Sell\ValidateCustomerOwnershipAction $validateCustomerOwnershipAction,
    ) {}

    public function buyOffers(Request $request)
    {
        try {
            $purchaseToken = $request->input('purchase_token');
            if (! $purchaseToken) {
                return response()->json([
                    'error' => 'Token de compra inválido o expirado',
                ], 400);
            }
            $lock = Cache::lock('purchase_token_'.$purchaseToken, 300);

            if (! $lock->get()) {
                return response()->json([
                    'error' => 'Token de compra inválido o expirado',
                ], 400);
            }

            // Reclamar y remover el token de la sesión
            $purchaseData = session()->pull('purchase_'.$purchaseToken);
            if (! $purchaseData) {
                return response()->json([
                    'error' => 'Token de compra inválido o expirado',
                ], 400);
            }
            session()->save();

            if (now()->isAfter($purchaseData['expires_at'])) {
                return response()->json([
                    'error' => 'El tiempo para confirmar la compra ha expirado',
                ], 400);
            }

            $preparePurchaseDTO = PreparePurchaseDTO::clone($purchaseData['preparePurchaseDTO']);

            $sellResult = null;

            DB::transaction(function () use ($preparePurchaseDTO, &$sellResult) {
                $this->validateOfferExpirationFromDTOAction->execute($preparePurchaseDTO->offers);
                $this->validateOfferIsActiveAction->execute($preparePurchaseDTO->offers);
                $this->offerIsFromFoodEstablishmentAction->execute(
                    $preparePurchaseDTO->offers,
                    $preparePurchaseDTO->food_establishment_id
                );
                $this->verifyPurchaseDataFreshnessAction->execute($preparePurchaseDTO);
                $sellResult = $this->makeSellAction->execute(
                    $preparePurchaseDTO,
                    Auth::id(),
                    $preparePurchaseDTO->food_establishment_id
                );

                $activeCart = app(CartController::class)->getActiveCartByEstablishment(
                    Auth::id(),
                    $preparePurchaseDTO->food_establishment_id
                );
                if ($activeCart) {
                    $activeCart->update(['state' => CartState::PURCHASED->value]);
                }
            });
            $sell = Sell::with(['customer', 'foodEstablishment', 'sellDetails'])
                ->findOrFail($sellResult['sell_id']);

            if ($sell) {
                PurchaseCompleted::dispatch($sell);
            }

            return response()->json([
                'message' => 'Compra realizada con éxito',
                'data' => [
                    'sell_id' => $sell->id,
                    'pickup_code' => $sell->pickup_code,
                    'max_pickup_datetime' => $sell->max_pickup_datetime,
                    'food_establishment_id' => $preparePurchaseDTO->food_establishment_id,
                    'establishment' => [
                        'id' => $sell->foodEstablishment?->id,
                        'name' => $sell->foodEstablishment?->name,
                        'address' => $sell->foodEstablishment?->address,
                    ],
                    'offers' => $preparePurchaseDTO->offers,
                ],
            ], 200);

        } catch (Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage(),
                'line' => $exception->getLine(),
            ], 400);
        }
    }

    /**
     * @throws Exception
     */
    public function prepareBuyOffers(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'food_establishment_id' => 'required|integer|exists:food_establishments,id',
                'offers' => 'required|array|min:1',
                'offers.*.id' => 'required|integer|exists:offers,id',
                'offers.*.quantity' => 'required|integer|min:1',
            ]);

            $preparePurchaseDTO = PreparePurchaseDTO::fromRequest($request);

            $this->validateOfferExpirationFromDTOAction->execute($preparePurchaseDTO->offers);
            $this->validateOfferIsActiveAction->execute($preparePurchaseDTO->offers);
            $this->offerIsFromFoodEstablishmentAction->execute(
                $preparePurchaseDTO->offers,
                $preparePurchaseDTO->food_establishment_id
            );

            $establishment = FoodEstablishment::find($preparePurchaseDTO->food_establishment_id);

            $purchaseToken = md5(uniqid(Auth::id(), true));

            session()->put('purchase_'.$purchaseToken, [
                'preparePurchaseDTO' => $preparePurchaseDTO,
                'expires_at' => now()->addMinutes(5),
            ]);

            return response()->json([
                'message' => 'Purchase preparation completed successfully',
                'data' => [
                    'purchase_token' => $purchaseToken,
                    'offers' => $preparePurchaseDTO,
                    'total_offers' => count($preparePurchaseDTO->offers),
                    'food_establishment_id' => $preparePurchaseDTO->food_establishment_id,
                    'establishment' => [
                        'id' => $establishment?->id,
                        'name' => $establishment?->name,
                        'address' => $establishment?->address,
                    ],
                    'expires_at' => now()->addMinutes(5)->toDateTimeString(),
                ],
            ], 200);

        } catch (Exception $exception) {
            return response()->json([
                'error' => 'Failed to prepare purchase',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function customerPurchases(Request $request)
    {
        try {
            $customerId = Auth::id();

            $customerSells = $this->getCustomerSellsAction->execute($customerId);

            return response()->json([
                'data' => $customerSells,
            ]);
        } catch (Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function getPurchaseCode(string $sellNumber)
    {
        try {
            $customerId = Auth::id();

            $sell = $this->validateCustomerOwnershipAction->execute($sellNumber, $customerId);

            return response()->json([
                'message' => 'Código de retiro obtenido exitosamente',
                'data' => [
                    'sell_id' => $sell->id,
                    'pickup_code' => $sell->pickup_code,
                    'establishment' => [
                        'id' => $sell->foodEstablishment->id,
                        'name' => $sell->foodEstablishment->name,
                        'address' => $sell->foodEstablishment->address,
                    ],
                    'created_at' => $sell->created_at,
                ],
            ], 200);

        } catch (Exception $exception) {
            $statusCode = $exception->getCode() ?: 500;

            return response()->json([
                'error' => $exception->getMessage(),
            ], $statusCode);
        }
    }

    public function historySell(): JsonResponse
    {
        try {
            $customerId = Auth::id();

            $sells = Sell::with(['foodEstablishment', 'sellDetails.offer'])
                ->where('bought_by', $customerId)
                ->where('is_picked_up', true)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'message' => 'Historial de compras obtenido exitosamente',
                'data' => SellResource::collection($sells),
            ], 200);

        } catch (Exception $exception) {
            $statusCode = $exception->getCode() ?: 500;

            return response()->json([
                'error' => $exception->getMessage(),
            ], $statusCode);
        }
    }
}
