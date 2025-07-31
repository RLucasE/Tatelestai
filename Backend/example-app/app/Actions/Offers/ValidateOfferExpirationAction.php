<?php

namespace App\Actions\Offers;

use App\Models\Offer;

class ValidateOfferExpirationAction
{
    /**
     * Valida si una oferta ha expirado
     *
     * @param Offer|int $offer Oferta o ID de oferta a validar
     * @return bool|Offer False si la oferta ha expirado, la oferta en caso contrario
     */
    public function execute(Offer|int $offer): bool
    {
        $offer = $this->resolveOffer($offer);

        if ($offer->expiration_datetime < now()) return false; // Offer has expired
        else return true;
    }

    /**
     * Resuelve una oferta a partir de su ID o instancia
     *
     * @param Offer|int $offer Oferta o ID de oferta
     * @return Offer
     */
    private function resolveOffer(Offer|int $offer): Offer
    {
        return is_int($offer)
            ? Offer::findOrFail($offer)
            : $offer;
    }
}
