<?php

namespace App\Actions\Sell;

use App\DTOs\PreparePurchaseDTO;
use App\Models\Offer;
use Exception;

class VerifyPurchaseDataFreshnessAction
{
    /**
     * Verifica que los datos en el DTO coincidan con los datos actuales en la base de datos
     * para evitar discrepancias entre lo que el usuario confirmó y lo que realmente está disponible.
     *
     * @throws Exception Si hay discrepancias entre los datos
     */
    public function execute(PreparePurchaseDTO $preparePurchaseDTO): bool
    {
        // Recolectar todos los IDs de ofertas para hacer una sola consulta
        $offerIds = array_map(fn ($offer) => $offer->id, $preparePurchaseDTO->offers);

        // Obtener las ofertas actualizadas desde la base de datos
        $currentOffers = Offer::whereIn('id', $offerIds)
            ->get()
            ->keyBy('id');

        // Verificar cada oferta
        foreach ($preparePurchaseDTO->offers as $offerDTO) {
            $currentOffer = $currentOffers->get($offerDTO->id);

            // Verificar si la oferta aún existe
            if (! $currentOffer) {
                throw new Exception("La oferta '{$offerDTO->title}' ya no está disponible.");
            }

            // Verificar si el título o descripción han cambiado
            if ($currentOffer->title !== $offerDTO->title) {
                throw new Exception('El título de la oferta ha cambiado. Por favor, actualiza tu carrito.');
            }

            if ($currentOffer->description !== $offerDTO->description) {
                throw new Exception('La descripción de la oferta ha cambiado. Por favor, actualiza tu carrito.');
            }

            // Verificar si el precio ha cambiado
            if ((int) $currentOffer->price !== (int) $offerDTO->price) {
                throw new Exception('El precio de la oferta ha cambiado. Por favor, actualiza tu carrito.');
            }

            // Verificar si la oferta sigue perteneciendo al mismo establecimiento
            if ($currentOffer->food_establishment_id != $preparePurchaseDTO->food_establishment_id) {
                throw new Exception('La oferta ya no pertenece al mismo establecimiento. Por favor, actualiza tu carrito.');
            }
        }

        // Si llegamos aquí, todos los datos están actualizados
        return true;
    }
}
