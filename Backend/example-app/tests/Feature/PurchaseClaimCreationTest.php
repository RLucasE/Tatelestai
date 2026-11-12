<?php

use App\Actions\Sell\makeSellAction;
use App\Actions\Sell\VerifyPurchaseDataFreshnessAction;
use App\DTOs\PrepareOfferDTO;
use App\DTOs\PreparePurchaseDTO;
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
        'name' => 'Bistró Circular',
    ]);

    $this->makeClaimableSell = function (array $sellOverrides = [], array $offerOverrides = [], array $detailOverrides = []): array {
        $offer = Offer::factory()->active()->create(array_merge([
            'food_establishment_id' => $this->establishment->id,
            'quantity' => 5,
            'price' => 2000,
            'allergens' => ['gluten', 'lacteos'],
            'pickup_start_datetime' => now()->subDays(3)->subHour(),
            'expiration_datetime' => now()->subDays(3),
        ], $offerOverrides));

        $sell = Sell::factory()->create(array_merge([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'state' => SellState::PICKED_UP->value,
            'payment_status' => PaymentStatus::APPROVED->value,
            'is_picked_up' => true,
            'picked_up_at' => now()->subDays(3),
            'max_pickup_datetime' => $offer->expiration_datetime,
            'created_at' => now()->subDays(3),
        ], $sellOverrides));

        $detail = SellDetail::factory()->create(array_merge([
            'sell_id' => $sell->id,
            'offer_id' => $offer->id,
            'offer_quantity' => 2,
            'pack_name' => $offer->title,
            'pack_description' => $offer->description,
            'pack_price' => $offer->price,
            'allergens' => $offer->allergens,
        ], $detailOverrides));

        return [$sell, $offer, $detail];
    };
});

test('customer can create claim within 14 days without photos and notifies admins', function () {
    [$sell] = ($this->makeClaimableSell)([
        'picked_up_at' => now()->subDays(5),
    ]);

    $response = $this->actingAs($this->customer)
        ->postJson("/api/customer/pack-reservations/{$sell->id}/claims", [
            'reason' => ClaimReason::SPOILED_FOOD->value,
            'description' => 'La comida tenía olor agrio y estaba vencida al abrir el envase.',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.sell_id', $sell->id)
        ->assertJsonPath('data.reason', ClaimReason::SPOILED_FOOD->value)
        ->assertJsonPath('data.status', ClaimStatus::PENDING->value)
        ->assertJsonPath('data.was_picked_up_at_claim', true);

    $this->assertDatabaseHas('purchase_claims', [
        'sell_id' => $sell->id,
        'customer_id' => $this->customer->id,
        'food_establishment_id' => $this->establishment->id,
        'reason' => ClaimReason::SPOILED_FOOD->value,
        'status' => ClaimStatus::PENDING->value,
        'was_picked_up_at_claim' => true,
    ]);

    Notification::assertSentTo($this->admin, RealtimeOrderNotification::class, function ($notification) {
        return $notification->eventType === 'claim_created';
    });
});

test('customer can attach optional photo evidences stored in private disk', function () {
    [$sell] = ($this->makeClaimableSell)();

    $photo1 = UploadedFile::fake()->image('evidencia1.jpg', 400, 400);
    $photo2 = UploadedFile::fake()->image('evidencia2.png', 400, 400);

    $response = $this->actingAs($this->customer)
        ->post("/api/customer/pack-reservations/{$sell->id}/claims", [
            'reason' => ClaimReason::NOT_AS_DESCRIBED->value,
            'description' => 'El pack contenía únicamente pan duro en lugar de menú del día.',
            'evidences' => [$photo1, $photo2],
        ], ['Accept' => 'application/json']);

    $response->assertStatus(201);

    $claim = PurchaseClaim::where('sell_id', $sell->id)->firstOrFail();
    expect($claim->evidences()->count())->toBe(2);

    foreach ($claim->evidences as $evidence) {
        Storage::disk('private')->assertExists($evidence->file_path);
    }
});

test('customer can claim unpicked expired purchase within 14 days outside cancellation window', function () {
    [$sell] = ($this->makeClaimableSell)([
        'state' => SellState::EXPIRED->value,
        'is_picked_up' => false,
        'picked_up_at' => null,
        'max_pickup_datetime' => now()->subDays(2),
        'created_at' => now()->subDays(2)->subHours(2),
    ]);

    $response = $this->actingAs($this->customer)
        ->postJson("/api/customer/pack-reservations/{$sell->id}/claims", [
            'reason' => ClaimReason::PICKUP_ISSUE->value,
            'description' => 'Fui dentro del horario de retiro pero el local había cerrado antes de tiempo.',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.was_picked_up_at_claim', false);
});

test('claim is rejected when 14 day window has expired', function () {
    [$pickedUpOld] = ($this->makeClaimableSell)([
        'picked_up_at' => now()->subDays(15),
    ]);

    [$unpickedOld] = ($this->makeClaimableSell)([
        'state' => SellState::EXPIRED->value,
        'is_picked_up' => false,
        'picked_up_at' => null,
        'max_pickup_datetime' => now()->subDays(15),
        'created_at' => now()->subDays(15)->subHours(2),
    ]);

    $this->actingAs($this->customer)
        ->postJson("/api/customer/pack-reservations/{$pickedUpOld->id}/claims", [
            'reason' => ClaimReason::SPOILED_FOOD->value,
            'description' => 'Intento de reclamo después de los 14 días permitidos.',
        ])
        ->assertStatus(422);

    $this->actingAs($this->customer)
        ->postJson("/api/customer/pack-reservations/{$unpickedOld->id}/claims", [
            'reason' => ClaimReason::PICKUP_ISSUE->value,
            'description' => 'Intento de reclamo no retirado después de los 14 días permitidos.',
        ])
        ->assertStatus(422);
});

test('claim is blocked when purchase is still eligible for immediate voluntary cancellation', function () {
    [$sell] = ($this->makeClaimableSell)([
        'state' => SellState::CONFIRMED->value,
        'is_picked_up' => false,
        'picked_up_at' => null,
        'max_pickup_datetime' => now()->addHours(4),
        'created_at' => now(),
    ], [
        'pickup_start_datetime' => now()->addHours(3),
        'expiration_datetime' => now()->addHours(4),
    ]);

    $this->actingAs($this->customer)
        ->postJson("/api/customer/pack-reservations/{$sell->id}/claims", [
            'reason' => ClaimReason::OTHER->value,
            'description' => 'Quiero cancelar mi pedido usando un reclamo en lugar del botón cancelar.',
        ])
        ->assertStatus(422);
});

test('claim is rejected for unpaid cancelled refunded or foreign purchases', function () {
    [$unpaidSell] = ($this->makeClaimableSell)(['payment_status' => PaymentStatus::PENDING->value]);
    [$cancelledSell] = ($this->makeClaimableSell)(['state' => SellState::CANCELLED->value]);
    [$refundedSell] = ($this->makeClaimableSell)(['payment_status' => PaymentStatus::REFUNDED->value]);
    [$partialRefundedSell] = ($this->makeClaimableSell)(['payment_status' => PaymentStatus::PARTIALLY_REFUNDED->value]);
    [$foreignSell] = ($this->makeClaimableSell)(['bought_by' => $this->otherCustomer->id]);

    $payload = [
        'reason' => ClaimReason::MISSING_ITEMS->value,
        'description' => 'Faltaron varios artículos de la bolsa sorpresa.',
    ];

    $this->actingAs($this->customer)->postJson("/api/customer/pack-reservations/{$unpaidSell->id}/claims", $payload)->assertStatus(422);
    $this->actingAs($this->customer)->postJson("/api/customer/pack-reservations/{$cancelledSell->id}/claims", $payload)->assertStatus(422);
    $this->actingAs($this->customer)->postJson("/api/customer/pack-reservations/{$refundedSell->id}/claims", $payload)->assertStatus(422);
    $this->actingAs($this->customer)->postJson("/api/customer/pack-reservations/{$partialRefundedSell->id}/claims", $payload)->assertStatus(422);
    $this->actingAs($this->customer)->postJson("/api/customer/pack-reservations/{$foreignSell->id}/claims", $payload)->assertStatus(403);
});

test('claim validates mandatory description and evidence limits', function () {
    [$sell] = ($this->makeClaimableSell)();

    $this->actingAs($this->customer)
        ->postJson("/api/customer/pack-reservations/{$sell->id}/claims", [
            'reason' => ClaimReason::SPOILED_FOOD->value,
            'description' => 'Corto',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['description']);

    $photos = [];
    for ($i = 0; $i < 6; $i++) {
        $photos[] = UploadedFile::fake()->image("img{$i}.jpg");
    }

    $this->actingAs($this->customer)
        ->post("/api/customer/pack-reservations/{$sell->id}/claims", [
            'reason' => ClaimReason::SPOILED_FOOD->value,
            'description' => 'Descripción suficientemente larga para validar el límite de fotos.',
            'evidences' => $photos,
        ], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['evidences']);
});

test('checkout snapshot freezes allergens even if offer is later edited or deleted', function () {
    $offer = Offer::factory()->active()->create([
        'food_establishment_id' => $this->establishment->id,
        'quantity' => 5,
        'price' => 2500,
        'allergens' => ['sin_tacc', 'soja'],
        'estimated_weight_kg' => 1.5,
        'minimum_value' => 5000,
    ]);

    $offerDTO = PrepareOfferDTO::createFromIdAndQuantity($offer->id, 1);

    $purchaseDTO = new PreparePurchaseDTO(
        food_establishment_id: $this->establishment->id,
        offers: [$offerDTO],
    );

    $makeSell = app(makeSellAction::class);
    $result = $makeSell->execute($purchaseDTO, $this->customer->id, $this->establishment->id);
    $sellId = $result['sell_id'];

    // Simular edición posterior de la oferta quitando los alérgenos
    $offer->update(['allergens' => ['nueces']]);
    $offer->delete();

    $detail = SellDetail::where('sell_id', $sellId)->firstOrFail();
    expect($detail->allergens)->toBe(['sin_tacc', 'soja'])
        ->and((float) $detail->estimated_weight_kg)->toBe(1.5)
        ->and((float) $detail->minimum_value)->toBe(5000.0);

    // Verificar detección de cambio de alérgenos durante checkout en VerifyPurchaseDataFreshnessAction
    $offer2 = Offer::factory()->active()->create([
        'food_establishment_id' => $this->establishment->id,
        'quantity' => 5,
        'price' => 2500,
        'allergens' => ['gluten'],
    ]);
    $staleDTO = new PreparePurchaseDTO(
        food_establishment_id: $this->establishment->id,
        offers: [
            PrepareOfferDTO::createFromIdAndQuantity($offer2->id, 1),
        ],
    );
    $offer2->update(['allergens' => ['gluten', 'mani']]);

    expect(fn () => app(VerifyPurchaseDataFreshnessAction::class)->execute($staleDTO))
        ->toThrow(Exception::class, 'alérgenos');
});

test('anti abuse rules enforce uniqueness open quota 30d quota unpicked 60d quota and abusive cooldown', function () {
    // 1. Unicidad: 1 reclamo por compra
    [$sell1] = ($this->makeClaimableSell)();
    PurchaseClaim::create([
        'sell_id' => $sell1->id,
        'customer_id' => $this->customer->id,
        'food_establishment_id' => $this->establishment->id,
        'reason' => ClaimReason::SPOILED_FOOD->value,
        'description' => 'Primer reclamo válido sobre esta compra.',
        'status' => ClaimStatus::PENDING->value,
        'was_picked_up_at_claim' => true,
    ]);

    $this->actingAs($this->customer)
        ->postJson("/api/customer/pack-reservations/{$sell1->id}/claims", [
            'reason' => ClaimReason::OTHER->value,
            'description' => 'Segundo intento sobre la misma compra.',
        ])
        ->assertStatus(422);

    // 2. Cuota abierta máxima (2 reclamos abiertos)
    [$sell2] = ($this->makeClaimableSell)();
    PurchaseClaim::create([
        'sell_id' => $sell2->id,
        'customer_id' => $this->customer->id,
        'food_establishment_id' => $this->establishment->id,
        'reason' => ClaimReason::MISSING_ITEMS->value,
        'description' => 'Segundo reclamo abierto.',
        'status' => ClaimStatus::UNDER_REVIEW->value,
        'was_picked_up_at_claim' => true,
    ]);

    [$sell3] = ($this->makeClaimableSell)();
    $this->actingAs($this->customer)
        ->postJson("/api/customer/pack-reservations/{$sell3->id}/claims", [
            'reason' => ClaimReason::SPOILED_FOOD->value,
            'description' => 'Tercer reclamo simultáneo bloqueado por cuota abierta.',
        ])
        ->assertStatus(422);

    // Cerramos los 2 anteriores como aprobados para probar cuota móvil de 30 días (máx 3)
    PurchaseClaim::where('customer_id', $this->customer->id)->update(['status' => ClaimStatus::APPROVED->value]);

    PurchaseClaim::create([
        'sell_id' => $sell3->id,
        'customer_id' => $this->customer->id,
        'food_establishment_id' => $this->establishment->id,
        'reason' => ClaimReason::OTHER->value,
        'description' => 'Tercer reclamo en 30 días ya resuelto.',
        'status' => ClaimStatus::APPROVED->value,
        'was_picked_up_at_claim' => true,
    ]);

    [$sell4] = ($this->makeClaimableSell)();
    $this->actingAs($this->customer)
        ->postJson("/api/customer/pack-reservations/{$sell4->id}/claims", [
            'reason' => ClaimReason::SPOILED_FOOD->value,
            'description' => 'Cuarto reclamo dentro de 30 días bloqueado.',
        ])
        ->assertStatus(422);

    // 3. Cuota estricta de no retirados en 60 días (máx 1) usando otro cliente limpio
    [$unpicked1] = ($this->makeClaimableSell)([
        'bought_by' => $this->otherCustomer->id,
        'state' => SellState::EXPIRED->value,
        'is_picked_up' => false,
        'picked_up_at' => null,
        'max_pickup_datetime' => now()->subDays(2),
    ]);
    [$unpicked2] = ($this->makeClaimableSell)([
        'bought_by' => $this->otherCustomer->id,
        'state' => SellState::EXPIRED->value,
        'is_picked_up' => false,
        'picked_up_at' => null,
        'max_pickup_datetime' => now()->subDays(1),
    ]);

    PurchaseClaim::create([
        'sell_id' => $unpicked1->id,
        'customer_id' => $this->otherCustomer->id,
        'food_establishment_id' => $this->establishment->id,
        'reason' => ClaimReason::PICKUP_ISSUE->value,
        'description' => 'Primer reclamo por no retiro en 60 días.',
        'status' => ClaimStatus::APPROVED->value,
        'was_picked_up_at_claim' => false,
    ]);

    $this->actingAs($this->otherCustomer)
        ->postJson("/api/customer/pack-reservations/{$unpicked2->id}/claims", [
            'reason' => ClaimReason::PICKUP_ISSUE->value,
            'description' => 'Segundo reclamo por no retiro en 60 días debe bloquearse.',
        ])
        ->assertStatus(422);

    // 4. Bloqueo por abuso previo (is_abusive = true dentro de 30 días)
    $thirdCustomer = User::factory()->withRole(UserRole::CUSTOMER->value)->create(['state' => UserState::ACTIVE->value]);
    [$abusiveSell] = ($this->makeClaimableSell)(['bought_by' => $thirdCustomer->id]);
    [$nextSell] = ($this->makeClaimableSell)(['bought_by' => $thirdCustomer->id]);

    PurchaseClaim::create([
        'sell_id' => $abusiveSell->id,
        'customer_id' => $thirdCustomer->id,
        'food_establishment_id' => $this->establishment->id,
        'reason' => ClaimReason::OTHER->value,
        'description' => 'Reclamo falso marcado como abusivo.',
        'status' => ClaimStatus::REJECTED->value,
        'was_picked_up_at_claim' => true,
        'is_abusive' => true,
        'reviewed_by' => $this->admin->id,
        'reviewed_at' => now()->subDays(5),
        'resolution_notes' => 'Reclamo fraudulento comprobado.',
    ]);

    $this->actingAs($thirdCustomer)
        ->postJson("/api/customer/pack-reservations/{$nextSell->id}/claims", [
            'reason' => ClaimReason::SPOILED_FOOD->value,
            'description' => 'Intento tras sanción de abuso.',
        ])
        ->assertStatus(422);
});
