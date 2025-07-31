<?php

namespace App\Actions\Offers;

use App\Models\FoodEstablishment;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use App\Actions\Offers\GetUserEstablishmentAction;
use App\Exceptions\Product\ProductOwnershipException;

class ValidateProductOwnershipAction
{
    /**
     * Valida si los productos pertenecen al establecimiento del usuario autenticado
     *
     * @param array $productIds IDs de los productos a validar
     * @return bool True si todos los productos pertenecen al establecimiento
     * @throws ProductOwnershipException Si los productos no pertenecen al establecimiento
     */
    public function execute(array $productIds): bool
    {
        $establishment = (new GetUserEstablishmentAction())->execute();

        if (!$establishment) {
            throw new ProductOwnershipException("No se encontró un establecimiento asociado al usuario");
        }

        $validProductsCount = Product::where('food_establishment_id', $establishment->id)
            ->whereIn('id', $productIds)
            ->count();

        if ($validProductsCount !== count($productIds)) {
            // Obtener los IDs de productos que no pertenecen al establecimiento
            $validProductIds = Product::where('food_establishment_id', $establishment->id)
                ->whereIn('id', $productIds)
                ->pluck('id')
                ->toArray();
            
            $invalidProductIds = array_diff($productIds, $validProductIds);
            
            throw (new ProductOwnershipException())
                ->setContext($invalidProductIds, $establishment->id);
        }

        return true;
    }
}
