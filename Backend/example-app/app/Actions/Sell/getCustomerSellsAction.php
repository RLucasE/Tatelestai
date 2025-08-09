<?php

namespace App\Actions\Sell;

use App\Models\Sell;

class getCustomerSellsAction
{
    public function __construct()
    {
    }

    /**
     * Obtiene todas las compras (sells) de un customer específico con sus detalles
     *
     * @param int $userId ID del usuario customer
     * @return array Array con las ventas y sus detalles
     */
    public function execute(int $userId): array
    {
        $sells = Sell::with([
            'sellDetails'
        ])
        ->where('bought_by', $userId)
        ->orderBy('created_at', 'desc')
        ->get();


        return $sells->toArray();
    }
}
