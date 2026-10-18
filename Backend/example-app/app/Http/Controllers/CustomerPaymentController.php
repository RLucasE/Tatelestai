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
use App\Http\Requests\CreatePaymentPreferenceRequest;
use App\Http\Requests\VerifyPaymentRequest;
use App\Models\Sell;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            return response()->json([
                'error' => 'Token de compra inválido o en procesamiento',
            ], 400);
        }

        try {
            $purchaseData = session()->get('purchase_'.$purchaseToken);
            if (! $purchaseData) {
                return response()->json([
                    'error' => 'Token de compra inválido o expirado',
                ], 400);
            }

            if (now()->isAfter($purchaseData['expires_at'])) {
                return response()->json([
                    'error' => 'El tiempo para confirmar la compra ha expirado',
                ], 400);
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

                $sellResult = $this->makeSellAction->execute(
                    $preparePurchaseDTO,
                    Auth::id(),
                    $preparePurchaseDTO->food_establishment_id,
                    paymentStatus: PaymentStatus::PENDING->value
                );
            });

            $sell = Sell::with(['customer', 'foodEstablishment', 'sellDetails'])
                ->findOrFail($sellResult['sell_id']);

            // Generar preferencia en Mercado Pago
            $preference = $this->paymentService->createPreference($sell, Auth::user());

            $sell->update([
                'preference_id' => $preference['preference_id'],
            ]);

            // Remover token de sesión tras reservar
            session()->forget('purchase_'.$purchaseToken);
            session()->save();

            return response()->json([
                'message' => 'Preferencia de pago generada exitosamente',
                'data' => [
                    'sell_id' => $sell->id,
                    'preference_id' => $preference['preference_id'],
                    'init_point' => $preference['init_point'],
                    'sandbox_init_point' => $preference['sandbox_init_point'],
                    'gross_amount' => $sell->getGrossAmount(),
                    'pickup_code' => $sell->pickup_code,
                    'max_pickup_datetime' => $sell->max_pickup_datetime,
                ],
            ], 200);

        } catch (Exception $e) {
            Log::error('Error al generar preferencia de Mercado Pago:', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        } finally {
            $lock->release();
        }
    }

    /**
     * Verifica y concilia el pago tras el retorno de Mercado Pago Checkout Pro.
     */
    public function verifyPayment(VerifyPaymentRequest $request): JsonResponse
    {
        try {
            $sell = Sell::with(['customer', 'foodEstablishment', 'sellDetails'])
                ->where('id', $request->validated('sell_id'))
                ->where('bought_by', Auth::id())
                ->firstOrFail();

            // Si ya fue aprobado (por ejemplo, vía Webhook concurrente)
            if ($sell->payment_status === PaymentStatus::APPROVED) {
                return response()->json([
                    'message' => 'Pago verificado y orden confirmada exitosamente',
                    'data' => [
                        'sell_id' => $sell->id,
                        'pickup_code' => $sell->pickup_code,
                        'payment_status' => PaymentStatus::APPROVED->value,
                        'max_pickup_datetime' => $sell->max_pickup_datetime,
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
                $sell->update([
                    'payment_status' => PaymentStatus::APPROVED->value,
                    'payment_id' => $paymentId ?: ($sell->payment_id ?: 'approved_direct'),
                    'payment_method' => $paymentMethod ?: 'mercadopago',
                    'state' => SellState::PENDING->value,
                ]);

                // Transicionar carrito a comprado
                $activeCart = app(CartController::class)->getActiveCartByEstablishment(
                    Auth::id(),
                    $sell->sold_by
                );
                if ($activeCart) {
                    $activeCart->update(['state' => CartState::PURCHASED->value]);
                }

                PurchaseCompleted::dispatch($sell);

                return response()->json([
                    'message' => 'Pago confirmado exitosamente',
                    'data' => [
                        'sell_id' => $sell->id,
                        'pickup_code' => $sell->pickup_code,
                        'payment_status' => PaymentStatus::APPROVED->value,
                        'max_pickup_datetime' => $sell->max_pickup_datetime,
                    ],
                ], 200);
            }

            // Si el pago fue rechazado o cancelado, revertir reserva de stock
            DB::transaction(function () use ($sell, $paymentId) {
                $sell->update([
                    'payment_status' => PaymentStatus::REJECTED->value,
                    'payment_id' => $paymentId,
                    'state' => SellState::CANCELLED->value,
                    'cancelled_by' => 'system',
                    'cancellation_reason' => 'Pago no aprobado en Mercado Pago',
                ]);

                foreach ($sell->sellDetails as $detail) {
                    if ($detail->offer_id && $detail->offer_quantity > 0) {
                        $this->restoreOfferStockAction->execute(
                            (int) $detail->offer_id,
                            (int) $detail->offer_quantity
                        );
                    }
                }
            });

            return response()->json([
                'error' => 'El pago no fue aprobado en Mercado Pago.',
                'data' => [
                    'sell_id' => $sell->id,
                    'payment_status' => PaymentStatus::REJECTED->value,
                ],
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
