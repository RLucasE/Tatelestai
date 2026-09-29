<?php

namespace Tests\Unit;

use App\Actions\Sell\CancelPurchaseAction;
use App\Enums\OfferState;
use App\Enums\SellState;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\Sell;
use App\Models\SellDetail;
use App\Models\User;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CancelPurchaseActionTest extends TestCase
{
    use RefreshDatabase;

    protected CancelPurchaseAction $action;

    protected User $customer;

    protected User $seller;

    protected FoodEstablishment $establishment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(EstablishmentTypeSeeder::class);

        $this->customer = User::factory()->withRole(UserRole::CUSTOMER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $this->seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $establishmentType = EstablishmentType::first();

        $this->establishment = FoodEstablishment::factory()->create([
            'user_id' => $this->seller->id,
            'establishment_type_id' => $establishmentType->id,
            'name' => 'Panadería Artesanal Test',
        ]);

        $this->action = app(CancelPurchaseAction::class);
    }

    private function createPurchase(array $offerOverrides = [], array $sellOverrides = []): array
    {
        $offer = Offer::factory()->active()->create(array_merge([
            'food_establishment_id' => $this->establishment->id,
            'quantity' => 5,
            'price' => 1800,
            'pickup_start_datetime' => now()->addHours(3),
            'expiration_datetime' => now()->addHours(4),
        ], $offerOverrides));

        $sell = Sell::factory()->create(array_merge([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'state' => SellState::CONFIRMED->value,
            'is_picked_up' => false,
            'pickup_code' => 'TESTCANCEL01',
            'max_pickup_datetime' => $offer->expiration_datetime,
            'created_at' => now(),
        ], $sellOverrides));

        $detail = SellDetail::factory()->create([
            'sell_id' => $sell->id,
            'offer_id' => $offer->id,
            'offer_quantity' => 2,
            'pack_name' => $offer->title,
            'pack_description' => $offer->description,
            'pack_price' => $offer->price,
        ]);

        return [$sell, $offer, $detail];
    }

    #[Test]
    public function it_successfully_cancels_a_valid_pack_purchase_and_updates_sell_state(): void
    {
        // Compra con franja a 3 horas de distancia (>= 2 hs de anticipación)
        [$sell, $offer] = $this->createPurchase([
            'pickup_start_datetime' => now()->addHours(3),
            'expiration_datetime' => now()->addHours(4),
        ]);

        $result = $this->action->execute($sell, $this->customer);

        $this->assertTrue($result['success'] ?? false);
        $this->assertEquals(SellState::CANCELLED, $sell->fresh()->state);
    }

    #[Test]
    public function it_restores_the_stock_of_the_pack_offer_when_purchase_is_cancelled(): void
    {
        $initialStock = 4;
        $boughtQuantity = 2;

        [$sell, $offer] = $this->createPurchase([
            'quantity' => $initialStock,
            'pickup_start_datetime' => now()->addHours(3),
            'expiration_datetime' => now()->addHours(4),
        ]);

        $this->action->execute($sell, $this->customer);

        // El inventario debe restituirse de 4 a 6
        $this->assertEquals($initialStock + $boughtQuantity, $offer->fresh()->quantity);
    }

    #[Test]
    public function it_reactivates_exhausted_pack_offer_back_to_active_when_cancelled(): void
    {
        [$sell, $offer] = $this->createPurchase([
            'quantity' => 0,
            'state' => OfferState::PURCHASED->value,
            'pickup_start_datetime' => now()->addHours(3),
            'expiration_datetime' => now()->addHours(4),
        ]);

        $this->action->execute($sell, $this->customer);

        $refreshedOffer = $offer->fresh();
        $this->assertEquals(2, $refreshedOffer->quantity);
        $this->assertEquals(OfferState::ACTIVE->value, $refreshedOffer->state);
    }

    #[Test]
    public function it_throws_exception_when_attempting_to_cancel_an_already_picked_up_pack(): void
    {
        [$sell, $offer] = $this->createPurchase([
            'pickup_start_datetime' => now()->addHours(3),
            'expiration_datetime' => now()->addHours(4),
        ], [
            'is_picked_up' => true,
            'state' => SellState::PICKED_UP->value,
            'picked_up_at' => now(),
        ]);

        $this->expectException(DomainException::class);

        $this->action->execute($sell, $this->customer);
    }

    #[Test]
    public function it_throws_exception_when_cancelling_after_pickup_window_has_expired_no_show(): void
    {
        $expiredTime = now()->subMinutes(10);

        [$sell, $offer] = $this->createPurchase([
            'pickup_start_datetime' => now()->subHours(1),
            'expiration_datetime' => $expiredTime,
        ], [
            'max_pickup_datetime' => $expiredTime,
        ]);

        $this->expectException(DomainException::class);

        $this->action->execute($sell, $this->customer);
    }

    #[Test]
    public function it_throws_exception_when_pre_window_grace_period_of_15_minutes_has_expired(): void
    {
        $purchaseTime = now();

        [$sell, $offer] = $this->createPurchase([
            'pickup_start_datetime' => $purchaseTime->copy()->addMinutes(90), // < 2 hs
            'expiration_datetime' => $purchaseTime->copy()->addMinutes(120),
        ], [
            'created_at' => $purchaseTime,
        ]);

        // Simulamos que pasaron 20 minutos de la compra (> 15 min)
        $this->travelTo($purchaseTime->copy()->addMinutes(20));

        $this->expectException(DomainException::class);

        try {
            $this->action->execute($sell, $this->customer);
        } finally {
            $this->travelBack();
        }
    }
}
