<?php

namespace App\Actions\Sell;

use App\Contracts\Payment\PaymentServiceInterface;
use App\Enums\OfferState;
use App\Enums\PaymentStatus;
use App\Enums\SellState;
use App\Models\Sell;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessExpiredSellsAction
{
    public function __construct(
        private readonly PaymentServiceInterface $paymentService,
    ) {}

    /**
     * Sincroniza de forma lazy las ventas cuya ventana de retiro ha vencido.
     * Evalúa las normas de reembolso del dominio:
     * - Incumplimiento/baja de oferta del comercio: 100% reembolso al cliente por Mercado Pago.
     * - Incomparecencia del cliente (No-Show): 0% reembolso, el comercio conserva su liquidación neta.
     *
     * @param  int|null  $boughtBy  Filtrar por ID de cliente (opcional)
     * @param  int|null  $soldBy  Filtrar por ID de establecimiento gastronómico (opcional)
     * @return int Cantidad de ventas procesadas
     */
    public function execute(?int $boughtBy = null, ?int $soldBy = null): int
    {
        $query = Sell::with(['sellDetails.offer', 'foodEstablishment'])
            ->where('is_picked_up', false)
            ->whereNotIn('state', [
                SellState::PICKED_UP->value,
                SellState::CANCELLED->value,
                SellState::EXPIRED->value,
            ])
            ->whereNotNull('max_pickup_datetime')
            ->where('max_pickup_datetime', '<', now());

        if ($boughtBy !== null) {
            $query->where('bought_by', $boughtBy);
        }

        if ($soldBy !== null) {
            $query->where('sold_by', $soldBy);
        }

        $expiredSells = $query->get();

        if ($expiredSells->isEmpty()) {
            return 0;
        }

        $processedCount = 0;

        foreach ($expiredSells as $sell) {
            DB::transaction(function () use ($sell, &$processedCount) {
                // Re-verificar estado atómicamente
                $current = Sell::where('id', $sell->id)
                    ->where('is_picked_up', false)
                    ->whereNotIn('state', [
                        SellState::PICKED_UP->value,
                        SellState::CANCELLED->value,
                        SellState::EXPIRED->value,
                    ])
                    ->lockForUpdate()
                    ->first();

                if (! $current) {
                    return;
                }

                // Evaluar si alguna oferta asociada fue desactivada por el comercio sin entregar
                $merchantFailed = false;
                foreach ($sell->sellDetails as $detail) {
                    if ($detail->offer) {
                        $offerState = $detail->offer->state instanceof OfferState
                            ? $detail->offer->state->value
                            : (string) $detail->offer->state;

                        if ($offerState === OfferState::INACTIVE->value) {
                            $merchantFailed = true;
                            break;
                        }
                    }
                }

                if ($merchantFailed) {
                    // Caso 1: El comercio no entregó / dio de baja la oferta -> 100% Reembolso en Mercado Pago
                    $current->update([
                        'state' => SellState::CANCELLED->value,
                        'payment_status' => PaymentStatus::REFUNDED->value,
                        'refunded_at' => now(),
                        'cancelled_by' => 'system',
                        'cancellation_reason' => 'Oferta desactivada / comercio no entregó el pedido',
                    ]);

                    if ($current->payment_id) {
                        try {
                            $this->paymentService->refundPayment($current->payment_id);
                        } catch (\Throwable $e) {
                            Log::error('Fallo al emitir reembolso lazy por no entrega del comercio:', [
                                'sell_id' => $current->id,
                                'payment_id' => $current->payment_id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                } else {
                    // Caso 2: No-Show del comprador -> 0% reembolso (el comercio cobra su 85% neto)
                    $current->update([
                        'state' => SellState::EXPIRED->value,
                        'cancellation_reason' => 'Incomparecencia del comprador (No-Show tras fin de franja)',
                    ]);
                }

                $processedCount++;
            });
        }

        return $processedCount;
    }
}
