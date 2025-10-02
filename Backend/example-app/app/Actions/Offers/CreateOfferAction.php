<?php

namespace App\Actions\Offers;

use App\Enums\OfferState;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreateOfferAction
{


    public function __construct(
        private GetUserEstablishmentAction $getUserEstablishmentAction,
        private ValidateProductOwnershipAction $validateProductOwnershipAction
    ) {}

    /**
     * Crea una nueva oferta con sus productos asociados
     *
     * @param Request $request Datos de la oferta
     * @return Offer Oferta creada
     * @throws \Exception Si no se encuentra el establecimiento o los productos no pertenecen al usuario
     */
    public function execute(Request $request): Offer
    {
        $establishment = $this->getUserEstablishmentAction->execute();
        if (!$establishment) {
            throw new \Exception('No se encontró establecimiento asociado al usuario');
        }
        $products = $request->array('products');
        $productIDs = $this->productsToProductsIDs($products);
        if (!$this->validateProductOwnershipAction->execute($productIDs)) {
            throw new \Exception('Uno o más productos no pertenecen a tu establecimiento');
        }
        DB::beginTransaction();
        try {
            $offer = Offer::create([
                'title' => $request->string('title'),
                'description' => $request->string('description'),
                'expiration_date' => $request->date('expiration_date')->toDateString(),
                'time' => $request->date('expiration_date')->toTimeString(),
                'expiration_datetime' => $request->date('expiration_date')->toDateTimeString(),
                'food_establishment_id' => $establishment->getAttribute('id'),
                'state' => OfferState::ACTIVE->value,
                'quantity' => $request->integer('quantity', 1),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->attachProductsToOffer($offer, $products);
            DB::commit();
            return $offer;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Convierte un array de productos a un array de IDs de productos
     *
     * @param array $products Array de productos
     * @return array Array de IDs de productos
     */
    private function productsToProductsIDs(array $products): array
    {
        return array_map(function ($product) {
            return $product['id'];
        }, $products);
    }

    /**
     * Asocia productos a una oferta
     *
     * @param Offer $offer Oferta
     * @param array $products Array de productos
     * @return void
     */
    private function attachProductsToOffer(Offer $offer, array $products): void
    {
        $productOfferData = array_map(function ($product) use ($offer) {
            return [
                'product_id' => $product['id'],
                'quantity' => $product['quantity'],
                'price' => $product['price'],
                'expiration_date' => $product['expirationDate'],
                'offer_id' => $offer->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $products);

        DB::table('product_offers')->insert($productOfferData);
    }
}
