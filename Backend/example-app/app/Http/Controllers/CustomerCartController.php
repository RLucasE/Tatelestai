<?php

namespace App\Http\Controllers;

use App\Actions\Cart\AddToCartAction;
use App\Actions\Cart\AssignFirstCartAction;
use App\Actions\Cart\ClearCustomerCartAction;
use App\Actions\Cart\GetCustomerCartAction;
use App\Enums\CartState;
use App\Exceptions\Cart\OfferQuantityExceededException;
use App\Models\OfferCart;
use App\Models\User;
use App\Models\UserCart;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        } catch (Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], status: 404);
        }

        if ($groupedOffers === null) {
            return response()->json(['message' => 'No active cart found.'], status: 404);
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
        } catch (OfferQuantityExceededException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'requested_quantity' => $e->context()['requested_quantity'] ?? null,
                'available_quantity' => $e->context()['available_quantity'] ?? null,
                'already_in_cart' => $e->context()['already_in_cart'] ?? null,
                'error' => $e->context()['error'] ?? null,
            ], 400);
        } catch (Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], status: 400);
        }
        if (! $offerCart) {
            return response()->json(['message' => 'Offer has expired.'], status: 400);
        }

        return response()->json($offerCart, status: 200);
    }

    public function removeFromCart(string $offerId)
    {
        try {
            $userCarts = UserCart::where('user_id', Auth::id())
                ->where('state', CartState::ACTIVE->value)
                ->pluck('id');

            if ($userCarts->isEmpty()) {
                return response()->json(['message' => 'No active cart found.'], status: 404);
            }

            $offerCart = OfferCart::whereIn('user_cart_id', $userCarts)
                ->where('offer_id', $offerId)
                ->first();

            if (! $offerCart) {
                return response()->json(['message' => 'Offer not found in cart.'], status: 404);
            }

            $offerCart->delete();
        } catch (Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], status: 404);
        }

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
            return response()->json(['message' => 'No active cart found.'], status: 404);
        }

        $offerCart = OfferCart::where('offer_id', $offerId)
            ->whereIn('user_cart_id', $userCarts)
            ->first();

        if (! $offerCart) {
            return response()->json(['message' => 'Offer not found in cart.'], status: 404);
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
