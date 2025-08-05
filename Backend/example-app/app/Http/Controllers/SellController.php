<?php

namespace App\Http\Controllers;


use App\Actions\Offers\OfferIsFromFoodEstablishmentAction;
use App\Actions\Offers\ValidateOfferExpirationAction;
use App\Actions\Sell\makeSellAction;
use App\Actions\Sell\SellValidationRules;
use App\Models\FoodEstablishment;
use App\Models\Sell;
use App\Models\UserCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class SellController
{
    use SellValidationRules;

    public function __construct(
        private readonly ValidateOfferExpirationAction $validateOfferExpiration,
        private readonly OfferIsFromFoodEstablishmentAction $offerIsFromFoodEstablishmentAction,
        private readonly makeSellAction $makeSellAction,
    ) {}
    public function buyOffers(Request $request)
    {
        $request->validate($this->sellsRule());
        try {
            $this->validateOfferExpiration->execute($request->get('offers'));
            $this->offerIsFromFoodEstablishmentAction->execute($request->get('offers'), $request->get('food_establishment_id'));
            $offers = $request->get('offers');
            $bought_by = Auth::id();
            $sold_by = FoodEstablishment::findOrFail($request->get('food_establishment_id'))->id;
            $result = $this->makeSellAction->execute($offers,$bought_by, $sold_by);
            $userCart = UserCart::with('offerCarts')->where('user_id', $bought_by)->first();
            foreach ($userCart->offerCarts as $offerCart) {
                $offerCart->delete();
            }
            return response()->json($result, 201);
        } catch (\Exception $exception) {
            return response()->json([
                $exception->getMessage(),
            ], 500);
        }
    }
    public function sellerSells(Request $request)
    {
        try {
            $foodEstablishment = FoodEstablishment::where('user_id', Auth::id())->firstOrFail();
            $sells = Sell::with(['sellDetails.offer'])
                ->where('sold_by', $foodEstablishment->id)
                ->orderBy('created_at','desc')->get();
            return response()->json($sells->toArray());
        }catch (\Exception $exception){
            return response()->json([
                'error' => $exception->getMessage(),
            ], 500);
        }
    }
}
