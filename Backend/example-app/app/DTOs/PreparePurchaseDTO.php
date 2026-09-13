<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class PreparePurchaseDTO
{
    public function __construct(
        public readonly int $food_establishment_id,
        /** @var array<PrepareOfferDTO> */
        public readonly array $offers,
    ) {}

    public static function fromRequest(Request $request): PreparePurchaseDTO
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
        $clonedOffers = array_map(function ($offer) {
            if ($offer instanceof PrepareOfferDTO) {
                return new PrepareOfferDTO(
                    id: $offer->id,
                    title: $offer->title,
                    description: $offer->description,
                    price: $offer->price,
                    quantity: $offer->quantity,
                );
            }

            return $offer;
        }, $original->offers);

        return new self(
            food_establishment_id: $original->food_establishment_id,
            offers: $clonedOffers
        );
    }
}
