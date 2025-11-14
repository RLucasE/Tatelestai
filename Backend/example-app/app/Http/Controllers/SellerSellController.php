<?php

namespace App\Http\Controllers;

use App\Actions\Sell\ValidatePickupCodeAction;
use App\Actions\Sell\ValidateCompleteSellAction;
use App\Actions\Sell\ValidatePickupCodeFromSellAction;
use App\Actions\Sell\ValidateMaxPickupDatetimeAction;
use App\Models\FoodEstablishment;
use App\Models\Sell;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SellerSellController extends Controller
{
    public function __construct(
        private readonly ValidateCompleteSellAction $validateCompleteSellAction,
        private readonly ValidatePickupCodeAction   $validatePickupCodeAction,
        private readonly ValidatePickupCodeFromSellAction $validatePickupCodeFromSellAction,
        private readonly ValidateMaxPickupDatetimeAction $validateMaxPickupDatetimeAction,
    )
    {
    }

    public function sellerSells(Request $request)
    {
        try {
            $foodEstablishment = FoodEstablishment::where('user_id', Auth::id())->firstOrFail();
            $sells = Sell::with(['sellDetails.offer'])
                ->where('sold_by', $foodEstablishment->id)
                ->orderBy('created_at', 'desc')->get();

            $formattedSells = $sells->map(function ($sell) {
                return [
                    'id' => $sell->id,
                    'created_at' => $sell->created_at,
                    'updated_at' => $sell->updated_at,
                    'sold_by' => $sell->sold_by,
                    'sell_details' => $sell->sellDetails->map(function ($detail) {
                        return [
                            'id' => $detail->id,
                            'sell_id' => $detail->sell_id,
                            'offer_id' => $detail->offer_id,
                            'offer_quantity' => $detail->offer_quantity,
                            'product_quantity' => $detail->product_quantity,
                            'product_price' => $detail->product_price,
                            'product_name' => $detail->product_name,
                            'product_description' => $detail->product_description,
                            'created_at' => $detail->created_at,
                            'updated_at' => $detail->updated_at,
                            'offer' => $detail->offer ? [
                                'id' => $detail->offer->id,
                                'title' => $detail->offer->title,
                                'description' => $detail->offer->description,
                                'price' => $detail->offer->price,
                                'quantity' => $detail->offer->quantity,
                                'is_active' => $detail->offer->is_active,
                                'expiration_datetime' => $detail->offer->expiration_datetime,
                            ] : null
                        ];
                    })
                ];
            });

            return response()->json($formattedSells->toArray());
        } catch (Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function checkCustomerCode(Request $request)
    {
        $request->validate([
            'pickup_code' => 'required|string',
        ]);

        try {
            $pickupCode = $request->input('pickup_code');

            $sell = $this->validatePickupCodeAction->execute($pickupCode, Auth::id());
            $sell = $this->validateMaxPickupDatetimeAction->execute($sell);

            $offers = $sell->sellDetails->map(function ($detail) {
                return [
                    'offer_id' => $detail->offer_id,
                    'offer_title' => $detail->offer->title ?? 'N/A',
                    'offer_quantity' => $detail->offer_quantity,
                    'product_name' => $detail->product_name,
                    'product_description' => $detail->product_description,
                    'product_quantity' => $detail->product_quantity,
                    'product_price' => $detail->product_price,
                ];
            });

            return response()->json([
                'message' => 'Código válido',
                'data' => [
                    'sell_id' => $sell->id,
                    'pickup_code' => $sell->pickup_code,
                    'customer' => [
                        'id' => $sell->customer->id,
                        'name' => $sell->customer->name,
                        'email' => $sell->customer->email,
                    ],
                    'offers' => $offers,
                    'created_at' => $sell->created_at,
                ]
            ], 200);

        } catch (Exception $exception) {
            $statusCode = $exception->getCode() ?: 400;
            return response()->json([
                'error' => $exception->getMessage()
            ], $statusCode);
        }
    }

    public function completeSell(Request $request,string $sellNumber)
    {
        $validatedRequest = $request->validate([
            'pick_up_code' => 'required|string',
        ]);
        try {
            $validatedSell = $this->validateCompleteSellAction->execute($sellNumber, Auth::id());
            $validatedSell = $this->validatePickupCodeFromSellAction->execute($validatedSell->id, $validatedRequest['pick_up_code']);
            $validatedSell = $this->validateMaxPickupDatetimeAction->execute($validatedSell);

            $validatedSell->update(
                [
                    'is_picked_up' => true,
                    'picked_up_at' => now(),
                ]
            );
            return response()->json([
                'message' => 'Venta completada exitosamente',
                'data' => [
                    'sell_id' => $validatedSell->id,
                    'is_picked_up' => $validatedSell->is_picked_up,
                    'picked_up_at' => $validatedSell->picked_up_at,
                ]
            ], 200);
        } catch (Exception $exception) {
            $statusCode = $exception->getCode() ?: 500;
            return response()->json([
                'error' => $exception->getMessage(),
            ], $statusCode);
        }
    }
}
