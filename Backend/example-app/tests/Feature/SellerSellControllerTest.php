<?php

namespace Tests\Feature;

use App\Enums\SellState;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\Sell;
use App\Models\SellDetail;
use App\Models\User;
use App\Notifications\RealtimeOrderNotification;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SellerSellControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $customer;

    protected User $seller;

    protected FoodEstablishment $establishment;

    protected Offer $offer;

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

        $establishmentType = EstablishmentType::inRandomOrder()->first();

        $this->establishment = FoodEstablishment::factory()->create([
            'user_id' => $this->seller->id,
            'establishment_type_id' => $establishmentType->id,
        ]);

        $this->offer = Offer::factory()->active()->create([
            'food_establishment_id' => $this->establishment->id,
            'quantity' => 10,
            'price' => 2000,
            'expiration_datetime' => now()->addDays(5),
        ]);
    }

    #[Test]
    public function it_can_check_valid_customer_code(): void
    {
        $this->actingAs($this->seller);

        $pickupCode = '2X3Y-4Z5A-6B7C';
        $sell = Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => $pickupCode,
        ]);

        // Crear detalles de venta
        SellDetail::factory()->create([
            'sell_id' => $sell->id,
            'offer_id' => $this->offer->id,
            'offer_quantity' => 2,
            'pack_price' => 400,
            'pack_name' => 'Pack Especial 1',
            'pack_description' => 'Descripción 1',
        ]);

        SellDetail::factory()->create([
            'sell_id' => $sell->id,
            'offer_id' => $this->offer->id,
            'offer_quantity' => 2,
            'pack_price' => 500,
            'pack_name' => 'Pack Especial 2',
            'pack_description' => 'Descripción 2',
        ]);

        // Verificar el código
        $response = $this->postJson('/api/check-customer-code', [
            'pickup_code' => $pickupCode,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'sell_id',
                    'pickup_code',
                    'customer',
                    'offers' => [
                        '*' => [
                            'offer_id',
                            'offer_title',
                            'offer_quantity',
                            'pack_name',
                            'pack_description',
                            'pack_price',
                        ],
                    ],
                    'created_at',
                ],
            ])
            ->assertJson([
                'message' => 'Código válido',
                'data' => [
                    'sell_id' => $sell->id,
                    'pickup_code' => $pickupCode,
                ],
            ]);
    }

    #[Test]
    public function it_can_check_valid_customer_code_using_qr_payload(): void
    {
        $this->actingAs($this->seller);

        $pickupCode = '2X3Y-4Z5A-6B7C';
        $sell = Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => $pickupCode,
        ]);

        SellDetail::factory()->create([
            'sell_id' => $sell->id,
            'offer_id' => $this->offer->id,
            'offer_quantity' => 1,
            'pack_price' => 500,
            'pack_name' => 'Pack QR Test',
            'pack_description' => 'Test Desc',
        ]);

        // Simular escaneo de QR con payload TAT:<pickup_code>:<sell_id>
        $qrPayload = "TAT:{$pickupCode}:{$sell->id}";

        $response = $this->postJson('/api/check-customer-code', [
            'pickup_code' => $qrPayload,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Código válido',
                'data' => [
                    'sell_id' => $sell->id,
                    'pickup_code' => $pickupCode,
                ],
            ]);
    }

    #[Test]
    public function it_fails_to_check_invalid_customer_code(): void
    {
        $this->actingAs($this->seller);

        $response = $this->postJson('/api/check-customer-code', [
            'pickup_code' => 'INVALID-CODE',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Código de pickup no encontrado',
            ]);
    }

    #[Test]
    public function it_fails_to_check_code_from_different_establishment(): void
    {
        $this->actingAs($this->seller);

        // Crear otro establecimiento y seller
        $anotherSeller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $anotherEstablishment = FoodEstablishment::factory()->create([
            'user_id' => $anotherSeller->id,
            'establishment_type_id' => EstablishmentType::inRandomOrder()->first()->id,
        ]);

        // Crear una venta para el otro establecimiento
        $pickupCode = '2X3Y-4Z5A-6B7C';
        $sell = Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $anotherEstablishment->id,
            'pickup_code' => $pickupCode,
        ]);

        // Intentar verificar el código con el seller original
        $response = $this->postJson('/api/check-customer-code', [
            'pickup_code' => $pickupCode,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'No tienes permiso para verificar este código',
            ]);
    }

    #[Test]
    public function it_fails_to_check_code_without_authentication(): void
    {
        $response = $this->postJson('/api/check-customer-code', [
            'pickup_code' => '2X3Y-4Z5A-6B7C',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function it_fails_to_check_code_without_pickup_code(): void
    {
        $this->actingAs($this->seller);

        $response = $this->postJson('/api/check-customer-code', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pickup_code']);
    }

    #[Test]
    public function it_fails_to_check_expired_sell(): void
    {
        $this->actingAs($this->seller);

        $pickupCode = '2X3Y-4Z5A-6B7C';
        $sell = Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => $pickupCode,
            'max_pickup_datetime' => now()->subHours(1), // Venta expirada hace 1 hora
        ]);

        // Crear detalles de venta
        SellDetail::factory()->create([
            'sell_id' => $sell->id,
            'offer_id' => $this->offer->id,
            'offer_quantity' => 2,
            'pack_price' => 400,
            'pack_name' => 'Pack 1',
            'pack_description' => 'Descripción 1',
        ]);

        // Intentar verificar el código expirado
        $response = $this->postJson('/api/check-customer-code', [
            'pickup_code' => $pickupCode,
        ]);

        $response->assertStatus(410)
            ->assertJson([
                'error' => 'El tiempo para recoger esta venta ha expirado',
            ]);
    }

    #[Test]
    public function it_does_not_expose_sensitive_data_in_seller_sells(): void
    {
        $this->actingAs($this->seller);

        // Crear múltiples ventas con códigos de pickup
        $pickupCode1 = 'SECRET-CODE-001';
        $pickupCode2 = 'SECRET-CODE-002';

        $sell1 = Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => $pickupCode1,
        ]);

        $sell2 = Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => $pickupCode2,
        ]);

        // Crear detalles de venta
        SellDetail::factory()->create([
            'sell_id' => $sell1->id,
            'offer_id' => $this->offer->id,
            'offer_quantity' => 2,
            'pack_price' => 400,
            'pack_name' => 'Test Pack 1',
            'pack_description' => 'Test Description 1',
        ]);

        SellDetail::factory()->create([
            'sell_id' => $sell2->id,
            'offer_id' => $this->offer->id,
            'offer_quantity' => 1,
            'pack_price' => 200,
            'pack_name' => 'Test Pack 2',
            'pack_description' => 'Test Description 2',
        ]);

        $response = $this->getJson('/api/sells');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'sold_by',
                    'sell_details' => [
                        '*' => [
                            'id',
                            'sell_id',
                            'offer_id',
                            'offer_quantity',
                            'pack_price',
                            'pack_name',
                            'pack_description',
                            'created_at',
                            'updated_at',
                            'offer' => [
                                'id',
                                'title',
                                'description',
                                'price',
                                'quantity',
                                'state',
                                'expiration_datetime',
                            ],
                        ],
                    ],
                ],
            ]);

        $responseData = $response->json();

        $this->assertNotEmpty($responseData);
        $this->assertCount(2, $responseData);

        foreach ($responseData as $sell) {
            $this->assertArrayNotHasKey('pickup_code', $sell,
                'El pickup_code no debe estar expuesto en sellerSells');

            $this->assertArrayNotHasKey('bought_by', $sell,
                'El bought_by (customer ID) no debe estar expuesto en sellerSells');

            $this->assertArrayHasKey('id', $sell);
            $this->assertArrayHasKey('created_at', $sell);
            $this->assertArrayHasKey('sell_details', $sell);
        }

        $responseString = json_encode($responseData);
        $this->assertStringNotContainsString($pickupCode1, $responseString,
            'El pickup_code no debe estar presente en la respuesta JSON');
        $this->assertStringNotContainsString($pickupCode2, $responseString,
            'El pickup_code no debe estar presente en la respuesta JSON');
    }

    #[Test]
    public function it_can_complete_sell_successfully(): void
    {
        $this->actingAs($this->seller);

        // Crear una venta pendiente de recoger
        $pickupCode = 'ABC-123-XYZ';
        $sell = Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => $pickupCode,
            'is_picked_up' => false,
            'picked_up_at' => null,
        ]);

        // Crear detalles de venta
        SellDetail::factory()->create([
            'sell_id' => $sell->id,
            'offer_id' => $this->offer->id,
            'offer_quantity' => 2,
            'pack_price' => 400,
            'pack_name' => 'Test Pack',
            'pack_description' => 'Test Description',
        ]);

        // Verificar estado inicial
        $this->assertFalse($sell->is_picked_up);
        $this->assertNull($sell->picked_up_at);

        // Completar la venta con el código correcto
        $response = $this->postJson("/api/complete-sell/{$sell->id}", [
            'pick_up_code' => $pickupCode,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Venta completada exitosamente',
            ])
            ->assertJsonStructure([
                'message',
                'data' => [
                    'sell_id',
                    'is_picked_up',
                    'picked_up_at',
                ],
            ]);

        // Verificar en base de datos que se actualizó
        $this->assertDatabaseHas('sells', [
            'id' => $sell->id,
            'is_picked_up' => true,
        ]);

        // Verificar que picked_up_at tiene una fecha
        $sell->refresh();
        $this->assertTrue($sell->is_picked_up);
        $this->assertNotNull($sell->picked_up_at);
    }

    #[Test]
    public function it_can_complete_sell_successfully_using_qr_payload(): void
    {
        $this->actingAs($this->seller);

        $pickupCode = 'ABC-123-XYZ';
        $sell = Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => $pickupCode,
            'is_picked_up' => false,
            'picked_up_at' => null,
        ]);

        SellDetail::factory()->create([
            'sell_id' => $sell->id,
            'offer_id' => $this->offer->id,
            'offer_quantity' => 1,
            'pack_price' => 400,
            'pack_name' => 'Test Pack',
            'pack_description' => 'Test Description',
        ]);

        // Simular escaneo de QR con payload TAT:<pickup_code>:<sell_id>
        $qrPayload = "TAT:{$pickupCode}:{$sell->id}";

        $response = $this->postJson("/api/complete-sell/{$sell->id}", [
            'pick_up_code' => $qrPayload,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Venta completada exitosamente',
            ]);

        $this->assertDatabaseHas('sells', [
            'id' => $sell->id,
            'is_picked_up' => true,
        ]);
    }

    #[Test]
    public function it_fails_to_complete_sell_with_incorrect_pickup_code(): void
    {
        $this->actingAs($this->seller);

        // Crear una venta pendiente de recoger
        $pickupCode = 'ABC-123-XYZ';
        $sell = Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => $pickupCode,
            'is_picked_up' => false,
            'picked_up_at' => null,
        ]);

        SellDetail::factory()->create([
            'sell_id' => $sell->id,
            'offer_id' => $this->offer->id,
            'offer_quantity' => 2,
            'pack_price' => 400,
            'pack_name' => 'Test Pack',
            'pack_description' => 'Test Description',
        ]);

        // Intentar completar la venta con un código incorrecto
        $response = $this->postJson("/api/complete-sell/{$sell->id}", [
            'pick_up_code' => 'WRONG-CODE',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Código de pickup incorrecto',
            ]);

        // Verificar que NO se actualizó
        $this->assertDatabaseHas('sells', [
            'id' => $sell->id,
            'is_picked_up' => false,
        ]);

        $sell->refresh();
        $this->assertFalse($sell->is_picked_up);
        $this->assertNull($sell->picked_up_at);
    }

    #[Test]
    public function it_fails_to_complete_sell_without_pickup_code(): void
    {
        $this->actingAs($this->seller);

        // Crear una venta pendiente de recoger
        $pickupCode = 'ABC-123-XYZ';
        $sell = Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => $pickupCode,
            'is_picked_up' => false,
            'picked_up_at' => null,
        ]);

        // Crear detalles de venta
        SellDetail::factory()->create([
            'sell_id' => $sell->id,
            'offer_id' => $this->offer->id,
            'offer_quantity' => 2,
            'pack_price' => 400,
            'pack_name' => 'Test Pack',
            'pack_description' => 'Test Description',
        ]);

        // Intentar completar la venta sin enviar el código
        $response = $this->postJson("/api/complete-sell/{$sell->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pick_up_code']);

        // Verificar que NO se actualizó
        $this->assertDatabaseHas('sells', [
            'id' => $sell->id,
            'is_picked_up' => false,
        ]);
    }

    #[Test]
    public function it_fails_to_complete_sell_from_different_establishment(): void
    {
        $this->actingAs($this->seller);

        // Crear otro establecimiento y seller
        $anotherSeller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $anotherEstablishment = FoodEstablishment::factory()->create([
            'user_id' => $anotherSeller->id,
            'establishment_type_id' => EstablishmentType::inRandomOrder()->first()->id,
        ]);

        // Crear una venta para el otro establecimiento
        $pickupCode = 'TEST-CODE';
        $sell = Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $anotherEstablishment->id,
            'pickup_code' => $pickupCode,
            'is_picked_up' => false,
            'picked_up_at' => null,
        ]);

        // Intentar completar la venta con el seller original
        $response = $this->postJson("/api/complete-sell/{$sell->id}", [
            'pick_up_code' => $pickupCode,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'No tienes permiso para completar esta venta',
            ]);

        // Verificar que no se actualizó
        $this->assertDatabaseHas('sells', [
            'id' => $sell->id,
            'is_picked_up' => false,
        ]);
    }

    #[Test]
    public function it_fails_to_complete_non_existent_sell(): void
    {
        $this->actingAs($this->seller);

        $response = $this->postJson('/api/complete-sell/99999', [
            'pick_up_code' => 'ANY-CODE',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Venta no encontrada',
            ]);
    }

    #[Test]
    public function it_fails_to_complete_sell_without_authentication(): void
    {
        $pickupCode = 'TEST-CODE';
        $sell = Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => $pickupCode,
            'is_picked_up' => false,
            'picked_up_at' => null,
        ]);

        $response = $this->postJson("/api/complete-sell/{$sell->id}", [
            'pick_up_code' => $pickupCode,
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function it_fails_to_complete_expired_sell(): void
    {
        $this->actingAs($this->seller);

        // Crear una venta expirada
        $pickupCode = 'ABC-123-XYZ';
        $sell = Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => $pickupCode,
            'is_picked_up' => false,
            'picked_up_at' => null,
            'max_pickup_datetime' => now()->subHours(2), // Expirada hace 2 horas
        ]);

        // Crear detalles de venta
        SellDetail::factory()->create([
            'sell_id' => $sell->id,
            'offer_id' => $this->offer->id,
            'offer_quantity' => 2,
            'pack_price' => 400,
            'pack_name' => 'Test Pack',
            'pack_description' => 'Test Description',
        ]);

        // Intentar completar la venta expirada
        $response = $this->postJson("/api/complete-sell/{$sell->id}", [
            'pick_up_code' => $pickupCode,
        ]);

        $response->assertStatus(410)
            ->assertJson([
                'error' => 'El tiempo para recoger esta venta ha expirado',
            ]);

        // Verificar que NO se actualizó
        $this->assertDatabaseHas('sells', [
            'id' => $sell->id,
            'is_picked_up' => false,
        ]);

        $sell->refresh();
        $this->assertFalse($sell->is_picked_up);
        $this->assertNull($sell->picked_up_at);
    }

    #[Test]
    public function it_fails_when_completing_sell_with_cross_sale_qr_payload(): void
    {
        $this->actingAs($this->seller);

        $sell1 = Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'CODE-SELL-001',
            'is_picked_up' => false,
            'state' => SellState::PENDING->value,
        ]);

        $sell2 = Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'CODE-SELL-002',
            'is_picked_up' => false,
            'state' => SellState::PENDING->value,
        ]);

        SellDetail::factory()->create(['sell_id' => $sell1->id, 'offer_id' => $this->offer->id]);
        SellDetail::factory()->create(['sell_id' => $sell2->id, 'offer_id' => $this->offer->id]);

        // Intentar completar sell1 usando el QR de sell2
        $crossQrPayload = "TAT:{$sell2->pickup_code}:{$sell2->id}";
        $response = $this->postJson("/api/complete-sell/{$sell1->id}", [
            'pick_up_code' => $crossQrPayload,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Código de pickup incorrecto',
            ]);

        $this->assertDatabaseHas('sells', ['id' => $sell1->id, 'is_picked_up' => false]);
        $this->assertDatabaseHas('sells', ['id' => $sell2->id, 'is_picked_up' => false]);
    }

    #[Test]
    public function it_fails_when_scanning_or_completing_qr_from_another_establishment(): void
    {
        $this->actingAs($this->seller);

        $anotherSeller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $anotherEstablishment = FoodEstablishment::factory()->create([
            'user_id' => $anotherSeller->id,
            'establishment_type_id' => EstablishmentType::inRandomOrder()->first()->id,
        ]);

        $otherSell = Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $anotherEstablishment->id,
            'pickup_code' => 'OTHER-EST-CODE',
            'is_picked_up' => false,
            'state' => SellState::PENDING->value,
        ]);

        $qrPayload = "TAT:{$otherSell->pickup_code}:{$otherSell->id}";

        // 1. Escaneo en check-customer-code
        $checkResponse = $this->postJson('/api/check-customer-code', [
            'pickup_code' => $qrPayload,
        ]);

        $checkResponse->assertStatus(403)
            ->assertJson([
                'error' => 'No tienes permiso para verificar este código',
            ]);

        // 2. Intento de completar en complete-sell
        $completeResponse = $this->postJson("/api/complete-sell/{$otherSell->id}", [
            'pick_up_code' => $qrPayload,
        ]);

        $completeResponse->assertStatus(403)
            ->assertJson([
                'error' => 'No tienes permiso para completar esta venta',
            ]);

        $this->assertDatabaseHas('sells', ['id' => $otherSell->id, 'is_picked_up' => false]);
    }

    #[Test]
    public function it_fails_to_complete_when_pickup_window_expires_between_check_and_complete(): void
    {
        $this->actingAs($this->seller);

        $pickupCode = 'BOUNDARY-TEST-CODE';
        $sell = Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => $pickupCode,
            'is_picked_up' => false,
            'state' => SellState::PENDING->value,
            'max_pickup_datetime' => now()->addSeconds(5),
        ]);

        SellDetail::factory()->create([
            'sell_id' => $sell->id,
            'offer_id' => $this->offer->id,
            'offer_quantity' => 1,
            'pack_price' => 300,
            'pack_name' => 'Boundary Pack',
        ]);

        $qrPayload = "TAT:{$pickupCode}:{$sell->id}";

        // Paso 1: En ventana horaria válida -> éxito
        $checkResponse = $this->postJson('/api/check-customer-code', [
            'pickup_code' => $qrPayload,
        ]);
        $checkResponse->assertStatus(200)
            ->assertJson(['message' => 'Código válido']);

        // El tiempo avanza 10 segundos (expirando la ventana de retiro)
        $this->travel(10)->seconds();

        // Paso 2: Al presionar confirmar -> 410 Gone
        $completeResponse = $this->postJson("/api/complete-sell/{$sell->id}", [
            'pick_up_code' => $qrPayload,
        ]);

        $completeResponse->assertStatus(410)
            ->assertJson([
                'error' => 'El tiempo para recoger esta venta ha expirado',
            ]);

        $this->assertDatabaseHas('sells', [
            'id' => $sell->id,
            'is_picked_up' => false,
        ]);
    }

    #[Test]
    public function it_strictly_enforces_whitelist_states_on_qr_operations(): void
    {
        $this->actingAs($this->seller);

        $nonPendingStates = [
            SellState::CANCELLED,
            SellState::PICKED_UP,
            SellState::EXPIRED,
            SellState::CONFIRMED,
            SellState::READY,
        ];

        foreach ($nonPendingStates as $state) {
            $code = 'STATE-'.strtoupper($state->value);
            $sell = Sell::factory()->create([
                'bought_by' => $this->customer->id,
                'sold_by' => $this->establishment->id,
                'pickup_code' => $code,
                'is_picked_up' => $state === SellState::PICKED_UP,
                'state' => $state->value,
            ]);

            SellDetail::factory()->create([
                'sell_id' => $sell->id,
                'offer_id' => $this->offer->id,
                'offer_quantity' => 1,
                'pack_price' => 100,
                'pack_name' => "Pack {$state->value}",
            ]);

            $qrPayload = "TAT:{$code}:{$sell->id}";

            // Check endpoint debe rechazar con 422
            $checkRes = $this->postJson('/api/check-customer-code', [
                'pickup_code' => $qrPayload,
            ]);
            $checkRes->assertStatus(422)
                ->assertJson(['error' => 'El pedido no se encuentra disponible para ser retirado.']);

            // Complete endpoint debe rechazar con 404 (porque ValidateCompleteSellAction busca state = PENDING)
            $completeRes = $this->postJson("/api/complete-sell/{$sell->id}", [
                'pick_up_code' => $qrPayload,
            ]);
            $completeRes->assertStatus(404)
                ->assertJson(['error' => 'Venta no encontrada']);
        }
    }

    #[Test]
    public function it_prevents_replay_attack_and_duplicate_notifications_on_double_qr_redemption(): void
    {
        Notification::fake();
        $this->actingAs($this->seller);

        $pickupCode = 'REPLAY-TEST-CODE';
        $sell = Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => $pickupCode,
            'is_picked_up' => false,
            'state' => SellState::PENDING->value,
        ]);

        SellDetail::factory()->create([
            'sell_id' => $sell->id,
            'offer_id' => $this->offer->id,
            'offer_quantity' => 1,
            'pack_price' => 500,
            'pack_name' => 'Replay Pack',
        ]);

        $qrPayload = "TAT:{$pickupCode}:{$sell->id}";

        // 1er canje -> exitoso
        $firstComplete = $this->postJson("/api/complete-sell/{$sell->id}", [
            'pick_up_code' => $qrPayload,
        ]);
        $firstComplete->assertStatus(200);

        Notification::assertSentTo(
            $this->customer,
            RealtimeOrderNotification::class,
            function (RealtimeOrderNotification $notification) use ($sell) {
                return $notification->eventType === 'order_picked_up'
                    && $notification->sellId === $sell->id;
            }
        );
        Notification::assertSentTimes(RealtimeOrderNotification::class, 1);

        // 2do intento con el mismo QR (Replay attack) en complete-sell -> 404
        $secondComplete = $this->postJson("/api/complete-sell/{$sell->id}", [
            'pick_up_code' => $qrPayload,
        ]);
        $secondComplete->assertStatus(404);

        // 2do intento en check-customer-code -> 422
        $secondCheck = $this->postJson('/api/check-customer-code', [
            'pickup_code' => $qrPayload,
        ]);
        $secondCheck->assertStatus(422);

        // La notificación NO debe haberse duplicado
        Notification::assertSentTimes(RealtimeOrderNotification::class, 1);
    }

    #[Test]
    public function it_fails_gracefully_when_scanning_external_or_arbitrary_qr_payloads(): void
    {
        $this->actingAs($this->seller);

        $externalPayloads = [
            'https://tatelestai.com/promo/summer-pack',
            'WIFI:S:MyCafeWifi;T:WPA;P:secret123;;',
            'MECARD:N:Juan Perez;TEL:123456789;;',
            'random-text-without-meaning-12345',
        ];

        foreach ($externalPayloads as $payload) {
            $response = $this->postJson('/api/check-customer-code', [
                'pickup_code' => $payload,
            ]);

            $response->assertStatus(404)
                ->assertJson([
                    'error' => 'Código de pickup no encontrado',
                ]);
        }
    }

    #[Test]
    public function it_forbids_customer_or_unregistered_seller_from_scanning_qr(): void
    {
        $pickupCode = 'ROLE-TEST-CODE';
        $sell = Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => $pickupCode,
            'state' => SellState::PENDING->value,
        ]);
        $qrPayload = "TAT:{$pickupCode}:{$sell->id}";

        // 1. Cliente intentando acceder a rutas de mostrador del vendedor -> 403 (middleware role:seller)
        $this->actingAs($this->customer);

        $this->postJson('/api/check-customer-code', [
            'pickup_code' => $qrPayload,
        ])->assertStatus(403);

        $this->postJson("/api/complete-sell/{$sell->id}", [
            'pick_up_code' => $qrPayload,
        ])->assertStatus(403);

        // 2. Vendedor sin establecimiento gastronómico registrado
        $sellerWithoutEst = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $this->actingAs($sellerWithoutEst);

        // En check-customer-code -> 403 (No tienes permiso para verificar este código)
        $this->postJson('/api/check-customer-code', [
            'pickup_code' => $qrPayload,
        ])->assertStatus(403)
            ->assertJson(['error' => 'No tienes permiso para verificar este código']);

        // En complete-sell -> 404 (Establecimiento no encontrado)
        $this->postJson("/api/complete-sell/{$sell->id}", [
            'pick_up_code' => $qrPayload,
        ])->assertStatus(404)
            ->assertJson(['error' => 'Establecimiento no encontrado']);
    }
}
