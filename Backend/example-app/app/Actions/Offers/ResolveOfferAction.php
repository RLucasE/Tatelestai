<?php

namespace App\Actions\Offers;

use App\Models\Offer;

class ResolveOfferAction
{
    public function __invoke(Offer|int $offer): Offer
    {
        return is_int($offer)
            ? Offer::findOrFail($offer)
            : $offer;
    }
}
