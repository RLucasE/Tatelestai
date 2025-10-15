<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;

class SellControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $customer;
    protected User $seller;
    protected User $adm;
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

        $this->adm = User::factory()->withRole(UserRole::ADMIN->value)->create([
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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function it_can_prepare_buy_offers_successfully(): void
    {
        $this->actingAs($this->customer);

        $requestData = [
            'food_establishment_id' => $this->establishment->id,
            'offers' => [
                [
                    'id' => $this->offer->id,
                    'quantity' => 2,
                ]
            ]
        ];

        $response = $this->postJson('/api/prepare-purchase', $requestData);



        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'purchase_token',
                    'offers',
                    'total_offers',
                    'food_establishment_id',
                    'expires_at',
                ]
            ])
            ->assertJson([
                'message' => 'Purchase preparation completed successfully',
                'data' => [
                    'total_offers' => 1,
                    'food_establishment_id' => $this->establishment->id,
                ]
            ]);

        $purchaseToken = $response->json('data.purchase_token');
        $this->assertNotNull($purchaseToken);
        $this->assertTrue(session()->has('purchase_' . $purchaseToken));

        $sessionData = session()->get('purchase_' . $purchaseToken);
        $this->assertArrayHasKey('preparePurchaseDTO', $sessionData);
        $this->assertArrayHasKey('expires_at', $sessionData);
        $this->assertEquals($this->establishment->id, $sessionData['preparePurchaseDTO']->food_establishment_id);
        $this->assertCount(1, $sessionData['preparePurchaseDTO']->offers);
    }

    #[Test]
    public function it_can_buy_offers_successfully(): void
    {
        $this->actingAs($this->customer);

        // Primero preparar la compra
        $requestData = [
            'food_establishment_id' => $this->establishment->id,
            'offers' => [
                [
                    'id' => $this->offer->id,
                    'quantity' => 2,
                ]
            ]
        ];

        $prepareResponse = $this->postJson('/api/prepare-purchase', $requestData);
        $purchaseToken = $prepareResponse->json('data.purchase_token');

        // Ahora realizar la compra
        $buyResponse = $this->postJson('/api/buy-offers', [
            'purchase_token' => $purchaseToken
        ]);

        $buyResponse->assertStatus(200)
            ->assertJson([
                'message' => 'Compra realizada con éxito'
            ])
            ->assertJsonStructure([
                'message',
                'data' => [
                    'food_establishment_id',
                    'offers'
                ]
            ]);

        // Verificar que se creó la venta en la base de datos
        $this->assertDatabaseHas('sells', [
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
        ]);

        // Verificar que se crearon los detalles de venta
        $this->assertDatabaseHas('sell_details', [
            'offer_id' => $this->offer->id,
        ]);
    }

    #[Test]
    public function it_fails_to_buy_offers_with_invalid_token(): void
    {
        $this->actingAs($this->customer);

        $response = $this->postJson('/api/buy-offers', [
            'purchase_token' => 'invalid_token_123'
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Token de compra inválido o expirado'
            ]);

        // Verificar que no se creó ninguna venta
        $this->assertDatabaseMissing('sells', [
            'bought_by' => $this->customer->id,
        ]);
    }

    #[Test]
    public function it_fails_to_buy_offers_without_token(): void
    {
        $this->actingAs($this->customer);

        $response = $this->postJson('/api/buy-offers', []);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Token de compra inválido o expirado'
            ]);
    }

    #[Test]
    public function it_fails_to_buy_offers_with_expired_token(): void
    {
        $this->actingAs($this->customer);

        // Preparar la compra
        $requestData = [
            'food_establishment_id' => $this->establishment->id,
            'offers' => [
                [
                    'id' => $this->offer->id,
                    'quantity' => 2,
                ]
            ]
        ];

        $prepareResponse = $this->postJson('/api/prepare-purchase', $requestData);
        $purchaseToken = $prepareResponse->json('data.purchase_token');

        // Modificar manualmente la sesión para que expire
        $sessionData = session()->get('purchase_' . $purchaseToken);
        $sessionData['expires_at'] = now()->subMinutes(10); // Expirado hace 10 minutos
        session()->put('purchase_' . $purchaseToken, $sessionData);

        // Intentar comprar con token expirado
        $buyResponse = $this->postJson('/api/buy-offers', [
            'purchase_token' => $purchaseToken
        ]);

        $buyResponse->assertStatus(400)
            ->assertJson([
                'error' => 'El tiempo para confirmar la compra ha expirado'
            ]);

        // Verificar que no se creó ninguna venta
        $this->assertDatabaseMissing('sells', [
            'bought_by' => $this->customer->id,
        ]);

        // Verificar que el token fue eliminado de la sesión
        $this->assertFalse(session()->has('purchase_' . $purchaseToken));
    }

    #[Test]
    public function it_can_check_valid_customer_code(): void
    {
        $this->actingAs($this->seller);

        // Primero crear una venta
        $pickupCode = '2X3Y-4Z5A-6B7C';
        $sell = \App\Models\Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => $pickupCode,
        ]);

        // Crear detalles de venta
        \App\Models\SellDetail::factory()->create([
            'sell_id' => $sell->id,
            'offer_id' => $this->offer->id,
            'offer_quantity' => 2,
            'product_quantity' => 1,
            'product_price' => 400,
            'product_name' => 'chincong',
            'product_description' => 'aojefjijoijioajeoifj'
        ]);


        \App\Models\SellDetail::factory()->create([
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
        $sell = \App\Models\Sell::factory()->create([
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
    public function it_can_get_purchase_code_for_customer(): void
    {
        $this->actingAs($this->customer);

        // Crear una venta con código de retiro
        $pickupCode = 'ABC123-DEF456';
        $sell = \App\Models\Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => $pickupCode,
        ]);

        // Crear detalles de venta
        \App\Models\SellDetail::factory()->create([
            'sell_id' => $sell->id,
            'offer_id' => $this->offer->id,
            'offer_quantity' => 2,
            'product_quantity' => 1,
            'product_price' => 400,
            'product_name' => 'Test nroduct',
            'product_description' => 'Test Description'
        ]);

        // Obtener el código de retiro
        $response = $this->getJson("/api/purchase-code/{$sell->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'sell_id',
                    'pickup_code',
                    'establishment',
                    'created_at',
                ]
            ])
            ->assertJson([
                'message' => 'Código de retiro obtenido exitosamente',
                'data' => [
                    'pickup_code' => $pickupCode,
                ]
            ]);
    }

    #[Test]
    public function it_fails_to_get_purchase_code_for_non_existent_sell(): void
    {
        $this->actingAs($this->customer);

        $response = $this->getJson("/api/purchase-code/99999");

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Compra no encontrada'
            ]);
    }

    #[Test]
    public function it_fails_to_get_purchase_code_for_different_customer(): void
    {
        $this->actingAs($this->customer);

        // Crear otro customer
        $anotherCustomer = User::factory()->withRole(UserRole::CUSTOMER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        // Crear una venta para el otro customer
        $pickupCode = 'XYZ789-UVW012';
        $sell = \App\Models\Sell::factory()->create([
            'bought_by' => $anotherCustomer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => $pickupCode,
        ]);

        // Intentar obtener el código con el customer original
        $response = $this->getJson("/api/purchase-code/{$sell->id}");

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'Compra no encontrada'
            ]);
    }

    #[Test]
    public function it_fails_to_get_purchase_code_without_authentication(): void
    {
        $sell = \App\Models\Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'TEST-CODE-123',
        ]);

        $response = $this->getJson("/api/purchase-code/{$sell->id}");

        $response->assertStatus(401);
    }

    #[Test]
    public function it_does_not_expose_sensitive_data_in_seller_sells(): void
    {
        $this->actingAs($this->seller);

        // Crear múltiples ventas con códigos de pickup
        $pickupCode1 = 'SECRET-CODE-001';
        $pickupCode2 = 'SECRET-CODE-002';

        $sell1 = \App\Models\Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => $pickupCode1,
        ]);

        $sell2 = \App\Models\Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => $pickupCode2,
        ]);

        // Crear detalles de venta
        \App\Models\SellDetail::factory()->create([
            'sell_id' => $sell1->id,
            'offer_id' => $this->offer->id,
            'offer_quantity' => 2,
            'product_quantity' => 1,
            'product_price' => 400,
            'product_name' => 'Test Product 1',
            'product_description' => 'Test Description 1'
        ]);

        \App\Models\SellDetail::factory()->create([
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
    public function it_can_get_last_sells_in_24_hours_with_2_hour_intervals(): void
    {
        $this->actingAs($this->adm);

        $currentTime = now()->setTime(16, 0, 0);
        $this->travelTo($currentTime);

        $this->travelTo($currentTime->copy()->subDay()->setTime(17, 30, 0));
        $sell1 = \App\Models\Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'created_at' => now(),
        ]);

        $this->travelTo($currentTime->copy()->subDay()->setTime(17, 45, 0));
        $sell2 = \App\Models\Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'created_at' => now(),
        ]);

        $this->travelTo($currentTime->copy()->subDay()->setTime(19, 45, 0));
        $sell3 = \App\Models\Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'created_at' => now(),
        ]);

        $this->travelTo($currentTime->copy()->subDay()->setTime(21, 15, 0));
        $sell4 = \App\Models\Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'created_at' => now(),
        ]);

        $this->travelTo($currentTime->copy()->setTime(8, 30, 0));
        $sell5 = \App\Models\Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'created_at' => now(),
        ]);

        $this->travelTo($currentTime->copy()->setTime(14, 20, 0));
        $sell6 = \App\Models\Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'created_at' => now(),
        ]);

        $this->travelTo($currentTime);

        $response = $this->getJson('/api/adm/last-sells');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'from',
                        'to',
                        'count'
                    ]
                ]
            ]);

        $data = $response->json('data');

        $this->assertCount(12, $data);

        foreach ($data as $interval) {
            $this->assertArrayHasKey('from', $interval);
            $this->assertArrayHasKey('to', $interval);
            $this->assertArrayHasKey('count', $interval);
        }

        $this->assertEquals(6, array_sum(array_column($data, 'count')));

        $intervals = collect($data);

        $interval16to18 = $intervals->first(function ($item) {
            return $item['from'] === '16:00' && $item['to'] === '18:00';
        });
        $this->assertNotNull($interval16to18);
        $this->assertEquals(2, $interval16to18['count']);

        $interval18to20 = $intervals->first(function ($item) {
            return $item['from'] === '18:00' && $item['to'] === '20:00';
        });
        $this->assertNotNull($interval18to20);
        $this->assertEquals(1, $interval18to20['count']);

        $interval20to22 = $intervals->first(function ($item) {
            return $item['from'] === '20:00' && $item['to'] === '22:00';
        });
        $this->assertNotNull($interval20to22);
        $this->assertEquals(1, $interval20to22['count']);

        $interval8to10 = $intervals->first(function ($item) {
            return $item['from'] === '08:00' && $item['to'] === '10:00';
        });
        $this->assertNotNull($interval8to10);
        $this->assertEquals(1, $interval8to10['count']);

        $interval14to16 = $intervals->first(function ($item) {
            return $item['from'] === '14:00' && $item['to'] === '16:00';
        });
        $this->assertNotNull($interval14to16);
        $this->assertEquals(1, $interval14to16['count']);

        $emptyIntervals = $intervals->where('count', 0);
        $this->assertGreaterThan(0, $emptyIntervals->count());
    }
    #[Test]
    public function it_returns_empty_intervals_when_no_sells_in_last_24_hours(): void
    {
        $this->actingAs($this->adm);

        $this->travelTo(now()->subHours(25));
        \App\Models\Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'created_at' => now(),
        ]);
        $this->travelBack();

        $response = $this->getJson('/api/adm/last-sells');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'from',
                        'to',
                        'count'
                    ]
                ]
            ])
            ->assertJson([
                'message' => 'Ventas de las últimas 24 horas obtenidas exitosamente'
            ]);

        $data = $response->json('data');

        $this->assertCount(12, $data);

        foreach ($data as $interval) {
            $this->assertArrayHasKey('from', $interval);
            $this->assertArrayHasKey('to', $interval);
            $this->assertArrayHasKey('count', $interval);
            $this->assertEquals(0, $interval['count']);
        }

        $this->assertEquals(0, array_sum(array_column($data, 'count')));
    }
}
