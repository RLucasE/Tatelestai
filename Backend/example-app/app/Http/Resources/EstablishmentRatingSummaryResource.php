<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EstablishmentRatingSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'establishment_id' => $this['establishment_id'],
            'rating_average' => $this['rating_average'],
            'reviews_count' => $this['reviews_count'],
            'recent_rating_average' => $this['recent_rating_average'],
            'recent_reviews_count' => $this['recent_reviews_count'],
            'distribution' => (object) $this['distribution'],
        ];
    }
}
