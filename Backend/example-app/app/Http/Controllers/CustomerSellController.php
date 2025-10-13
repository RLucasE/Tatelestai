<?php

namespace App\Http\Controllers;

use App\Actions\Offers\OfferIsFromFoodEstablishmentAction;
use App\Actions\Offers\ValidateOfferExpirationFromDTOAction;
use App\Actions\Offers\ValidateOfferIsActiveAction;
use App\Actions\Sell\getCustomerSellsAction;
use App\Actions\Sell\makeSellAction;
use App\Actions\Sell\VerifyPurchaseDataFreshnessAction;
use App\DTOs\PreparePurchaseDTO;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerSellController extends Controller
{
    public function __construct(
        private readonly ValidateOfferExpirationFromDTOAction $validateOfferExpirationFromDTOAction,
        private readonly ValidateOfferIsActiveAction          $validateOfferIsActiveAction,
        private readonly OfferIsFromFoodEstablishmentAction   $offerIsFromFoodEstablishmentAction,
        private readonly getCustomerSellsAction               $getCustomerSellsAction,
        private readonly makeSellAction                       $makeSellAction,
        private readonly VerifyPurchaseDataFreshnessAction    $verifyPurchaseDataFreshnessAction,
        private readonly \App\Actions\Sell\ValidateCustomerOwnershipAction $validateCustomerOwnershipAction,
    )
    {
    }

    public function buyOffers(Request $request)
    {
        try {
            $purchaseToken = $request->input('purchase_token');
            if (!$purchaseToken || !session()->has('purchase_' . $purchaseToken)) {
                return response()->json([
                    'error' => 'Token de compra inválido o expirado'
                ], 400);
            }
            $purchaseData = session()->get('purchase_' . $purchaseToken);
            if (now()->isAfter($purchaseData['expires_at'])) {
                session()->forget('purchase_' . $purchaseToken);
                return response()->json([
                    'error' => 'El tiempo para confirmar la compra ha expirado'
                ], 400);
            }

            $preparePurchaseDTO = PreparePurchaseDTO::clone($purchaseData['preparePurchaseDTO']);

            DB::transaction(function () use ($preparePurchaseDTO) {
                $this->validateOfferExpirationFromDTOAction->execute($preparePurchaseDTO->offers);
                $this->validateOfferIsActiveAction->execute($preparePurchaseDTO->offers);
                $this->offerIsFromFoodEstablishmentAction->execute(
                    $preparePurchaseDTO->offers,
                    $preparePurchaseDTO->food_establishment_id
                );
                $this->verifyPurchaseDataFreshnessAction->execute($preparePurchaseDTO);
                $this->makeSellAction->execute(
                    $preparePurchaseDTO,
                    Auth::id(),
                    $preparePurchaseDTO->food_establishment_id
                );
            });

            return response()->json([
                'message' => 'Compra realizada con éxito',
                'data' => $preparePurchaseDTO
            ], 200);

        } catch (Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage(),
                'line' => $exception->getLine()
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

            $purchaseToken = md5(uniqid(Auth::id(), true));

            session()->put('purchase_' . $purchaseToken, [
                'preparePurchaseDTO' => $preparePurchaseDTO,
                'expires_at' => now()->addMinutes(5)
            ]);

            return response()->json([
                'message' => 'Purchase preparation completed successfully',
                'data' => [
                    'purchase_token' => $purchaseToken,
                    'offers' => $preparePurchaseDTO,
                    'total_offers' => count($preparePurchaseDTO->offers),
                    'food_establishment_id' => $preparePurchaseDTO->food_establishment_id,
                    'expires_at' => now()->addMinutes(5)->toDateTimeString(),
                ]
            ], 200);

        } catch (Exception $exception) {
            return response()->json([
                'error' => 'Failed to prepare purchase',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function customerPurchases(Request $request)
    {
        try {
            $customerId = Auth::id();

            $customerSells = $this->getCustomerSellsAction->execute($customerId);

            return response()->json([
                'data' => $customerSells
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
                ]
            ], 200);

        } catch (Exception $exception) {
            $statusCode = $exception->getCode() ?: 500;
            return response()->json([
                'error' => $exception->getMessage()
            ], $statusCode);
        }
    }

    public function historySell(): JsonResponse
    {
        try {
            $customerId = Auth::id();

            $sells = \App\Models\Sell::with(['foodEstablishment', 'sellDetails.offer'])
                ->where('bought_by', $customerId)
                ->where('is_picked_up', true)
                ->orderBy('created_at', 'desc')
                ->get();

            $history = $sells->map(function ($sell) {
                return [
                    'sell_id' => $sell->id,
                    'is_picked_up' => $sell->is_picked_up,
                    'created_at' => $sell->created_at,
                    'establishment' => [
                        'id' => $sell->foodEstablishment->id,
                        'name' => $sell->foodEstablishment->name,
                        'address' => $sell->foodEstablishment->address,
                    ],
                    'offers' => $sell->sellDetails->map(function ($detail) {
                        return [
                            'offer_id' => $detail->offer_id,
                            'offer_quantity' => $detail->offer_quantity,
                            'product_name' => $detail->product_name,
                            'product_description' => $detail->product_description,
                            'product_quantity' => $detail->product_quantity,
                            'product_price' => $detail->product_price,
                        ];
                    })->toArray(),
                ];
            });

            return response()->json([
                'message' => 'Historial de compras obtenido exitosamente',
                'data' => $history
            ], 200);

        } catch (Exception $exception) {
            $statusCode = $exception->getCode() ?: 500;
            return response()->json([
                'error' => $exception->getMessage()
            ], $statusCode);
        }
    }
}
