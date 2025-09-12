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

    static function fromRequest(Request $request): PreparePurchaseDTO {
        $offersDTO = array_map(function ($offer) {
            return PrepareOfferDTO::createFromIdAndQuantity($offer['id'], $offer['quantity']);
        }, $request->get('offers'));

        return new self(
            food_establishment_id: $request->get('food_establishment_id'),
            offers: $offersDTO
        );
    }

    public static function fromArray(array $data): PreparePurchaseDTO {
        return new self(
            food_establishment_id: $data['food_establishment_id'],
            offers: $data['offers']
        );
    }


}
