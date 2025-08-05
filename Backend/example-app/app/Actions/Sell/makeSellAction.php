<?php

namespace App\Actions\Sell;

use App\Actions\Offers\GetOfferAction;
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
class makeSellAction{
    public function __construct(private GetOfferAction $getOfferAction)
    {
    }

    public function execute(array $offers, int $bought_by, int $sold_by): array
    {
        return DB::transaction(function () use ($offers, $bought_by, $sold_by) {

            $sell = Sell::create([
                'bought_by' => $bought_by,
                'sold_by' => $sold_by,
            ]);


            foreach ($offers as $offerData) {
                $offer = $this->getOfferAction->execute($offerData['id'],true);


                if ($offer->quantity < $offerData['quantity']) {
                    throw new \Exception("No hay suficiente stock disponible para la oferta: {$offer->title}");
                }
                foreach ($offer->products as $productData) {
                    SellDetail::create([
                        'sell_id' => $sell->id,
                        'offer_id' => $offer->id,
                        'offer_quantity' => $offerData['quantity'],
                        'product_quantity' => $productData['pivot']['quantity'],
                        'product_price' => $productData['pivot']['price'],
                        'product_name' => $productData['name'],
                        'product_description' => $productData['description'] ?? null,
                    ]);
                }


                $offer->update([
                    'quantity' => $offer->quantity - $offerData['quantity']
                ]);

                if($offer->quantity == 0){
                    //Desactivar la oferta para que ya no pueda ser comprada por nadie
                }
            }

            return [
                'sell_id' => $sell->id,
                'total' => $sell->total,
                'message' => 'Venta realizada con éxito'
            ];
        });
    }

    private function calculateTotal(array $offers): float
    {
        $total = 0;
        foreach ($offers as $offerData) {
            $offer = Offer::findOrFail($offerData['id']);
            $total += $this->calculateOfferPrice($offer) * $offerData['quantity'];
        }
        return $total;
    }

    private function calculateOfferPrice(Offer $offer): float
    {
        return $offer->products->sum(function ($product) {
            return $product->pivot->price * $product->pivot->quantity;
        });
    }
}
