<?php

namespace App\Actions\Offers;

use App\Models\Offer;

class ValidateOfferVisibilityAction
{
    /**
     * Valida si las ofertas ya se encuentran visibles para compra.
     *
     * @param  Offer|int|Offer[]|int[]  $offer
     *
     * @throws \Exception
     */
    public function execute(Offer|int|array $offer): bool
    {
        if (is_array($offer)) {
            foreach ($offer as $item) {
                $offerId = is_array($item) ? ($item['id'] ?? null) : $item;
                $resolved = $this->resolveOffer($offerId);
                if ($resolved->visible_from && $resolved->visible_from->isFuture()) {
                    throw new \Exception('La oferta aún no está disponible para compra');
                }
            }
        } else {
            $resolved = $this->resolveOffer($offer);
            if ($resolved->visible_from && $resolved->visible_from->isFuture()) {
                throw new \Exception('La oferta aún no está disponible para compra');
            }
        }

        return true;
    }

    /**
     * Pasa de Offer|int a Offer
     */
    private function resolveOffer(Offer|int $offer): Offer
    {
        return is_int($offer)
            ? Offer::findOrFail($offer)
            : $offer;
    }
}
