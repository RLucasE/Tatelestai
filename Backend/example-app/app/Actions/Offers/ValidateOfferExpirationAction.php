<?php

namespace App\Actions\Offers;

use App\Models\Offer;
use http\Exception;

class ValidateOfferExpirationAction
{

    /**
     * Valida si las ofertas están vencidas
     *
     * @param Offer|int|Offer[]|int[] $offer
     */
    public function execute(Offer|int|array $offer): bool
    {
        if (is_array($offer)) {
            foreach ($offer as $item) {
                $resolved = $this->resolveOffer($item['id']);
                if ($resolved->expiration_datetime < now()) {
                    throw new \Exception("Alguno de las ofertas está expirada");
                }
            }
        } else {
            $resolved = $this->resolveOffer($offer);
            if ($resolved->expiration_datetime < now()) {
                throw new \Exception("La oferta esta expirada");
            }
        }
        return true;
    }

    /**
     * Pasa de Offer|int a Offer
     *
     * @param Offer|int $offer
     * @return Offer
     */
    private function resolveOffer(Offer|int $offer): Offer
    {
        return is_int($offer)
            ? Offer::findOrFail($offer)
            : $offer;
    }
}
