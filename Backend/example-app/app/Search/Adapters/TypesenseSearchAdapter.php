<?php

namespace App\Search\Adapters;

use App\Contracts\Search\SearchServiceInterface;
use App\Models\Offer;
use App\Search\DTOs\SearchQueryDTO;
use Illuminate\Support\Collection;

class TypesenseSearchAdapter implements SearchServiceInterface
{
    public function searchOffers(SearchQueryDTO $query): Collection
    {
        return Offer::search($query->query)
            ->where('state', $query->state)
            ->get()
            ->filter(function ($offer) {
                return $offer->expiration_datetime >= now();
            })
            ->forPage($query->page, $query->perPage)
            ->load([
                'products' => function ($q) {
                    $q->select(
                        'products.id',
                        'products.name',
                        'products.description',
                        'product_offers.price',
                        'product_offers.quantity as product_quantity',
                        'product_offers.offer_id',
                        'product_offers.expiration_date'
                    );
                },
                'foodEstablishment' => function ($q) {
                    $q->select('id', 'name', 'address');
                },
            ]);
    }

    public function indexOffer(int $offerId): void
    {
        Offer::findOrFail($offerId)->searchable();
    }

    public function removeOfferFromIndex(int $offerId): void
    {
        Offer::findOrFail($offerId)->unsearchable();
    }
}
