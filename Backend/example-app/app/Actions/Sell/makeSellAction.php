<?php

namespace App\Actions\Sell;

use App\Actions\Cart\NotifyCartStockDisruptionAction;
use App\Actions\Offers\GetOfferAction;
use App\DTOs\PrepareOfferDTO;
use App\DTOs\PreparePurchaseDTO;
use App\Enums\CartState;
use App\Enums\OfferState;
use App\Models\Offer;
use App\Models\OfferCart;
use App\Models\Sell;
use App\Models\SellDetail;
use App\Models\UserCart;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Pre-Requirements
 * -Offers belong to $sold_by
 * -Offers are not expired
 * -Offers belong to $sold_by's food_establishment
 * -Offers exist
 */
class makeSellAction
{
    public function __construct(
        private GetOfferAction $getOfferAction,
        private GeneratePickupCodeAction $generatePickupCodeAction,
        private CalculateMaxPickupDatetimeAction $calculateMaxPickupDatetimeAction,
        private ?NotifyCartStockDisruptionAction $notifyCartStockDisruptionAction = null
    ) {
        $this->notifyCartStockDisruptionAction ??= app(NotifyCartStockDisruptionAction::class);
    }

    /**
     * @throws \Throwable
     */
    public function execute(
        PreparePurchaseDTO $preparePurchaseDTO,
        int $bought_by,
        int $sold_by,
        string $paymentStatus = 'approved',
        ?string $paymentId = null,
        ?string $preferenceId = null,
        ?string $paymentMethod = null
    ): array {
        return DB::transaction(function () use ($preparePurchaseDTO, $bought_by, $sold_by, $paymentStatus, $paymentId, $preferenceId, $paymentMethod) {
            return $this->createSingleSell(
                $preparePurchaseDTO,
                $bought_by,
                $sold_by,
                $paymentStatus,
                $paymentId,
                $preferenceId,
                $paymentMethod
            );
        });
    }

    /**
     * Agrupa las ofertas del DTO por ventana horaria de retiro (pickup_start_datetime | expiration_datetime)
     * y genera un Sell independiente con su propio pickup_code por cada grupo de horario distinto.
     *
     * @throws \Throwable
     */
    public function executeMultiple(
        PreparePurchaseDTO $preparePurchaseDTO,
        int $bought_by,
        int $sold_by,
        string $paymentStatus = 'approved',
        ?string $paymentId = null,
        ?string $preferenceId = null,
        ?string $paymentMethod = null,
        bool $cleanCart = true
    ): array {
        return DB::transaction(function () use ($preparePurchaseDTO, $bought_by, $sold_by, $paymentStatus, $paymentId, $preferenceId, $paymentMethod, $cleanCart) {
            $groupedOffers = [];
            $purchasedOfferIds = [];

            foreach ($preparePurchaseDTO->offers as $offerDTO) {
                $offerId = $offerDTO instanceof PrepareOfferDTO ? $offerDTO->id : ($offerDTO['id'] ?? null);
                $purchasedOfferIds[] = (int) $offerId;

                $offerModel = $this->getOfferAction->execute((int) $offerId);
                $startKey = $offerModel && $offerModel->pickup_start_datetime
                    ? Carbon::parse($offerModel->pickup_start_datetime)->format('Y-m-d H:i')
                    : 'no-start';
                $endKey = $offerModel && $offerModel->expiration_datetime
                    ? Carbon::parse($offerModel->expiration_datetime)->format('Y-m-d H:i')
                    : 'no-end';

                $windowKey = "{$startKey}|{$endKey}";
                $groupedOffers[$windowKey][] = $offerDTO;
            }

            $createdSells = [];
            foreach ($groupedOffers as $windowOffers) {
                $groupDTO = new PreparePurchaseDTO(
                    food_establishment_id: $preparePurchaseDTO->food_establishment_id,
                    offers: $windowOffers
                );

                $singleResult = $this->createSingleSell(
                    $groupDTO,
                    $bought_by,
                    $sold_by,
                    $paymentStatus,
                    $paymentId,
                    $preferenceId,
                    $paymentMethod
                );

                $createdSells[] = $singleResult;
            }

            if ($cleanCart && ! empty($purchasedOfferIds)) {
                $this->cleanPurchasedOffersFromCart($bought_by, $sold_by, $purchasedOfferIds);
            }

            $sellIds = array_map(fn ($r) => $r['sell_id'], $createdSells);

            return [
                'sell_id' => $sellIds[0] ?? null,
                'sell_ids' => $sellIds,
                'sells_count' => count($sellIds),
                'message' => 'Venta realizada exitosamente',
                'offers_processed' => count($preparePurchaseDTO->offers),
            ];
        });
    }

    /**
     * Remueve selectivamente del carrito activo del establecimiento solo las ofertas compradas.
     * Si el carrito queda sin ofertas restantes, transiciona su estado a PURCHASED.
     *
     * @param  array<int>  $purchasedOfferIds
     */
    public function cleanPurchasedOffersFromCart(int $userId, int $establishmentId, array $purchasedOfferIds): void
    {
        $activeCart = UserCart::where('user_id', $userId)
            ->where('food_establishment_id', $establishmentId)
            ->where('state', CartState::ACTIVE->value)
            ->latest('id')
            ->first();

        if (! $activeCart) {
            return;
        }

        OfferCart::where('user_cart_id', $activeCart->id)
            ->whereIn('offer_id', $purchasedOfferIds)
            ->delete();

        $remainingItemsCount = OfferCart::where('user_cart_id', $activeCart->id)->count();

        if ($remainingItemsCount === 0) {
            $activeCart->update(['state' => CartState::PURCHASED->value]);
        }
    }

    /**
     * @throws \Throwable
     */
    private function createSingleSell(
        PreparePurchaseDTO $preparePurchaseDTO,
        int $bought_by,
        int $sold_by,
        string $paymentStatus = 'approved',
        ?string $paymentId = null,
        ?string $preferenceId = null,
        ?string $paymentMethod = null
    ): array {
        $pickupCode = $this->generatePickupCodeAction->execute($bought_by, $sold_by, $preparePurchaseDTO);

        $maxPickupDatetime = $this->calculateMaxPickupDatetimeAction->execute($preparePurchaseDTO->offers);

        $grossAmount = 0.0;
        foreach ($preparePurchaseDTO->offers as $offerDTO) {
            $grossAmount += (int) $offerDTO->quantity * (float) $offerDTO->price;
        }
        $grossAmount = round($grossAmount, 2);

        $commissionPercentage = (float) config('services.mercadopago.commission_percentage', 15.0);
        $commissionAmount = round($grossAmount * ($commissionPercentage / 100), 2);
        $netAmount = round($grossAmount - $commissionAmount, 2);

        $sell = Sell::create([
            'bought_by' => $bought_by,
            'sold_by' => $sold_by,
            'pickup_code' => $pickupCode,
            'max_pickup_datetime' => $maxPickupDatetime,
            'payment_status' => $paymentStatus,
            'payment_id' => $paymentId,
            'preference_id' => $preferenceId,
            'payment_method' => $paymentMethod,
            'gross_amount' => $grossAmount,
            'commission_percentage' => $commissionPercentage,
            'commission_amount' => $commissionAmount,
            'net_amount' => $netAmount,
        ]);

        foreach ($preparePurchaseDTO->offers as $offerDTO) {
            $updatedRows = Offer::query()
                ->where('id', $offerDTO->id)
                ->where('state', OfferState::ACTIVE->value)
                ->where('quantity', '>=', $offerDTO->quantity)
                ->decrement('quantity', $offerDTO->quantity);

            if ($updatedRows === 0) {
                $currentOffer = $this->getOfferAction->execute($offerDTO->id);
                $title = $currentOffer ? $currentOffer->title : "ID {$offerDTO->id}";
                throw new \Exception("No hay suficiente stock disponible para la oferta: {$title}");
            }

            $offer = $this->getOfferAction->execute($offerDTO->id);

            SellDetail::create([
                'sell_id' => $sell->id,
                'offer_id' => $offerDTO->id,
                'offer_quantity' => $offerDTO->quantity,
                'pack_name' => $offerDTO->title,
                'pack_description' => $offerDTO->description,
                'pack_price' => $offerDTO->price,
                'allergens' => $offerDTO->allergens ?? (is_array($offer->allergens) ? array_values($offer->allergens) : null),
                'estimated_weight_kg' => $offerDTO->estimated_weight_kg ?? $offer->estimated_weight_kg,
                'minimum_value' => $offerDTO->minimum_value ?? $offer->minimum_value,
            ]);

            if ($offer->quantity <= 0) {
                $offer->update(['state' => OfferState::PURCHASED->value]);

                // Notificar al vendedor dueño del local que la oferta se agotó
                $sellerUser = $offer->foodEstablishment?->user;
                if ($sellerUser) {
                    $sellerUser->notify(new \App\Notifications\RealtimeOrderNotification(
                        eventType: 'offer_sold_out',
                        title: '¡Oferta agotada!',
                        message: "Se agotaron todas las unidades disponibles de «{$offer->title}».",
                        offerId: $offer->id,
                        establishmentId: $offer->food_establishment_id,
                        actionUrl: '/seller/my-offers',
                        meta: ['offer_title' => $offer->title]
                    ));
                }
            }

            // Notificar en forma privada a clientes con carritos activos afectados
            $this->notifyCartStockDisruptionAction->execute($offer, $bought_by);
        }

        return [
            'sell_id' => $sell->id,
            'message' => 'Venta realizada exitosamente',
            'offers_processed' => count($preparePurchaseDTO->offers),
        ];
    }
}
