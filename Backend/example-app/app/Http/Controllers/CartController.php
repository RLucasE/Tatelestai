<?php

namespace App\Http\Controllers;

use App\Actions\Offers\ResolveOfferAction;
use App\Models\User;
use App\Enums\CartState;
use App\Models\UserCart;
use App\Http\Controllers\OfferController;
use App\Models\Offer;
use App\Models\OfferCart;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function __construct(
    )
    {
    }
    public function addOfferToCart(Offer|int $offer, int $quantity): OfferCart
    {
        $offer = app(ResolveOfferAction::class)($offer);

        return  OfferCart::create([
            'offer_id' => $offer->id,
            'user_cart_id' => $this->getLastActiveCart(Auth::id())->id,
            'quantity' => $quantity,
        ]);


    }

    public function resolveUser(User|int $userOrId): User
    {
        return is_int($userOrId)
            ? User::findOrFail($userOrId)
            : $userOrId;
    }

    public function getLastActiveCart(User|int $userOrId): UserCart | null
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
