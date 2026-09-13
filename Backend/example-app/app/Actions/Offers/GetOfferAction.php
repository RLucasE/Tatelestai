<?php

namespace App\Actions\Offers;

use App\Models\Offer;

class GetOfferAction
{
    /**
     * Obtiene una oferta por su ID
     *
     * @param  int  $offerId  ID de la oferta
     *
     * @throws \Exception
     */
    public function execute(int $offerId): Offer
    {
        try {
            return Offer::findOrFail($offerId);
        } catch (\Exception $exception) {
            throw new \Exception('Oferta no encontrada', 404, $exception);
        }
    }
}
