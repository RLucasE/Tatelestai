<?php

namespace App\Actions\Offers;

use App\Models\Offer;
use PhpParser\Node\Expr\Cast\Bool_;

/**
 *
 */
class ValidateOfferStateAction
{

    /**
     * @param int $offerId
     * @param string $expectedState
     * @throws \Exception
     */
    public function execute(int $offerId, string $expectedState)
    {
        $offer = Offer::findOrFail($offerId);
        if ($offer->state === $expectedState) {
            return true;
        }else throw new \Exception("La oferta no está en el estado esperado: {$expectedState}");
    }
}
