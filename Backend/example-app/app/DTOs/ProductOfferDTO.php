<?php

namespace App\DTOs;

class ProductOfferDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly int $quantity,
        public readonly float $price,
    ) {}
}
