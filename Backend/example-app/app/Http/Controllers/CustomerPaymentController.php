<?php

namespace App\Http\Controllers;

use App\Actions\Offers\OfferIsFromFoodEstablishmentAction;
use App\Actions\Offers\RestoreOfferStockAction;
use App\Actions\Offers\ValidateOfferExpirationFromDTOAction;
use App\Actions\Offers\ValidateOfferIsActiveAction;
use App\Actions\Offers\ValidateOfferVisibilityFromDTOAction;
use App\Actions\Sell\makeSellAction;
use App\Actions\Sell\VerifyPurchaseDataFreshnessAction;
use App\Contracts\Payment\PaymentServiceInterface;
use App\DTOs\PreparePurchaseDTO;
use App\Enums\CartState;
use App\Enums\PaymentStatus;
use App\Enums\SellState;
use App\Events\PurchaseCompleted;
use App\Exceptions\ApiException;
use App\Exceptions\BusinessRuleException;
use App\Http\Middleware\AssignRequestId;
use App\Http\Requests\CreatePaymentPreferenceRequest;
use App\Http\Requests\VerifyPaymentRequest;
use App\Models\Sell;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CustomerPaymentController extends Controller
{
    public function __construct(
        private readonly PaymentServiceInterface $paymentService,
        private readonly ValidateOfferExpirationFromDTOAction $validateOfferExpirationFromDTOAction,
        private readonly ValidateOfferVisibilityFromDTOAction $validateOfferVisibilityFromDTOAction,
        private readonly ValidateOfferIsActiveAction $validateOfferIsActiveAction,
        private readonly OfferIsFromFoodEstablishmentAction $offerIsFromFoodEstablishmentAction,
        private readonly VerifyPurchaseDataFreshnessAction $verifyPurchaseDataFreshnessAction,
        private readonly makeSellAction $makeSellAction,
        private readonly RestoreOfferStockAction $restoreOfferStockAction,
    ) {}

    /**
     * Prepara la orden y genera la preferencia de Checkout Pro en Mercado Pago.
     */
    public function createPreference(CreatePaymentPreferenceRequest $request): JsonResponse
    {
        $purchaseToken = $request->validated('purchase_token');

        $lock = Cache::lock('purchase_token_'.$purchaseToken, 300);
        if (! $lock->get()) {
            throw BusinessRuleException::badRequest('Token de compra inválido o en procesamiento', 'INVALID_PURCHASE_TOKEN');
        }

        try {
            $purchaseData = session()->get('purchase_'.$purchaseToken);
            if (! $purchaseData) {
                throw BusinessRuleException::badRequest('Token de compra inválido o expirado', 'INVALID_PURCHASE_TOKEN');
            }

            if (now()->isAfter($purchaseData['expires_at'])) {
                throw BusinessRuleException::badRequest('El tiempo para confirmar la compra ha expirado', 'PURCHASE_TOKEN_EXPIRED');
            }

            $preparePurchaseDTO = PreparePurchaseDTO::clone($purchaseData['preparePurchaseDTO']);

            $sellResult = null;

            // Validación atómica de stock y reserva de la venta en estado pendiente
            DB::transaction(function () use ($preparePurchaseDTO, &$sellResult) {
                $this->validateOfferExpirationFromDTOAction->execute($preparePurchaseDTO->offers);
                $this->validateOfferVisibilityFromDTOAction->execute($preparePurchaseDTO->offers);
                $this->validateOfferIsActiveAction->execute($preparePurchaseDTO->offers);
                $this->offerIsFromFoodEstablishmentAction->execute(
                    $preparePurchaseDTO->offers,
                    $preparePurchaseDTO->food_establishment_id
                );
                $this->verifyPurchaseDataFreshnessAction->execute($preparePurchaseDTO);

                $sellResult = $this->makeSellAction->executeMultiple(
                    $preparePurchaseDTO,
                    Auth::id(),
                    $preparePurchaseDTO->food_establishment_id,
                    paymentStatus: PaymentStatus::PENDING->value,
                    cleanCart: false
                );
            });

            $sellIds = $sellResult['sell_ids'] ?? [$sellResult['sell_id']];
            $sells = Sell::with(['customer', 'foodEstablishment', 'sellDetails.offer'])
                ->whereIn('id', $sellIds)
                ->orderBy('id')
                ->get();

            $primarySell = $sells->firstOrFail();

            // Si se crearon múltiples Sells, combinar todos los sellDetails temporalmente en primarySell para que MP cobre el total
            if ($sells->count() > 1) {
                $allDetails = $sells->flatMap(fn (Sell $s) => $s->sellDetails);
                $primarySell->setRelation('sellDetails', $allDetails);
            }

            // Generar preferencia en Mercado Pago
            $preference = $this->paymentService->createPreference($primarySell, Auth::user());

            Sell::whereIn('id', $sellIds)->update([
                'preference_id' => $preference['preference_id'],
            ]);

            // Remover token de sesión tras reservar
            session()->forget('purchase_'.$purchaseToken);
            session()->save();

            // Recargar los detalles propios de cada sell
            $sells = Sell::with(['customer', 'foodEstablishment', 'sellDetails.offer'])
                ->whereIn('id', $sellIds)
                ->orderBy('id')
                ->get();
            $primarySell = $sells->firstOrFail();

            $totalGrossAmount = round((float) $sells->sum(fn (Sell $s) => $s->getGrossAmount()), 2);

            $sellsPayload = $sells->map(fn (Sell $s) => [
                'sell_id' => $s->id,
                'pickup_code' => $s->pickup_code,
                'pickup_start_datetime' => $s->getPickupStartDatetime(),
                'max_pickup_datetime' => $s->max_pickup_datetime,
                'gross_amount' => $s->getGrossAmount(),
            ])->values()->toArray();

            return response()->json([
                'message' => 'Preferencia de pago generada exitosamente',
                'data' => [
                    'sell_id' => $primarySell->id,
                    'sell_ids' => $sellIds,
                    'sells_count' => count($sellsPayload),
                    'sells' => $sellsPayload,
                    'preference_id' => $preference['preference_id'],
                    'init_point' => $preference['init_point'],
                    'sandbox_init_point' => $preference['sandbox_init_point'],
                    'gross_amount' => $totalGrossAmount,
                    'pickup_code' => $primarySell->pickup_code,
                    'max_pickup_datetime' => $primarySell->max_pickup_datetime,
                ],
            ], 200);

        } catch (ApiException|ValidationException|ModelNotFoundException|QueryException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Error al generar preferencia de Mercado Pago:', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            throw BusinessRuleException::unprocessable($e->getMessage(), 'PAYMENT_PREFERENCE_FAILED');
        } finally {
            $lock->release();
        }
    }

    /**
     * Verifica y concilia el pago tras el retorno de Mercado Pago Checkout Pro.
     */
    public function verifyPayment(VerifyPaymentRequest $request): JsonResponse
    {
        $sell = Sell::with(['customer', 'foodEstablishment', 'sellDetails.offer'])
            ->where('id', $request->validated('sell_id'))
            ->where('bought_by', Auth::id())
            ->firstOrFail();

        $preferenceId = $request->validated('preference_id') ?: $sell->preference_id;

        if ($preferenceId) {
            $sells = Sell::with(['customer', 'foodEstablishment', 'sellDetails.offer'])
                ->where('bought_by', Auth::id())
                ->where('preference_id', $preferenceId)
                ->orderBy('id')
                ->get();

            if ($sells->isEmpty()) {
                $sells = collect([$sell]);
            }
        } else {
            $sells = collect([$sell]);
        }

        $formatSellsPayload = fn ($collection) => $collection->map(fn (Sell $s) => [
            'sell_id' => $s->id,
            'pickup_code' => $s->pickup_code,
            'payment_status' => PaymentStatus::APPROVED->value,
            'pickup_start_datetime' => $s->getPickupStartDatetime(),
            'max_pickup_datetime' => $s->max_pickup_datetime,
            'gross_amount' => $s->getGrossAmount(),
            'offers' => $s->sellDetails->map(fn ($d) => [
                'id' => $d->offer_id,
                'title' => $d->pack_name,
                'description' => $d->pack_description,
                'price' => $d->pack_price,
                'quantity' => $d->offer_quantity,
            ])->values()->toArray(),
        ])->values()->toArray();

        // Si ya fue aprobado (por ejemplo, vía Webhook concurrente)
        if ($sell->payment_status === PaymentStatus::APPROVED) {
            $sellsPayload = $formatSellsPayload($sells);

            return response()->json([
                'message' => 'Pago verificado y orden confirmada exitosamente',
                'data' => [
                    'sell_id' => $sell->id,
                    'pickup_code' => $sell->pickup_code,
                    'payment_status' => PaymentStatus::APPROVED->value,
                    'max_pickup_datetime' => $sell->max_pickup_datetime,
                    'sells_count' => count($sellsPayload),
                    'sells' => $sellsPayload,
                ],
            ], 200);
        }

        $paymentId = $request->validated('payment_id');
        $status = $request->validated('status');

        $isApproved = false;
        $paymentMethod = null;

        if ($paymentId) {
            $details = $this->paymentService->getPaymentDetails($paymentId);
            $isApproved = ($details['status'] === 'approved');
            $paymentMethod = $details['payment_method_id'] ?? null;
        } elseif ($status === 'approved') {
            $isApproved = true;
        }

        if ($isApproved) {
            $purchasedOfferIds = [];

            foreach ($sells as $targetSell) {
                $targetSell->update([
                    'payment_status' => PaymentStatus::APPROVED->value,
                    'payment_id' => $paymentId ?: ($targetSell->payment_id ?: 'approved_direct'),
                    'payment_method' => $paymentMethod ?: 'mercadopago',
                    'state' => SellState::PENDING->value,
                ]);

                foreach ($targetSell->sellDetails as $detail) {
                    if ($detail->offer_id) {
                        $purchasedOfferIds[] = (int) $detail->offer_id;
                    }
                }
            }

            // Limpiar selectivamente del carrito las ofertas compradas
            if (! empty($purchasedOfferIds)) {
                $this->makeSellAction->cleanPurchasedOffersFromCart(
                    (int) Auth::id(),
                    (int) $sell->sold_by,
                    array_unique($purchasedOfferIds)
                );
            } else {
                $activeCart = app(CartController::class)->getActiveCartByEstablishment(
                    Auth::id(),
                    $sell->sold_by
                );
                if ($activeCart) {
                    $activeCart->update(['state' => CartState::PURCHASED->value]);
                }
            }

            foreach ($sells as $targetSell) {
                PurchaseCompleted::dispatch($targetSell);
            }

            $sellsPayload = $formatSellsPayload($sells);

            return response()->json([
                'message' => 'Pago confirmado exitosamente',
                'data' => [
                    'sell_id' => $sell->id,
                    'pickup_code' => $sell->pickup_code,
                    'payment_status' => PaymentStatus::APPROVED->value,
                    'max_pickup_datetime' => $sell->max_pickup_datetime,
                    'sells_count' => count($sellsPayload),
                    'sells' => $sellsPayload,
                ],
            ], 200);
        }

        // Si el pago fue rechazado o cancelado, revertir reserva de stock de todos los Sells vinculados
        DB::transaction(function () use ($sells, $paymentId) {
            foreach ($sells as $targetSell) {
                $targetSell->update([
                    'payment_status' => PaymentStatus::REJECTED->value,
                    'payment_id' => $paymentId,
                    'state' => SellState::CANCELLED->value,
                    'cancelled_by' => 'system',
                    'cancellation_reason' => 'Pago no aprobado en Mercado Pago',
                ]);

                foreach ($targetSell->sellDetails as $detail) {
                    if ($detail->offer_id && $detail->offer_quantity > 0) {
                        $this->restoreOfferStockAction->execute(
                            (int) $detail->offer_id,
                            (int) $detail->offer_quantity
                        );
                    }
                }
            }
        });

        $requestId = AssignRequestId::resolveRequestId($request);

        return response()->json([
            'message' => 'El pago no fue aprobado en Mercado Pago.',
            'error' => 'El pago no fue aprobado en Mercado Pago.',
            'error_code' => 'PAYMENT_NOT_APPROVED',
            'errors' => null,
            'data' => [
                'sell_id' => $sell->id,
                'payment_status' => PaymentStatus::REJECTED->value,
            ],
            'context' => [
                'request_id' => $requestId,
                'sell_id' => $sell->id,
                'payment_status' => PaymentStatus::REJECTED->value,
            ],
        ], 422, [
            AssignRequestId::HEADER_NAME => $requestId,
        ]);
    }
}
