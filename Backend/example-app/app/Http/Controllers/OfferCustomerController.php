<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\Request;


class OfferCustomerController extends Controller
{
    //
    public function index(Request $request)
    {
        $offers = Offer::select([
            'offers.id',
            'offers.title',
            'offers.description',
            'offers.expiration_datetime',
            'offers.food_establishment_id',
            'product_offers.price',
            'product_offers.quantity',
            'products.id as product_id',
            'products.name as product_name',
            'products.description as product_description'
        ])
            ->join('product_offers', 'offers.id', '=', 'product_offers.offer_id')
            ->join('products', 'product_offers.product_id', '=', 'products.id')
            ->where("offers.expiration_datetime", ">=", now())
            ->get();

        // Agrupar por oferta
        $groupedOffers = $offers->groupBy('id')->map(function ($offerGroup) {
            $firstOffer = $offerGroup->first();

            return [
                'id' => $firstOffer->id,
                'title' => $firstOffer->title,
                'description' => $firstOffer->description,
                'expiration_datetime' => $firstOffer->expiration_datetime,
                'food_establishment_id' => $firstOffer->food_establishment_id,
                'products' => $offerGroup->map(function (Offer $item) {
                    /**
                     * @var object{
                     *     id: int,
                     *     title: string,
                     *     description: string,
                     *     expiration_datetime: string,
                     *     food_establishment_id: int,
                     *     price: float,
                     *     quantity: int,
                     *     product_id: int,
                     *     product_name: string,
                     *     product_description: string
                     * } $item
                     */

                    return [
                        'id' => $item->product_id,
                        'name' => $item->product_name,
                        'description' => $item->product_description,
                        'pivot' => [
                            'price' => $item->price,
                            'quantity' => $item->quantity
                        ]
                    ];
                })
            ];
        })->values();

        return $groupedOffers;
    }
}
