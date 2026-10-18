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

            $sell = Sell::find($sellId);
            if (! $sell) {
                return response()->json(['message' => 'Venta no encontrada'], 200);
            }

            if ($details['status'] === 'approved' && $sell->payment_status !== PaymentStatus::APPROVED) {
                $sell->update([
                    'payment_status' => PaymentStatus::APPROVED->value,
                    'payment_id' => (string) $paymentId,
                    'payment_method' => $details['payment_method_id'] ?? $sell->payment_method,
                    'state' => SellState::PENDING->value,
                ]);

                PurchaseCompleted::dispatch($sell);
            } elseif ($details['status'] === 'refunded' && $sell->payment_status !== PaymentStatus::REFUNDED) {
                $sell->update([
                    'payment_status' => PaymentStatus::REFUNDED->value,
                    'refunded_at' => now(),
                ]);
            }

            return response()->json(['status' => 'success'], 200);

        } catch (Exception $e) {
            Log::error('Excepción en Webhook de Mercado Pago:', ['error' => $e->getMessage()]);

            // Responder 200 para evitar reintentos infinitos si fue un error no recuperable
            return response()->json(['error' => $e->getMessage()], 200);
        }
    }
}
