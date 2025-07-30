<?php

namespace App\Http\Controllers;


use App\Exceptions\Cart\OfferQuantityExceededException;
use App\Actions\Cart\AddToCartAction;
use App\Actions\Cart\AssignFirstCartAction;
use App\Actions\Cart\GetCustomerCartAction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerCartController extends CartController
{
    public function asingFirstCart(User | int $userOrId)
    {
        $user = $this->resolveUser($userOrId);
        $cart = app(AssignFirstCartAction::class)->handle($user);

        return response()->json(['cart' => $cart], status: 200);
    }

    public function customerCart()
    {
        $user = Auth::user();
        $groupedOffers = app(GetCustomerCartAction::class)->handle($user);

        if (!$groupedOffers) {
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
        }



        if (!$offerCart) {
            return response()->json(['message' => 'Offer has expired.'], status: 400);
        }

        return response()->json($offerCart, status: 200);
    }
}
