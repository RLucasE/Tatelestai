<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Actions\Offers\GetUserEstablishmentAction;
use App\Actions\Offers\ResolveOfferAction;


class OfferController extends Controller
{
    public function __construct() {}
    protected function getOfferValidationRules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'expiration_date' => 'required|date',
            'quantity' => 'required|integer|min:1',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|integer|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.price' => 'required|numeric|min:1',
            'products.*.expirationDate' => 'required|date|after:today'
        ];
    }

    public function resolveOffer(Offer|int $offer): Offer
    {
        return app(ResolveOfferAction::class)($offer);
    }


    protected function productsToProductsIDs(array $products): array
    {
        return array_map(function ($product) {
            return $product['id'];
        }, $products);
    }


    protected function invalidProductsResponse()
    {
        return response()->json([
            'message' => 'Uno o más productos no pertenecen a tu establecimiento'
        ], 403);
    }
}
