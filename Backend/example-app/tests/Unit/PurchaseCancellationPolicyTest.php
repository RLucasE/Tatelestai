<?php

namespace Tests\Unit;

use App\Enums\SellState;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Exceptions\CancellationNotAllowedException;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\Sell;
use App\Models\SellDetail;
use App\Models\User;
use App\Policies\PurchaseCancellationPolicy;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurchaseCancellationPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected PurchaseCancellationPolicy $policy;

    protected User $customer;

    protected User $otherCustomer;

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

        $this->otherCustomer = User::factory()->withRole(UserRole::CUSTOMER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $this->seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $establishmentType = EstablishmentType::first();

        $this->establishment = FoodEstablishment::factory()->create([
            'user_id' => $this->seller->id,
            'establishment_type_id' => $establishmentType->id,
        ]);

        $this->policy = new PurchaseCancellationPolicy;
    }

    private function createPurchase(array $offerOverrides = [], array $sellOverrides = []): Sell
    {
        $offer = Offer::factory()->active()->create(array_merge([
            'food_establishment_id' => $this->establishment->id,
            'pickup_start_datetime' => now()->addHours(3),
            'expiration_datetime' => now()->addHours(4),
        ], $offerOverrides));

        $sell = Sell::factory()->create(array_merge([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'state' => SellState::CONFIRMED->value,
            'is_picked_up' => false,
            'max_pickup_datetime' => $offer->expiration_datetime,
            'created_at' => now(),
        ], $sellOverrides));

        SellDetail::factory()->create([
            'sell_id' => $sell->id,
            'offer_id' => $offer->id,
            'offer_quantity' => 1,
            'pack_price' => $offer->price,
            'pack_name' => $offer->title,
            'pack_description' => $offer->description,
        ]);

        return $sell;
    }

    #[Test]
    public function it_approves_cancellation_when_more_than_2_hours_remain_before_pickup(): void
    {
        $sell = $this->createPurchase([
            'pickup_start_datetime' => now()->addHours(3),
            'expiration_datetime' => now()->addHours(4),
        ]);

        $this->assertTrue($this->policy->canCancel($sell, $this->customer));
    }

    #[Test]
    public function it_approves_cancellation_within_15_minutes_grace_period_for_pre_window_purchase(): void
    {
        $purchaseTime = now();
        $sell = $this->createPurchase([
            'pickup_start_datetime' => $purchaseTime->copy()->addMinutes(90), // < 2 hs
            'expiration_datetime' => $purchaseTime->copy()->addMinutes(120),
        ], [
            'created_at' => $purchaseTime,
        ]);

        // 10 minutos después de la compra (<= 15 min de gracia)
        $evalTime = $purchaseTime->copy()->addMinutes(10);
        $this->assertTrue($this->policy->canCancel($sell, $this->customer, $evalTime));
    }

    #[Test]
    public function it_rejects_cancellation_when_15_minutes_grace_period_expires_and_less_than_2_hours_remain(): void
    {
        $purchaseTime = now();
        $sell = $this->createPurchase([
            'pickup_start_datetime' => $purchaseTime->copy()->addMinutes(90),
            'expiration_datetime' => $purchaseTime->copy()->addMinutes(120),
        ], [
            'created_at' => $purchaseTime,
        ]);

        // 20 minutos después de la compra (> 15 min de gracia)
        $evalTime = $purchaseTime->copy()->addMinutes(20);

        $this->assertFalse($this->policy->canCancel($sell, $this->customer, $evalTime));

        $this->expectException(CancellationNotAllowedException::class);
        $this->policy->assertCanCancel($sell, $this->customer, $evalTime);
    }

    #[Test]
    public function it_approves_cancellation_within_5_minutes_grace_period_for_intra_window_purchase(): void
    {
        $baseTime = now();
        $purchaseTime = $baseTime->copy()->addMinutes(10); // Compró dentro de la franja

        $sell = $this->createPurchase([
            'pickup_start_datetime' => $baseTime,
            'expiration_datetime' => $baseTime->copy()->addMinutes(30),
        ], [
            'created_at' => $purchaseTime,
        ]);

        // 3 minutos después de la compra (<= 5 min de gracia exprés)
        $evalTime = $purchaseTime->copy()->addMinutes(3);
        $this->assertTrue($this->policy->canCancel($sell, $this->customer, $evalTime));
    }

    #[Test]
    public function it_rejects_cancellation_when_5_minutes_grace_period_expires_for_intra_window_purchase(): void
    {
        $baseTime = now();
        $purchaseTime = $baseTime->copy()->addMinutes(10);

        $sell = $this->createPurchase([
            'pickup_start_datetime' => $baseTime,
            'expiration_datetime' => $baseTime->copy()->addMinutes(30),
        ], [
            'created_at' => $purchaseTime,
        ]);

        // 7 minutos después de la compra (> 5 min de gracia exprés)
        $evalTime = $purchaseTime->copy()->addMinutes(7);

        $this->assertFalse($this->policy->canCancel($sell, $this->customer, $evalTime));

        $this->expectException(CancellationNotAllowedException::class);
        $this->policy->assertCanCancel($sell, $this->customer, $evalTime);
    }

    #[Test]
    public function it_rejects_cancellation_when_pack_is_already_picked_up(): void
    {
        $sell = $this->createPurchase([
            'pickup_start_datetime' => now()->addHours(3),
            'expiration_datetime' => now()->addHours(4),
        ], [
            'is_picked_up' => true,
            'state' => SellState::PICKED_UP->value,
            'picked_up_at' => now(),
        ]);

        $this->assertFalse($this->policy->canCancel($sell, $this->customer));

        $this->expectException(CancellationNotAllowedException::class);
        $this->policy->assertCanCancel($sell, $this->customer);
    }

    #[Test]
    public function it_rejects_cancellation_when_pickup_window_has_ended_no_show(): void
    {
        $expiredTime = now()->subMinutes(15);
        $sell = $this->createPurchase([
            'pickup_start_datetime' => now()->subHours(1),
            'expiration_datetime' => $expiredTime,
        ], [
            'max_pickup_datetime' => $expiredTime,
        ]);

        $this->assertFalse($this->policy->canCancel($sell, $this->customer));

        $this->expectException(CancellationNotAllowedException::class);
        $this->policy->assertCanCancel($sell, $this->customer);
    }

    #[Test]
    public function it_rejects_cancellation_when_user_does_not_own_the_purchase(): void
    {
        $sell = $this->createPurchase([
            'pickup_start_datetime' => now()->addHours(3),
            'expiration_datetime' => now()->addHours(4),
        ]);

        $this->assertFalse($this->policy->canCancel($sell, $this->otherCustomer));

        $this->expectException(CancellationNotAllowedException::class);
        $this->policy->assertCanCancel($sell, $this->otherCustomer);
    }
}
