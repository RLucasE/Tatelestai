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
        ->where('is_picked_up', false)
        ->orderBy('created_at', 'desc')
        ->get();

        // Mapear para asegurar estructura y casting del enum state
        return $sells->map(function (Sell $sell) {
            return [
                'id' => $sell->id,
                'bought_by' => $sell->bought_by,
                'sold_by' => $sell->sold_by,
                'pickup_code' => $sell->pickup_code,
                'is_picked_up' => $sell->is_picked_up,
                'picked_up_at' => $sell->picked_up_at,
                'created_at' => $sell->created_at,
                'updated_at' => $sell->updated_at,
                'max_pickup_datetime' => $sell->max_pickup_datetime,
                'state' => $sell->state?->value ?? $sell->state,
                'sell_details' => $sell->sellDetails->map(function ($detail) {
                    return [
                        'id' => $detail->id,
                        'offer_id' => $detail->offer_id,
                        'offer_quantity' => $detail->offer_quantity,
                        'product_quantity' => $detail->product_quantity,
                        'product_price' => $detail->product_price,
                        'product_name' => $detail->product_name,
                        'product_description' => $detail->product_description,
                    ];
                })->toArray(),
            ];
        })->toArray();
    }
}
