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

        $response->dump();

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'sell_id',
                    'pickup_code',
                    'customer',
                    'total_price',
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

        $response->dump();

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pickup_code']);
    }
}
