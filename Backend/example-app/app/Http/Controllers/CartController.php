<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Enums\CartState;
use App\Models\UserCart;

class CartController extends Controller
{

    protected function resolveUser(User|int $userOrId): User
    {
        return is_int($userOrId)
            ? User::findOrFail($userOrId)
            : $userOrId;
    }

    protected function getLastActiveCart(User|int $userOrId): UserCart | null
    {
        $user = $this->resolveUser($userOrId);

        $cart = $user->carts()->where("state", CartState::ACTIVE->value)->first();

        return !$cart ? null : $cart;
    }


    public function deactivateCart(User|int $userOrId): bool
    {
        $user = $this->resolveUser($userOrId);
        $activeCart = $this->getLastActiveCart($user);

        if (!$activeCart) {
            return false; // No active cart to deactivate
        }

        $activeCart->state = CartState::PURCHASED->value;
        return $activeCart->save();
    }




    public function newCart(User|int $userOrId): UserCart | null
    {
        $user = $this->resolveUser($userOrId);

        $cart = UserCart::create([
            'user_id' => $user->id,
            'state' => CartState::ACTIVE->value,
        ]);

        return !$cart ? null : $cart;
    }
}
