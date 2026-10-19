<?php

namespace App\Listeners;

use App\Events\PurchaseCompleted;
use App\Notifications\RealtimeOrderNotification;

class SendSellerNewSaleNotification
{
    /**
     * Notifica al vendedor dueño del establecimiento cuando se completa una compra.
     */
    public function handle(PurchaseCompleted $event): void
    {
        $sell = $event->sell;
        $sell->loadMissing(['customer', 'foodEstablishment.user', 'sellDetails']);

        $sellerUser = $sell->foodEstablishment?->user;
        if (! $sellerUser) {
            return;
        }

        $customerName = $sell->customer?->name ?? 'Un cliente';
        $itemCount = $sell->sellDetails->sum('offer_quantity');
        $itemSummary = $itemCount === 1 ? '1 bolsa sorpresa' : "{$itemCount} bolsas sorpresa";

        $sellerUser->notify(new RealtimeOrderNotification(
            eventType: 'new_sale',
            title: '¡Nueva venta recibida!',
            message: "{$customerName} reservó {$itemSummary} (Venta #{$sell->id}).",
            sellId: $sell->id,
            establishmentId: $sell->sold_by,
            actionUrl: '/seller/sells',
            meta: [
                'sell_id' => $sell->id,
                'customer_name' => $customerName,
                'gross_amount' => $sell->getGrossAmount(),
                'net_amount' => $sell->getNetAmount(),
            ]
        ));
    }
}
