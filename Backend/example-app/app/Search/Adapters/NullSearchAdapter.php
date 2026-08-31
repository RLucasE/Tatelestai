<?php

namespace App\Search\Adapters;

use App\Contracts\Search\SearchServiceInterface;
use App\Search\DTOs\SearchQueryDTO;
use Illuminate\Support\Collection;

class NullSearchAdapter implements SearchServiceInterface
{
    public function searchOffers(SearchQueryDTO $query): Collection
    {
        return collect();
    }

    public function indexOffer(int $offerId): void
    {
        // No-op
    }

    public function indexOffers(array $offerIds): void
    {
        // No-op
    }

    public function removeOfferFromIndex(int $offerId): void
    {
        // No-op
    }

    public function flushIndex(): void
    {
        // No-op
    }

    public function reindexAll(): void
    {
        // No-op
    }
}

