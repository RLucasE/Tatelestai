<?php

namespace App\DTOs;

class CreateOfferProductDTO
{
    public function __construct(
        public readonly int    $id,
        public readonly int    $quantity,
        public readonly int    $price,
        public readonly string $expirationDate,
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            quantity: $data['quantity'],
            price: $data['price'],
            expirationDate: $data['expirationDate'],
        );
    }
}
