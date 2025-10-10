<?php

namespace App\Http\Controllers;

use App\Models\Sell;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;

class SellController extends Controller
{
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
        } catch (Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function adminSellDetail(string $id)
    {
        $user = User::findOrFail($id);
        if (!$user->hasRole('seller')) {
            return response()->json(['error' => 'User is not a seller'], 400);
        }
        $sells = Sell::with(['sellDetails', 'foodEstablishment.user', 'customer'])
            ->where('sold_by', $user->foodEstablishment->id)
            ->get();
        return response()->json([
            'sells' => $sells
        ], 200);
    }

    public function adminCustomerSells(string $id)
    {
        $user = User::findOrFail($id);

        if (!$user->hasRole('customer')) {
            return response()->json(['error' => 'User is not a customer'], 400);
        }

        $purchases = Sell::with(['sellDetails', 'foodEstablishment.user', 'customer'])
            ->where('bought_by', $user->id)
            ->get();

        return response()->json([
            'purchases' => $purchases
        ], 200);
    }
}
