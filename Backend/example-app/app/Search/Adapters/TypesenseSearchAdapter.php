<?php

namespace App\Search\Adapters;

use App\Contracts\Search\SearchServiceInterface;
use App\Models\Offer;
use App\Search\DTOs\SearchQueryDTO;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class TypesenseSearchAdapter implements SearchServiceInterface
{
    public function searchOffers(SearchQueryDTO $query): Collection
    {
        try {
            return $this->performSearch($query);
        } catch (Throwable $e) {
            Log::error('Error executing search with Typesense', [
                'query' => $query->query,
                'state' => $query->state,
                'page' => $query->page,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Perform the Scout query and eager load relationships.
     * Exposed as public/protected so it can be mocked or tested independently.
     */
    public function performSearch(SearchQueryDTO $query): Collection
    {
        $now = now()->timestamp;
        $filterConditions = [
            "state:={$query->state}",
            "expiration_datetime:>={$now}",
        ];

        $options = [];

        if ($query->hasGeoFilter()) {
            $filterConditions[] = "_geoloc:({$query->latitude}, {$query->longitude}, {$query->radiusKm} km)";
            $options['sort_by'] = "_geoloc({$query->latitude}, {$query->longitude}):asc";
        }

        $options['filter_by'] = implode(' && ', $filterConditions);

        $paginator = Offer::search($query->query)
            ->options($options)
            ->paginate($query->perPage, 'page', $query->page);

        $paginator->load([
            'fullProducts',
            'foodEstablishment' => fn($q) => $q->select('id', 'name', 'address', 'latitude', 'longitude'),
        ]);

        return $paginator->getCollection();
    }

    public function indexOffer(int $offerId): void
    {
        Offer::with(['products', 'fullProducts', 'foodEstablishment'])
            ->findOrFail($offerId)
            ->searchable();
    }

    public function indexOffers(array $offerIds): void
    {
        if (empty($offerIds)) {
            return;
        }

        Offer::with(['products', 'fullProducts', 'foodEstablishment'])
            ->whereIn('id', $offerIds)
            ->get()
            ->searchable();
    }

    public function removeOfferFromIndex(int $offerId): void
    {
        Offer::findOrFail($offerId)->unsearchable();
    }

    public function flushIndex(): void
    {
        Offer::removeAllFromSearch();
    }

    public function reindexAll(): void
    {
        Offer::makeAllSearchable();
    }
}
