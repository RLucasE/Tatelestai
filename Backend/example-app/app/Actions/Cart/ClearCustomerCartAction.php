<?php

namespace App\Actions\Cart;

use App\Models\OfferCart;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\CustomerCartController;

class ClearCustomerCartAction
{
    /**
     * Elimina todos los OfferCart del usuario para el/los food_establishments dados.
     * Si OfferCart no tiene food_establishment_id, filtra por la relación offer.food_establishment_id.
     *
     * @param int|string $userId
     * @param int|string|array $foodEstablishmentIds Puede ser uno o varios IDs
     * @param bool $hardDelete forceDelete si el modelo usa SoftDeletes
     * @return int                   Cantidad de ítems eliminados
     * @throws \Throwable
     */
    public function __invoke(int|string $userId, int|string|array $foodEstablishmentIds, bool $hardDelete = false): int
    {
        $ids = array_values(array_filter(
            Arr::wrap($foodEstablishmentIds),
            fn ($v) => $v !== null && $v !== ''
        ));

        if (empty($ids)) {
            return 0;
        }

        return DB::transaction(function () use ($userId, $ids, $hardDelete): int {
            $userCart = app(CustomerCartController::class)->getLastActiveCart($userId);
            $query = OfferCart::query()
                ->where('user_cart_id', $userCart->id);
            $query->whereHas('offer', fn ($q) => $q->whereIn('food_establishment_id', $ids));
            return $hardDelete ? $query->forceDelete() : $query->delete();
        });
    }
}

