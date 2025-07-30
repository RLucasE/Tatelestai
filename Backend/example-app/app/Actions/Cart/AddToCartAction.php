<?php

namespace App\Actions\Cart;

use App\Exceptions\Cart\OfferQuantityExceededException;
use App\Models\OfferCart;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\CartController;
use Illuminate\Support\Facades\Auth;

class AddToCartAction
{
    protected $offerController;
    protected $cartController;

    public function __construct(OfferController $offerController, CartController $cartController)
    {
        $this->offerController = $offerController;
        $this->cartController = $cartController;
    }

    public function handle(int $offerId, int $quantity)
    {
        $offer = $this->offerController->validateExpiration($offerId);

        if (!$offer) {
            return null;
        }

        if ($this->offerIsInCart($offerId)) {
            return $this->updateOfferQuantity($offerId, $quantity);
        }

        return $this->cartController->addOfferToCart($offer, $quantity);
    }

    protected function offerIsInCart(int $offerId)
    {
        $activeCart = $this->cartController->getLastActiveCart(Auth::id());

        if (!$activeCart) {
            return false;
        }

        // Verificar si existe un registro con el offerId en el carrito activo
        return OfferCart::where('offer_id', $offerId)
            ->where('user_cart_id', $activeCart->id)
            ->exists();
    }

    protected function updateOfferQuantity(int $offerId, int $quantity)
    {
        $activeCart = $this->cartController->getLastActiveCart(Auth::id());
        $offer = $this->offerController->resolveOffer($offerId);



        if (!$activeCart) {
            return null;
        }

        $offerCart = OfferCart::where('offer_id', $offerId)
            ->where('user_cart_id', $activeCart->id)
            ->first();



        if ($offerCart) {
            $newQuantity = $offerCart->quantity + $quantity;
            if ($newQuantity > $offer->quantity) {
                $exeption = new OfferQuantityExceededException();
                $exeption->setContext($offerId, $quantity, $offer->quantity, $offer->quantity);
                throw $exeption;
            }
            $offerCart->quantity = $newQuantity;
            $offerCart->save();
        }

        return $offerCart;
    }
}
