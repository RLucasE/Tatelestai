<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class PreparePurchaseDTO
{
    public function __construct(
        public readonly int   $food_establishment_id,
        /** @var array<PrepareOfferDTO> */
        public readonly array $offers,
    )
    {
    }

    static function fromRequest(Request $request): PreparePurchaseDTO
    {

        $offersDTO = array_map(function ($offer) {
            return PrepareOfferDTO::createFromIdAndQuantity($offer['id'], $offer['quantity']);
        }, $request->get('offers'));



        return new self(
            food_establishment_id: $request->get('food_establishment_id'),
            offers: $offersDTO
        );
    }

    public static function fromArray(array $data): PreparePurchaseDTO
    {
        return new self(
            food_establishment_id: $data['food_establishment_id'],
            offers: $data['offers']
        );
    }

    /**
     * Crea una copia nueva y completa del DTO sin compartir referencias
     */
    public static function clone(PreparePurchaseDTO $original): self
    {
        // Clonar profundamente cada oferta
        $clonedOffers = array_map(function ($offer) {
            if ($offer instanceof PrepareOfferDTO) {
                // Clonar los productos dentro de cada oferta
                $clonedProducts = array_map(function ($product) {
                    if ($product instanceof ProductOfferDTO) {
                        return new ProductOfferDTO(
                            name: $product->name,
                            description: $product->description,
                            quantity: $product->quantity,
                            price: $product->price,
                            expiration_date: $product->expiration_date,
                        );
                    }
                    return $product;
                }, $offer->products);

                return new PrepareOfferDTO(
                    id: $offer->id,
                    title: $offer->title,
                    description: $offer->description,
                    quantity: $offer->quantity,
                    products: $clonedProducts
                );
            }

            // Si es un array asociativo (caso cuando viene de session)
            return $offer;
        }, $original->offers);

        return new self(
            food_establishment_id: $original->food_establishment_id,
            offers: $clonedOffers
        );
    }

}
