<?php

namespace App\Actions\Sell;

use App\DTOs\PreparePurchaseDTO;
use App\Actions\Offers\GetOfferAction;
use App\Enums\CartState;
use App\Enums\OfferState;
use App\Models\Offer;
use App\Models\OfferCart;
use App\Models\Sell;
use App\Models\SellDetail;
use App\Models\UserCart;
use Illuminate\Support\Facades\DB;

/**
 * Pre-Requirements
 * -Offers belong to $sold_by
 * -Offers are not expired
 * -Offers belong to $sold_by's food_establishment
 * -Offers exist
 */
class makeSellAction{
    public function __construct(private GetOfferAction $getOfferAction, private GeneratePickupCodeAction $generatePickupCodeAction)
    {
    }

    /**
     * @param PreparePurchaseDTO $preparePurchaseDTO
     * @param int $bought_by
     * @param int $sold_by
     * @return array
     * @throws \Throwable
     */
    public function execute(PreparePurchaseDTO $preparePurchaseDTO, int $bought_by, int $sold_by): array
    {
        $pickupCode = $this->generatePickupCodeAction->execute($bought_by, $sold_by, $preparePurchaseDTO);

        $sell = Sell::create([
            'bought_by' => $bought_by,
            'sold_by' => $sold_by,
            'pickup_code' => $pickupCode,
        ]);


        foreach ($preparePurchaseDTO->offers as $offerDTO) {
            $offer = $this->getOfferAction->execute($offerDTO->id, true);


            if ($offer->quantity < $offerDTO->quantity) {
                throw new \Exception("No hay suficiente stock disponible para la oferta: {$offer->title}");
            }

            foreach ($offerDTO->products as $productDTO) {
                SellDetail::create([
                    'sell_id' => $sell->id,
                    'offer_quantity' => $offerDTO->quantity,
                    'offer_id' => $offerDTO->id,
                    'product_name' => $productDTO->name,
                    'product_description' => $productDTO->description,
                    'product_quantity' => $productDTO->quantity,
                    'product_price' => $productDTO->price,
                ]);
            }

            $offer->decrement('quantity', $offerDTO->quantity);

            if ($offer->quantity <= 0) {
                $offer->update(['state' => 'purchased']);
            }
        }

        return [
            'sell_id' => $sell->id,
            'message' => 'Venta realizada exitosamente',
            'offers_processed' => count($preparePurchaseDTO->offers)
        ];
    }
}
