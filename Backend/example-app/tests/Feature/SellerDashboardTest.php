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
use Carbon\Carbon;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SellerDashboardTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $seller;

    protected FoodEstablishment $establishment;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(EstablishmentTypeSeeder::class);

        $this->seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $establishmentType = EstablishmentType::inRandomOrder()->first();

        $this->establishment = FoodEstablishment::factory()->create([
            'user_id' => $this->seller->id,
            'name' => 'Panadería Test',
            'establishment_type_id' => $establishmentType->id,
        ]);

        $this->customer = User::factory()->withRole(UserRole::CUSTOMER->value)->create([
            'state' => UserState::ACTIVE->value,
            'name' => 'Cliente Feliz',
        ]);
    }

    #[Test]
    public function it_returns_unified_seller_dashboard_successfully(): void
    {
        $this->actingAs($this->seller);

        $response = $this->getJson('/api/seller/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'date',
                    'establishment' => [
                        'id',
                        'name',
                    ],
                    'summary' => [
                        'orders_today',
                        'packs_sold_today',
                        'pending_pickups_today',
                        'completed_pickups_today',
                        'earnings_today',
                    ],
                    'live_stock',
                    'today_pickups',
                ],
            ])
            ->assertJson([
                'message' => 'Dashboard del vendedor obtenido exitosamente',
                'data' => [
                    'establishment' => [
                        'id' => $this->establishment->id,
                        'name' => 'Panadería Test',
                    ],
                ],
            ]);
    }

    #[Test]
    public function it_calculates_summary_metrics_and_live_stock_accurately(): void
    {
        $this->actingAs($this->seller);

        // Oferta 1: Quedan 5 unidades, se venden 2 hoy
        $offerA = Offer::factory()->active()->create([
            'food_establishment_id' => $this->establishment->id,
            'title' => 'Pack Mediodía',
            'quantity' => 5,
            'price' => 2000,
            'minimum_value' => 4000,
            'pickup_start_datetime' => now()->startOfHour(),
            'expiration_datetime' => now()->addHours(3),
        ]);

        // Oferta 2: Agotada (0 unidades), se vendieron 3 hoy
        $offerB = Offer::factory()->active()->create([
            'food_establishment_id' => $this->establishment->id,
            'title' => 'Pack Merienda',
            'quantity' => 0,
            'price' => 1500,
            'minimum_value' => 3000,
            'pickup_start_datetime' => now()->startOfHour(),
            'expiration_datetime' => now()->addHours(2),
        ]);

        // Venta 1 de hoy: 2 unidades de Offer A ($4000 total), pendiente de retiro
        $sell1 = Sell::create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'CODE-AAA-111',
            'is_picked_up' => false,
            'max_pickup_datetime' => now()->addHours(2),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        SellDetail::create([
            'sell_id' => $sell1->id,
            'offer_id' => $offerA->id,
            'offer_quantity' => 2,
            'pack_price' => 2000,
            'pack_name' => $offerA->title,
        ]);

        // Venta 2 de hoy: 3 unidades de Offer B ($4500 total), ya entregada
        $sell2 = Sell::create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'CODE-BBB-222',
            'is_picked_up' => true,
            'picked_up_at' => now(),
            'max_pickup_datetime' => now()->addHours(1),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        SellDetail::create([
            'sell_id' => $sell2->id,
            'offer_id' => $offerB->id,
            'offer_quantity' => 3,
            'pack_price' => 1500,
            'pack_name' => $offerB->title,
        ]);

        // Venta 3 de ayer: no debe contabilizarse en las métricas de hoy
        $yesterdaySell = Sell::create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'CODE-YESTERDAY',
            'is_picked_up' => true,
            'picked_up_at' => now()->subDay(),
            'max_pickup_datetime' => now()->subDay()->addHours(2),
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
        SellDetail::create([
            'sell_id' => $yesterdaySell->id,
            'offer_id' => $offerA->id,
            'offer_quantity' => 1,
            'pack_price' => 2000,
            'pack_name' => $offerA->title,
        ]);

        // Venta 4 cancelada de hoy: no debe sumar a packs vendidos ni ingresos
        $cancelledSell = Sell::create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'CODE-CANCELLED',
            'state' => SellState::CANCELLED->value,
            'is_picked_up' => false,
            'max_pickup_datetime' => now()->addHours(2),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        SellDetail::create([
            'sell_id' => $cancelledSell->id,
            'offer_id' => $offerA->id,
            'offer_quantity' => 4,
            'pack_price' => 2000,
            'pack_name' => $offerA->title,
        ]);

        $response = $this->getJson('/api/seller/dashboard');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Validar Summary
        // orders_today = 2 (sell1 y sell2)
        $this->assertEquals(2, $data['summary']['orders_today']);
        // packs_sold_today = 5 (2 de sell1 + 3 de sell2)
        $this->assertEquals(5, $data['summary']['packs_sold_today']);
        // earnings_today = 8500 (4000 + 4500)
        $this->assertEquals(8500.00, $data['summary']['earnings_today']);
        // pending_pickups_today = 1 (sell1)
        $this->assertEquals(1, $data['summary']['pending_pickups_today']);
        // completed_pickups_today = 1 (sell2)
        $this->assertEquals(1, $data['summary']['completed_pickups_today']);

        // Validar Live Stock
        $liveStock = collect($data['live_stock']);

        $stockA = $liveStock->firstWhere('offer_id', $offerA->id);
        $this->assertNotNull($stockA);
        $this->assertEquals(5, $stockA['stock_remaining']);
        $this->assertEquals(2, $stockA['stock_sold_today']);
        $this->assertFalse($stockA['is_sold_out']);

        $stockB = $liveStock->firstWhere('offer_id', $offerB->id);
        $this->assertNotNull($stockB);
        $this->assertEquals(0, $stockB['stock_remaining']);
        $this->assertEquals(3, $stockB['stock_sold_today']);
        $this->assertTrue($stockB['is_sold_out']);

        // Validar que la venta cancelada figura en today_pickups con status 'cancelled' para permitir su filtrado en el panel
        $pickups = collect($data['today_pickups']);
        $cancelledPickup = $pickups->firstWhere('id', $cancelledSell->id);
        $this->assertNotNull($cancelledPickup, 'La venta cancelada debe figurar en today_pickups');
        $this->assertEquals('cancelled', $cancelledPickup['status']);
    }

    #[Test]
    public function it_lists_today_pickups_with_proper_time_windows(): void
    {
        $this->actingAs($this->seller);

        $offer = Offer::factory()->active()->create([
            'food_establishment_id' => $this->establishment->id,
            'title' => 'Pack Especial',
            'price' => 1200,
            'pickup_start_datetime' => now()->startOfHour(),
            'expiration_datetime' => now()->addHours(2),
        ]);

        $sell = Sell::create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'SECRET-PICKUP-CODE-999',
            'is_picked_up' => false,
            'max_pickup_datetime' => now()->addHours(2),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        SellDetail::create([
            'sell_id' => $sell->id,
            'offer_id' => $offer->id,
            'offer_quantity' => 2,
            'pack_price' => 1200,
            'pack_name' => 'Pack Especial',
        ]);

        $response = $this->getJson('/api/seller/dashboard');

        $response->assertStatus(200);
        $pickups = $response->json('data.today_pickups');

        $this->assertNotEmpty($pickups);
        $pickup = collect($pickups)->firstWhere('id', $sell->id);

        $this->assertNotNull($pickup);
        $this->assertEquals('Cliente Feliz', $pickup['customer_name']);
        $this->assertEquals(2400.00, $pickup['total_price']);
        $this->assertEquals('pending', $pickup['status']);
        $this->assertFalse($pickup['is_picked_up']);
        $this->assertNotNull($pickup['pickup_start_datetime']);
        $this->assertNotNull($pickup['max_pickup_datetime']);
        $this->assertCount(1, $pickup['packs']);
        $this->assertEquals('Pack Especial', $pickup['packs'][0]['pack_name']);
        $this->assertEquals(2, $pickup['packs'][0]['quantity']);
    }

    #[Test]
    public function it_does_not_leak_pickup_code_in_today_pickups(): void
    {
        $this->actingAs($this->seller);

        $secretCode = 'SUPER-SECRET-CODE-XYZ';

        $offer = Offer::factory()->active()->create([
            'food_establishment_id' => $this->establishment->id,
            'title' => 'Pack Secreto',
            'price' => 1000,
        ]);

        $sell = Sell::create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => $secretCode,
            'is_picked_up' => false,
            'max_pickup_datetime' => now()->addHours(2),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        SellDetail::create([
            'sell_id' => $sell->id,
            'offer_id' => $offer->id,
            'offer_quantity' => 1,
            'pack_price' => 1000,
            'pack_name' => 'Pack Secreto',
        ]);

        $response = $this->getJson('/api/seller/dashboard');

        $response->assertStatus(200);
        $jsonString = $response->getContent();

        $this->assertStringNotContainsString($secretCode, $jsonString, 'El pickup_code jamás debe exponerse en el dashboard');
    }

    #[Test]
    public function it_isolates_data_between_different_sellers(): void
    {
        // Crear un segundo vendedor con su propio establecimiento y venta
        $otherSeller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $otherEstablishment = FoodEstablishment::factory()->create([
            'user_id' => $otherSeller->id,
            'name' => 'Comercio Competidor',
            'establishment_type_id' => EstablishmentType::inRandomOrder()->first()->id,
        ]);

        $otherOffer = Offer::factory()->active()->create([
            'food_establishment_id' => $otherEstablishment->id,
            'title' => 'Pack de la Competencia',
            'quantity' => 8,
            'price' => 3000,
        ]);

        $otherSell = Sell::create([
            'bought_by' => $this->customer->id,
            'sold_by' => $otherEstablishment->id,
            'pickup_code' => 'OTHER-SECRET',
            'is_picked_up' => false,
            'max_pickup_datetime' => now()->addHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        SellDetail::create([
            'sell_id' => $otherSell->id,
            'offer_id' => $otherOffer->id,
            'offer_quantity' => 3,
            'pack_price' => 3000,
            'pack_name' => 'Pack de la Competencia',
        ]);

        // Autenticar como el primer vendedor
        $this->actingAs($this->seller);

        $response = $this->getJson('/api/seller/dashboard');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Debe ver solo su establecimiento
        $this->assertEquals($this->establishment->id, $data['establishment']['id']);
        $this->assertEquals(0, $data['summary']['orders_today']);
        $this->assertEquals(0, $data['summary']['packs_sold_today']);
        $this->assertEmpty($data['today_pickups']);

        $offerIdsInStock = collect($data['live_stock'])->pluck('offer_id')->toArray();
        $this->assertNotContains($otherOffer->id, $offerIdsInStock);
    }

    #[Test]
    public function it_denies_access_to_unauthenticated_users(): void
    {
        $response = $this->getJson('/api/seller/dashboard');

        $response->assertStatus(401);
    }

    #[Test]
    public function it_denies_access_to_customers(): void
    {
        $this->actingAs($this->customer);

        $response = $this->getJson('/api/seller/dashboard');

        $response->assertStatus(403);
    }

    #[Test]
    public function it_denies_access_to_inactive_sellers(): void
    {
        $inactiveSeller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::INACTIVE->value,
        ]);

        $this->actingAs($inactiveSeller);

        $response = $this->getJson('/api/seller/dashboard');

        $response->assertStatus(403);
    }

    #[Test]
    public function it_accepts_valid_custom_date_filter(): void
    {
        $this->actingAs($this->seller);

        $pastDate = Carbon::yesterday();

        $response = $this->getJson('/api/seller/dashboard?date='.$pastDate->format('Y-m-d'));

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'date' => $pastDate->format('Y-m-d'),
                ],
            ]);
    }

    #[Test]
    public function it_validates_date_format(): void
    {
        $this->actingAs($this->seller);

        $response = $this->getJson('/api/seller/dashboard?date=invalid-date');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }
}
