<?php

use App\Actions\Seller\GetSellerDashboardStatsAction;
use App\Contracts\Payment\PaymentServiceInterface;
use App\Enums\ClaimReason;
use App\Enums\ClaimStatus;
use App\Enums\PaymentStatus;
use App\Enums\SellState;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\PurchaseClaim;
use App\Models\Sell;
use App\Models\SellDetail;
use App\Models\User;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(EstablishmentTypeSeeder::class);

    $this->customer = User::factory()->withRole(UserRole::CUSTOMER->value)->create([
        'state' => UserState::ACTIVE->value,
    ]);

    $this->seller = User::factory()->withRole(UserRole::SELLER->value)->create([
        'state' => UserState::ACTIVE->value,
    ]);

    $this->admin = User::factory()->withRole(UserRole::ADMIN->value)->create([
        'state' => UserState::ACTIVE->value,
    ]);

    $establishmentType = EstablishmentType::first();

    $this->establishment = FoodEstablishment::factory()->create([
        'user_id' => $this->seller->id,
        'establishment_type_id' => $establishmentType->id,
        'name' => 'Mercado Orgánico',
    ]);

    $this->offer = Offer::factory()->active()->create([
        'food_establishment_id' => $this->establishment->id,
        'quantity' => 10,
        'price' => 2000,
        'allergens' => ['gluten'],
    ]);
});

test('seller dashboard stats exclude full refunds subtract partial refunds and exclude expired pending pickups', function () {
    // Venta 1: Aprobada y retirada ($4000)
    $sellApproved = Sell::factory()->create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'state' => SellState::PICKED_UP->value,
        'payment_status' => PaymentStatus::APPROVED->value,
        'is_picked_up' => true,
        'picked_up_at' => now(),
        'created_at' => now(),
    ]);
    SellDetail::factory()->create([
        'sell_id' => $sellApproved->id,
        'offer_id' => $this->offer->id,
        'offer_quantity' => 2,
        'pack_price' => 2000,
    ]);

    // Venta 2: Reembolso total por reclamo ($2000 -> efectivo $0)
    $sellRefunded = Sell::factory()->create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'state' => SellState::PICKED_UP->value,
        'payment_status' => PaymentStatus::REFUNDED->value,
        'refunded_amount' => 2000,
        'refunded_at' => now(),
        'is_picked_up' => true,
        'picked_up_at' => now(),
        'created_at' => now(),
    ]);
    SellDetail::factory()->create([
        'sell_id' => $sellRefunded->id,
        'offer_id' => $this->offer->id,
        'offer_quantity' => 1,
        'pack_price' => 2000,
    ]);

    // Venta 3: Reembolso parcial ($3000 - $1000 = $2000 efectivo)
    $sellPartial = Sell::factory()->create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'state' => SellState::PICKED_UP->value,
        'payment_status' => PaymentStatus::PARTIALLY_REFUNDED->value,
        'refunded_amount' => 1000,
        'refunded_at' => now(),
        'is_picked_up' => true,
        'picked_up_at' => now(),
        'created_at' => now(),
    ]);
    SellDetail::factory()->create([
        'sell_id' => $sellPartial->id,
        'offer_id' => $this->offer->id,
        'offer_quantity' => 1,
        'pack_price' => 3000,
    ]);

    // Venta 4: Expirada (no debe contarse en pending_pickups_today)
    $sellExpired = Sell::factory()->create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'state' => SellState::EXPIRED->value,
        'payment_status' => PaymentStatus::APPROVED->value,
        'is_picked_up' => false,
        'max_pickup_datetime' => now()->subMinutes(10),
        'created_at' => now()->subHours(2),
    ]);
    SellDetail::factory()->create([
        'sell_id' => $sellExpired->id,
        'offer_id' => $this->offer->id,
        'offer_quantity' => 1,
        'pack_price' => 1000,
    ]);

    // Ingreso efectivo: 4000 (approved) + 2000 (partial) + 1000 (expired approved) = 7000
    // Comisión 15%: 1050; Neto: 5950
    $stats = app(GetSellerDashboardStatsAction::class)->execute($this->establishment);
    $summary = $stats['summary'];

    expect($summary['orders_today'])->toBe(3)
        ->and($summary['pending_pickups_today'])->toBe(0)
        ->and($summary['completed_pickups_today'])->toBe(2)
        ->and((float) $summary['gross_earnings_today'])->toBe(7000.0)
        ->and((float) $summary['commission_today'])->toBe(1050.0)
        ->and((float) $summary['net_earnings_today'])->toBe(5950.0);
});

test('seller sells endpoint returns refunded amount effective financials and claim summary without leaking customer identity', function () {
    $sell = Sell::factory()->create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'state' => SellState::PICKED_UP->value,
        'payment_status' => PaymentStatus::PARTIALLY_REFUNDED->value,
        'refunded_amount' => 1000,
        'refunded_at' => now(),
        'is_picked_up' => true,
        'picked_up_at' => now(),
        'pickup_code' => 'SECRET99',
    ]);
    SellDetail::factory()->create([
        'sell_id' => $sell->id,
        'offer_id' => $this->offer->id,
        'offer_quantity' => 2,
        'pack_price' => 2000,
    ]);

    PurchaseClaim::create([
        'sell_id' => $sell->id,
        'customer_id' => $this->customer->id,
        'food_establishment_id' => $this->establishment->id,
        'reason' => ClaimReason::MISSING_ITEMS->value,
        'description' => 'Faltó uno de los productos prometidos.',
        'status' => ClaimStatus::APPROVED->value,
        'was_picked_up_at_claim' => true,
        'refund_amount' => 1000,
    ]);

    $response = $this->actingAs($this->seller)->getJson('/api/sells');
    $response->assertStatus(200)
        ->assertJsonPath('0.gross_amount', 4000)
        ->assertJsonPath('0.refunded_amount', 1000)
        ->assertJsonPath('0.effective_gross_amount', 3000)
        ->assertJsonPath('0.commission_amount', 450)
        ->assertJsonPath('0.net_amount', 2550)
        ->assertJsonPath('0.claim.status', ClaimStatus::APPROVED->value)
        ->assertJsonPath('0.claim.reason', ClaimReason::MISSING_ITEMS->value);

    $item = $response->json('0');
    expect($item)->not->toHaveKey('pickup_code')
        ->and($item)->not->toHaveKey('bought_by');
});

test('admin sells and last sells reflect claim refunds accurately', function () {
    $sellApproved = Sell::factory()->create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'state' => SellState::PICKED_UP->value,
        'payment_status' => PaymentStatus::APPROVED->value,
        'is_picked_up' => true,
        'created_at' => now()->subHour(),
    ]);
    SellDetail::factory()->create([
        'sell_id' => $sellApproved->id,
        'offer_id' => $this->offer->id,
        'offer_quantity' => 1,
        'pack_price' => 2000,
    ]);

    $sellRefunded = Sell::factory()->create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'state' => SellState::PICKED_UP->value,
        'payment_status' => PaymentStatus::REFUNDED->value,
        'refunded_amount' => 2000,
        'is_picked_up' => true,
        'created_at' => now()->subHour(),
    ]);
    SellDetail::factory()->create([
        'sell_id' => $sellRefunded->id,
        'offer_id' => $this->offer->id,
        'offer_quantity' => 1,
        'pack_price' => 2000,
    ]);

    $this->actingAs($this->admin)
        ->getJson('/api/adm-sells')
        ->assertStatus(200)
        ->assertJsonFragment(['id' => $sellRefunded->id, 'payment_status' => PaymentStatus::REFUNDED->value, 'refunded_amount' => 2000]);

    $lastSells = $this->actingAs($this->admin)
        ->getJson('/api/adm/last-sells')
        ->assertStatus(200)
        ->json('data');

    $totalLastSellsCount = collect($lastSells)->sum('count');
    expect($totalLastSellsCount)->toBe(1);
});

test('customer history and purchases expose claim eligibility allergen snapshot and refund info', function () {
    $pickedSell = Sell::factory()->create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'state' => SellState::PICKED_UP->value,
        'payment_status' => PaymentStatus::APPROVED->value,
        'is_picked_up' => true,
        'picked_up_at' => now()->subDays(4),
    ]);
    SellDetail::factory()->create([
        'sell_id' => $pickedSell->id,
        'offer_id' => $this->offer->id,
        'offer_quantity' => 1,
        'pack_price' => 2000,
        'allergens' => ['gluten', 'soja'],
    ]);

    $this->actingAs($this->customer)
        ->getJson('/api/customer/pack-reservations/history')
        ->assertStatus(200)
        ->assertJsonPath('data.0.can_claim', true)
        ->assertJsonPath('data.0.offers.0.allergens.0', 'gluten');

    $expiredUnpicked = Sell::factory()->create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'state' => SellState::EXPIRED->value,
        'payment_status' => PaymentStatus::APPROVED->value,
        'is_picked_up' => false,
        'max_pickup_datetime' => now()->subDays(1),
        'created_at' => now()->subDays(1)->subHours(2),
    ]);
    SellDetail::factory()->create([
        'sell_id' => $expiredUnpicked->id,
        'offer_id' => $this->offer->id,
        'offer_quantity' => 1,
        'pack_price' => 2000,
        'allergens' => ['mani'],
    ]);

    $this->actingAs($this->customer)
        ->getJson('/api/customer/pack-reservations')
        ->assertStatus(200)
        ->assertJsonPath('data.0.can_claim', true)
        ->assertJsonPath('data.0.sell_details.0.allergens.0', 'mani');
});

test('mercadopago webhook preserves partially refunded and refunded statuses idempotently', function () {
    $partialSell = Sell::factory()->create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'state' => SellState::PICKED_UP->value,
        'payment_status' => PaymentStatus::PARTIALLY_REFUNDED->value,
        'payment_id' => 'mp_partial_999',
        'refunded_amount' => 1200,
        'refunded_at' => now()->subMinutes(5),
        'is_picked_up' => true,
    ]);
    SellDetail::factory()->create([
        'sell_id' => $partialSell->id,
        'offer_id' => $this->offer->id,
        'offer_quantity' => 2,
        'pack_price' => 2000,
    ]);

    $mockPayment = Mockery::mock(PaymentServiceInterface::class);
    $mockPayment->shouldReceive('getPaymentDetails')
        ->twice()
        ->with('mp_partial_999')
        ->andReturnValues([
            [
                'status' => 'refunded',
                'external_reference' => (string) $partialSell->id,
                'payment_method_id' => 'visa',
            ],
            [
                'status' => 'approved',
                'external_reference' => (string) $partialSell->id,
                'payment_method_id' => 'visa',
            ],
        ]);
    $this->app->instance(PaymentServiceInterface::class, $mockPayment);

    $payload = [
        'type' => 'payment',
        'data' => ['id' => 'mp_partial_999'],
    ];

    $this->postJson('/api/webhooks/mercadopago', $payload)->assertStatus(200);
    $this->postJson('/api/webhooks/mercadopago', $payload)->assertStatus(200);

    $partialSell->refresh();
    expect($partialSell->payment_status)->toBe(PaymentStatus::PARTIALLY_REFUNDED)
        ->and((float) $partialSell->refunded_amount)->toBe(1200.0);
});
