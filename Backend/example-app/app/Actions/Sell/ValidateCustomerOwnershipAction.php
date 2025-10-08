<?php

namespace App\Actions\Sell;

use App\Models\Sell;
use Exception;

class ValidateCustomerOwnershipAction
{
    /**
     * Valida que el customer autenticado es el dueño de la compra
     *
     * @param string $sellNumber
     * @param int $customerId
     * @return Sell
     * @throws Exception
     */
    public function execute(string $sellNumber, int $customerId): Sell
    {
        $sell = Sell::with(['foodEstablishment', 'sellDetails'])
            ->where('id', $sellNumber)
            ->first();

        // Verificar si existe la venta
        if (!$sell) {
            throw new Exception('Compra no encontrada', 404);
        }

        // Verificar que el customer autenticado es el dueño de la compra
        if ($sell->bought_by !== $customerId) {
            throw new Exception('Compra no encontrada', 403);
        }

        return $sell;
    }
}

