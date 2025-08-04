<?php

namespace App\Actions\Offers\Customer;

use App\Enums\OfferState;
use App\Models\Offer;

class GetCustomerOffersAction
{
    public function execute(): array
    {
        return Offer::select([
            'offers.id',
            'offers.quantity as offer_quantity',
            'offers.title',
            'offers.description',
            'offers.expiration_datetime',
            'offers.food_establishment_id'
        ])
            ->with(['products' => function ($query) {
                $query->select(
                    'products.id',
                    'products.name',
                    'products.description',
                    'product_offers.price',
                    'product_offers.quantity as product_quantity',
                    'product_offers.offer_id'
                );
            }])
            ->where("offers.state", OfferState::ACTIVE)
            ->where("offers.expiration_datetime", ">=", now())
            ->get()
            ->toArray();
    }
}
