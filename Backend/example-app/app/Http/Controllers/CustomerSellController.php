<?php

namespace App\Http\Controllers;

use App\Actions\Offers\OfferIsFromFoodEstablishmentAction;
use App\Actions\Offers\ValidateOfferExpirationFromDTOAction;
use App\Actions\Offers\ValidateOfferIsActiveAction;
use App\Actions\Offers\ValidateOfferVisibilityFromDTOAction;
use App\Actions\Sell\CancelPurchaseAction;
use App\Actions\Sell\getCustomerSellsAction;
use App\Actions\Sell\makeSellAction;
use App\Actions\Sell\ValidateCustomerOwnershipAction;
use App\Actions\Sell\VerifyPurchaseDataFreshnessAction;
use App\DTOs\PreparePurchaseDTO;
use App\Enums\CartState;
use App\Enums\SellState;
use App\Events\PurchaseCompleted;
use App\Exceptions\ApiErrorRenderer;
use App\Exceptions\ApiException;
use App\Exceptions\BusinessRuleException;
use App\Http\Middleware\AssignRequestId;
use App\Http\Resources\SellResource;
use App\Models\FoodEstablishment;
use App\Models\Sell;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerSellController extends Controller
{
    public function __construct(
        private readonly ValidateOfferExpirationFromDTOAction $validateOfferExpirationFromDTOAction,
        private readonly ValidateOfferVisibilityFromDTOAction $validateOfferVisibilityFromDTOAction,
        private readonly ValidateOfferIsActiveAction $validateOfferIsActiveAction,
        private readonly OfferIsFromFoodEstablishmentAction $offerIsFromFoodEstablishmentAction,
        private readonly getCustomerSellsAction $getCustomerSellsAction,
        private readonly makeSellAction $makeSellAction,
        private readonly VerifyPurchaseDataFreshnessAction $verifyPurchaseDataFreshnessAction,
        private readonly ValidateCustomerOwnershipAction $validateCustomerOwnershipAction,
        private readonly CancelPurchaseAction $cancelPurchaseAction,
    ) {}

    public function buyOffers(Request $request)
    {
        $purchaseToken = $request->input('purchase_token');
        if (! $purchaseToken) {
            throw BusinessRuleException::badRequest('Token de compra inválido o expirado', 'INVALID_PURCHASE_TOKEN');
        }

        $lock = Cache::lock('purchase_token_'.$purchaseToken, 300);

        if (! $lock->get()) {
            throw BusinessRuleException::badRequest('Token de compra inválido o expirado', 'INVALID_PURCHASE_TOKEN');
        }

        // Reclamar y remover el token de la sesión
        $purchaseData = session()->pull('purchase_'.$purchaseToken);
        if (! $purchaseData) {
            $lock->release();
            throw BusinessRuleException::badRequest('Token de compra inválido o expirado', 'INVALID_PURCHASE_TOKEN');
        }
        session()->save();

        if (now()->isAfter($purchaseData['expires_at'])) {
            $lock->release();
            throw BusinessRuleException::badRequest('El tiempo para confirmar la compra ha expirado', 'PURCHASE_TOKEN_EXPIRED');
        }

        try {
            $preparePurchaseDTO = PreparePurchaseDTO::clone($purchaseData['preparePurchaseDTO']);

            $sellResult = null;

            DB::transaction(function () use ($preparePurchaseDTO, &$sellResult) {
                $this->validateOfferExpirationFromDTOAction->execute($preparePurchaseDTO->offers);
                $this->validateOfferVisibilityFromDTOAction->execute($preparePurchaseDTO->offers);
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

        } catch (ApiException|ValidationException|QueryException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            throw BusinessRuleException::badRequest($exception->getMessage(), 'PURCHASE_CONFIRMATION_FAILED');
        } finally {
            $lock->release();
        }
    }

    /**
     * @throws Exception
     */
    public function prepareBuyOffers(Request $request): JsonResponse
    {
        $request->validate([
            'food_establishment_id' => 'required|integer|exists:food_establishments,id',
            'offers' => 'required|array|min:1',
            'offers.*.id' => 'required|integer|exists:offers,id',
            'offers.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $preparePurchaseDTO = PreparePurchaseDTO::fromRequest($request);

            $this->validateOfferExpirationFromDTOAction->execute($preparePurchaseDTO->offers);
            $this->validateOfferVisibilityFromDTOAction->execute($preparePurchaseDTO->offers);
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

        } catch (ApiException|ValidationException|QueryException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            $requestId = AssignRequestId::resolveRequestId($request);

            return response()->json([
                'error' => 'Failed to prepare purchase',
                'message' => $exception->getMessage(),
                'error_code' => 'PURCHASE_PREPARATION_FAILED',
                'errors' => null,
                'context' => [
                    'request_id' => $requestId,
                ],
            ], 500, [
                AssignRequestId::HEADER_NAME => $requestId,
            ]);
        }
    }

    public function customerPurchases(Request $request)
    {
        $customerId = Auth::id();

        $customerSells = $this->getCustomerSellsAction->execute($customerId);

        return response()->json([
            'data' => $customerSells,
        ]);
    }

    public function getPurchaseCode(Request $request, string $sellNumber)
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

        } catch (ApiException|ValidationException|QueryException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            $statusCode = (is_int($exception->getCode()) && $exception->getCode() >= 400 && $exception->getCode() < 600)
                ? $exception->getCode()
                : 500;

            if ($statusCode >= 500) {
                throw $exception;
            }

            return ApiErrorRenderer::buildResponse(
                requestId: AssignRequestId::resolveRequestId($request),
                status: $statusCode,
                message: $exception->getMessage(),
                errorCode: $statusCode === 404 ? 'RESOURCE_NOT_FOUND' : ($statusCode === 403 ? 'FORBIDDEN' : 'BUSINESS_RULE_VIOLATION')
            );
        }
    }

    public function historySell(): JsonResponse
    {
        $customerId = Auth::id();

        $sells = Sell::with(['foodEstablishment', 'sellDetails.offer', 'review'])
            ->where('bought_by', $customerId)
            ->where('state', SellState::PICKED_UP->value)
            ->where('is_picked_up', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Historial de compras obtenido exitosamente',
            'data' => SellResource::collection($sells),
        ], 200);
    }

    /**
     * Cancela una compra y emite el reembolso correspondiente si cumple la política.
     */
    public function cancelPurchase(Request $request, string $sellNumber): JsonResponse
    {
        try {
            $customerId = Auth::id();

            // Valida pertenencia de la compra al cliente autenticado
            $sell = $this->validateCustomerOwnershipAction->execute($sellNumber, $customerId);

            // Ejecuta la cancelación bajo la supervisión de la política
            $result = $this->cancelPurchaseAction->execute($sell, Auth::user());

            return response()->json([
                'message' => $result['message'],
                'data' => [
                    'sell_id' => $sell->id,
                    'state' => SellState::CANCELLED->value,
                ],
            ], 200);

        } catch (ApiException|ValidationException|QueryException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            $statusCode = (is_int($exception->getCode()) && $exception->getCode() >= 400 && $exception->getCode() < 600)
                ? $exception->getCode()
                : 422;

            return ApiErrorRenderer::buildResponse(
                requestId: AssignRequestId::resolveRequestId($request),
                status: $statusCode,
                message: $exception->getMessage(),
                errorCode: 'CANCELLATION_FAILED'
            );
        }
    }
}
