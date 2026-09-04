<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'sell_id' => $this->id,
            'is_picked_up' => $this->is_picked_up,
            'created_at' => $this->created_at,
            'establishment' => [
                'id' => $this->foodEstablishment?->id,
                'name' => $this->foodEstablishment?->name,
                'address' => $this->foodEstablishment?->address,
            ],
            'offers' => $this->sellDetails?->map(function ($detail) {
                return [
                    'offer_id' => $detail->offer_id,
                    'offer_quantity' => $detail->offer_quantity,
                    'product_name' => $detail->product_name,
                    'product_description' => $detail->product_description,
                    'product_quantity' => $detail->product_quantity,
                    'product_price' => $detail->product_price,
                ];
            })->values()->toArray() ?? [],
        ];
    }
}
