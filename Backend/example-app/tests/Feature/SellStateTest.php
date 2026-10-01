<?php

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
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(EstablishmentTypeSeeder::class);

    $this->seller = User::factory()->withRole(UserRole::SELLER->value)->create([
        'state' => UserState::ACTIVE->value,
    ]);

    $establishmentType = EstablishmentType::inRandomOrder()->first();

    $this->establishment = FoodEstablishment::factory()->create([
        'user_id' => $this->seller->id,
        'name' => 'Comercio Gastronómico Test',
        'establishment_type_id' => $establishmentType->id,
    ]);

    $this->customer = User::factory()->withRole(UserRole::CUSTOMER->value)->create([
        'state' => UserState::ACTIVE->value,
    ]);

    $this->offer = Offer::factory()->active()->create([
        'food_establishment_id' => $this->establishment->id,
        'title' => 'Bolsa Sorpresa Especial',
        'price' => 2500,
        'pickup_start_datetime' => now()->startOfHour(),
        'expiration_datetime' => now()->addHours(3),
    ]);
});

test('sell state enum defines multiple states beyond pending and picked_up with correct values and labels', function () {
    // 1. Verificar todos los casos del enum
    $cases = SellState::cases();
    expect($cases)->toHaveCount(6);

    // 2. Verificar valores textuales
    expect(SellState::PENDING->value)->toBe('pending')
        ->and(SellState::CONFIRMED->value)->toBe('confirmed')
        ->and(SellState::READY->value)->toBe('ready')
        ->and(SellState::PICKED_UP->value)->toBe('picked_up')
        ->and(SellState::CANCELLED->value)->toBe('cancelled')
        ->and(SellState::EXPIRED->value)->toBe('expired');

    // 3. Verificar etiquetas legibles de presentación
    expect(SellState::PENDING->label())->toBe('Pendiente')
        ->and(SellState::CONFIRMED->label())->toBe('Confirmado')
        ->and(SellState::READY->label())->toBe('Listo para retirar')
        ->and(SellState::PICKED_UP->label())->toBe('Retirado')
        ->and(SellState::CANCELLED->label())->toBe('Cancelado')
        ->and(SellState::EXPIRED->label())->toBe('Expirado');

    // 4. Verificar método helper de valores
    expect(SellState::values())->toBe([
        'pending',
        'confirmed',
        'ready',
        'picked_up',
        'cancelled',
        'expired',
    ]);
});

test('a newly created sell defaults to the initial valid state and casts to SellState enum', function () {
    $sell = Sell::create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'pickup_code' => 'CODE-INITIAL-STATE',
        'max_pickup_datetime' => now()->addHours(2),
    ]);

    // Recargar desde base de datos para sincronizar los valores por defecto definidos en la migración
    $sell->refresh();

    // Debe inicializarse en el estado PENDING por defecto
    expect($sell->state)->toBe(SellState::PENDING)
        ->and($sell->state)->toBeInstanceOf(SellState::class)
        ->and($sell->state->value)->toBe('pending')
        ->and($sell->state->label())->toBe('Pendiente');

    // Verificar persistencia en base de datos
    $this->assertDatabaseHas('sells', [
        'id' => $sell->id,
        'state' => 'pending',
    ]);
});

test('a sell can transition and persist across all multiple states', function () {
    $sell = Sell::create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'pickup_code' => 'CODE-TRANSITIONS',
        'max_pickup_datetime' => now()->addHours(2),
    ]);

    $sell->refresh();

    expect($sell->state)->toBe(SellState::PENDING);

    // Transición a CONFIRMED
    $sell->update(['state' => SellState::CONFIRMED->value]);
    expect($sell->fresh()->state)->toBe(SellState::CONFIRMED);

    // Transición a READY (Listo para retirar)
    $sell->update(['state' => SellState::READY->value]);
    expect($sell->fresh()->state)->toBe(SellState::READY);

    // Transición a PICKED_UP (Retirado)
    $sell->update(['state' => SellState::PICKED_UP->value]);
    expect($sell->fresh()->state)->toBe(SellState::PICKED_UP);

    // Transición a CANCELLED (Cancelado)
    $sell->update(['state' => SellState::CANCELLED->value]);
    expect($sell->fresh()->state)->toBe(SellState::CANCELLED);

    // Transición a EXPIRED (Expirado)
    $sell->update(['state' => SellState::EXPIRED->value]);
    expect($sell->fresh()->state)->toBe(SellState::EXPIRED);
});

test('sell factory creates predictable pending uncollected sell by default and supports pickedUp modifier', function () {
    // 1. Factory por defecto genera venta pendiente y no retirada
    $defaultSell = Sell::factory()->create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
    ]);

    expect($defaultSell->state)->toBe(SellState::PENDING)
        ->and($defaultSell->is_picked_up)->toBeFalse()
        ->and($defaultSell->picked_up_at)->toBeNull();

    // 2. Factory con modifier pickedUp() genera venta entregada
    $pickedUpSell = Sell::factory()->pickedUp()->create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
    ]);

    expect($pickedUpSell->state)->toBe(SellState::PICKED_UP)
        ->and($pickedUpSell->is_picked_up)->toBeTrue()
        ->and($pickedUpSell->picked_up_at)->not->toBeNull();
});

test('seller dashboard accurately reflects various states in today pickups including cancelled status', function () {
    $this->actingAs($this->seller);

    // 1. Venta Confirmada
    $sellConfirmed = Sell::create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'pickup_code' => 'CODE-CONFIRMED',
        'state' => SellState::CONFIRMED->value,
        'is_picked_up' => false,
        'max_pickup_datetime' => now()->addHours(2),
        'created_at' => now(),
    ]);
    SellDetail::create([
        'sell_id' => $sellConfirmed->id,
        'offer_id' => $this->offer->id,
        'offer_quantity' => 1,
        'pack_price' => 2500,
        'pack_name' => 'Pack Confirmado',
    ]);

    // 2. Venta Lista para Retirar (READY)
    $sellReady = Sell::create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'pickup_code' => 'CODE-READY',
        'state' => SellState::READY->value,
        'is_picked_up' => false,
        'max_pickup_datetime' => now()->addHours(2),
        'created_at' => now(),
    ]);
    SellDetail::create([
        'sell_id' => $sellReady->id,
        'offer_id' => $this->offer->id,
        'offer_quantity' => 1,
        'pack_price' => 2500,
        'pack_name' => 'Pack Listo',
    ]);

    // 3. Venta Pendiente
    $sellPending = Sell::create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'pickup_code' => 'CODE-PENDING',
        'state' => SellState::PENDING->value,
        'is_picked_up' => false,
        'max_pickup_datetime' => now()->addHours(2),
        'created_at' => now(),
    ]);
    SellDetail::create([
        'sell_id' => $sellPending->id,
        'offer_id' => $this->offer->id,
        'offer_quantity' => 1,
        'pack_price' => 2500,
        'pack_name' => 'Pack Pendiente',
    ]);

    // 4. Venta Cancelada (figura con status cancelled para el filtro de Cancelados)
    $sellCancelled = Sell::create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'pickup_code' => 'CODE-CANCELLED',
        'state' => SellState::CANCELLED->value,
        'is_picked_up' => false,
        'max_pickup_datetime' => now()->addHours(2),
        'created_at' => now(),
    ]);
    SellDetail::create([
        'sell_id' => $sellCancelled->id,
        'offer_id' => $this->offer->id,
        'offer_quantity' => 1,
        'pack_price' => 2500,
        'pack_name' => 'Pack Cancelado',
    ]);

    // 5. Venta Ya Retirada (PICKED_UP)
    $sellPickedUp = Sell::create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'pickup_code' => 'CODE-PICKED-UP',
        'state' => SellState::PICKED_UP->value,
        'is_picked_up' => true,
        'picked_up_at' => now(),
        'max_pickup_datetime' => now()->addHours(2),
        'created_at' => now(),
    ]);
    SellDetail::create([
        'sell_id' => $sellPickedUp->id,
        'offer_id' => $this->offer->id,
        'offer_quantity' => 1,
        'pack_price' => 2500,
        'pack_name' => 'Pack Retirado',
    ]);

    $response = $this->getJson('/api/seller/dashboard');
    $response->assertStatus(200);

    $pickups = collect($response->json('data.today_pickups'));

    // 1. La venta cancelada debe figurar con status 'cancelled' para permitir su filtrado
    $itemCancelled = $pickups->firstWhere('id', $sellCancelled->id);
    expect($itemCancelled)->not->toBeNull()
        ->and($itemCancelled['status'])->toBe('cancelled');

    // 2. La venta confirmada debe figurar con status 'confirmed'
    $itemConfirmed = $pickups->firstWhere('id', $sellConfirmed->id);
    expect($itemConfirmed)->not->toBeNull()
        ->and($itemConfirmed['status'])->toBe('confirmed');

    // 3. La venta lista debe figurar con status 'ready'
    $itemReady = $pickups->firstWhere('id', $sellReady->id);
    expect($itemReady)->not->toBeNull()
        ->and($itemReady['status'])->toBe('ready');

    // 4. La venta pendiente debe figurar con status 'pending'
    $itemPending = $pickups->firstWhere('id', $sellPending->id);
    expect($itemPending)->not->toBeNull()
        ->and($itemPending['status'])->toBe('pending');

    // 5. La venta entregada debe figurar con status 'picked_up'
    $itemPickedUp = $pickups->firstWhere('id', $sellPickedUp->id);
    expect($itemPickedUp)->not->toBeNull()
        ->and($itemPickedUp['status'])->toBe('picked_up');
});

test('only a sell in PENDING state can be checked and redeemed via pickup code, while other states are rejected', function () {
    $this->actingAs($this->seller);

    // 1. Venta en estado CANCELLED -> rechaza chequeo (422) y completar (404)
    $sellCancelled = Sell::create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'pickup_code' => 'CANCELLED-CODE-999',
        'state' => SellState::CANCELLED->value,
        'is_picked_up' => false,
        'max_pickup_datetime' => now()->addHours(2),
        'created_at' => now(),
    ]);

    $responseCancelled = $this->postJson('/api/check-customer-code', [
        'pickup_code' => 'CANCELLED-CODE-999',
    ]);
    $responseCancelled->assertStatus(422)
        ->assertJson(['error' => 'El pedido no se encuentra disponible para ser retirado.']);

    $completeCancelled = $this->postJson("/api/complete-sell/{$sellCancelled->id}", [
        'pick_up_code' => 'CANCELLED-CODE-999',
    ]);
    $completeCancelled->assertStatus(404);

    // 2. Venta en estado CONFIRMED -> rechaza chequeo (422) y completar (404)
    $sellConfirmed = Sell::create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'pickup_code' => 'CONFIRMED-CODE-333',
        'state' => SellState::CONFIRMED->value,
        'is_picked_up' => false,
        'max_pickup_datetime' => now()->addHours(2),
        'created_at' => now(),
    ]);

    $responseConfirmed = $this->postJson('/api/check-customer-code', [
        'pickup_code' => 'CONFIRMED-CODE-333',
    ]);
    $responseConfirmed->assertStatus(422)
        ->assertJson(['error' => 'El pedido no se encuentra disponible para ser retirado.']);

    $completeConfirmed = $this->postJson("/api/complete-sell/{$sellConfirmed->id}", [
        'pick_up_code' => 'CONFIRMED-CODE-333',
    ]);
    $completeConfirmed->assertStatus(404);

    // 3. Venta en estado READY -> rechaza chequeo (422) y completar (404)
    $sellReady = Sell::create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'pickup_code' => 'READY-CODE-444',
        'state' => SellState::READY->value,
        'is_picked_up' => false,
        'max_pickup_datetime' => now()->addHours(2),
        'created_at' => now(),
    ]);

    $responseReady = $this->postJson('/api/check-customer-code', [
        'pickup_code' => 'READY-CODE-444',
    ]);
    $responseReady->assertStatus(422)
        ->assertJson(['error' => 'El pedido no se encuentra disponible para ser retirado.']);

    $completeReady = $this->postJson("/api/complete-sell/{$sellReady->id}", [
        'pick_up_code' => 'READY-CODE-444',
    ]);
    $completeReady->assertStatus(404);

    // 4. Venta en estado PICKED_UP (ya entregada) -> rechaza chequeo (422) y completar (404)
    $sellPickedUp = Sell::create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'pickup_code' => 'PICKED-UP-CODE-555',
        'state' => SellState::PICKED_UP->value,
        'is_picked_up' => true,
        'picked_up_at' => now(),
        'max_pickup_datetime' => now()->addHours(2),
        'created_at' => now(),
    ]);

    $responsePickedUp = $this->postJson('/api/check-customer-code', [
        'pickup_code' => 'PICKED-UP-CODE-555',
    ]);
    $responsePickedUp->assertStatus(422)
        ->assertJson(['error' => 'El pedido no se encuentra disponible para ser retirado.']);

    $completePickedUp = $this->postJson("/api/complete-sell/{$sellPickedUp->id}", [
        'pick_up_code' => 'PICKED-UP-CODE-555',
    ]);
    $completePickedUp->assertStatus(404);

    // 5. Venta en estado PENDING -> SÍ puede ser verificada y completada
    $sellPending = Sell::create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'pickup_code' => 'PENDING-CODE-111',
        'state' => SellState::PENDING->value,
        'is_picked_up' => false,
        'max_pickup_datetime' => now()->addHours(2),
        'created_at' => now(),
    ]);
    SellDetail::create([
        'sell_id' => $sellPending->id,
        'offer_id' => $this->offer->id,
        'offer_quantity' => 1,
        'pack_price' => 2500,
        'pack_name' => 'Bolsa Sorpresa Especial',
    ]);

    $checkPending = $this->postJson('/api/check-customer-code', [
        'pickup_code' => 'PENDING-CODE-111',
    ]);
    $checkPending->assertStatus(200)
        ->assertJson(['message' => 'Código válido']);

    $completePending = $this->postJson("/api/complete-sell/{$sellPending->id}", [
        'pick_up_code' => 'PENDING-CODE-111',
    ]);
    $completePending->assertStatus(200)
        ->assertJson(['message' => 'Venta completada exitosamente']);

    expect($sellPending->fresh()->state)->toBe(SellState::PICKED_UP)
        ->and($sellPending->fresh()->is_picked_up)->toBeTrue();
});

test('pickup code verification and sell completion enforce merchant authorization across different establishments', function () {
    // Crear un segundo vendedor con su propio comercio
    $otherSeller = User::factory()->withRole(UserRole::SELLER->value)->create([
        'state' => UserState::ACTIVE->value,
    ]);
    $otherEstablishment = FoodEstablishment::factory()->create([
        'user_id' => $otherSeller->id,
        'name' => 'Otro Comercio',
    ]);

    // Crear venta para el segundo comercio
    $otherSell = Sell::create([
        'bought_by' => $this->customer->id,
        'sold_by' => $otherEstablishment->id,
        'pickup_code' => 'OTHER-ESTABLISHMENT-CODE',
        'state' => SellState::PENDING->value,
        'is_picked_up' => false,
        'max_pickup_datetime' => now()->addHours(2),
        'created_at' => now(),
    ]);

    // Autenticar como el primer vendedor (no autorizado para este código)
    $this->actingAs($this->seller);

    // 1. Intentar chequear código de otro comercio -> 403 Forbidden
    $responseCheck = $this->postJson('/api/check-customer-code', [
        'pickup_code' => 'OTHER-ESTABLISHMENT-CODE',
    ]);
    $responseCheck->assertStatus(403)
        ->assertJson(['error' => 'No tienes permiso para verificar este código']);

    // 2. Intentar completar venta de otro comercio -> 403 Forbidden
    $responseComplete = $this->postJson("/api/complete-sell/{$otherSell->id}", [
        'pick_up_code' => 'OTHER-ESTABLISHMENT-CODE',
    ]);
    $responseComplete->assertStatus(403)
        ->assertJson(['error' => 'No tienes permiso para completar esta venta']);
});

test('pickup code verification rejects nonexistent codes and mismatched redemption codes', function () {
    $this->actingAs($this->seller);

    // 1. Chequear código que no existe en el sistema -> 404
    $responseNotFound = $this->postJson('/api/check-customer-code', [
        'pickup_code' => 'NON-EXISTENT-CODE-999',
    ]);
    $responseNotFound->assertStatus(404)
        ->assertJson(['error' => 'Código de pickup no encontrado']);

    // 2. Intentar completar la venta enviando un código que no coincide con la venta -> 400
    $sell = Sell::create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'pickup_code' => 'REAL-CODE-123',
        'state' => SellState::PENDING->value,
        'is_picked_up' => false,
        'max_pickup_datetime' => now()->addHours(2),
        'created_at' => now(),
    ]);

    $responseMismatch = $this->postJson("/api/complete-sell/{$sell->id}", [
        'pick_up_code' => 'WRONG-CODE-999',
    ]);
    $responseMismatch->assertStatus(400)
        ->assertJson(['error' => 'Código de pickup incorrecto']);
});

test('pickup code check rejects expired pickup windows with status 410', function () {
    $this->actingAs($this->seller);

    // Crear venta cuya ventana horaria máxima expiró hace 10 minutos
    $expiredSell = Sell::create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'pickup_code' => 'EXPIRED-TIME-CODE-000',
        'state' => SellState::PENDING->value,
        'is_picked_up' => false,
        'max_pickup_datetime' => now()->subMinutes(10),
        'created_at' => now()->subHours(2),
    ]);

    $response = $this->postJson('/api/check-customer-code', [
        'pickup_code' => 'EXPIRED-TIME-CODE-000',
    ]);

    $response->assertStatus(410)
        ->assertJson(['error' => 'El tiempo para recoger esta venta ha expirado']);
});

test('sell with EXPIRED state is rejected with 422 on code check and 404 on completion', function () {
    $this->actingAs($this->seller);

    $expiredSell = Sell::create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'pickup_code' => 'EXPIRED-STATE-CODE-777',
        'state' => SellState::EXPIRED->value,
        'is_picked_up' => false,
        'max_pickup_datetime' => now()->addHours(2),
        'created_at' => now(),
    ]);

    // 1. Chequeo de código debe ser rechazado con 422 por no estar en PENDING
    $responseCheck = $this->postJson('/api/check-customer-code', [
        'pickup_code' => 'EXPIRED-STATE-CODE-777',
    ]);
    $responseCheck->assertStatus(422)
        ->assertJson(['error' => 'El pedido no se encuentra disponible para ser retirado.']);

    // 2. Completar venta debe responder 404 al no estar en estado PENDING
    $responseComplete = $this->postJson("/api/complete-sell/{$expiredSell->id}", [
        'pick_up_code' => 'EXPIRED-STATE-CODE-777',
    ]);
    $responseComplete->assertStatus(404);
});

test('end-to-end flow: customer purchase cancellation updates dashboard status to cancelled, decrements pending count, and prevents redemption', function () {
    // 1. Crear oferta con ventana para retiro en 3 horas
    $offer = Offer::factory()->active()->create([
        'food_establishment_id' => $this->establishment->id,
        'title' => 'Bolsa de Panadería Premium',
        'quantity' => 10,
        'price' => 1500,
        'pickup_start_datetime' => now()->addHours(3),
        'expiration_datetime' => now()->addHours(4),
    ]);

    // 2. Crear compra en estado PENDING para el cliente
    $pickupCode = 'CANCEL-FLOW-CODE-888';
    $sell = Sell::create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'pickup_code' => $pickupCode,
        'state' => SellState::PENDING->value,
        'is_picked_up' => false,
        'max_pickup_datetime' => $offer->expiration_datetime,
        'created_at' => now(),
    ]);

    SellDetail::create([
        'sell_id' => $sell->id,
        'offer_id' => $offer->id,
        'offer_quantity' => 2,
        'pack_price' => 1500,
        'pack_name' => $offer->title,
    ]);

    // Disminuir stock simulando la reserva previa: 10 - 2 = 8
    $offer->update(['quantity' => 8]);

    // 3. Verificar estado en dashboard del vendedor antes de cancelar: figura como pending
    $this->actingAs($this->seller);
    $dashboardBefore = $this->getJson('/api/seller/dashboard');
    $dashboardBefore->assertStatus(200);

    $pickupsBefore = collect($dashboardBefore->json('data.today_pickups'));
    $itemBefore = $pickupsBefore->firstWhere('id', $sell->id);
    expect($itemBefore)->not->toBeNull()
        ->and($itemBefore['status'])->toBe('pending');
    expect($dashboardBefore->json('data.summary.pending_pickups_today'))->toBe(1);

    // 4. El cliente cancela su compra (con > 2 hs de anticipación)
    $this->actingAs($this->customer);
    $cancelResponse = $this->postJson("/api/customer/pack-reservations/{$sell->id}/cancel");
    $cancelResponse->assertStatus(200)
        ->assertJson(['message' => 'Compra cancelada y reembolso emitido exitosamente']);

    expect($sell->fresh()->state)->toBe(SellState::CANCELLED);
    // Stock restituido: 8 + 2 = 10
    expect($offer->fresh()->quantity)->toBe(10);

    // 5. El vendedor consulta el dashboard: ahora figura con status 'cancelled' y los pendientes son 0
    $this->actingAs($this->seller);
    $dashboardAfter = $this->getJson('/api/seller/dashboard');
    $dashboardAfter->assertStatus(200);

    $pickupsAfter = collect($dashboardAfter->json('data.today_pickups'));
    $itemAfter = $pickupsAfter->firstWhere('id', $sell->id);
    expect($itemAfter)->not->toBeNull()
        ->and($itemAfter['status'])->toBe('cancelled');
    // pending_pickups_today no debe incluir compras canceladas
    expect($dashboardAfter->json('data.summary.pending_pickups_today'))->toBe(0)
        ->and($dashboardAfter->json('data.summary.orders_today'))->toBe(0)
        ->and($dashboardAfter->json('data.summary.packs_sold_today'))->toBe(0);

    // 6. El vendedor intenta canjear el código cancelado en el mostrador -> rechazo estricto
    $checkResponse = $this->postJson('/api/check-customer-code', [
        'pickup_code' => $pickupCode,
    ]);
    $checkResponse->assertStatus(422)
        ->assertJson(['error' => 'El pedido no se encuentra disponible para ser retirado.']);

    $completeResponse = $this->postJson("/api/complete-sell/{$sell->id}", [
        'pick_up_code' => $pickupCode,
    ]);
    $completeResponse->assertStatus(404);
});

test('double redemption prevention: successfully completing a sell marks it as picked_up and prevents second redemption attempt', function () {
    $this->actingAs($this->seller);

    $pickupCode = 'DOUBLE-REDEEM-CODE-123';
    $sell = Sell::create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'pickup_code' => $pickupCode,
        'state' => SellState::PENDING->value,
        'is_picked_up' => false,
        'max_pickup_datetime' => now()->addHours(2),
        'created_at' => now(),
    ]);

    SellDetail::create([
        'sell_id' => $sell->id,
        'offer_id' => $this->offer->id,
        'offer_quantity' => 1,
        'pack_price' => 2500,
        'pack_name' => $this->offer->title,
    ]);

    // 1. Primer canje: Verificar código exitosamente
    $checkFirst = $this->postJson('/api/check-customer-code', [
        'pickup_code' => $pickupCode,
    ]);
    $checkFirst->assertStatus(200)
        ->assertJson(['message' => 'Código válido']);

    // Completar la venta exitosamente
    $completeFirst = $this->postJson("/api/complete-sell/{$sell->id}", [
        'pick_up_code' => $pickupCode,
    ]);
    $completeFirst->assertStatus(200)
        ->assertJson(['message' => 'Venta completada exitosamente']);

    $sell->refresh();
    expect($sell->state)->toBe(SellState::PICKED_UP)
        ->and($sell->is_picked_up)->toBeTrue()
        ->and($sell->picked_up_at)->not->toBeNull();

    // 2. Segundo intento de canje (reintento del mismo código ya entregado): debe ser rechazado
    $checkSecond = $this->postJson('/api/check-customer-code', [
        'pickup_code' => $pickupCode,
    ]);
    $checkSecond->assertStatus(422)
        ->assertJson(['error' => 'El pedido no se encuentra disponible para ser retirado.']);

    $completeSecond = $this->postJson("/api/complete-sell/{$sell->id}", [
        'pick_up_code' => $pickupCode,
    ]);
    $completeSecond->assertStatus(404);
});

test('cancelled order is never classified as pending in today pickups even within active time window', function () {
    $this->actingAs($this->seller);

    // Venta cancelada con ventana activa en el futuro
    $cancelledSell = Sell::create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'pickup_code' => 'ACTIVE-WINDOW-CANCELLED',
        'state' => SellState::CANCELLED->value,
        'is_picked_up' => false,
        'max_pickup_datetime' => now()->addHours(3),
        'created_at' => now(),
    ]);

    SellDetail::create([
        'sell_id' => $cancelledSell->id,
        'offer_id' => $this->offer->id,
        'offer_quantity' => 1,
        'pack_price' => 2500,
        'pack_name' => $this->offer->title,
    ]);

    $response = $this->getJson('/api/seller/dashboard');
    $response->assertStatus(200);

    $pickups = collect($response->json('data.today_pickups'));
    $pickup = $pickups->firstWhere('id', $cancelledSell->id);

    expect($pickup)->not->toBeNull()
        ->and($pickup['status'])->toBe('cancelled')
        ->and($pickup['status'])->not->toBe('pending');

    // Comprobar que pending_pickups_today en summary es 0
    expect($response->json('data.summary.pending_pickups_today'))->toBe(0);
});
