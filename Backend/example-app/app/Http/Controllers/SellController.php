<?php

namespace App\Http\Controllers;


use App\Actions\Offers\OfferIsFromFoodEstablishmentAction;
use App\Actions\Offers\ValidateOfferExpirationAction;
use App\Actions\Offers\ValidateOfferExpirationFromDTOAction;
use App\Actions\Offers\ValidateOfferIsActiveAction;
use App\Actions\Sell\makeSellAction;
use App\Actions\Sell\getCustomerSellsAction;
use App\Actions\Sell\SellValidationRules;
use App\Actions\Sell\VerifyPurchaseDataFreshnessAction;
use App\DTOs\PreparePurchaseDTO;
use App\Models\FoodEstablishment;
use App\Models\Sell;
use App\Models\User;
use App\Models\UserCart;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class SellController
{
    use SellValidationRules;

    public function __construct(
        private readonly ValidateOfferExpirationAction        $validateOfferExpiration,
        private readonly OfferIsFromFoodEstablishmentAction   $offerIsFromFoodEstablishmentAction,
        private readonly ValidateOfferExpirationFromDTOAction $validateOfferExpirationFromDTOAction,
        private readonly ValidateOfferIsActiveAction          $validateOfferIsActiveAction,
        private readonly getCustomerSellsAction               $getCustomerSellsAction,
        private readonly makeSellAction                       $makeSellAction,
        private readonly VerifyPurchaseDataFreshnessAction    $verifyPurchaseDataFreshnessAction,
        private readonly \App\Actions\Sell\ValidatePickupCodeAction $validatePickupCodeAction,
        private readonly \App\Actions\Sell\ValidateCustomerOwnershipAction $validateCustomerOwnershipAction,
    )
    {
    }

    public function buyOffers(Request $request)
    {
        try {
            $purchaseToken = $request->input('purchase_token');
            if (!$purchaseToken || !session()->has('purchase_' . $purchaseToken)) {
                return response()->json([
                    'error' => 'Token de compra inválido o expirado'
                ], 400);
            }
            $purchaseData = session()->get('purchase_' . $purchaseToken);
            if (now()->isAfter($purchaseData['expires_at'])) {
                session()->forget('purchase_' . $purchaseToken);
                return response()->json([
                    'error' => 'El tiempo para confirmar la compra ha expirado'
                ], 400);
            }

            $preparePurchaseDTO = PreparePurchaseDTO::clone($purchaseData['preparePurchaseDTO']);


            DB::transaction(function () use ($preparePurchaseDTO) {
                $this->validateOfferExpirationFromDTOAction->execute($preparePurchaseDTO->offers);
                $this->validateOfferIsActiveAction->execute($preparePurchaseDTO->offers);
                $this->offerIsFromFoodEstablishmentAction->execute(
                    $preparePurchaseDTO->offers,
                    $preparePurchaseDTO->food_establishment_id
                );
                $this->verifyPurchaseDataFreshnessAction->execute($preparePurchaseDTO);
                $this->makeSellAction->execute(
                    $preparePurchaseDTO,
                    Auth::id(),
                    $preparePurchaseDTO->food_establishment_id
                );
            });

            return response()->json([
                'message' => 'Compra realizada con éxito',
                'data' => $preparePurchaseDTO
            ], 200);

        } catch (Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage(),
                'line' => $exception->getLine()
            ], 400);
        }
    }


    /**
     * @throws Exception
     */
    public function prepareBuyOffers(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'food_establishment_id' => 'required|integer|exists:food_establishments,id',
                'offers' => 'required|array|min:1',
                'offers.*.id' => 'required|integer|exists:offers,id',
                'offers.*.quantity' => 'required|integer|min:1',
            ]);


            $preparePurchaseDTO = PreparePurchaseDTO::fromRequest($request);



            $this->validateOfferExpirationFromDTOAction->execute($preparePurchaseDTO->offers);
            $this->validateOfferIsActiveAction->execute($preparePurchaseDTO->offers);
            $this->offerIsFromFoodEstablishmentAction->execute(
                $preparePurchaseDTO->offers,
                $preparePurchaseDTO->food_establishment_id
            );

            $purchaseToken = md5(uniqid(Auth::id(), true));

            session()->put('purchase_' . $purchaseToken, [
                'preparePurchaseDTO' => $preparePurchaseDTO,
                'expires_at' => now()->addMinutes(5)
            ]);

            return response()->json([
                'message' => 'Purchase preparation completed successfully',
                'data' => [
                    'purchase_token' => $purchaseToken,
                    'offers' => $preparePurchaseDTO,
                    'total_offers' => count($preparePurchaseDTO->offers),
                    'food_establishment_id' => $preparePurchaseDTO->food_establishment_id,
                    'expires_at' => now()->addMinutes(5)->toDateTimeString(),
                ]
            ], 200);

        } catch (Exception $exception) {
            return response()->json([
                'error' => 'Failed to prepare purchase',
                'message' => $exception->getMessage()
            ], 500);
        }
    }


    public function sellerSells(Request $request)
    {
        try {
            $foodEstablishment = FoodEstablishment::where('user_id', Auth::id())->firstOrFail();
            $sells = Sell::with(['sellDetails.offer'])
                ->where('sold_by', $foodEstablishment->id)
                ->orderBy('created_at', 'desc')->get();
            return response()->json($sells->toArray());
        } catch (Exception $exception) {
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
        } catch (Exception $exception) {
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

    public function checkCustomerCode(Request $request)
    {
        $request->validate([
            'pickup_code' => 'required|string',
        ]);

        try {
            $pickupCode = $request->input('pickup_code');

            $sell = $this->validatePickupCodeAction->execute($pickupCode, Auth::id());

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

    public function getPurchaseCode(string $sellNumber)
    {
        try {
            $customerId = Auth::id();

            $sell = $this->validateCustomerOwnershipAction->execute($sellNumber, $customerId);

            return response()->json([
                'message' => 'Código de retiro obtenido exitosamente',
                'data' => [
                    'sell_id' => $sell->id,
                    'pickup_code' => $sell->pickup_code,
                    'establishment' => [
                        'id' => $sell->foodEstablishment->id,
                        'name' => $sell->foodEstablishment->name,
                        'address' => $sell->foodEstablishment->address,
                    ],
                    'created_at' => $sell->created_at,
                ]
            ], 200);

        } catch (Exception $exception) {
            $statusCode = $exception->getCode() ?: 500;
            return response()->json([
                'error' => $exception->getMessage()
            ], $statusCode);
        }
    }
}
