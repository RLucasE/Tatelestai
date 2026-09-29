<?php

namespace App\Actions\Sell;

use App\Actions\Offers\RestoreOfferStockAction;
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
                ]);

            if ($affected === 0) {
                throw new CancellationNotAllowedException('La compra ya se encuentra cancelada.', 422);
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

            return [
                'success' => true,
                'message' => 'Compra cancelada y reembolso emitido exitosamente',
                'sell_id' => $sell->id,
            ];
        });
    }
}
