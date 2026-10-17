<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackOfferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pack_template_id' => $this->pack_template_id,
            'food_establishment_id' => $this->food_establishment_id,
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price !== null ? (int) $this->price : null,
            'minimum_value' => $this->minimum_value !== null ? (int) $this->minimum_value : null,
            'allergens' => $this->allergens ?? [],
            'estimated_weight_kg' => $this->estimated_weight_kg !== null
                ? (float) $this->estimated_weight_kg
                : null,
            'quantity' => (int) $this->quantity,
            'state' => $this->state,
            'pickup_start_datetime' => $this->pickup_start_datetime,
            'visible_from' => $this->visible_from,
            'expiration_datetime' => $this->expiration_datetime,
            'establishment' => $this->whenLoaded('foodEstablishment', fn () => [
                'id' => $this->foodEstablishment->id,
                'name' => $this->foodEstablishment->name,
                'address' => $this->foodEstablishment->address,
                'latitude' => $this->foodEstablishment->latitude !== null
                    ? (float) $this->foodEstablishment->latitude
                    : null,
                'longitude' => $this->foodEstablishment->longitude !== null
                    ? (float) $this->foodEstablishment->longitude
                    : null,
                'rating_average' => $this->foodEstablishment->rating_average !== null
                    ? (float) $this->foodEstablishment->rating_average
                    : null,
                'reviews_count' => (int) $this->foodEstablishment->reviews_count,
                'recent_rating_average' => $this->foodEstablishment->recent_rating_average !== null
                    ? (float) $this->foodEstablishment->recent_rating_average
                    : null,
                'recent_reviews_count' => (int) $this->foodEstablishment->recent_reviews_count,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
