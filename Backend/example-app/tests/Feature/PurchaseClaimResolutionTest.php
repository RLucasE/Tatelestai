<?php

use App\Contracts\Payment\PaymentServiceInterface;
use App\Enums\ClaimReason;
use App\Enums\ClaimStatus;
use App\Enums\OfferState;
use App\Enums\PaymentStatus;
use App\Enums\SellState;
use App\Enums\TrustScoreCriterion;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\PurchaseClaim;
use App\Models\PurchaseClaimEvidence;
use App\Models\Sell;
use App\Models\SellDetail;
use App\Models\User;
use App\Notifications\RealtimeOrderNotification;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('private');
    Notification::fake();

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

    $this->admin = User::factory()->withRole(UserRole::ADMIN->value)->create([
        'state' => UserState::ACTIVE->value,
    ]);

    $establishmentType = EstablishmentType::first();

    $this->establishment = FoodEstablishment::factory()->create([
        'user_id' => $this->seller->id,
        'establishment_type_id' => $establishmentType->id,
        'name' => 'Deli Sostenible',
    ]);

    $this->createClaimWithSell = function (array $sellOverrides = [], array $claimOverrides = []): array {
        $offer = Offer::factory()->active()->create([
            'food_establishment_id' => $this->establishment->id,
            'quantity' => 3,
            'price' => 2000,
            'allergens' => ['lacteos'],
        ]);

        $sell = Sell::factory()->create(array_merge([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'state' => SellState::PICKED_UP->value,
            'payment_status' => PaymentStatus::APPROVED->value,
            'payment_id' => 'mp_pay_'.uniqid(),
            'is_picked_up' => true,
            'picked_up_at' => now()->subDays(2),
            'created_at' => now()->subDays(2),
        ], $sellOverrides));

        SellDetail::factory()->create([
            'sell_id' => $sell->id,
            'offer_id' => $offer->id,
            'offer_quantity' => 2,
            'pack_name' => $offer->title,
            'pack_description' => $offer->description,
            'pack_price' => 2000,
            'allergens' => ['lacteos'],
        ]);

        $claim = PurchaseClaim::create(array_merge([
            'sell_id' => $sell->id,
            'customer_id' => $this->customer->id,
            'food_establishment_id' => $this->establishment->id,
            'reason' => ClaimReason::UNDECLARED_ALLERGEN->value,
            'description' => 'El producto contenía queso aunque indicaba sin lácteos.',
            'status' => ClaimStatus::PENDING->value,
            'was_picked_up_at_claim' => (bool) $sell->is_picked_up,
        ], $claimOverrides));

        return [$claim, $sell, $offer];
    };
});

test('non admin user cannot access admin claim endpoints', function () {
    [$claim] = ($this->createClaimWithSell)();

    $this->actingAs($this->customer)->getJson('/api/adm/claims')->assertStatus(403);
    $this->actingAs($this->seller)->getJson("/api/adm/claims/{$claim->id}")->assertStatus(403);
    $this->actingAs($this->customer)->postJson("/api/adm/claims/{$claim->id}/resolve", [
        'decision' => 'approve',
    ])->assertStatus(403);
});

test('admin can view claim detail with allergen snapshot and customer risk summary and mark under review', function () {
    [$claim] = ($this->createClaimWithSell)();

    $detailResponse = $this->actingAs($this->admin)->getJson("/api/adm/claims/{$claim->id}");
    $detailResponse->assertStatus(200)
        ->assertJsonPath('data.id', $claim->id)
        ->assertJsonPath('data.sell.sell_details.0.allergens.0', 'lacteos')
        ->assertJsonStructure([
            'data' => [
                'customer_risk_summary' => [
                    'total_purchases',
                    'total_claims',
                    'risk_level',
                    'claim_rate_percentage',
                ],
            ],
        ]);

    $this->actingAs($this->admin)
        ->patchJson("/api/adm/claims/{$claim->id}/under-review")
        ->assertStatus(200)
        ->assertJsonPath('data.status', ClaimStatus::UNDER_REVIEW->value);
});

test('admin approves full refund without restoring stock or altering sell state', function () {
    [$claim, $sell, $offer] = ($this->createClaimWithSell)();

    $mockPayment = Mockery::mock(PaymentServiceInterface::class);
    $mockPayment->shouldReceive('refundPayment')
        ->once()
        ->with($sell->payment_id, null)
        ->andReturn([
            'id' => 'ref_mp_full_001',
            'status' => 'approved',
            'amount' => 4000.0,
        ]);
    $this->app->instance(PaymentServiceInterface::class, $mockPayment);

    $response = $this->actingAs($this->admin)
        ->postJson("/api/adm/claims/{$claim->id}/resolve", [
            'decision' => 'approve',
            'resolution_notes' => 'Verificado alérgeno no declarado en el envío.',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.status', ClaimStatus::APPROVED->value)
        ->assertJsonPath('data.refund_amount', 4000);

    $sell->refresh();
    $offer->refresh();
    $claim->refresh();

    expect($sell->payment_status)->toBe(PaymentStatus::REFUNDED)
        ->and((float) $sell->refunded_amount)->toBe(4000.0)
        ->and($sell->state)->toBe(SellState::PICKED_UP)
        ->and($offer->quantity)->toBe(3)
        ->and($claim->gateway_refund_id)->toBe('ref_mp_full_001');

    Notification::assertSentTo($this->customer, RealtimeOrderNotification::class, fn ($n) => $n->eventType === 'claim_approved');
    Notification::assertSentTo($this->seller, RealtimeOrderNotification::class, fn ($n) => $n->eventType === 'claim_refunded_seller');
});

test('admin approves partial refund and sets partially refunded status', function () {
    [$claim, $sell, $offer] = ($this->createClaimWithSell)();

    $mockPayment = Mockery::mock(PaymentServiceInterface::class);
    $mockPayment->shouldReceive('refundPayment')
        ->once()
        ->with($sell->payment_id, 1500.0)
        ->andReturn([
            'id' => 'ref_mp_partial_002',
            'status' => 'approved',
            'amount' => 1500.0,
        ]);
    $this->app->instance(PaymentServiceInterface::class, $mockPayment);

    $response = $this->actingAs($this->admin)
        ->postJson("/api/adm/claims/{$claim->id}/resolve", [
            'decision' => 'approve',
            'refund_amount' => 1500,
            'resolution_notes' => 'Reembolso parcial por faltante de una unidad.',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.status', ClaimStatus::APPROVED->value)
        ->assertJsonPath('data.refund_amount', 1500);

    $sell->refresh();
    $offer->refresh();

    expect($sell->payment_status)->toBe(PaymentStatus::PARTIALLY_REFUNDED)
        ->and((float) $sell->refunded_amount)->toBe(1500.0)
        ->and($sell->state)->toBe(SellState::PICKED_UP)
        ->and($offer->quantity)->toBe(3);
});

test('mock gateway returning zero amount on full refund preserves requested gross refund amount', function () {
    [$claim, $sell] = ($this->createClaimWithSell)();

    $mockPayment = Mockery::mock(PaymentServiceInterface::class);
    $mockPayment->shouldReceive('refundPayment')
        ->once()
        ->with($sell->payment_id, null)
        ->andReturn([
            'id' => 'ref_mock_123',
            'status' => 'approved',
            'amount' => 0.0,
        ]);
    $this->app->instance(PaymentServiceInterface::class, $mockPayment);

    $this->actingAs($this->admin)
        ->postJson("/api/adm/claims/{$claim->id}/resolve", [
            'decision' => 'approve',
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.refund_amount', 4000);

    $sell->refresh();
    expect((float) $sell->refunded_amount)->toBe(4000.0);
});

test('gateway failure aborts approval without mutating claim or sell state allowing safe retry', function () {
    [$claim, $sell] = ($this->createClaimWithSell)();

    $mockPayment = Mockery::mock(PaymentServiceInterface::class);
    $mockPayment->shouldReceive('refundPayment')
        ->once()
        ->andThrow(new RuntimeException('Timeout Mercado Pago'));
    $this->app->instance(PaymentServiceInterface::class, $mockPayment);

    $this->actingAs($this->admin)
        ->postJson("/api/adm/claims/{$claim->id}/resolve", [
            'decision' => 'approve',
        ])
        ->assertStatus(502);

    $claim->refresh();
    $sell->refresh();

    expect($claim->status)->toBe(ClaimStatus::PENDING)
        ->and($sell->payment_status)->toBe(PaymentStatus::APPROVED)
        ->and((float) $sell->refunded_amount)->toBe(0.0);
});

test('admin can deactivate offer and apply trust penalty on approval', function () {
    [$claim, $sell, $offer] = ($this->createClaimWithSell)();

    $mockPayment = Mockery::mock(PaymentServiceInterface::class);
    $mockPayment->shouldReceive('refundPayment')
        ->once()
        ->andReturn(['id' => 'ref_mod', 'status' => 'approved', 'amount' => 4000.0]);
    $this->app->instance(PaymentServiceInterface::class, $mockPayment);

    $this->actingAs($this->admin)
        ->postJson("/api/adm/claims/{$claim->id}/resolve", [
            'decision' => 'approve',
            'deactivate_offer' => true,
            'apply_trust_penalty' => true,
            'resolution_notes' => 'Alérgeno grave no declarado.',
        ])
        ->assertStatus(200);

    $offer->refresh();
    expect($offer->state)->toBe(OfferState::INACTIVE->value);

    $this->assertDatabaseHas('establishment_trust_logs', [
        'food_establishment_id' => $this->establishment->id,
        'source_type' => TrustScoreCriterion::OTHER->value,
        'points' => -15,
        'verified_by_user_id' => $this->admin->id,
    ]);
});

test('admin rejection requires resolution notes and optional abusive flag blocks future claims', function () {
    [$claim, $sell] = ($this->createClaimWithSell)();

    $this->actingAs($this->admin)
        ->postJson("/api/adm/claims/{$claim->id}/resolve", [
            'decision' => 'reject',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['resolution_notes']);

    $this->actingAs($this->admin)
        ->postJson("/api/adm/claims/{$claim->id}/resolve", [
            'decision' => 'reject',
            'resolution_notes' => 'Evidencia inconsistente con el pedido entregado.',
            'is_abusive' => true,
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.status', ClaimStatus::REJECTED->value)
        ->assertJsonPath('data.is_abusive', true);

    $sell->refresh();
    expect($sell->payment_status)->toBe(PaymentStatus::APPROVED);

    Notification::assertSentTo($this->customer, RealtimeOrderNotification::class, fn ($n) => $n->eventType === 'claim_rejected');

    // No puede volver a resolverse
    $this->actingAs($this->admin)
        ->postJson("/api/adm/claims/{$claim->id}/resolve", [
            'decision' => 'approve',
        ])
        ->assertStatus(409);

    // El comprador queda bloqueado por cooldown abusivo en otra compra
    [$nextSell] = (function () {
        $s = Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'state' => SellState::PICKED_UP->value,
            'payment_status' => PaymentStatus::APPROVED->value,
            'is_picked_up' => true,
            'picked_up_at' => now()->subDay(),
        ]);

        return [$s];
    })();

    $this->actingAs($this->customer)
        ->postJson("/api/customer/pack-reservations/{$nextSell->id}/claims", [
            'reason' => ClaimReason::SPOILED_FOOD->value,
            'description' => 'Intento de reclamo durante cooldown abusivo.',
        ])
        ->assertStatus(422);
});

test('private claim evidence endpoint enforces strict owner or admin authorization', function () {
    [$claim] = ($this->createClaimWithSell)();

    $file = UploadedFile::fake()->image('prueba.jpg', 200, 200);
    $path = $file->storeAs("claim-evidences/{$claim->id}", 'prueba.jpg', 'private');

    $evidence = PurchaseClaimEvidence::create([
        'purchase_claim_id' => $claim->id,
        'file_path' => $path,
        'original_name' => 'prueba.jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 1024,
    ]);

    $this->actingAs($this->customer)
        ->get("/api/claims/{$claim->id}/evidences/{$evidence->id}")
        ->assertStatus(200);

    $this->actingAs($this->admin)
        ->get("/api/claims/{$claim->id}/evidences/{$evidence->id}")
        ->assertStatus(200);

    $this->actingAs($this->otherCustomer)
        ->getJson("/api/claims/{$claim->id}/evidences/{$evidence->id}")
        ->assertStatus(403);

    $this->actingAs($this->seller)
        ->getJson("/api/claims/{$claim->id}/evidences/{$evidence->id}")
        ->assertStatus(403);
});

test('approved claim on unpicked pending sell blocks redemption, blocks double-cancellation, and prevents double-refund on expiration', function () {
    [$claim, $sell, $offer] = ($this->createClaimWithSell)([
        'state' => SellState::PENDING->value,
        'is_picked_up' => false,
        'picked_up_at' => null,
        'pickup_code' => '998877',
        'max_pickup_datetime' => now()->subMinutes(5),
    ]);

    $mockPayment = Mockery::mock(PaymentServiceInterface::class);
    $mockPayment->shouldReceive('refundPayment')
        ->once()
        ->with($sell->payment_id, 1500.0)
        ->andReturn([
            'id' => 'ref_mp_partial_unpicked',
            'status' => 'approved',
            'amount' => 1500.0,
        ]);
    $this->app->instance(PaymentServiceInterface::class, $mockPayment);

    $this->actingAs($this->admin)
        ->postJson("/api/adm/claims/{$claim->id}/resolve", [
            'decision' => 'approve',
            'refund_amount' => 1500,
            'deactivate_offer' => true,
            'resolution_notes' => 'Reembolso parcial por falla antes del retiro.',
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.status', ClaimStatus::APPROVED->value);

    $sell->refresh();
    $offer->refresh();
    expect($sell->payment_status)->toBe(PaymentStatus::PARTIALLY_REFUNDED)
        ->and((float) $sell->refunded_amount)->toBe(1500.0)
        ->and($sell->state)->toBe(SellState::PENDING)
        ->and($offer->state)->toBe(OfferState::INACTIVE->value);

    // No se puede validar código de retiro ni completar venta tras reembolso
    $this->actingAs($this->seller)
        ->postJson('/api/check-customer-code', ['pickup_code' => $sell->pickup_code])
        ->assertStatus(422);

    $this->actingAs($this->seller)
        ->postJson("/api/complete-sell/{$sell->id}", ['pick_up_code' => $sell->pickup_code])
        ->assertStatus(404);

    // No se puede cancelar desde comercio ni cliente sobre venta ya reembolsada
    $this->actingAs($this->seller)
        ->postJson("/api/seller/sells/{$sell->id}/cancel", ['reason' => 'Intento duplicado'])
        ->assertStatus(422);

    $this->actingAs($this->customer)
        ->postJson("/api/customer/pack-reservations/{$sell->id}/cancel")
        ->assertStatus(422);

    // Al expirar la franja horaria, ProcessExpiredSellsAction NO debe re-reembolsar ni pisar payment_status/refunded_amount
    app(\App\Actions\Sell\ProcessExpiredSellsAction::class)->execute();

    $sell->refresh();
    expect($sell->state)->toBe(SellState::EXPIRED)
        ->and($sell->payment_status)->toBe(PaymentStatus::PARTIALLY_REFUNDED)
        ->and((float) $sell->refunded_amount)->toBe(1500.0);
});

test('approving claim with deactivate_offer deduplicates repeated offer_id across sell details', function () {
    [$claim, $sell, $offer] = ($this->createClaimWithSell)();

    SellDetail::factory()->create([
        'sell_id' => $sell->id,
        'offer_id' => $offer->id,
        'offer_quantity' => 1,
        'pack_name' => $offer->title,
        'pack_description' => $offer->description,
        'pack_price' => 2000,
        'allergens' => ['lacteos'],
    ]);

    $mockPayment = Mockery::mock(PaymentServiceInterface::class);
    $mockPayment->shouldReceive('refundPayment')
        ->once()
        ->with($sell->payment_id, null)
        ->andReturn([
            'id' => 'ref_mp_dedup',
            'status' => 'approved',
            'amount' => 6000.0,
        ]);
    $this->app->instance(PaymentServiceInterface::class, $mockPayment);

    $this->actingAs($this->admin)
        ->postJson("/api/adm/claims/{$claim->id}/resolve", [
            'decision' => 'approve',
            'deactivate_offer' => true,
            'resolution_notes' => 'Baja de oferta con múltiples detalles.',
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.status', ClaimStatus::APPROVED->value);

    expect($offer->fresh()->state)->toBe(OfferState::INACTIVE->value);
});
