<?php

namespace App\Http\Controllers;

use App\Actions\Offers\GetUserEstablishmentAction;
use App\Actions\Offers\OfferIsFromFoodEstablishmentAction;
use App\Actions\Offers\ValidateOfferExpirationAction;
use App\Actions\Sell\makeSellAction;
use App\Actions\Sell\SellValidationRules;
use App\Models\FoodEstablishment;
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
            $sold_by = FoodEstablishment::findOrFail($request->get('food_establishment_id'))->user_id;
            $result = $this->makeSellAction->execute($offers,$bought_by, $sold_by);
            return response()->json($result, 201);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 500);
        }
    }
}
