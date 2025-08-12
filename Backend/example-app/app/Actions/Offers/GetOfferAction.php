<?php

namespace App\Actions\Offers;

use App\Models\Offer;

class GetOfferAction
{
    /**
     * Obtiene una oferta por su ID
     *
     * @param int $offerId ID de la oferta
     * @param bool $withProducts Indica si se deben cargar los productos asociados
     * @return Offer|null
     * @throws \Exception
     */
    public function execute(int $offerId, bool $withProducts = false): ?Offer
    {
        try {
            if ($withProducts) {
                return Offer::with(['products' => function ($query) {
                    $query->select('products.id', 'products.name', 'products.description')
                        ->withPivot('price', 'quantity');
                }])->findOrFail($offerId);
            }

            return Offer::findOrFail($offerId);
        }catch (\Exception $exception){
            throw new \Exception('Oferta no encontrada', 404, $exception);
        }
    }
}
