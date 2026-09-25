<?php

namespace App\Http\Controllers;

use App\Actions\Offers\ResolveOfferAction;
use App\Enums\CartState;
use App\Models\Offer;
use App\Models\OfferCart;
use App\Models\User;
use App\Models\UserCart;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function __construct() {}

    public function addOfferToCart(Offer|int $offer, int $quantity): OfferCart
    {
        $offer = app(ResolveOfferAction::class)($offer);
        $activeCart = $this->findOrCreateActiveCart(Auth::id(), $offer->food_establishment_id);

        return OfferCart::create([
            'offer_id' => $offer->id,
            'user_cart_id' => $activeCart->id,
            'quantity' => $quantity,
        ]);
    }

    public function resolveUser(User|int $userOrId): User
    {
        return is_int($userOrId)
            ? User::findOrFail($userOrId)
            : $userOrId;
    }

    public function getActiveCartByEstablishment(User|int $userOrId, int $establishmentId): ?UserCart
    {
        $user = $this->resolveUser($userOrId);

        return UserCart::where('user_id', $user->id)
            ->where('food_establishment_id', $establishmentId)
            ->where('state', CartState::ACTIVE->value)
            ->latest('id')
            ->first();
    }

    public function findOrCreateActiveCart(User|int $userOrId, int $establishmentId): UserCart
    {
        $user = $this->resolveUser($userOrId);

        return UserCart::firstOrCreate([
            'user_id' => $user->id,
            'food_establishment_id' => $establishmentId,
            'state' => CartState::ACTIVE->value,
        ]);
    }

    public function getAllActiveCarts(User|int $userOrId): Collection
    {
        $user = $this->resolveUser($userOrId);

        return UserCart::where('user_id', $user->id)
            ->where('state', CartState::ACTIVE->value)
            ->with(['foodEstablishment', 'offerCarts.offer'])
            ->get();
    }

    public function getLastActiveCart(User|int $userOrId, ?int $establishmentId = null): ?UserCart
    {
        $user = $this->resolveUser($userOrId);

        $query = UserCart::where('user_id', $user->id)
            ->where('state', CartState::ACTIVE->value);

        if ($establishmentId !== null) {
            $query->where('food_establishment_id', $establishmentId);
        }

        return $query->latest('id')->first();
    }

    public function deactivateCart(UserCart|User|int $cartOrUser, ?int $establishmentId = null): bool
    {
        if ($cartOrUser instanceof UserCart) {
            $cartOrUser->state = CartState::PURCHASED->value;

            return $cartOrUser->save();
        }

        $user = $this->resolveUser($cartOrUser);
        $activeCart = $this->getLastActiveCart($user, $establishmentId);

        if (! $activeCart) {
            return false;
        }

        $activeCart->state = CartState::PURCHASED->value;

        return $activeCart->save();
    }

    public function newCart(User|int $userOrId, ?int $establishmentId = null): ?UserCart
    {
        $user = $this->resolveUser($userOrId);

        $data = [
            'user_id' => $user->id,
            'state' => CartState::ACTIVE->value,
        ];

        if ($establishmentId !== null) {
            $data['food_establishment_id'] = $establishmentId;
        }

        $cart = UserCart::create($data);

        return ! $cart ? null : $cart;
    }
}
