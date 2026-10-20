<?php

namespace App\Http\Controllers;

use App\Actions\Sell\ProcessExpiredSellsAction;
use App\Actions\Sell\SellerCancelSellAction;
use App\Actions\Sell\ValidateCompleteSellAction;
use App\Actions\Sell\ValidateMaxPickupDatetimeAction;
use App\Actions\Sell\ValidatePickupCodeAction;
use App\Actions\Sell\ValidatePickupCodeFromSellAction;
use App\Http\Requests\Sell\CheckCustomerCodeRequest;
use App\Http\Requests\Sell\CompleteSellRequest;
use App\Models\FoodEstablishment;
use App\Models\Sell;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SellerSellController extends Controller
{
    public function __construct(
        private readonly ValidateCompleteSellAction $validateCompleteSellAction,
        private readonly ValidatePickupCodeAction $validatePickupCodeAction,
        private readonly ValidatePickupCodeFromSellAction $validatePickupCodeFromSellAction,
        private readonly ValidateMaxPickupDatetimeAction $validateMaxPickupDatetimeAction,
        private readonly ProcessExpiredSellsAction $processExpiredSellsAction,
        private readonly SellerCancelSellAction $sellerCancelSellAction,
    ) {}

    public function sellerSells(Request $request)
    {
        try {
            $foodEstablishment = FoodEstablishment::where('user_id', Auth::id())->firstOrFail();

            // Sincronización lazy de pedidos vencidos antes de listar
            $this->processExpiredSellsAction->execute(soldBy: $foodEstablishment->id);

            $sells = Sell::with(['sellDetails.offer'])
                ->where('sold_by', $foodEstablishment->id)
                ->orderBy('created_at', 'desc')->get();

            $formattedSells = $sells->map(function ($sell) {
                return [
                    'id' => $sell->id,
                    'created_at' => $sell->created_at,
                    'updated_at' => $sell->updated_at,
                    'sold_by' => $sell->sold_by,
                    'state' => $sell->state?->value ?? $sell->state,
                    'payment_status' => $sell->payment_status?->value ?? 'approved',
                    'gross_amount' => $sell->getGrossAmount(),
                    'commission_percentage' => (float) ($sell->commission_percentage ?: config('services.mercadopago.commission_percentage', 15.0)),
                    'commission_amount' => $sell->getCommissionAmount(),
                    'net_amount' => $sell->getNetAmount(),
                    'cancelled_by' => $sell->cancelled_by,
                    'cancellation_reason' => $sell->cancellation_reason,
                    'sell_details' => $sell->sellDetails->map(function ($detail) {
                        return [
                            'id' => $detail->id,
                            'sell_id' => $detail->sell_id,
                            'offer_id' => $detail->offer_id,
                            'offer_quantity' => $detail->offer_quantity,
                            'pack_price' => $detail->pack_price,
                            'pack_name' => $detail->pack_name,
                            'pack_description' => $detail->pack_description,
                            'created_at' => $detail->created_at,
                            'updated_at' => $detail->updated_at,
                            'offer' => $detail->offer ? [
                                'id' => $detail->offer->id,
                                'title' => $detail->offer->title,
                                'description' => $detail->offer->description,
                                'price' => $detail->offer->price,
                                'quantity' => $detail->offer->quantity,
                                'state' => $detail->offer->state,
                                'expiration_datetime' => $detail->offer->expiration_datetime,
                            ] : null,
                        ];
                    }),
                ];
            });

            return response()->json($formattedSells->toArray());
        } catch (Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function checkCustomerCode(CheckCustomerCodeRequest $request)
    {
        try {
            $pickupCode = $request->validated('pickup_code');

            $sell = $this->validatePickupCodeAction->execute($pickupCode, Auth::id());
            $sell = $this->validateMaxPickupDatetimeAction->execute($sell);

            $offers = $sell->sellDetails->map(function ($detail) {
                return [
                    'offer_id' => $detail->offer_id,
                    'offer_title' => $detail->offer->title ?? 'N/A',
                    'offer_quantity' => $detail->offer_quantity,
                    'pack_name' => $detail->pack_name,
                    'pack_description' => $detail->pack_description,
                    'pack_price' => $detail->pack_price,
                ];
            });

            $totalPrice = $sell->sellDetails->reduce(function ($total, $detail) {
                return $total + ((int) $detail->offer_quantity * (int) $detail->pack_price);
            }, 0);

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
                    'total_price' => $totalPrice,
                    'created_at' => $sell->created_at,
                ],
            ], 200);

        } catch (Exception $exception) {
            $statusCode = $exception->getCode() ?: 400;

            return response()->json([
                'error' => $exception->getMessage(),
            ], $statusCode);
        }
    }

    public function completeSell(CompleteSellRequest $request, string $sellNumber)
    {
        try {
            $validatedSell = $this->validateCompleteSellAction->execute($sellNumber, Auth::id());
            $validatedSell = $this->validatePickupCodeFromSellAction->execute($validatedSell->id, $request->validated('pick_up_code'));
            $validatedSell = $this->validateMaxPickupDatetimeAction->execute($validatedSell);

            $validatedSell->update(
                [
                    'is_picked_up' => true,
                    'picked_up_at' => now(),
                    'state' => \App\Enums\SellState::PICKED_UP->value,
                ]
            );

            // Notificar al cliente que el pack fue retirado con éxito
            $validatedSell->loadMissing(['customer', 'foodEstablishment']);
            if ($validatedSell->customer) {
                $establishmentName = $validatedSell->foodEstablishment?->name ?? 'el comercio';
                $validatedSell->customer->notify(new \App\Notifications\RealtimeOrderNotification(
                    eventType: 'order_picked_up',
                    title: '¡Pack retirado con éxito!',
                    message: "Confirmamos la entrega de tu reserva #{$validatedSell->id} en {$establishmentName}. ¡Que lo disfrutes!",
                    sellId: $validatedSell->id,
                    establishmentId: $validatedSell->sold_by,
                    actionUrl: '/customer/history',
                    meta: [
                        'sell_id' => $validatedSell->id,
                        'establishment_name' => $establishmentName,
                    ]
                ));
            }

            return response()->json([
                'message' => 'Venta completada exitosamente',
                'data' => [
                    'sell_id' => $validatedSell->id,
                    'is_picked_up' => $validatedSell->is_picked_up,
                    'picked_up_at' => $validatedSell->picked_up_at,
                ],
            ], 200);
        } catch (Exception $exception) {
            $statusCode = $exception->getCode() ?: 500;

            return response()->json([
                'error' => $exception->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Permite al vendedor cancelar una venta que no puede entregar (por falta de merma) y reembolsar al cliente.
     */
    public function cancelSell(Request $request, string $sellNumber)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
            'restore_stock' => 'nullable|boolean',
        ]);

        try {
            $foodEstablishment = FoodEstablishment::where('user_id', Auth::id())->firstOrFail();
            $sell = Sell::where('id', $sellNumber)
                ->where('sold_by', $foodEstablishment->id)
                ->firstOrFail();

            $reason = $validated['reason'] ?? 'Comercio sin merma suficiente para armar el pack';
            $restoreStock = (bool) ($validated['restore_stock'] ?? false);

            $result = $this->sellerCancelSellAction->execute($sell, Auth::user(), $reason, $restoreStock);

            return response()->json($result, 200);
        } catch (\App\Exceptions\CancellationNotAllowedException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 422);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;

            return response()->json(['error' => $e->getMessage()], $statusCode);
        }
    }
}
