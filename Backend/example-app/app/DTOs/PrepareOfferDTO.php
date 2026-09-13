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
        );
    }
}
