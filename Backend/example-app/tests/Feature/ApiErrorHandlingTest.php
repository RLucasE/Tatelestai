<?php

use App\Enums\ReportReason;
use App\Enums\SellState;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Exceptions\BusinessRuleException;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\PackTemplate;
use App\Models\Sell;
use App\Models\User;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(EstablishmentTypeSeeder::class);

    $this->customer = User::factory()->withRole(UserRole::CUSTOMER->value)->create([
        'state' => UserState::ACTIVE->value,
    ]);

    $this->seller = User::factory()->withRole(UserRole::SELLER->value)->create([
        'state' => UserState::ACTIVE->value,
    ]);

    $type = EstablishmentType::first();

    $this->establishment = FoodEstablishment::factory()->create([
        'user_id' => $this->seller->id,
        'establishment_type_id' => $type->id,
    ]);
});

test('toda respuesta de la API incluye cabecera X-Request-ID y respeta un X-Request-ID entrante válido', function () {
    $response = $this->actingAs($this->customer)->getJson('/api/customer/pack-reservations/history');

    $response->assertStatus(200);
    $generatedRequestId = $response->headers->get('X-Request-ID');
    expect($generatedRequestId)->toBeString()->toStartWith('req_');

    // Con X-Request-ID enviado por el cliente
    $customId = 'req_custom_client_9911';
    $responseWithCustomId = $this->actingAs($this->customer)
        ->withHeader('X-Request-ID', $customId)
        ->getJson('/api/customer/pack-reservations/history');

    $responseWithCustomId->assertStatus(200)
        ->assertHeader('X-Request-ID', $customId);
});

test('petición no autenticada devuelve 401 con formato estandarizado y request_id', function () {
    $response = $this->getJson('/api/customer/pack-reservations/history');

    $response->assertStatus(401)
        ->assertJsonStructure([
            'message',
            'error',
            'error_code',
            'errors',
            'context' => ['request_id'],
        ])
        ->assertJson([
            'message' => 'Unauthenticated.',
            'error' => 'Unauthenticated.',
            'error_code' => 'UNAUTHENTICATED',
            'errors' => null,
        ]);

    $headerRequestId = $response->headers->get('X-Request-ID');
    expect($response->json('context.request_id'))->toBe($headerRequestId);
});

test('error de validación en prepareBuyOffers devuelve 422 (y no 500) con estructura estandarizada', function () {
    $response = $this->actingAs($this->customer)
        ->postJson('/api/customer/pack-reservations/prepare', [
            'food_establishment_id' => null,
            'offers' => [],
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['food_establishment_id', 'offers'])
        ->assertJsonPath('error_code', 'VALIDATION_ERROR')
        ->assertJsonStructure([
            'message',
            'error',
            'error_code',
            'errors' => ['food_establishment_id', 'offers'],
            'context' => ['request_id'],
        ]);
});

test('OfferQuantityExceededException devuelve 400 con publicContext, request_id y campos retrocompatibles', function () {
    $offer = Offer::factory()->active()->create([
        'food_establishment_id' => $this->establishment->id,
        'quantity' => 2,
        'expiration_datetime' => now()->addHours(5),
    ]);

    $response = $this->actingAs($this->customer)
        ->postJson('/api/add-to-cart', [
            'offer_id' => $offer->id,
            'quantity' => 5,
        ]);

    $response->assertStatus(400)
        ->assertJsonPath('error_code', 'OFFER_QUANTITY_EXCEEDED')
        ->assertJsonPath('requested_quantity', 5)
        ->assertJsonPath('available_quantity', 2)
        ->assertJsonPath('context.offer_id', $offer->id)
        ->assertJsonPath('context.requested_quantity', 5)
        ->assertJsonPath('context.available_quantity', 2);

    expect($response->json('context.request_id'))->toStartWith('req_');
});

test('excepciones de dominio (CancellationNotAllowed, ReviewNotAllowed, DuplicateReport, PackTemplateOwnership) devuelven su código y contexto seguro', function () {
    // 1. PackTemplateOwnershipException (403)
    $otherSeller = User::factory()->withRole(UserRole::SELLER->value)->create([
        'state' => UserState::ACTIVE->value,
    ]);
    $otherEstablishment = FoodEstablishment::factory()->create([
        'user_id' => $otherSeller->id,
        'establishment_type_id' => EstablishmentType::first()->id,
    ]);
    $otherTemplate = PackTemplate::factory()->create([
        'food_establishment_id' => $otherEstablishment->id,
    ]);

    $packResponse = $this->actingAs($this->seller)->postJson('/api/packs', [
        'pack_template_id' => $otherTemplate->id,
        'quantity' => 3,
        'price' => 1500,
        'minimum_value' => 4500,
        'pickup_start_datetime' => now()->addHour()->format('Y-m-d H:i:s'),
        'pickup_end_datetime' => now()->addHours(4)->format('Y-m-d H:i:s'),
    ]);

    $packResponse->assertStatus(403)
        ->assertJsonPath('error_code', 'PACK_TEMPLATE_OWNERSHIP_ERROR')
        ->assertJsonPath('message', 'La plantilla de pack no pertenece a tu establecimiento')
        ->assertJsonPath('error', 'La plantilla de pack no pertenece a tu establecimiento')
        ->assertJsonPath('context.pack_template_id', $otherTemplate->id);

    // 2. ReviewNotAllowedException (422 - intentando calificar compra no retirada)
    $pendingSell = Sell::factory()->create([
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishment->id,
        'state' => SellState::PENDING->value,
        'is_picked_up' => false,
    ]);

    $reviewResponse = $this->actingAs($this->customer)
        ->postJson("/api/customer/pack-reservations/{$pendingSell->id}/review", [
            'rating' => 5,
        ]);

    $reviewResponse->assertStatus(422)
        ->assertJsonPath('error_code', 'REVIEW_NOT_ALLOWED')
        ->assertJsonStructure(['message', 'error', 'error_code', 'context' => ['request_id']]);

    // 3. DuplicateReportException (409 - sin exponer user_id en publicContext)
    $offer = Offer::factory()->active()->create([
        'food_establishment_id' => $this->establishment->id,
    ]);

    $reportPayload = [
        'reportable_type' => 'offer',
        'reportable_id' => $offer->id,
        'reason' => ReportReason::SPAM->value,
        'description' => 'Este contenido es spam repetido de prueba.',
    ];

    $this->actingAs($this->customer)->postJson('/api/customer/reports', $reportPayload)->assertStatus(201);
    $dupResponse = $this->actingAs($this->customer)->postJson('/api/customer/reports', $reportPayload);

    $dupResponse->assertStatus(409)
        ->assertJsonPath('error_code', 'DUPLICATE_REPORT')
        ->assertJsonPath('error', 'You have already reported this element');

    expect($dupResponse->json('context'))->not->toHaveKey('user_id');
});

test('ModelNotFoundException y rutas con recursos inexistentes devuelven 404 limpio con RESOURCE_NOT_FOUND sin exponer clases internas', function () {
    // 1. Ruta con callback missing() estandarizado
    $reviewResponse = $this->actingAs($this->customer)
        ->postJson('/api/customer/pack-reservations/9999999/review', [
            'rating' => 5,
        ]);

    $reviewResponse->assertStatus(404)
        ->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND')
        ->assertJsonPath('message', 'La compra especificada no existe o no fue encontrada.');

    // 2. Endpoint con firstOrFail() nativo (ModelNotFoundException)
    $cancelResponse = $this->actingAs($this->seller)
        ->postJson('/api/seller/sells/9999999/cancel', [
            'reason' => 'Sin stock',
        ]);

    $cancelResponse->assertStatus(404)
        ->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND')
        ->assertJsonPath('message', 'Recurso no encontrado.')
        ->assertJsonPath('error', 'Recurso no encontrado.');

    expect($cancelResponse->json('message'))->not->toContain('App\\Models\\Sell');
});

test('separa el reporting interno del renderizado JSON: errores 500 ocultan detalles al cliente pero se registran en los logs con el request_id', function () {
    Route::middleware('api')->get('/api/test-unexpected-failure', function () {
        throw new RuntimeException('SQLSTATE[42P01]: Undefined table: 7 ERROR: relation "secret_internal_table" does not exist');
    });

    Route::middleware('api')->get('/api/test-expected-business-rule', function () {
        throw new BusinessRuleException(
            message: 'Esta oferta ya fue reservada por otro usuario.',
            status: 409,
            errorCode: 'OFFER_ALREADY_RESERVED',
            publicContext: ['offer_id' => 77],
            internalContext: ['db_lock_wait_ms' => 142, 'internal_node' => 'worker-2']
        );
    });

    Log::spy();

    // 1. La excepción de regla de negocio (409) NO debe reportarse como error crítico en los logs
    // y NO debe exponer el internalContext al cliente.
    $businessResponse = $this->withHeader('X-Request-ID', 'req_biz_409')
        ->getJson('/api/test-expected-business-rule');

    $businessResponse->assertStatus(409)
        ->assertHeader('X-Request-ID', 'req_biz_409')
        ->assertJson([
            'message' => 'Esta oferta ya fue reservada por otro usuario.',
            'error' => 'Esta oferta ya fue reservada por otro usuario.',
            'error_code' => 'OFFER_ALREADY_RESERVED',
            'errors' => null,
            'context' => [
                'request_id' => 'req_biz_409',
                'offer_id' => 77,
            ],
        ]);

    expect($businessResponse->json('context'))
        ->not->toHaveKey('db_lock_wait_ms')
        ->not->toHaveKey('internal_node');

    Log::shouldNotHaveReceived('error');

    // 2. La excepción inesperada (500) SÍ se registra en los logs con la traza real,
    // pero el cliente recibe únicamente un mensaje genérico seguro y su request_id.
    $crashResponse = $this->withHeader('X-Request-ID', 'req_crash_500')
        ->getJson('/api/test-unexpected-failure');

    $crashResponse->assertStatus(500)
        ->assertHeader('X-Request-ID', 'req_crash_500')
        ->assertJson([
            'message' => 'Ocurrió un error interno en el servidor.',
            'error' => 'Ocurrió un error interno en el servidor.',
            'error_code' => 'INTERNAL_ERROR',
            'errors' => null,
            'context' => [
                'request_id' => 'req_crash_500',
            ],
        ]);

    expect($crashResponse->getContent())
        ->not->toContain('secret_internal_table')
        ->not->toContain('SQLSTATE');

    Log::shouldHaveReceived('error')->once();
});
