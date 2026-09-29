<?php

namespace App\Actions\Offers;

use App\Enums\OfferState;
use App\Models\Offer;

class RestoreOfferStockAction
{
    /**
     * Incrementa el stock de una oferta y transiciona su estado bajo reglas estrictas de dominio.
     */
    public function execute(int $offerId, int $quantityToRestore): ?Offer
    {
        $offer = Offer::find($offerId);

        if (! $offer) {
            return null;
        }

        // 1. Incrementar stock
        $offer->increment('quantity', $quantityToRestore);
        $offer->refresh();

        // 2. Reactivación atómica si estaba agotada por ventas (purchased) y su franja sigue vigente
        Offer::query()
            ->where('id', $offerId)
            ->where('state', OfferState::PURCHASED->value)
            ->where(function ($query) {
                $query->whereNull('expiration_datetime')
                    ->orWhere('expiration_datetime', '>', now());
            })
            ->update(['state' => OfferState::ACTIVE->value]);

        return $offer->refresh();
    }
}
