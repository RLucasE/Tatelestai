<?php

namespace App\Actions\Cart;

use App\Enums\CartState;
use App\Models\OfferCart;
use App\Models\UserCart;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ClearCustomerCartAction
{
    /**
     * Elimina todos los OfferCart del usuario para el/los food_establishments dados
     * y elimina los user_carts correspondientes.
     *
     * @param  int|string|array  $foodEstablishmentIds  Puede ser uno o varios IDs
     * @param  bool  $hardDelete  forceDelete si el modelo usa SoftDeletes
     * @return int Cantidad de ítems eliminados
     *
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
            $userCarts = UserCart::where('user_id', $userId)
                ->where('state', CartState::ACTIVE->value)
                ->where(function ($q) use ($ids) {
                    $q->whereIn('food_establishment_id', $ids)
                        ->orWhereNull('food_establishment_id');
                })
                ->pluck('id');

            if ($userCarts->isEmpty()) {
                return 0;
            }

            $query = OfferCart::query()
                ->whereIn('user_cart_id', $userCarts);
            $query->whereHas('offer', fn ($q) => $q->whereIn('food_establishment_id', $ids));

            $deleted = $hardDelete ? $query->forceDelete() : $query->delete();

            UserCart::whereIn('id', $userCarts)->delete();

            return $deleted;
        });
    }
}
