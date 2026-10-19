<?php

namespace App\Actions\Sell;

use App\Actions\Offers\RestoreOfferStockAction;
use App\Contracts\Payment\PaymentServiceInterface;
use App\Enums\PaymentStatus;
use App\Enums\SellState;
use App\Exceptions\CancellationNotAllowedException;
use App\Models\Sell;
use App\Models\User;
use App\Policies\PurchaseCancellationPolicy;
use Illuminate\Support\Facades\DB;

class CancelPurchaseAction
{
    public function __construct(
        private readonly PurchaseCancellationPolicy $policy,
        private readonly RestoreOfferStockAction $restoreOfferStockAction,
        private readonly PaymentServiceInterface $paymentService,
    ) {}

    /**
     * Ejecuta la cancelación de una compra y la restitución de stock bajo el arbitraje de la política de cancelación.
     *
     * @throws \Throwable
     */
    public function execute(Sell $sell, ?User $customer = null): array
    {
        // 1. Validar la política de cancelación
        $this->policy->assertCanCancel($sell, $customer);

        // 2. Ejecutar la mutación en transacción atómica
        return DB::transaction(function () use ($sell) {
            // Actualización condicional atómica: solo cancela si no estaba cancelada previamente
            $affected = Sell::where('id', $sell->id)
                ->where('state', '!=', SellState::CANCELLED->value)
                ->update([
                    'state' => SellState::CANCELLED->value,
                    'payment_status' => PaymentStatus::REFUNDED->value,
                    'refunded_at' => now(),
                    'cancelled_by' => 'customer',
                    'cancellation_reason' => 'Cancelación voluntaria del comprador dentro de plazo',
                ]);

            if ($affected === 0) {
                throw new CancellationNotAllowedException('La compra ya se encuentra cancelada.', 422);
            }

            // Si tiene pago registrado en Mercado Pago, emitir reembolso
            if ($sell->payment_id) {
                try {
                    $this->paymentService->refundPayment($sell->payment_id);
                } catch (\Throwable $e) {
                    // Loguear pero continuar para no romper la cancelación del usuario
                    \Illuminate\Support\Facades\Log::error('Fallo al emitir reembolso en pasarela:', [
                        'sell_id' => $sell->id,
                        'payment_id' => $sell->payment_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Sincronizar el modelo en memoria y cargar detalles
            $sell->refresh();
            $sell->loadMissing('sellDetails');

            // Restituir el stock de cada oferta delegando en la acción de dominio de ofertas
            foreach ($sell->sellDetails as $detail) {
                if ($detail->offer_id && $detail->offer_quantity > 0) {
                    $this->restoreOfferStockAction->execute(
                        (int) $detail->offer_id,
                        (int) $detail->offer_quantity
                    );
                }
            }

            // Notificar al vendedor en tiempo real que el cliente canceló la reserva
            $sell->loadMissing(['foodEstablishment.user', 'customer']);
            $sellerUser = $sell->foodEstablishment?->user;
            if ($sellerUser) {
                $sellerUser->notify(new \App\Notifications\RealtimeOrderNotification(
                    eventType: 'cancelled_by_customer',
                    title: 'Reserva cancelada por cliente',
                    message: "El cliente canceló la reserva #{$sell->id}. El stock fue restituido automáticamente.",
                    sellId: $sell->id,
                    establishmentId: $sell->sold_by,
                    actionUrl: '/seller/sells',
                    meta: [
                        'sell_id' => $sell->id,
                        'customer_name' => $sell->customer?->name,
                    ]
                ));
            }

            return [
                'success' => true,
                'message' => 'Compra cancelada y reembolso emitido exitosamente',
                'sell_id' => $sell->id,
            ];
        });
    }
}
