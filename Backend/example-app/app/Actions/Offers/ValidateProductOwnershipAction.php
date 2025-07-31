<?php

namespace App\Actions\Offers;

use App\Models\FoodEstablishment;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use App\Actions\Offers\GetUserEstablishmentAction;

class ValidateProductOwnershipAction
{
    /**
     * Valida si los productos pertenecen al establecimiento del usuario autenticado
     *
     * @param array $productIds IDs de los productos a validar
     * @return bool True si todos los productos pertenecen al establecimiento, false en caso contrario
     */
    public function execute(array $productIds): bool
    {
        $establishment = (new GetUserEstablishmentAction())->execute();

        if (!$establishment) {
            return false;
        }

        $validProductsCount = Product::where('food_establishment_id', $establishment->id)
            ->whereIn('id', $productIds)
            ->count();

        return $validProductsCount === count($productIds);
    }
}
