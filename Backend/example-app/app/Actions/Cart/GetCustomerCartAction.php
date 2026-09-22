<?php

namespace App\Actions\Cart;

use App\Enums\CartState;
use App\Http\Controllers\CartController;
use App\Models\OfferCart;
use App\Models\User;
use App\Models\UserCart;
use Illuminate\Support\Facades\Auth;

class GetCustomerCartAction
{
    protected $cartController;

    public function __construct(CartController $cartController)
    {
        $this->cartController = $cartController;
    }

    public function handle(?User $user = null)
    {
        $user = $user ?? Auth::user();

        if (! $user) {
            return null;
        }

        $activeCarts = UserCart::where('user_id', $user->id)
            ->where('state', CartState::ACTIVE->value)
            ->get();

        if ($activeCarts->isEmpty()) {
            return null;
        }

        $offers = OfferCart::with(['offer', 'offer.foodEstablishment'])
            ->whereIn('user_cart_id', $activeCarts->pluck('id'))
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn ($offerCart) => $offerCart->offer !== null)
            ->map(function ($offerCart) {
                return [
                    'offer_id' => $offerCart->offer->id,
                    'establishment_id' => $offerCart->offer->food_establishment_id,
                    'establishment_name' => $offerCart->offer->foodEstablishment?->name,
                    'establishment_address' => $offerCart->offer->foodEstablishment?->address,
                    'offer_title' => $offerCart->offer->title,
                    'offer_description' => $offerCart->offer->description,
                    'offer_price' => (int) $offerCart->offer->price,
                    'minimum_value' => (int) $offerCart->offer->minimum_value,
                    'allergens' => $offerCart->offer->allergens,
                    'estimated_weight_kg' => $offerCart->offer->estimated_weight_kg,
                    'offer_max_quantity' => $offerCart->offer->quantity,
                    'offer_state' => $offerCart->offer->state,
                    'pickup_start_datetime' => $offerCart->offer->pickup_start_datetime,
                    'offer_expiration_datetime' => $offerCart->offer->expiration_datetime,
                    'quantity' => $offerCart->quantity,
                ];
            });

        return $offers->groupBy('establishment_id')->values();
    }
}

