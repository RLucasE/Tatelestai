<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class CreateNewOfferDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly int $total_price,
        public readonly string $expiration_date,
        public readonly int $quantity,
        /** @var CreateOfferProductDTO[] */
        public readonly array $products,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $products = [];
        foreach ($request->input('products', []) as $productData) {
            $products[] = CreateOfferProductDTO::fromArray($productData);
        }

        return new self(
            title: $request->input('title'),
            description: $request->input('description'),
            total_price: $request->input('total_price'),
            expiration_date: $request->input('expiration_date'),
            quantity: $request->input('quantity'),
            products: $products,
        );
    }
}
