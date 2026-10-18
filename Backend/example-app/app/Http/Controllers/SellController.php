<?php

namespace App\Http\Controllers;

use App\Actions\Sell\ProcessExpiredSellsAction;
use App\Models\Sell;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class SellController extends Controller
{
    public function __construct(
        private readonly ProcessExpiredSellsAction $processExpiredSellsAction,
    ) {}

    public function adminSells(Request $request)
    {
        try {
            // Sincronización lazy global de compras vencidas
            $this->processExpiredSellsAction->execute();

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
                            'pack_name' => $detail->pack_name ?? 'N/A',
                            'pack_description' => $detail->pack_description ?? 'N/A',
                            'pack_price' => $detail->pack_price ?? 0,
                            'offer_quantity' => $detail->offer_quantity ?? 0,
                        ];
                    }),
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
        if (! $user->hasRole('seller')) {
            return response()->json(['error' => 'User is not a seller'], 400);
        }

        if ($user->foodEstablishment) {
            $this->processExpiredSellsAction->execute(soldBy: $user->foodEstablishment->id);
        }

        $sells = Sell::with(['sellDetails', 'foodEstablishment.user', 'customer'])
            ->where('sold_by', $user->foodEstablishment->id)
            ->get();

        return response()->json([
            'sells' => $sells,
        ], 200);
    }

    public function adminCustomerSells(string $id)
    {
        $user = User::findOrFail($id);

        if (! $user->hasRole('customer')) {
            return response()->json(['error' => 'User is not a customer'], 400);
        }

        $this->processExpiredSellsAction->execute(boughtBy: $user->id);

        $purchases = Sell::with(['sellDetails', 'foodEstablishment.user', 'customer'])
            ->where('bought_by', $user->id)
            ->get();

        return response()->json([
            'purchases' => $purchases,
        ], 200);
    }

    public function lastSells()
    {
        $currentTime = now();
        $startTime = $currentTime->copy()->subDay();

        $sells = Sell::whereBetween('created_at', [$startTime, $currentTime])
            ->where('is_picked_up', true)
            ->get();

        $intervals = [];
        for ($i = 0; $i < 12; $i++) {
            $intervalStart = $startTime->copy()->addHours($i * 2);
            $intervalEnd = $intervalStart->copy()->addHours(2);

            $sellsInInterval = $sells->filter(function ($sell) use ($intervalStart, $intervalEnd) {
                $sellTime = Carbon::parse($sell->created_at);

                return $sellTime->between($intervalStart, $intervalEnd, false);
            })->count();

            $intervals[] = [
                'from' => $intervalStart->format('H:i'),
                'to' => $intervalEnd->format('H:i'),
                'count' => $sellsInInterval,
            ];
        }

        return response()->json([
            'message' => 'Ventas de las últimas 24 horas obtenidas exitosamente',
            'data' => $intervals,
        ], 200);
    }
}
