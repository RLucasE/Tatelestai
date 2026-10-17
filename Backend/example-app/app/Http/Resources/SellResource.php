<?php

namespace App\Http\Resources;

use App\Enums\SellState;
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
        $review = $this->relationLoaded('review') ? $this->review : $this->review()->first();
        $isReviewed = $review !== null;
        $userRating = $review?->rating;
        $isPickedUp = (bool) $this->is_picked_up && ($this->state === SellState::PICKED_UP || $this->state === SellState::PICKED_UP->value);
        $canReview = (bool) ($isPickedUp && ! $isReviewed && $this->picked_up_at?->gte(now()->subDays(14)));

        return [
            'sell_id' => $this->id,
            'is_picked_up' => (bool) $this->is_picked_up,
            'picked_up_at' => $this->picked_up_at,
            'is_reviewed' => $isReviewed,
            'user_rating' => $userRating,
            'can_review' => $canReview,
            'created_at' => $this->created_at,
            'establishment' => [
                'id' => $this->foodEstablishment?->id,
                'name' => $this->foodEstablishment?->name,
                'address' => $this->foodEstablishment?->address,
            ],
            'total_price' => $this->sellDetails?->reduce(function ($total, $detail) {
                return $total + ((int) $detail->offer_quantity * (int) $detail->pack_price);
            }, 0) ?? 0,
            'offers' => $this->sellDetails?->map(function ($detail) {
                return [
                    'offer_id' => $detail->offer_id,
                    'offer_quantity' => $detail->offer_quantity,
                    'pack_name' => $detail->pack_name,
                    'pack_description' => $detail->pack_description,
                    'pack_price' => $detail->pack_price,
                ];
            })->values()->toArray() ?? [],
        ];
    }
}
