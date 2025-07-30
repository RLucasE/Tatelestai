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

        $offers = OfferCart::with(['offer.products', 'offer'])
            ->where('user_cart_id', $cart->id)
            ->get()
            ->map(function ($offerCart) {
                return [
                    'offer_id' => $offerCart->offer->id,
                    'establishment_id' => $offerCart->offer->food_establishment_id,
                    'offer_title' => $offerCart->offer->title,
                    'offer_description' => $offerCart->offer->description,
                    'quantity' => $offerCart->quantity,
                    'products' => $offerCart->offer->products->map(function ($product) {
                        return [
                            'product_name' => $product->name,
                            'product_price' => $product->pivot->price,
                            'product_quantity' => $product->pivot->quantity,
                        ];
                    })->toArray(),
                ];
            });

        return $offers->groupBy('establishment_id')->values();
    }
}
