<?php

namespace App\Actions\Offers;

use App\Models\Offer;
use Illuminate\Support\Collection;

class OfferIsFromFoodEstablishmentAction
{
    /**
     * @param array|Collection
     * @param int
     * @return boolean
     * @throws \Exception
     */
    public function execute(array|Collection $offers, int $establishmentId): bool
    {
        $offers = collect($offers)->pluck('id');

        $offersCount = Offer::query()
            ->whereIn('id', $offers)
            ->where('food_establishment_id', $establishmentId)
            ->count();

        return $offersCount === $offers->count() ? true : throw new \Exception("Esos productos no pertenecen a ese establecimiento");
    }
}
