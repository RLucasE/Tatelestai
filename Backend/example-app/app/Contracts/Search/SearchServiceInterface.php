<?php

namespace App\Contracts\Search;

use App\Search\DTOs\SearchQueryDTO;
use Illuminate\Support\Collection;

interface SearchServiceInterface
{
    public function searchOffers(SearchQueryDTO $query): Collection;

    public function indexOffer(int $offerId): void;

    public function removeOfferFromIndex(int $offerId): void;
}
