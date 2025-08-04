<?php

namespace App\Actions\Cart;

use App\Actions\Offers\ValidateOfferExpirationAction;
use App\Actions\Offers\ValidateOfferStateAction;
use App\Enums\OfferState;
use App\Exceptions\Cart\OfferQuantityExceededException;
use App\Models\OfferCart;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\CartController;
use Illuminate\Support\Facades\Auth;
use App\Actions\Offers\GetOfferAction;

class AddToCartAction
{


    public function __construct(
        private OfferController $offerController,
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
        if (!$this->validateOfferExpiration->execute($offerId)) {
            return null;
        }
        try {
            $this->validateOfferState->execute($offerId,OfferState::ACTIVE->value);
            if ($this->offerIsInCart($offerId)) {
                return $this->updateOfferQuantity($offerId, $quantity);
            }
            $offer = $this->getOfferAction->execute($offerId);
            $this->validQuantity($offer->quantity,0,$quantity);
        }catch (OfferQuantityExceededException $exception) {
            $exception->setOfferId($offerId);
            throw $exception;
        }
        catch (\Exception $exception) {
            throw new \Exception("Error al agregar la oferta al carrito, " . $exception->getMessage());
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

    /**
     * @throws OfferQuantityExceededException
     */
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
        if ($offerQuantity < $quantityInCart + $newQuantity) throw (new OfferQuantityExceededException())->setContext(0,$newQuantity,$offerQuantity,$quantityInCart); else return true;
    }
}
