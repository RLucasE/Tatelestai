<?php

namespace App\Search\DTOs;

use App\Enums\OfferState;

final class SearchQueryDTO
{
    public function __construct(
        public readonly string $query,
        public readonly string $state = OfferState::ACTIVE->value,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly float $radiusKm = 5.0,
    ) {}

    public function hasGeoFilter(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
