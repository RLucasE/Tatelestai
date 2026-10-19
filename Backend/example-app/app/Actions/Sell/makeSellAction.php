<?php

namespace App\Actions\Sell;

use App\Actions\Cart\NotifyCartStockDisruptionAction;
use App\Actions\Offers\GetOfferAction;
use App\DTOs\PreparePurchaseDTO;
use App\Enums\OfferState;
use App\Models\Offer;
use App\Models\Sell;
use App\Models\SellDetail;
use Illuminate\Support\Facades\DB;

/**
 * Pre-Requirements
 * -Offers belong to $sold_by
 * -Offers are not expired
 * -Offers belong to $sold_by's food_establishment
 * -Offers exist
 */
class makeSellAction
{
    public function __construct(
        private GetOfferAction $getOfferAction,
        private GeneratePickupCodeAction $generatePickupCodeAction,
        private CalculateMaxPickupDatetimeAction $calculateMaxPickupDatetimeAction,
        private ?NotifyCartStockDisruptionAction $notifyCartStockDisruptionAction = null
    ) {
        $this->notifyCartStockDisruptionAction ??= app(NotifyCartStockDisruptionAction::class);
    }

    /**
     * @throws \Throwable
     */
    public function execute(
        PreparePurchaseDTO $preparePurchaseDTO,
        int $bought_by,
        int $sold_by,
        string $paymentStatus = 'approved',
        ?string $paymentId = null,
        ?string $preferenceId = null,
        ?string $paymentMethod = null
    ): array {
        return DB::transaction(function () use ($preparePurchaseDTO, $bought_by, $sold_by, $paymentStatus, $paymentId, $preferenceId, $paymentMethod) {
            $pickupCode = $this->generatePickupCodeAction->execute($bought_by, $sold_by, $preparePurchaseDTO);

            $maxPickupDatetime = $this->calculateMaxPickupDatetimeAction->execute($preparePurchaseDTO->offers);

            $grossAmount = 0.0;
            foreach ($preparePurchaseDTO->offers as $offerDTO) {
                $grossAmount += (int) $offerDTO->quantity * (float) $offerDTO->price;
            }
            $grossAmount = round($grossAmount, 2);

            $commissionPercentage = (float) config('services.mercadopago.commission_percentage', 15.0);
            $commissionAmount = round($grossAmount * ($commissionPercentage / 100), 2);
            $netAmount = round($grossAmount - $commissionAmount, 2);

            $sell = Sell::create([
                'bought_by' => $bought_by,
                'sold_by' => $sold_by,
                'pickup_code' => $pickupCode,
                'max_pickup_datetime' => $maxPickupDatetime,
                'payment_status' => $paymentStatus,
                'payment_id' => $paymentId,
                'preference_id' => $preferenceId,
                'payment_method' => $paymentMethod,
                'gross_amount' => $grossAmount,
                'commission_percentage' => $commissionPercentage,
                'commission_amount' => $commissionAmount,
                'net_amount' => $netAmount,
            ]);

            foreach ($preparePurchaseDTO->offers as $offerDTO) {
                $updatedRows = Offer::query()
                    ->where('id', $offerDTO->id)
                    ->where('state', OfferState::ACTIVE->value)
                    ->where('quantity', '>=', $offerDTO->quantity)
                    ->decrement('quantity', $offerDTO->quantity);

                if ($updatedRows === 0) {
                    $currentOffer = $this->getOfferAction->execute($offerDTO->id);
                    $title = $currentOffer ? $currentOffer->title : "ID {$offerDTO->id}";
                    throw new \Exception("No hay suficiente stock disponible para la oferta: {$title}");
                }

                $offer = $this->getOfferAction->execute($offerDTO->id);

                SellDetail::create([
                    'sell_id' => $sell->id,
                    'offer_id' => $offerDTO->id,
                    'offer_quantity' => $offerDTO->quantity,
                    'pack_name' => $offerDTO->title,
                    'pack_description' => $offerDTO->description,
                    'pack_price' => $offerDTO->price,
                ]);

                if ($offer->quantity <= 0) {
                    $offer->update(['state' => OfferState::PURCHASED->value]);

                    // Notificar al vendedor dueño del local que la oferta se agotó
                    $sellerUser = $offer->foodEstablishment?->user;
                    if ($sellerUser) {
                        $sellerUser->notify(new \App\Notifications\RealtimeOrderNotification(
                            eventType: 'offer_sold_out',
                            title: '¡Oferta agotada!',
                            message: "Se agotaron todas las unidades disponibles de «{$offer->title}».",
                            offerId: $offer->id,
                            establishmentId: $offer->food_establishment_id,
                            actionUrl: '/seller/my-offers',
                            meta: ['offer_title' => $offer->title]
                        ));
                    }
                }

                // Notificar en forma privada a clientes con carritos activos afectados
                $this->notifyCartStockDisruptionAction->execute($offer, $bought_by);
            }

            return [
                'sell_id' => $sell->id,
                'message' => 'Venta realizada exitosamente',
                'offers_processed' => count($preparePurchaseDTO->offers),
            ];
        });
    }
}
