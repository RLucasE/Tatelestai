<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\OfferCart;






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


            /*  $offers = DB::select(
            "
            SELECT
                ofs.id as offer_id,
                ofs.food_establishment_id as estalishment_id,
                ofs.title as offer_title,
                ofs.description as offer_description,
                ofc.quantity as quantity,
                po.name as product_name,
                pof.price as product_price,
                pof.quantity as product_quantity
            FROM
                offers ofs
                INNER JOIN product_offers pof ON ofs.id = pof.offer_id
                INNER JOIN products po ON pof.product_id = po.id
                INNER JOIN offer_carts ofc ON ofs.id = ofc.offer_id
            WHERE
                ofc.user_cart_id = :userCartId
            ",
            ['userCartId' => $cart->id]
        ) */;

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

        $groupedOffers = $offers->groupBy('establishment_id')->values();



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
        $offerController = new OfferController();
        $offer = $offerController->validateExpiration($request->offer_id);

        if (!$offer) {
            return response()->json(['message' => 'Offer has expired.'], status: 400);
        }

        $offerCart = $this->addOfferToCart($offer, $request->quantity);

        return response()->json($offerCart, status: 200);
    }
}
