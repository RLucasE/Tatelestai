<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Enums\CartState;
use App\Models\UserCart;
use App\Http\Controllers\OfferController;
use App\Models\Offer;
use App\Models\OfferCart;
use Illuminate\Support\Facades\Auth;



class CartController extends Controller
{

    protected function addOfferToCart(Offer|int $offer, int $quantity): OfferCart
    {
        $OfferController = new OfferController();
        $offer = $OfferController->resolveOffer($offer);

        $offerCart = OfferCart::create([
            'offer_id' => $offer->id,
            'user_cart_id' => $this->getLastActiveCart(Auth::id())->id,
            'quantity' => $quantity,
        ]);

        return $offerCart;
    }
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


    protected function deactivateCart(User|int $userOrId): bool
    {
        $user = $this->resolveUser($userOrId);
        $activeCart = $this->getLastActiveCart($user);

        if (!$activeCart) {
            return false; // No active cart to deactivate
        }

        $activeCart->state = CartState::PURCHASED->value;
        return $activeCart->save();
    }


    protected function newCart(User|int $userOrId): UserCart | null
    {
        $user = $this->resolveUser($userOrId);

        $cart = UserCart::create([
            'user_id' => $user->id,
            'state' => CartState::ACTIVE->value,
        ]);

        return !$cart ? null : $cart;
    }
}
