<?php

namespace App\DTOs;

use App\Models\Offer;

class PrepareOfferDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $description,
        public readonly int $quantity,
        /** @var array<ProductOfferDTO> */
        public readonly array $products,
    ) {}

    /**
     * Crea una instancia del DTO usando solo id y quantity
     *
     * @param int $id
     * @param int $quantity
     * @return self
     */
    public static function createFromIdAndQuantity(int $id, int $quantity): self
    {
        $offer = Offer::with('products')->find($id);
        $productsData = array_map(function ($product) {
            return [
                'name' => $product['name'],
                'description' => $product['description'],
                'quantity' => $product['pivot']['quantity'],
                'price' => $product['pivot']['price'],
            ];
        }, $offer->products->toArray());
        $productsDTO = array_map(function ($productData) {
            return new ProductOfferDTO(
                name: $productData['name'],
                description: $productData['description'],
                quantity: $productData['quantity'],
                price: $productData['price']
            );
        }, $productsData);
        return new self(
            id: $id,
            title: $offer->title,
            description: $offer->description,
            quantity: $quantity,
            products: $productsDTO
        );
    }

    public function resolveProducts(array $productsData): array
    {
        return array_map(function ($productData) {
            return new ProductOfferDTO(
                name: $productData['name'],
                description: $productData['description'],
                quantity: $productData['quantity'],
                price: $productData['price']
            );
        }, $productsData);
    }
}
