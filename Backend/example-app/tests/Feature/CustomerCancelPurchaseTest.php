<?php

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

beforeEach(function () {
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
        'name' => 'Panadería Central',
    ]);
});

/**
 * Helper para crear una venta con ofertas y detalles configurando franjas horarias específicas.
 */
if (! function_exists('createTestPurchase')) {
    function createTestPurchase(User $customer, FoodEstablishment $establishment, array $offerOverrides = [], array $sellOverrides = []): array
    {
        $offer = Offer::factory()->active()->create(array_merge([
            'food_establishment_id' => $establishment->id,
            'quantity' => 5,
            'price' => 2000,
            'pickup_start_datetime' => now()->addHours(3),
            'expiration_datetime' => now()->addHours(4),
        ], $offerOverrides));

        $sell = Sell::factory()->create(array_merge([
            'bought_by' => $customer->id,
            'sold_by' => $establishment->id,
            'state' => SellState::CONFIRMED->value,
            'is_picked_up' => false,
            'pickup_code' => 'TESTCODE123',
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
}

test('customer can cancel purchase with more than 2 hours in advance to pickup start', function () {
    // Franja inicia en 3 horas (>= 2 hs de anticipación)
    [$sell, $offer] = createTestPurchase($this->customer, $this->establishment, [
        'pickup_start_datetime' => now()->addHours(3),
        'expiration_datetime' => now()->addHours(4),
    ]);

    $response = $this->actingAs($this->customer)
        ->postJson("/api/customer/pack-reservations/{$sell->id}/cancel");

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Compra cancelada y reembolso emitido exitosamente',
        ]);

    expect($sell->fresh()->state)->toBe(SellState::CANCELLED);
});

test('customer can cancel purchase within 15 minutes grace period for purchase made before pickup start', function () {
    // Franja inicia en 90 minutos (< 2 hs), pero el cliente compró hace 10 minutos (<= 15 min de gracia)
    $purchaseTime = now();
    [$sell, $offer] = createTestPurchase($this->customer, $this->establishment, [
        'pickup_start_datetime' => $purchaseTime->copy()->addMinutes(90),
        'expiration_datetime' => $purchaseTime->copy()->addMinutes(120),
    ], [
        'created_at' => $purchaseTime,
    ]);

    // Simulamos que pasaron 10 minutos de la compra
    $this->travelTo($purchaseTime->copy()->addMinutes(10));

    $response = $this->actingAs($this->customer)
        ->postJson("/api/customer/pack-reservations/{$sell->id}/cancel");

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Compra cancelada y reembolso emitido exitosamente',
        ]);

    expect($sell->fresh()->state)->toBe(SellState::CANCELLED);

    $this->travelBack();
});

test('customer cannot cancel purchase after 15 minutes grace period when less than 2 hours to pickup start', function () {
    // Franja inicia en 90 minutos (< 2 hs) y pasaron 20 minutos de la compra (> 15 min de gracia)
    $purchaseTime = now();
    [$sell, $offer] = createTestPurchase($this->customer, $this->establishment, [
        'pickup_start_datetime' => $purchaseTime->copy()->addMinutes(90),
        'expiration_datetime' => $purchaseTime->copy()->addMinutes(120),
    ], [
        'created_at' => $purchaseTime,
    ]);

    // Simulamos que pasaron 20 minutos de la compra
    $this->travelTo($purchaseTime->copy()->addMinutes(20));

    $response = $this->actingAs($this->customer)
        ->postJson("/api/customer/pack-reservations/{$sell->id}/cancel");

    $response->assertStatus(422)
        ->assertJsonStructure(['error']);

    expect($sell->fresh()->state)->not->toBe(SellState::CANCELLED);

    $this->travelBack();
});

test('customer can cancel purchase within 5 minutes express grace period for purchase made during pickup window', function () {
    // Compra realizada dentro de la franja (la franja inició hace 10 minutos y termina en 20 minutos)
    $baseTime = now();
    $purchaseTime = $baseTime->copy()->addMinutes(10);

    [$sell, $offer] = createTestPurchase($this->customer, $this->establishment, [
        'pickup_start_datetime' => $baseTime,
        'expiration_datetime' => $baseTime->copy()->addMinutes(30),
    ], [
        'created_at' => $purchaseTime,
    ]);

    // Simulamos que pasaron 3 minutos de la compra dentro de la franja (<= 5 min de gracia exprés)
    $this->travelTo($purchaseTime->copy()->addMinutes(3));

    $response = $this->actingAs($this->customer)
        ->postJson("/api/customer/pack-reservations/{$sell->id}/cancel");

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Compra cancelada y reembolso emitido exitosamente',
        ]);

    expect($sell->fresh()->state)->toBe(SellState::CANCELLED);

    $this->travelBack();
});

test('customer cannot cancel purchase after 5 minutes express grace period for purchase made during pickup window', function () {
    // Compra realizada dentro de la franja y pasaron 7 minutos (> 5 min de gracia exprés)
    $baseTime = now();
    $purchaseTime = $baseTime->copy()->addMinutes(10);

    [$sell, $offer] = createTestPurchase($this->customer, $this->establishment, [
        'pickup_start_datetime' => $baseTime,
        'expiration_datetime' => $baseTime->copy()->addMinutes(30),
    ], [
        'created_at' => $purchaseTime,
    ]);

    // Simulamos que pasaron 7 minutos de la compra dentro de la franja
    $this->travelTo($purchaseTime->copy()->addMinutes(7));

    $response = $this->actingAs($this->customer)
        ->postJson("/api/customer/pack-reservations/{$sell->id}/cancel");

    $response->assertStatus(422)
        ->assertJsonStructure(['error']);

    expect($sell->fresh()->state)->not->toBe(SellState::CANCELLED);

    $this->travelBack();
});

test('customer cannot cancel purchase if it has already been picked up at the store', function () {
    [$sell, $offer] = createTestPurchase($this->customer, $this->establishment, [
        'pickup_start_datetime' => now()->addHours(3),
        'expiration_datetime' => now()->addHours(4),
    ], [
        'is_picked_up' => true,
        'state' => SellState::PICKED_UP->value,
        'picked_up_at' => now(),
    ]);

    $response = $this->actingAs($this->customer)
        ->postJson("/api/customer/pack-reservations/{$sell->id}/cancel");

    $response->assertStatus(422)
        ->assertJsonStructure(['error']);

    expect($sell->fresh()->state)->toBe(SellState::PICKED_UP);
});

test('customer cannot cancel purchase if pickup window has already expired (No-Show)', function () {
    // La franja finalizó hace 15 minutos
    $expiredTime = now()->subMinutes(15);
    [$sell, $offer] = createTestPurchase($this->customer, $this->establishment, [
        'pickup_start_datetime' => now()->subHours(1),
        'expiration_datetime' => $expiredTime,
    ], [
        'max_pickup_datetime' => $expiredTime,
    ]);

    $response = $this->actingAs($this->customer)
        ->postJson("/api/customer/pack-reservations/{$sell->id}/cancel");

    $response->assertStatus(422)
        ->assertJsonStructure(['error']);

    expect($sell->fresh()->state)->not->toBe(SellState::CANCELLED);
});

test('cancelling a purchase restores the stock of each offer in the order', function () {
    $initialStock = 5;
    $boughtQuantity = 2;

    [$sell, $offer] = createTestPurchase($this->customer, $this->establishment, [
        'quantity' => $initialStock,
        'pickup_start_datetime' => now()->addHours(3),
        'expiration_datetime' => now()->addHours(4),
    ]);

    $response = $this->actingAs($this->customer)
        ->postJson("/api/customer/pack-reservations/{$sell->id}/cancel");

    $response->assertStatus(200);

    // El stock debe restituirse: 5 + 2 = 7
    expect($offer->fresh()->quantity)->toBe($initialStock + $boughtQuantity);
});

test('cancelling a purchase reactivates offer from purchased back to active if it was exhausted', function () {
    [$sell, $offer] = createTestPurchase($this->customer, $this->establishment, [
        'quantity' => 0,
        'state' => OfferState::PURCHASED->value,
        'pickup_start_datetime' => now()->addHours(3),
        'expiration_datetime' => now()->addHours(4),
    ]);

    $response = $this->actingAs($this->customer)
        ->postJson("/api/customer/pack-reservations/{$sell->id}/cancel");

    $response->assertStatus(200);

    $refreshedOffer = $offer->fresh();
    expect($refreshedOffer->quantity)->toBe(2)
        ->and($refreshedOffer->state)->toBe(OfferState::ACTIVE->value);
});

test('customer cannot cancel another customer purchase', function () {
    [$sell] = createTestPurchase($this->customer, $this->establishment, [
        'pickup_start_datetime' => now()->addHours(3),
        'expiration_datetime' => now()->addHours(4),
    ]);

    // El otro cliente intenta cancelar
    $response = $this->actingAs($this->otherCustomer)
        ->postJson("/api/customer/pack-reservations/{$sell->id}/cancel");

    $response->assertStatus(403);

    expect($sell->fresh()->state)->not->toBe(SellState::CANCELLED);
});

test('unauthenticated user cannot cancel any purchase', function () {
    [$sell] = createTestPurchase($this->customer, $this->establishment, [
        'pickup_start_datetime' => now()->addHours(3),
        'expiration_datetime' => now()->addHours(4),
    ]);

    $response = $this->postJson("/api/customer/pack-reservations/{$sell->id}/cancel");

    $response->assertStatus(401);

    expect($sell->fresh()->state)->not->toBe(SellState::CANCELLED);
});

test('customer cannot cancel an already cancelled purchase', function () {
    [$sell, $offer] = createTestPurchase($this->customer, $this->establishment, [
        'pickup_start_datetime' => now()->addHours(3),
        'expiration_datetime' => now()->addHours(4),
    ], [
        'state' => SellState::CANCELLED->value,
    ]);

    $response = $this->actingAs($this->customer)
        ->postJson("/api/customer/pack-reservations/{$sell->id}/cancel");

    $response->assertStatus(422)
        ->assertJson([
            'error' => 'La compra ya se encuentra cancelada.',
        ]);
});

test('sending cancel request twice rapidly only cancels once and does not duplicate restored stock', function () {
    $initialStock = 5;
    $boughtQuantity = 2;

    [$sell, $offer] = createTestPurchase($this->customer, $this->establishment, [
        'quantity' => $initialStock,
        'pickup_start_datetime' => now()->addHours(3),
        'expiration_datetime' => now()->addHours(4),
    ]);

    // Primer envío rápido de cancelación
    $firstResponse = $this->actingAs($this->customer)
        ->postJson("/api/customer/pack-reservations/{$sell->id}/cancel");

    $firstResponse->assertStatus(200)
        ->assertJson([
            'message' => 'Compra cancelada y reembolso emitido exitosamente',
        ]);

    // Segundo envío rápido inmediato (doble clic del cliente o reintento de red)
    $secondResponse = $this->actingAs($this->customer)
        ->postJson("/api/customer/pack-reservations/{$sell->id}/cancel");

    $secondResponse->assertStatus(422)
        ->assertJson([
            'error' => 'La compra ya se encuentra cancelada.',
        ]);

    // El stock debe incrementarse ÚNICAMENTE una vez: 5 + 2 = 7 (no 5 + 2 + 2 = 9)
    expect($offer->fresh()->quantity)->toBe($initialStock + $boughtQuantity);
    expect($sell->fresh()->state)->toBe(SellState::CANCELLED);
});


