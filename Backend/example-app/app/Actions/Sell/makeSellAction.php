<?php

namespace App\Actions\Sell;

use App\Actions\Offers\GetOfferAction;
use App\DTOs\PreparePurchaseDTO;
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
        private CalculateMaxPickupDatetimeAction $calculateMaxPickupDatetimeAction
    ) {}

    /**
     * @throws \Throwable
     */
    public function execute(PreparePurchaseDTO $preparePurchaseDTO, int $bought_by, int $sold_by): array
    {
        return DB::transaction(function () use ($preparePurchaseDTO, $bought_by, $sold_by) {
            $pickupCode = $this->generatePickupCodeAction->execute($bought_by, $sold_by, $preparePurchaseDTO);

            $maxPickupDatetime = $this->calculateMaxPickupDatetimeAction->execute($preparePurchaseDTO->offers);

            $sell = Sell::create([
                'bought_by' => $bought_by,
                'sold_by' => $sold_by,
                'pickup_code' => $pickupCode,
                'max_pickup_datetime' => $maxPickupDatetime,
            ]);

            foreach ($preparePurchaseDTO->offers as $offerDTO) {
                $updatedRows = Offer::query()
                    ->where('id', $offerDTO->id)
                    ->where('quantity', '>=', $offerDTO->quantity)
                    ->decrement('quantity', $offerDTO->quantity);

                if ($updatedRows === 0) {
                    $currentOffer = $this->getOfferAction->execute($offerDTO->id);
                    throw new \Exception("No hay suficiente stock disponible para la oferta: {$currentOffer->title}");
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
                    $offer->update(['state' => 'purchased']);
                }
            }

            return [
                'sell_id' => $sell->id,
                'message' => 'Venta realizada exitosamente',
                'offers_processed' => count($preparePurchaseDTO->offers),
            ];
        });
    }
}
