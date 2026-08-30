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
    ) {}
}
