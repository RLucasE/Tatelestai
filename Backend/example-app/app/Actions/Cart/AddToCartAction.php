<?php

namespace App\Actions\Cart;

use App\Actions\Offers\GetOfferAction;
use App\Actions\Offers\ResolveOfferAction;
use App\Actions\Offers\ValidateOfferExpirationAction;
use App\Actions\Offers\ValidateOfferStateAction;
use App\Enums\OfferState;
use App\Exceptions\Cart\OfferQuantityExceededException;
use App\Http\Controllers\CartController;
use App\Models\OfferCart;
use Illuminate\Support\Facades\Auth;

class AddToCartAction
{
    public function __construct(
        private ResolveOfferAction $resolveOfferAction,
        private CartController $cartController,
        private ValidateOfferExpirationAction $validateOfferExpiration,
        private GetOfferAction $getOfferAction,
        private ValidateOfferStateAction $validateOfferState,
    ) {}

    /**
     * @throws OfferQuantityExceededException
     * @throws \Exception
     */
    public function handle(int $offerId, int $quantity)
    {
        if (! $this->validateOfferExpiration->execute($offerId)) {
            return null;
        }
        try {
            $this->validateOfferState->execute($offerId, OfferState::ACTIVE->value);
            $offer = $this->getOfferAction->execute($offerId);

            if ($this->offerIsInCart($offerId, $offer->food_establishment_id)) {
                return $this->updateOfferQuantity($offerId, $quantity, $offer->food_establishment_id);
            }

            $this->validQuantity($offer->quantity, 0, $quantity);
        } catch (OfferQuantityExceededException $exception) {
            $exception->setOfferId($offerId);
            throw $exception;
        } catch (\Exception $exception) {
            throw new \Exception('Error al agregar la oferta al carrito, '.$exception->getMessage());
        }

        return $this->cartController->addOfferToCart($offer, $quantity);
    }

    protected function offerIsInCart(int $offerId, ?int $establishmentId = null): bool
    {
        $activeCart = $establishmentId
            ? $this->cartController->getActiveCartByEstablishment(Auth::id(), $establishmentId)
            : $this->cartController->getLastActiveCart(Auth::id());

        if (! $activeCart) {
            return false;
        }

        return OfferCart::where('offer_id', $offerId)
            ->where('user_cart_id', $activeCart->id)
            ->exists();
    }

    /**
     * @throws OfferQuantityExceededException
     */
    protected function updateOfferQuantity(int $offerId, int $quantity, ?int $establishmentId = null): ?OfferCart
    {
        $offer = ($this->resolveOfferAction)($offerId);
        $establishmentId = $establishmentId ?? $offer->food_establishment_id;
        $activeCart = $this->cartController->getActiveCartByEstablishment(Auth::id(), $establishmentId);

        if (! $activeCart) {
            return null;
        }

        $offerCart = OfferCart::where('offer_id', $offerId)
            ->where('user_cart_id', $activeCart->id)
            ->first();

        if ($offerCart) {
            $this->validQuantity($offer->quantity, $offerCart->quantity, $quantity);
            $offerCart->quantity += $quantity;
            $offerCart->save();
        }

        return $offerCart;
    }

    /**
     * @throws OfferQuantityExceededException
     */
    protected function validQuantity(int $offerQuantity, int $quantityInCart, int $newQuantity): bool
    {
        if ($offerQuantity < $quantityInCart + $newQuantity) {
            throw (new OfferQuantityExceededException)->setContext(0, $newQuantity, $offerQuantity, $quantityInCart);
        } else {
            return true;
        }
    }
}
