<?php

namespace App\Http\Controllers;

use App\Actions\Cart\AddToCartAction;
use App\Actions\Cart\AssignFirstCartAction;
use App\Actions\Cart\ClearCustomerCartAction;
use App\Actions\Cart\GetCustomerCartAction;
use App\Enums\CartState;
use App\Exceptions\ApiException;
use App\Exceptions\BusinessRuleException;
use App\Models\Offer;
use App\Models\OfferCart;
use App\Models\User;
use App\Models\UserCart;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CustomerCartController extends CartController
{
    public function __construct(private readonly GetCustomerCartAction $customerCartAction) {}

    public function asingFirstCart(User|int $userOrId)
    {
        $user = $this->resolveUser($userOrId);
        $cart = app(AssignFirstCartAction::class)->handle($user);

        return response()->json(['cart' => $cart], status: 200);
    }

    public function customerCart()
    {
        try {
            $groupedOffers = $this->customerCartAction->handle(Auth::user());
        } catch (ApiException|QueryException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            throw BusinessRuleException::notFound($exception->getMessage(), 'CART_NOT_FOUND');
        }

        if ($groupedOffers === null) {
            throw BusinessRuleException::notFound('No active cart found.', 'CART_NOT_FOUND');
        }

        return response()->json(
            $groupedOffers,
            status: 200
        );
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'offer_id' => 'required|integer|exists:offers,id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $offerCart = app(AddToCartAction::class)->handle(
                $request->offer_id,
                $request->quantity
            );
        } catch (ApiException|ValidationException|QueryException $e) {
            throw $e;
        } catch (Exception $exception) {
            throw BusinessRuleException::badRequest($exception->getMessage(), 'ADD_TO_CART_FAILED');
        }

        if (! $offerCart) {
            throw BusinessRuleException::badRequest('Offer has expired.', 'OFFER_EXPIRED');
        }

        return response()->json($offerCart, status: 200);
    }

    public function removeFromCart(string $offerId)
    {
        $userCarts = UserCart::where('user_id', Auth::id())
            ->where('state', CartState::ACTIVE->value)
            ->pluck('id');

        if ($userCarts->isEmpty()) {
            throw BusinessRuleException::notFound('No active cart found.', 'CART_NOT_FOUND');
        }

        $offerCart = OfferCart::whereIn('user_cart_id', $userCarts)
            ->where('offer_id', $offerId)
            ->first();

        if (! $offerCart) {
            throw BusinessRuleException::notFound('Offer not found in cart.', 'CART_ITEM_NOT_FOUND');
        }

        $offerCart->delete();

        return response()->json(['deleted' => $offerCart]);
    }

    public function updateCart(Request $request, int $offerId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $userCarts = UserCart::where('user_id', Auth::id())
            ->where('state', CartState::ACTIVE->value)
            ->pluck('id');

        if ($userCarts->isEmpty()) {
            throw BusinessRuleException::notFound('No active cart found.', 'CART_NOT_FOUND');
        }

        $offerCart = OfferCart::where('offer_id', $offerId)
            ->whereIn('user_cart_id', $userCarts)
            ->first();

        if (! $offerCart) {
            throw BusinessRuleException::notFound('Offer not found in cart.', 'CART_ITEM_NOT_FOUND');
        }

        $offer = Offer::find($offerId);
        if ($offer && $offer->visible_from && $offer->visible_from->isFuture()) {
            throw BusinessRuleException::badRequest('La oferta aún no está disponible para compra', 'OFFER_NOT_VISIBLE_YET');
        }

        $offerCart->update(['quantity' => $request->quantity]);

        return response()->json(['updated' => $offerCart]);
    }

    /**
     * @throws \Throwable
     */
    public function clearByEstablishment($establishment_id, ClearCustomerCartAction $clearCart)
    {
        $deleted = $clearCart(
            Auth::id(),
            $establishment_id
        );

        return response()->json(['deleted' => $deleted]);
    }
}
