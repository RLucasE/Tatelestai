<?php

namespace App\Actions\Sell;

use App\Models\FoodEstablishment;
use App\Models\Sell;
use Exception;

class ValidatePickupCodeAction
{
    /**
     * Valida que el código de pickup exista y pertenezca al establecimiento del seller.
     *
     * @param string $pickupCode Código de pickup a validar
     * @param int $userId ID del usuario (seller) que intenta verificar el código
     * @return Sell Venta encontrada
     * @throws Exception Si el código no existe o no pertenece al seller
     */
    public function execute(string $pickupCode, int $userId): Sell
    {

        $sell = Sell::with(['customer', 'sellDetails.offer', 'foodEstablishment'])
            ->where('pickup_code', $pickupCode)
            ->first();


        if (!$sell) {
            throw new Exception('Código de pickup no encontrado', 404);
        }

        $establishment = FoodEstablishment::where('user_id', $userId)->first();

        if (!$establishment || $sell->sold_by !== $establishment->id) {
            throw new Exception('No tienes permiso para verificar este código', 403);
        }

        return $sell;
    }
}

