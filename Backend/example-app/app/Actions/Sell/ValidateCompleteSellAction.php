<?php

namespace App\Actions\Sell;

use App\Models\FoodEstablishment;
use App\Models\Sell;
use Exception;

class ValidateCompleteSellAction
{
    /**
     * Valida que la venta exista, no esté completada y pertenezca al establecimiento del usuario autenticado
     *
     * @param string $sellNumber
     * @param int $userId
     * @return Sell
     * @throws Exception
     */
    public function execute(string $sellNumber, int $userId): Sell
    {
        $sell = Sell::where('id', $sellNumber)
            ->where('is_picked_up', false)
            ->first();

        if (!$sell) {
            throw new Exception('Venta no encontrada', 404);
        }

        $establishment = FoodEstablishment::where('user_id', $userId)->first();

        if (!$establishment) {
            throw new Exception('Establecimiento no encontrado', 404);
        }

        if ($sell->sold_by !== $establishment->id) {
            throw new Exception('No tienes permiso para completar esta venta', 403);
        }

        return $sell;
    }
}
