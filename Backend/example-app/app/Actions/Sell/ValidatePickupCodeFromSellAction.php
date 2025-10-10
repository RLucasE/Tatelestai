<?php

namespace App\Actions\Sell;

use App\Models\Sell;
use Exception;

class ValidatePickupCodeFromSellAction
{
    /**
     * Valida que el código de pickup coincida con el de la venta
     *
     * @param int $sellId
     * @param string $pickupCode
     * @return Sell
     * @throws Exception
     */
    public function execute(int $sellId, string $pickupCode): Sell
    {
        $sell = Sell::find($sellId);

        if (!$sell) {
            throw new Exception('Venta no encontrada', 404);
        }

        if ($sell->pickup_code !== $pickupCode) {
            throw new Exception('Código de pickup incorrecto', 400);
        }

        return $sell;
    }
}

