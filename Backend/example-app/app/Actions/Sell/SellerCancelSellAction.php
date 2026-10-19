<?php

namespace App\Actions\Sell;

use App\Actions\Offers\RestoreOfferStockAction;
use App\Contracts\Payment\PaymentServiceInterface;
use App\Enums\PaymentStatus;
use App\Enums\SellState;
use App\Exceptions\CancellationNotAllowedException;
use App\Models\FoodEstablishment;
use App\Models\Sell;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SellerCancelSellAction
{
    public function __construct(
        private readonly PaymentServiceInterface $paymentService,
        private readonly RestoreOfferStockAction $restoreOfferStockAction,
    ) {}

    /**
     * Cancela una orden desde el comercio por falta de merma o imprevisto, emitiendo reembolso al cliente.
     *
     * @throws Exception
     */
    public function execute(Sell $sell, User $sellerUser, string $reason = 'Comercio sin merma suficiente', bool $restoreStock = false): array
    {
        $establishment = FoodEstablishment::where('user_id', $sellerUser->id)->first();

        if (! $establishment || $sell->sold_by !== $establishment->id) {
            throw new CancellationNotAllowedException('No tienes permiso para gestionar los pedidos de este establecimiento.', 403);
        }

        if ($sell->is_picked_up || $sell->state === SellState::PICKED_UP) {
            throw new CancellationNotAllowedException('El pedido ya fue entregado y no puede ser cancelado.', 422);
        }

        if ($sell->state === SellState::CANCELLED) {
            throw new CancellationNotAllowedException('El pedido ya se encuentra cancelado.', 422);
        }

        return DB::transaction(function () use ($sell, $reason, $restoreStock) {
            $affected = Sell::where('id', $sell->id)
                ->where('state', '!=', SellState::CANCELLED->value)
                ->update([
                    'state' => SellState::CANCELLED->value,
                    'payment_status' => PaymentStatus::REFUNDED->value,
                    'refunded_at' => now(),
                    'cancelled_by' => 'seller',
                    'cancellation_reason' => $reason,
                ]);

            if ($affected === 0) {
                throw new CancellationNotAllowedException('El pedido ya se encuentra cancelado.', 422);
            }

            // Devolver automáticamente el 100% del dinero por Mercado Pago
            if ($sell->payment_id) {
                try {
                    $this->paymentService->refundPayment($sell->payment_id);
                } catch (\Throwable $e) {
                    Log::error('Fallo al emitir reembolso de comercio en pasarela:', [
                        'sell_id' => $sell->id,
                        'payment_id' => $sell->payment_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Opcionalmente restituir el stock si fue cancelación accidental
            if ($restoreStock) {
                $sell->loadMissing('sellDetails');
                foreach ($sell->sellDetails as $detail) {
                    if ($detail->offer_id && $detail->offer_quantity > 0) {
                        $this->restoreOfferStockAction->execute(
                            (int) $detail->offer_id,
                            (int) $detail->offer_quantity
                        );
                    }
                }
            }

            // Notificar al cliente que el vendedor canceló la orden y se emitió reembolso
            $sell->loadMissing(['customer', 'foodEstablishment']);
            if ($sell->customer) {
                $establishmentName = $sell->foodEstablishment?->name ?? 'El comercio';
                $sell->customer->notify(new \App\Notifications\RealtimeOrderNotification(
                    eventType: 'cancelled_by_seller',
                    title: 'Reserva cancelada por el comercio',
                    message: "{$establishmentName} canceló tu pedido #{$sell->id}. Motivo: {$reason}. Se emitió tu reembolso.",
                    sellId: $sell->id,
                    establishmentId: $sell->sold_by,
                    actionUrl: '/customer/purchases',
                    meta: [
                        'sell_id' => $sell->id,
                        'reason' => $reason,
                        'establishment_name' => $establishmentName,
                    ]
                ));
            }

            return [
                'success' => true,
                'message' => 'Pedido cancelado por el comercio y reembolso del 100% emitido al cliente con éxito.',
                'sell_id' => $sell->id,
            ];
        });
    }
}
