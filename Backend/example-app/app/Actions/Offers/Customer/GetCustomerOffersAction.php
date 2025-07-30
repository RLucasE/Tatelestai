<?php

namespace App\Actions\Offers\Customer;

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
            ->where("offers.expiration_datetime", ">=", now())
            ->get()
            ->map(function ($offer) {
                return [
                    'id' => $offer->id,
                    'offer_quantity' => $offer->offer_quantity,
                    'title' => $offer->title,
                    'description' => $offer->description,
                    'expiration_datetime' => $offer->expiration_datetime,
                    'food_establishment_id' => $offer->food_establishment_id,
                    'products' => $offer->products->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'description' => $product->description,
                            'pivot' => [
                                'price' => $product->price,
                                'quantity' => $product->product_quantity
                            ]
                        ];
                    })
                ];
            })
            ->toArray();
    }
}