<?php

namespace App\Actions\Seller;

use App\Enums\OfferState;
use App\Enums\SellState;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\Sell;
use App\Models\SellDetail;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GetSellerDashboardStatsAction
{
    /**
     * Obtiene y estructura las estadísticas y la operativa del día para el vendedor.
     */
    public function execute(FoodEstablishment $establishment, ?Carbon $targetDate = null): array
    {
        $date = $targetDate ? $targetDate->copy() : Carbon::today();
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        return [
            'date' => $date->format('Y-m-d'),
            'establishment' => [
                'id' => $establishment->id,
                'name' => $establishment->name,
            ],
            'summary' => $this->buildSummary($establishment, $startOfDay, $endOfDay),
            'live_stock' => $this->buildLiveStock($establishment, $startOfDay, $endOfDay),
            'today_pickups' => $this->buildTodayPickups($establishment, $startOfDay, $endOfDay),
        ];
    }

    /**
     * Construye las métricas cuantitativas clave de la jornada.
     */
    public function buildSummary(FoodEstablishment $establishment, Carbon $startOfDay, Carbon $endOfDay): array
    {
        $sellsQuery = Sell::query()
            ->where('sold_by', $establishment->id)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->where('state', '!=', SellState::CANCELLED->value);

        $ordersToday = (clone $sellsQuery)->count();

        // Obtener detalles de ventas para calcular unidades vendidas y recaudación
        $sellDetails = SellDetail::query()
            ->whereHas('sell', function ($query) use ($establishment, $startOfDay, $endOfDay) {
                $query->where('sold_by', $establishment->id)
                    ->whereBetween('created_at', [$startOfDay, $endOfDay])
                    ->where('state', '!=', SellState::CANCELLED->value);
            })
            ->get(['offer_quantity', 'pack_price']);

        $packsSoldToday = (int) $sellDetails->sum('offer_quantity');
        $earningsToday = (float) $sellDetails->sum(function ($detail) {
            return (int) $detail->offer_quantity * (float) $detail->pack_price;
        });

        // Retiros completados hoy
        $completedPickupsToday = Sell::query()
            ->where('sold_by', $establishment->id)
            ->where('is_picked_up', true)
            ->where(function ($query) use ($startOfDay, $endOfDay) {
                $query->whereBetween('picked_up_at', [$startOfDay, $endOfDay])
                    ->orWhere(function ($q) use ($startOfDay, $endOfDay) {
                        $q->whereNull('picked_up_at')
                            ->whereBetween('created_at', [$startOfDay, $endOfDay]);
                    });
            })
            ->count();

        // Retiros pendientes para la jornada
        $pendingPickupsToday = Sell::query()
            ->where('sold_by', $establishment->id)
            ->where('is_picked_up', false)
            ->where('state', '!=', SellState::CANCELLED->value)
            ->where(function ($query) use ($startOfDay, $endOfDay) {
                $query->whereBetween('created_at', [$startOfDay, $endOfDay])
                    ->orWhereBetween('max_pickup_datetime', [$startOfDay, $endOfDay]);
            })
            ->count();

        return [
            'orders_today' => $ordersToday,
            'packs_sold_today' => $packsSoldToday,
            'pending_pickups_today' => $pendingPickupsToday,
            'completed_pickups_today' => $completedPickupsToday,
            'earnings_today' => round($earningsToday, 2),
        ];
    }

    /**
     * Construye el estado de stock en vivo de las ofertas del local.
     */
    public function buildLiveStock(FoodEstablishment $establishment, Carbon $startOfDay, Carbon $endOfDay): array
    {
        // Unidades vendidas hoy por cada oferta (consulta agrupada optimizada)
        $salesByOffer = SellDetail::query()
            ->whereHas('sell', function ($q) use ($establishment, $startOfDay, $endOfDay) {
                $q->where('sold_by', $establishment->id)
                    ->where('state', '!=', SellState::CANCELLED->value)
                    ->whereBetween('created_at', [$startOfDay, $endOfDay]);
            })
            ->selectRaw('offer_id, SUM(offer_quantity) as total_sold')
            ->groupBy('offer_id')
            ->pluck('total_sold', 'offer_id');

        // Ofertas activas o que tuvieron actividad en la fecha
        $offers = Offer::where('food_establishment_id', $establishment->id)
            ->where(function ($query) use ($startOfDay, $endOfDay) {
                $query->where('state', OfferState::ACTIVE->value)
                    ->orWhereBetween('created_at', [$startOfDay, $endOfDay])
                    ->orWhereHas('offerCarts');
            })
            ->orderBy('expiration_datetime', 'asc')
            ->get();

        return $offers->map(function (Offer $offer) use ($salesByOffer) {
            $soldToday = (int) ($salesByOffer[$offer->id] ?? 0);
            $remaining = (int) $offer->quantity;
            $isSoldOut = $remaining <= 0 || $offer->state === OfferState::PURCHASED->value;

            return [
                'offer_id' => $offer->id,
                'title' => $offer->title,
                'price' => (float) $offer->price,
                'minimum_value' => (float) $offer->minimum_value,
                'stock_remaining' => $remaining,
                'stock_sold_today' => $soldToday,
                'is_sold_out' => $isSoldOut,
                'pickup_start_datetime' => $offer->pickup_start_datetime?->toIso8601String(),
                'expiration_datetime' => $offer->expiration_datetime?->toIso8601String(),
            ];
        })->values()->toArray();
    }

    /**
     * Construye el listado de pedidos programados para retiro en la jornada.
     */
    public function buildTodayPickups(FoodEstablishment $establishment, Carbon $startOfDay, Carbon $endOfDay): array
    {
        $sells = Sell::with(['customer:id,name', 'sellDetails'])
            ->where('sold_by', $establishment->id)
            ->where(function ($query) use ($startOfDay, $endOfDay) {
                $query->whereBetween('created_at', [$startOfDay, $endOfDay])
                    ->orWhereBetween('max_pickup_datetime', [$startOfDay, $endOfDay]);
            })
            ->orderByRaw('is_picked_up ASC, max_pickup_datetime ASC, created_at DESC')
            ->get();

        return $sells->map(function (Sell $sell) {
            $items = $sell->sellDetails->map(function (SellDetail $detail) {
                $quantity = (int) $detail->offer_quantity;
                $price = (float) $detail->pack_price;

                return [
                    'offer_id' => $detail->offer_id,
                    'pack_name' => $detail->pack_name,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => round($quantity * $price, 2),
                ];
            });

            $totalPrice = round((float) $items->sum('subtotal'), 2);

            // Determinar estado de la entrega soportando los múltiples estados de SellState
            $status = match (true) {
                $sell->state === SellState::CANCELLED => 'cancelled',
                $sell->is_picked_up || $sell->state === SellState::PICKED_UP => 'picked_up',
                $sell->max_pickup_datetime && now()->isAfter($sell->max_pickup_datetime) => 'expired',
                $sell->state === SellState::READY => 'ready',
                $sell->state === SellState::CONFIRMED => 'confirmed',
                default => 'pending',
            };

            return [
                'id' => $sell->id,
                'customer_name' => $sell->customer?->name ?? 'Cliente',
                'packs' => $items->values()->toArray(),
                'total_price' => $totalPrice,
                'status' => $status,
                'is_picked_up' => (bool) $sell->is_picked_up,
                'pickup_start_datetime' => $sell->getPickupStartDatetime()?->toIso8601String(),
                'max_pickup_datetime' => $sell->max_pickup_datetime?->toIso8601String(),
                'created_at' => $sell->created_at?->toIso8601String(),
            ];
        })->values()->toArray();
    }
}
