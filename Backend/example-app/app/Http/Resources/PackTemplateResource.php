<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackTemplateResource extends JsonResource
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
            'food_establishment_id' => $this->food_establishment_id,
            'title' => $this->title,
            'description' => $this->description,
            'allergens' => $this->allergens ?? [],
            'estimated_weight_kg' => $this->estimated_weight_kg !== null
                ? (float) $this->estimated_weight_kg
                : null,
            'offers_count' => $this->whenCounted('offers'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
