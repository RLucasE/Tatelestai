<?php

namespace App\DTOs;

use App\Models\Offer;

class PrepareOfferDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $description,
        public readonly int $price,
        public readonly int $quantity,
        public readonly ?array $allergens = null,
        public readonly ?float $estimated_weight_kg = null,
        public readonly ?float $minimum_value = null,
    ) {}

    /**
     * Crea una instancia del DTO usando solo id y quantity
     */
    public static function createFromIdAndQuantity(int $id, int $quantity): self
    {
        $offer = Offer::findOrFail($id);

        return new self(
            id: $offer->id,
            title: $offer->title,
            description: $offer->description,
            price: (int) $offer->price,
            quantity: $quantity,
            allergens: is_array($offer->allergens) ? array_values($offer->allergens) : [],
            estimated_weight_kg: $offer->estimated_weight_kg !== null ? (float) $offer->estimated_weight_kg : null,
            minimum_value: $offer->minimum_value !== null ? (float) $offer->minimum_value : null,
        );
    }
}
