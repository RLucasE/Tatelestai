<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FoodEstablishmentResource extends JsonResource
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
            'name' => $this->name,
            'address' => $this->address,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'establishment_type' => $this->establishmentType?->name,
            'phone' => $this->phone,
            'phone_number' => $this->phone,
            'email' => $this->user?->email,
            'description' => $this->description,
            'rating_average' => $this->rating_average !== null ? (float) $this->rating_average : null,
            'reviews_count' => (int) $this->reviews_count,
            'recent_rating_average' => $this->recent_rating_average !== null ? (float) $this->recent_rating_average : null,
            'recent_reviews_count' => (int) $this->recent_reviews_count,
        ];
    }
}
