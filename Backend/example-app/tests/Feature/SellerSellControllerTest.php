<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Sell;
use App\Models\SellDetail;
use App\Models\User;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SellerSellControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $customer;
    protected User $seller;
    protected FoodEstablishment $establishment;
    protected Offer $offer;
    protected Product $product;

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

        $this->product = Product::factory()->create([
            'food_establishment_id' => $this->establishment->id,
        ]);

        $this->offer = Offer::factory()->active()->withProducts(4)->create([
            'food_establishment_id' => $this->establishment->id,
            'quantity' => 10,
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
            'product_quantity' => 1,
            'product_price' => 400,
            'product_name' => 'chincong',
            'product_description' => 'aojefjijoijioajeoifj'
        ]);

        SellDetail::factory()->create([
            'sell_id' => $sell->id,
            'offer_id' => $this->offer->id,
            'offer_quantity' => 2,
            'product_quantity' => 2,
            'product_price' => 5,
            'product_name' => 'Chinese Food',
            'product_description' => 'Delicious Chinese food'
        ]);

        // Verificar el código
        $response = $this->postJson('/api/check-customer-code', [
            'pickup_code' => $pickupCode
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'sell_id',
                    'pickup_code',
                    'customer',
                    'offers',
                    'created_at',
                ]
            ])
            ->assertJson([
                'message' => 'Código válido',
                'data' => [
                    'sell_id' => $sell->id,
                    'pickup_code' => $pickupCode,
                ]
            ]);
    }

    #[Test]
    public function it_fails_to_check_invalid_customer_code(): void
    {
        $this->actingAs($this->seller);

        $response = $this->postJson('/api/check-customer-code', [
            'pickup_code' => 'INVALID-CODE'
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Código de pickup no encontrado'
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
            'pickup_code' => $pickupCode
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'No tienes permiso para verificar este código'
            ]);
    }

    #[Test]
    public function it_fails_to_check_code_without_authentication(): void
    {
        $response = $this->postJson('/api/check-customer-code', [
            'pickup_code' => '2X3Y-4Z5A-6B7C'
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
            'product_quantity' => 1,
            'product_price' => 400,
            'product_name' => 'chincong',
            'product_description' => 'aojefjijoijioajeoifj'
        ]);

        // Intentar verificar el código expirado
        $response = $this->postJson('/api/check-customer-code', [
            'pickup_code' => $pickupCode
        ]);

        $response->assertStatus(410)
            ->assertJson([
                'error' => 'El tiempo para recoger esta venta ha expirado'
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
            'product_quantity' => 1,
            'product_price' => 400,
            'product_name' => 'Test Product 1',
            'product_description' => 'Test Description 1'
        ]);

        SellDetail::factory()->create([
            'sell_id' => $sell2->id,
            'offer_id' => $this->offer->id,
            'offer_quantity' => 1,
            'product_quantity' => 3,
            'product_price' => 200,
            'product_name' => 'Test Product 2',
            'product_description' => 'Test Description 2'
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
                            'product_quantity',
                            'product_price',
                            'product_name',
                            'product_description',
                            'created_at',
                            'updated_at',
                            'offer' => [
                                'id',
                                'title',
                                'description',
                                'price',
                                'quantity',
                                'is_active',
                                'expiration_datetime',
                            ]
                        ]
                    ]
                ]
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
            'product_quantity' => 1,
            'product_price' => 400,
            'product_name' => 'Test Product',
            'product_description' => 'Test Description'
        ]);

        // Verificar estado inicial
        $this->assertFalse($sell->is_picked_up);
        $this->assertNull($sell->picked_up_at);

        // Completar la venta con el código correcto
        $response = $this->postJson("/api/complete-sell/{$sell->id}", [
            'pick_up_code' => $pickupCode
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Venta completada exitosamente'
            ])
            ->assertJsonStructure([
                'message',
                'data' => [
                    'sell_id',
                    'is_picked_up',
                    'picked_up_at',
                ]
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
            'product_quantity' => 1,
            'product_price' => 400,
            'product_name' => 'Test Product',
            'product_description' => 'Test Description'
        ]);

        // Intentar completar la venta con un código incorrecto
        $response = $this->postJson("/api/complete-sell/{$sell->id}", [
            'pick_up_code' => 'WRONG-CODE'
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Código de pickup incorrecto'
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
            'product_quantity' => 1,
            'product_price' => 400,
            'product_name' => 'Test Product',
            'product_description' => 'Test Description'
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
            'pick_up_code' => $pickupCode
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'No tienes permiso para completar esta venta'
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

        $response = $this->postJson("/api/complete-sell/99999", [
            'pick_up_code' => 'ANY-CODE'
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Venta no encontrada'
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
            'pick_up_code' => $pickupCode
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
            'product_quantity' => 1,
            'product_price' => 400,
            'product_name' => 'Test Product',
            'product_description' => 'Test Description'
        ]);

        // Intentar completar la venta expirada
        $response = $this->postJson("/api/complete-sell/{$sell->id}", [
            'pick_up_code' => $pickupCode
        ]);

        $response->assertStatus(410)
            ->assertJson([
                'error' => 'El tiempo para recoger esta venta ha expirado'
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
}
