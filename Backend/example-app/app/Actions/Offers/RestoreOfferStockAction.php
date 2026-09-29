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

        // 2. Evaluar transición de estado:
        // - Solo se reactiva si su estado era 'purchased' (agotada por ventas).
        // - Debe tener stock disponible > 0.
        // - NO debe estar expirada (su ventana de retiro aún debe ser válida).
        // - NO debe estar pausada voluntariamente por el vendedor ('inactive').
        $wasPurchased = ($offer->state === OfferState::PURCHASED->value || $offer->state === 'purchased');
        $hasValidWindow = ! $offer->expiration_datetime || $offer->expiration_datetime->isFuture();

        if ($wasPurchased && $offer->quantity > 0 && $hasValidWindow) {
            $offer->update(['state' => OfferState::ACTIVE->value]);
        }

        return $offer;
    }
}
