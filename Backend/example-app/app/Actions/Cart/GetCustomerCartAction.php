<?php

namespace App\Actions\Cart;

use App\Models\OfferCart;
use App\Models\User;
use App\Http\Controllers\CartController;
use Illuminate\Support\Facades\Auth;

class GetCustomerCartAction
{
    protected $cartController;

    public function __construct(CartController $cartController)
    {
        $this->cartController = $cartController;
    }

    public function handle(User $user)
    {
        $user = $user ?? Auth::user();
        $cart = $this->cartController->getLastActiveCart($user);

        if (!$cart) {
            return null;
        }

        $offers = OfferCart::with(['offer.fullProducts', 'offer','offer.foodEstablishment'])
            ->where('user_cart_id', $cart->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($offerCart) {
                return [
                    'offer_id' => $offerCart->offer->id,
                    'establishment_id' => $offerCart->offer->food_establishment_id,
                    'establishment_name' => $offerCart->offer->foodEstablishment->name,
                    'establishment_address' => $offerCart->offer->foodEstablishment->address,
                    'offer_title' => $offerCart->offer->title,
                    'offer_description' => $offerCart->offer->description,
                    'offer_max_quantity' => $offerCart->offer->quantity,
                    'offer_state' => $offerCart->offer->state,
                    'offer_expiration_datetime' => $offerCart->offer->expiration_datetime,
                    'quantity' => $offerCart->quantity,
                    'products' => $offerCart->offer->fullProducts->map(function ($product) {
                        return [
                            'product_name' => $product->name,
                            'product_description' => $product->description,
                            'product_price' => $product->pivot->price,
                            'product_quantity' => $product->pivot->quantity,
                            'product_expiration_date' => $product->pivot->expiration_date,
                        ];
                    })->toArray(),
                ];
            });

        dump($offers);

        return $offers->groupBy('establishment_id')->values();
    }
}
