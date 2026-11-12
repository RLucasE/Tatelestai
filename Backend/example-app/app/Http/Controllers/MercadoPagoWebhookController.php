<?php

namespace App\Http\Controllers;

use App\Contracts\Payment\PaymentServiceInterface;
use App\Enums\PaymentStatus;
use App\Enums\SellState;
use App\Events\PurchaseCompleted;
use App\Models\Sell;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentServiceInterface $paymentService,
    ) {}

    /**
     * Endpoint público para recibir notificaciones asíncronas IPN de Mercado Pago.
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $paymentId = $request->input('data.id')
                ?? $request->input('id')
                ?? $request->query('data_id')
                ?? $request->query('id');

            $type = $request->input('type') ?? $request->query('topic');

            if (! $paymentId || ($type && ! in_array($type, ['payment', 'payment.updated', 'payment.created']))) {
                return response()->json(['message' => 'Evento omitido'], 200);
            }

            $details = $this->paymentService->getPaymentDetails((string) $paymentId);

            $sellId = $details['external_reference'] ?? null;
            if (! $sellId) {
                return response()->json(['message' => 'Sin referencia de venta'], 200);
            }

            $result = DB::transaction(function () use ($sellId, $details, $paymentId) {
                $sell = Sell::whereKey($sellId)->lockForUpdate()->first();
                if (! $sell) {
                    return [
                        'not_found' => true,
                        'approved_sells' => [],
                        'purchased_offer_ids' => [],
                        'bought_by' => null,
                        'sold_by' => null,
                    ];
                }

                $sells = $sell->preference_id
                    ? Sell::where('preference_id', $sell->preference_id)->orderBy('id')->lockForUpdate()->get()
                    : collect([$sell]);

                if ($sells->isEmpty()) {
                    $sells = collect([$sell]);
                }

                $approvedSells = [];
                $purchasedOfferIds = [];

                foreach ($sells as $targetSell) {
                    if (
                        $details['status'] === 'approved'
                        && $targetSell->payment_status !== PaymentStatus::APPROVED
                        && ! $targetSell->isRefundedOrPartiallyRefunded()
                    ) {
                        $targetSell->update([
                            'payment_status' => PaymentStatus::APPROVED->value,
                            'payment_id' => (string) $paymentId,
                            'payment_method' => $details['payment_method_id'] ?? $targetSell->payment_method,
                            'state' => SellState::PENDING->value,
                        ]);

                        $targetSell->loadMissing('sellDetails');
                        foreach ($targetSell->sellDetails as $detail) {
                            if ($detail->offer_id) {
                                $purchasedOfferIds[] = (int) $detail->offer_id;
                            }
                        }

                        $approvedSells[] = $targetSell->fresh();
                    } elseif (
                        $details['status'] === 'refunded'
                        && $sells->count() === 1
                        && ! $targetSell->isRefundedOrPartiallyRefunded()
                    ) {
                        $targetSell->update([
                            'payment_status' => PaymentStatus::REFUNDED->value,
                            'refunded_amount' => $targetSell->getGrossAmount(),
                            'refunded_at' => now(),
                        ]);
                    }
                }

                return [
                    'not_found' => false,
                    'approved_sells' => $approvedSells,
                    'purchased_offer_ids' => array_unique($purchasedOfferIds),
                    'bought_by' => (int) $sell->bought_by,
                    'sold_by' => (int) $sell->sold_by,
                ];
            });

            if ($result['not_found']) {
                return response()->json(['message' => 'Venta no encontrada'], 200);
            }

            foreach ($result['approved_sells'] as $approvedSell) {
                PurchaseCompleted::dispatch($approvedSell);
            }

            if (! empty($result['purchased_offer_ids'])) {
                app(\App\Actions\Sell\makeSellAction::class)->cleanPurchasedOffersFromCart(
                    $result['bought_by'],
                    $result['sold_by'],
                    $result['purchased_offer_ids']
                );
            }

            return response()->json(['status' => 'success'], 200);

        } catch (Exception $e) {
            Log::error('Excepción en Webhook de Mercado Pago:', ['error' => $e->getMessage()]);

            // Responder 200 para evitar reintentos infinitos si fue un error no recuperable
            return response()->json(['error' => $e->getMessage()], 200);
        }
    }
}
