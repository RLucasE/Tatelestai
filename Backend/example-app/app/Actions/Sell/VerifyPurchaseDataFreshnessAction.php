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
     * @param PreparePurchaseDTO $preparePurchaseDTO
     * @return bool
     * @throws Exception Si hay discrepancias entre los datos
     */
    public function execute(PreparePurchaseDTO $preparePurchaseDTO): bool
    {
        // Recolectar todos los IDs de ofertas para hacer una sola consulta
        $offerIds = array_map(fn($offer) => $offer->id, $preparePurchaseDTO->offers);

        // Obtener las ofertas actualizadas desde la base de datos
        $currentOffers = Offer::with(['products'])
            ->whereIn('id', $offerIds)
            ->get()
            ->keyBy('id');

        // Verificar cada oferta
        foreach ($preparePurchaseDTO->offers as $offerDTO) {
            $currentOffer = $currentOffers->get($offerDTO->id);

            // Verificar si la oferta aún existe
            if (!$currentOffer) {
                throw new Exception("La oferta '{$offerDTO->title}' ya no está disponible.");
            }

            // Verificar si el título o descripción han cambiado
            if ($currentOffer->title !== $offerDTO->title) {
                throw new Exception("El título de la oferta ha cambiado. Por favor, actualiza tu carrito.");
            }

            if ($currentOffer->description !== $offerDTO->description) {
                throw new Exception("La descripción de la oferta ha cambiado. Por favor, actualiza tu carrito.");
            }

            // Verificar si la oferta sigue perteneciendo al mismo establecimiento
            if ($currentOffer->food_establishment_id != $preparePurchaseDTO->food_establishment_id) {
                throw new Exception("La oferta ya no pertenece al mismo establecimiento. Por favor, actualiza tu carrito.");
            }

            // Verificar productos
            $currentProducts = $currentOffer->products->keyBy('id');

            // Crear un mapa de productos del DTO por nombre y descripción para comparación
            $dtoProductsMap = [];
            foreach ($offerDTO->products as $productDTO) {
                $key = md5($productDTO->name . '|' . $productDTO->description);
                $dtoProductsMap[$key] = $productDTO;
            }

            // Crear un mapa similar para los productos actuales
            $currentProductsMap = [];
            foreach ($currentOffer->products as $product) {
                $key = md5($product->name . '|' . ($product->description ?? ''));
                $currentProductsMap[$key] = [
                    'product' => $product,
                    'price' => (float) $product->pivot->price,
                    'quantity' => (int) $product->pivot->quantity
                ];
            }

            // Verificar si hay productos nuevos o eliminados
            $dtoKeys = array_keys($dtoProductsMap);
            $currentKeys = array_keys($currentProductsMap);

            $missingProducts = array_diff($dtoKeys, $currentKeys);
            $newProducts = array_diff($currentKeys, $dtoKeys);

            if (count($missingProducts) > 0) {
                throw new Exception("Algunos productos en la oferta '{$offerDTO->title}' ya no están disponibles. Por favor, actualiza tu carrito.");
            }

            if (count($newProducts) > 0) {
                throw new Exception("La oferta '{$offerDTO->title}' ha cambiado su composición. Por favor, actualiza tu carrito.");
            }

            // Verificar cambios en precios o cantidades
            foreach ($dtoProductsMap as $key => $productDTO) {
                $currentProductData = $currentProductsMap[$key];

                if (abs($currentProductData['price'] - $productDTO->price) > 0.001) {
                    throw new Exception("El precio del producto '{$productDTO->name}' ha cambiado. Por favor, actualiza tu carrito.");
                }

                if ($currentProductData['quantity'] !== $productDTO->quantity) {
                    throw new Exception("La cantidad disponible del producto '{$productDTO->name}' ha cambiado. Por favor, actualiza tu carrito.");
                }
            }
        }

        // Si llegamos aquí, todos los datos están actualizados
        return true;
    }
}
