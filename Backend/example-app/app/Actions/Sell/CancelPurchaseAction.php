<?php

namespace App\Actions\Sell;

use App\Actions\Offers\RestoreOfferStockAction;
use App\Enums\SellState;
use App\Models\Sell;
use App\Models\User;
use App\Policies\PurchaseCancellationPolicy;
use Illuminate\Support\Facades\DB;

class CancelPurchaseAction
{
    public function __construct(
        private readonly PurchaseCancellationPolicy $policy,
        private readonly RestoreOfferStockAction $restoreOfferStockAction,
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
            // Actualizar estado de la venta a cancelada
            $sell->update([
                'state' => SellState::CANCELLED,
            ]);

            // Cargar detalles de la venta si no están cargados
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

            return [
                'success' => true,
                'message' => 'Compra cancelada y reembolso emitido exitosamente',
                'sell_id' => $sell->id,
            ];
        });
    }
}
