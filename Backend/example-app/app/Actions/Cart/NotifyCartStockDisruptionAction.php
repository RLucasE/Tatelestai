<?php

namespace App\Actions\Cart;

use App\Enums\CartState;
use App\Enums\OfferState;
use App\Models\Offer;
use App\Models\OfferCart;
use App\Notifications\RealtimeOrderNotification;

class NotifyCartStockDisruptionAction
{
    /**
     * Notifica a clientes con carritos activos afectados por la falta o agotamiento de stock.
     */
    public function execute(Offer $offer, ?int $buyerUserId = null): void
    {
        $isSoldOut = $offer->quantity <= 0 || $offer->state !== OfferState::ACTIVE->value;

        // Buscar items de carritos activos que contengan esta oferta
        $affectedOfferCarts = OfferCart::query()
            ->where('offer_id', $offer->id)
            ->whereHas('userCart', function ($query) {
                $query->where('state', CartState::ACTIVE->value);
            })
            ->with(['userCart.user'])
            ->get();

        foreach ($affectedOfferCarts as $offerCart) {
            $customer = $offerCart->userCart?->user;
            if (! $customer || ($buyerUserId && $customer->id === $buyerUserId)) {
                continue;
            }

            $cartQuantity = (int) $offerCart->quantity;

            if ($isSoldOut) {
                $customer->notify(new RealtimeOrderNotification(
                    eventType: 'cart_item_sold_out',
                    title: '¡Bolsa agotada en tu carrito!',
                    message: "La bolsa sorpresa «{$offer->title}» que tienes en tu carrito se acaba de agotar.",
                    offerId: $offer->id,
                    establishmentId: $offer->food_establishment_id,
                    actionUrl: '/customer/carrito',
                    meta: [
                        'offer_id' => $offer->id,
                        'cart_quantity' => $cartQuantity,
                        'available_quantity' => 0,
                    ]
                ));
            } elseif ($cartQuantity > $offer->quantity) {
                $customer->notify(new RealtimeOrderNotification(
                    eventType: 'cart_item_stock_reduced',
                    title: 'Stock insuficiente en tu carrito',
                    message: "Solo queda(n) {$offer->quantity} unidad(es) de «{$offer->title}». Tenías {$cartQuantity} en tu carrito.",
                    offerId: $offer->id,
                    establishmentId: $offer->food_establishment_id,
                    actionUrl: '/customer/carrito',
                    meta: [
                        'offer_id' => $offer->id,
                        'cart_quantity' => $cartQuantity,
                        'available_quantity' => $offer->quantity,
                    ]
                ));
            }
            // Si $cartQuantity <= $offer->quantity, el cliente no se ve afectado y NO se notifica.
        }
    }
}
