<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;





class CustomerCartController extends CartController
{





    public function asingFirstCart(User | int $userOrId)
    {
        $user = $this->resolveUser($userOrId);

        $cart = $this->getLastActiveCart($user);
        if (!$cart) {
            $cart = $this->newCart($user);
        }

        return response()->json(['cart' => $cart], status: 200);
    }

    public function customerCart()
    {
        $user = Auth::user();
        $cart = $this->getLastActiveCart($user);

        if (!$cart) {
            return response()->json(['message' => 'No active cart found.'], status: 404);
        }

        return response()->json(['cart' => $cart], status: 200);
    }


    public function addToCart(Request $request)
    {
        $request->validate([
            'offer_id' => 'required|integer|exists:offers,id',
            'quantity' => 'required|integer|min:1',
        ]);
        $offerController = new OfferController();
        $offer = $offerController->validateExpiration($request->offer_id);

        if (!$offer) {
            return response()->json(['message' => 'Offer has expired.'], status: 400);
        }

        $offerCart = $this->addOfferToCart($offer, $request->quantity);

        return response()->json($offerCart, status: 200);
    }
}
