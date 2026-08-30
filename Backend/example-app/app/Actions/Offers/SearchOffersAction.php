<?php

namespace App\Actions\Offers;

use App\Contracts\Search\SearchServiceInterface;
use App\Search\DTOs\SearchQueryDTO;
use Illuminate\Support\Collection;

class SearchOffersAction
{
    public function __construct(
        private readonly SearchServiceInterface $searchService
    ) {}

    public function execute(SearchQueryDTO $query): Collection
    {
        return $this->searchService->searchOffers($query);
    }
}
