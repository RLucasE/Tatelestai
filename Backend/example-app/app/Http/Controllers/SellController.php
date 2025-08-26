<?php

namespace App\Http\Controllers;


use App\Actions\Offers\OfferIsFromFoodEstablishmentAction;
use App\Actions\Offers\ValidateOfferExpirationAction;
use App\Actions\Sell\makeSellAction;
use App\Actions\Sell\getCustomerSellsAction;
use App\Actions\Sell\SellValidationRules;
use App\Models\FoodEstablishment;
use App\Models\Sell;
use App\Models\User;
use App\Models\UserCart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class SellController
{
    use SellValidationRules;

    public function __construct(
        private readonly ValidateOfferExpirationAction $validateOfferExpiration,
        private readonly OfferIsFromFoodEstablishmentAction $offerIsFromFoodEstablishmentAction,
        private readonly getCustomerSellsAction $getCustomerSellsAction,
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
    public function customerPurchases(Request $request)
    {
        try {
            $customerId = Auth::id();

            $customerSells = $this->getCustomerSellsAction->execute($customerId);

            return response()->json([
                'data' => $customerSells
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function adminSells(Request $request)
    {
        try {
            $sells = Sell::with(['sellDetails', 'foodEstablishment.user', 'customer'])
                ->orderBy('created_at', 'desc')
                ->get();
            $formattedSells = $sells->map(function ($sell) {
                return [
                    'id' => $sell->id,
                    'created_at' => $sell->created_at,
                    'customer_id' => $sell->bought_by,
                    'customer_name' => $sell->customer->name ?? 'N/A',
                    'establishment_name' => $sell->foodEstablishment->name ?? 'N/A',
                    'seller_id' => $sell->foodEstablishment->user_id ?? 'N/A',
                    'sold_by' => $sell->foodEstablishment->name ?? 'N/A',
                    'sell_details' => $sell->sellDetails->map(function ($detail) {
                        return [
                            'id' => $detail->id,
                            'product_name' => $detail->product_name ?? 'N/A',
                            'product_description' => $detail->product_description ?? 'N/A',
                            'product_price' => $detail->product_price ?? 0,
                            'offer_quantity' => $detail->offer_quantity ?? 0,
                            'product_quantity' => $detail->product_quantity ?? 0,
                        ];
                    })
                ];
            });

            return response()->json($formattedSells);
        } catch (\Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function adminSellDetail(string $id)
    {
        $user = User::findOrFail($id);
        if(!$user->hasRole('seller')) {
            return response()->json(['error' => 'User is not a seller'], 400);
        }
        $sells = Sell::with(['sellDetails', 'foodEstablishment.user', 'customer'])
            ->where('sold_by', $user->foodEstablishment->id)
            ->get();
        return response()->json([
          'sells' => $sells
        ],200);
    }
}
