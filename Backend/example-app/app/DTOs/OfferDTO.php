<?php

namespace App\DTOs;

class OfferDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $offer_quantity,
        public readonly string $title,
        public readonly string $description,
        public readonly string $expiration_datetime,
        public readonly int $establishment_id,
        public readonly string $establishment_name,
        public readonly string $establishment_address,
        public readonly int $food_establishment_id,
        /** @var ProductOfferDTO[] */
        public readonly array $products,
    ) {}
}
