<?php

namespace Tests\Feature;

use App\Enums\OfferState;
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
use Tests\TestCase;

class AdmOfferControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(EstablishmentTypeSeeder::class);

        // Crear y autenticar admin para todos los tests
        $this->admin = User::factory()->withRole(UserRole::ADMIN->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $this->actingAs($this->admin);
    }

    #[Test]
    public function it_returns_active_offers_stats_grouped_by_establishment_type_from_last_7_days(): void
    {
        // Crear tipos de establecimiento
        $panaderia = EstablishmentType::where('name', 'Panadería')->first();
        $restaurante = EstablishmentType::where('name', 'Restaurante')->first();

        // Si no existen, crearlos
        if (!$panaderia) {
            $panaderia = EstablishmentType::factory()->create(['name' => 'Panadería']);
        }
        if (!$restaurante) {
            $restaurante = EstablishmentType::factory()->create(['name' => 'Restaurante']);
        }

        // Crear sellers
        $seller1 = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $seller2 = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        // Crear establecimientos
        $establishment1 = FoodEstablishment::factory()->create([
            'user_id' => $seller1->id,
            'establishment_type_id' => $panaderia->id,
        ]);
        $establishment2 = FoodEstablishment::factory()->create([
            'user_id' => $seller2->id,
            'establishment_type_id' => $restaurante->id,
        ]);

        // Crear ofertas activas de los últimos 7 días
        // 20 ofertas de Panadería
        for ($i = 0; $i < 20; $i++) {
            Offer::factory()->create([
                'food_establishment_id' => $establishment1->id,
                'state' => OfferState::ACTIVE->value,
                'created_at' => now()->subDays(rand(0, 6)),
            ]);
        }

        // 22 ofertas de Restaurante
        for ($i = 0; $i < 22; $i++) {
            Offer::factory()->create([
                'food_establishment_id' => $establishment2->id,
                'state' => OfferState::ACTIVE->value,
                'created_at' => now()->subDays(rand(0, 6)),
            ]);
        }

        // Crear ofertas que NO deben contarse:
        // Ofertas inactivas de los últimos 7 días
        Offer::factory()->create([
            'food_establishment_id' => $establishment1->id,
            'state' => OfferState::INACTIVE->value,
            'created_at' => now()->subDays(3),
        ]);

        // Ofertas activas pero de hace más de 7 días
        Offer::factory()->create([
            'food_establishment_id' => $establishment1->id,
            'state' => OfferState::ACTIVE->value,
            'created_at' => now()->subDays(10),
        ]);

        Offer::factory()->create([
            'food_establishment_id' => $establishment2->id,
            'state' => OfferState::ACTIVE->value,
            'created_at' => now()->subDays(8),
        ]);

        // Hacer la petición al endpoint
        $response = $this->getJson('/api/adm/offer-stats');


        // Verificar respuesta
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'establishment_type',
                        'count'
                    ]
                ],
                'message'
            ])
            ->assertJson([
                'message' => 'Estadísticas de ofertas obtenidas exitosamente',
                'data' => [
                    [
                        'establishment_type' => 'Panadería',
                        'count' => 20
                    ],
                    [
                        'establishment_type' => 'Restaurante',
                        'count' => 22
                    ]
                ]
            ]);
    }

    #[Test]
    public function it_returns_empty_stats_when_no_active_offers_in_last_7_days(): void
    {
        // Crear establecimiento con ofertas antiguas
        $panaderia = EstablishmentType::factory()->create(['name' => 'Panadería']);
        $seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $establishment = FoodEstablishment::factory()->create([
            'user_id' => $seller->id,
            'establishment_type_id' => $panaderia->id,
        ]);

        // Crear ofertas de hace más de 7 días
        Offer::factory()->create([
            'food_establishment_id' => $establishment->id,
            'state' => OfferState::ACTIVE->value,
            'created_at' => now()->subDays(10),
        ]);

        $response = $this->getJson('/api/adm/offer-stats');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Estadísticas de ofertas obtenidas exitosamente',
                'data' => []
            ]);
    }

    #[Test]
    public function it_returns_active_offers_count_grouped_by_establishment_type_from_last_7_days(): void
    {
        $panaderia = EstablishmentType::where('name', 'Panadería')->first();
        $restaurante = EstablishmentType::where('name', 'Restaurante')->first();

        if (!$panaderia) {
            $panaderia = EstablishmentType::factory()->create(['name' => 'Panadería']);
        }
        if (!$restaurante) {
            $restaurante = EstablishmentType::factory()->create(['name' => 'Restaurante']);
        }

        $seller1 = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $seller2 = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $establishment1 = FoodEstablishment::factory()->create([
            'user_id' => $seller1->id,
            'establishment_type_id' => $panaderia->id,
        ]);
        $establishment2 = FoodEstablishment::factory()->create([
            'user_id' => $seller2->id,
            'establishment_type_id' => $restaurante->id,
        ]);

        for ($i = 0; $i < 15; $i++) {
            Offer::factory()->create([
                'food_establishment_id' => $establishment1->id,
                'state' => OfferState::ACTIVE->value,
                'created_at' => now()->subDays(rand(0, 6)),
            ]);
        }

        for ($i = 0; $i < 10; $i++) {
            Offer::factory()->create([
                'food_establishment_id' => $establishment2->id,
                'state' => OfferState::ACTIVE->value,
                'created_at' => now()->subDays(rand(0, 6)),
            ]);
        }

        Offer::factory()->create([
            'food_establishment_id' => $establishment1->id,
            'state' => OfferState::INACTIVE->value,
            'created_at' => now()->subDays(3),
        ]);

        Offer::factory()->create([
            'food_establishment_id' => $establishment1->id,
            'state' => OfferState::ACTIVE->value,
            'created_at' => now()->subDays(10),
        ]);

        Offer::factory()->create([
            'food_establishment_id' => $establishment2->id,
            'state' => OfferState::ACTIVE->value,
            'created_at' => now()->subDays(8),
        ]);

        $response = $this->getJson('/api/adm/active-offers-count');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'establishment_type',
                        'count'
                    ]
                ],
                'message'
            ])
            ->assertJson([
                'message' => 'Cantidad de ofertas activas obtenidas exitosamente',
                'data' => [
                    [
                        'establishment_type' => 'Panadería',
                        'count' => 15
                    ],
                    [
                        'establishment_type' => 'Restaurante',
                        'count' => 10
                    ]
                ]
            ]);
    }

    #[Test]
    public function it_returns_empty_data_when_no_active_offers_in_last_7_days_for_active_offers_count(): void
    {
        $panaderia = EstablishmentType::factory()->create(['name' => 'Panadería']);
        $seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $establishment = FoodEstablishment::factory()->create([
            'user_id' => $seller->id,
            'establishment_type_id' => $panaderia->id,
        ]);

        Offer::factory()->create([
            'food_establishment_id' => $establishment->id,
            'state' => OfferState::ACTIVE->value,
            'created_at' => now()->subDays(10),
        ]);

        $response = $this->getJson('/api/adm/active-offers-count');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Cantidad de ofertas activas obtenidas exitosamente',
                'data' => []
            ]);
    }

    #[Test]
    public function it_returns_expiring_offers_count_grouped_by_day_of_week(): void
    {
        $panaderia = EstablishmentType::where('name', 'Panadería')->first();
        if (!$panaderia) {
            $panaderia = EstablishmentType::factory()->create(['name' => 'Panadería']);
        }

        $seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $establishment = FoodEstablishment::factory()->create([
            'user_id' => $seller->id,
            'establishment_type_id' => $panaderia->id,
        ]);

        $product1 = Product::factory()->create([
            'food_establishment_id' => $establishment->id,
        ]);

        $product2 = Product::factory()->create([
            'food_establishment_id' => $establishment->id,
        ]);

        $product3 = Product::factory()->create([
            'food_establishment_id' => $establishment->id,
        ]);

        // Crear ofertas activas
        $offer1 = Offer::factory()->create([
            'food_establishment_id' => $establishment->id,
            'state' => OfferState::ACTIVE->value,
        ]);

        $offer2 = Offer::factory()->create([
            'food_establishment_id' => $establishment->id,
            'state' => OfferState::ACTIVE->value,
        ]);

        // Crear productos que expiran en diferentes días de esta semana
        $today = now()->startOfDay();

        // Producto que expira mañana (día 1)
        \DB::table('product_offers')->insert([
            'offer_id' => $offer1->id,
            'product_id' => $product1->id,
            'price' => 10.00,
            'quantity' => 5,
            'expiration_date' => $today->copy()->addDays(1),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Producto que expira en 3 días (día 3)
        \DB::table('product_offers')->insert([
            'offer_id' => $offer2->id,
            'product_id' => $product2->id,
            'price' => 15.00,
            'quantity' => 3,
            'expiration_date' => $today->copy()->addDays(3),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Producto que expira fuera del rango (no debe contarse)
        \DB::table('product_offers')->insert([
            'offer_id' => $offer1->id,
            'product_id' => $product3->id,
            'price' => 20.00,
            'quantity' => 2,
            'expiration_date' => $today->copy()->addDays(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/adm/expiring-offers-count');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'day',
                        'count'
                    ]
                ],
                'message'
            ])
            ->assertJson([
                'message' => 'Ofertas que expiran esta semana obtenidas exitosamente'
            ]);

        // Verificar que se devuelven 7 días
        $data = $response->json('data');
        $this->assertCount(7, $data);

        // Verificar que todos los días de la semana están presentes
        $daysOfWeek = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        foreach ($data as $index => $dayData) {
            $this->assertEquals($daysOfWeek[$index], $dayData['day']);
            $this->assertIsInt($dayData['count']);
        }
    }

    #[Test]
    public function it_returns_zero_counts_when_no_offers_expiring_this_week(): void
    {
        $panaderia = EstablishmentType::factory()->create(['name' => 'Panadería']);
        $seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $establishment = FoodEstablishment::factory()->create([
            'user_id' => $seller->id,
            'establishment_type_id' => $panaderia->id,
        ]);

        $product = Product::factory()->create([
            'food_establishment_id' => $establishment->id,
        ]);

        $offer = Offer::factory()->create([
            'food_establishment_id' => $establishment->id,
            'state' => OfferState::ACTIVE->value,
        ]);

        // Crear producto que expira fuera del rango
        \DB::table('product_offers')->insert([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'price' => 10.00,
            'quantity' => 5,
            'expiration_date' => now()->addDays(15),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/adm/expiring-offers-count');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Ofertas que expiran esta semana obtenidas exitosamente'
            ]);

        $data = $response->json('data');
        $this->assertCount(7, $data);

        // Verificar que todos los días tienen count = 0
        foreach ($data as $dayData) {
            $this->assertEquals(0, $dayData['count']);
        }
    }

    #[Test]
    public function it_returns_pending_offers_with_complete_information(): void
    {
        // Crear tipos de establecimiento
        $panaderia = EstablishmentType::where('name', 'Panadería')->first();
        if (!$panaderia) {
            $panaderia = EstablishmentType::factory()->create(['name' => 'Panadería']);
        }

        // Crear seller
        $seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
            'name' => 'Juan Vendedor',
            'email' => 'juan@example.com',
        ]);

        // Crear establecimiento
        $establishment = FoodEstablishment::factory()->create([
            'user_id' => $seller->id,
            'establishment_type_id' => $panaderia->id,
            'name' => 'Panadería El Sol',
            'address' => 'Calle 123',
        ]);

        // Crear productos
        $product1 = Product::factory()->create([
            'food_establishment_id' => $establishment->id,
            'name' => 'Pan Integral',
            'description' => 'Pan integral fresco',
        ]);

        $product2 = Product::factory()->create([
            'food_establishment_id' => $establishment->id,
            'name' => 'Croissant',
            'description' => 'Croissant de mantequilla',
        ]);

        // Crear ofertas en estado VERIFYING
        $offer1 = Offer::factory()->create([
            'food_establishment_id' => $establishment->id,
            'state' => OfferState::VERIFIYING->value,
            'title' => 'Oferta de Pan',
            'description' => 'Pan fresco del día',
            'quantity' => 10,
            'expiration_datetime' => now()->addDays(2),
        ]);

        $offer2 = Offer::factory()->create([
            'food_establishment_id' => $establishment->id,
            'state' => OfferState::VERIFIYING->value,
            'title' => 'Oferta de Croissants',
            'description' => 'Croissants recién horneados',
            'quantity' => 5,
            'expiration_datetime' => now()->addDays(1),
        ]);

        // Crear ofertas que NO deben aparecer (estados diferentes)
        Offer::factory()->create([
            'food_establishment_id' => $establishment->id,
            'state' => OfferState::ACTIVE->value,
            'title' => 'Oferta Activa',
        ]);

        Offer::factory()->create([
            'food_establishment_id' => $establishment->id,
            'state' => OfferState::INACTIVE->value,
            'title' => 'Oferta Inactiva',
        ]);

        // Asociar productos a las ofertas
        $offer1->products()->attach($product1->id, [
            'price' => 25,
            'quantity' => 10,
            'expiration_date' => now()->addDays(2),
        ]);

        $offer2->products()->attach($product2->id, [
            'price' => 18,
            'quantity' => 5,
            'expiration_date' => now()->addDays(1),
        ]);

        // Hacer la petición
        $response = $this->getJson('/api/adm-pending-offers');

        // Verificar respuesta
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'state',
                        'quantity',
                        'expiration_datetime',
                        'created_at',
                        'establishment' => [
                            'id',
                            'name',
                            'address',
                            'type',
                        ],
                        'seller' => [
                            'id',
                            'name',
                            'email',
                        ],
                        'products' => [
                            '*' => [
                                'id',
                                'name',
                                'description',
                                'price',
                                'quantity',
                                'expiration_date',
                            ]
                        ]
                    ]
                ],
                'message'
            ]);

        $data = $response->json('data');

        // Verificar que solo se retornan las 2 ofertas en estado VERIFYING
        $this->assertCount(2, $data);

        // Verificar que las ofertas están ordenadas por fecha de creación (más antiguas primero)
        $this->assertEquals($offer1->id, $data[0]['id']);
        $this->assertEquals($offer2->id, $data[1]['id']);

        // Verificar datos de la primera oferta
        $this->assertEquals('Oferta de Pan', $data[0]['title']);
        $this->assertEquals('Pan fresco del día', $data[0]['description']);
        $this->assertEquals(OfferState::VERIFIYING->value, $data[0]['state']);
        $this->assertEquals(10, $data[0]['quantity']);

        $this->assertEquals('Panadería El Sol', $data[0]['establishment']['name']);
        $this->assertEquals('Calle 123', $data[0]['establishment']['address']);
        $this->assertEquals('Panadería', $data[0]['establishment']['type']);

        // Verificar datos del vendedor
        $this->assertEquals('Juan Vendedor', $data[0]['seller']['name']);
        $this->assertEquals('juan@example.com', $data[0]['seller']['email']);

        // Verificar productos
        $this->assertCount(1, $data[0]['products']);
        $this->assertEquals('Pan Integral', $data[0]['products'][0]['name']);
        $this->assertEquals(25, $data[0]['products'][0]['price']);
        $this->assertEquals(10, $data[0]['products'][0]['quantity']);
    }

    #[Test]
    public function it_returns_empty_array_when_no_pending_offers(): void
    {
        // Crear ofertas en diferentes estados (pero no VERIFYING)
        $panaderia = EstablishmentType::factory()->create(['name' => 'Panadería']);
        $seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $establishment = FoodEstablishment::factory()->create([
            'user_id' => $seller->id,
            'establishment_type_id' => $panaderia->id,
        ]);

        Offer::factory()->create([
            'food_establishment_id' => $establishment->id,
            'state' => OfferState::ACTIVE->value,
        ]);

        Offer::factory()->create([
            'food_establishment_id' => $establishment->id,
            'state' => OfferState::INACTIVE->value,
        ]);

        $response = $this->getJson('/api/adm-pending-offers');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
                'message' => 'Ofertas pendientes obtenidas exitosamente'
            ]);
    }

    #[Test]
    public function it_approves_an_offer_and_changes_state_to_active(): void
    {
        // Crear establecimiento y oferta
        $panaderia = EstablishmentType::factory()->create(['name' => 'Panadería']);
        $seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $establishment = FoodEstablishment::factory()->create([
            'user_id' => $seller->id,
            'establishment_type_id' => $panaderia->id,
        ]);

        $offer = Offer::factory()->create([
            'food_establishment_id' => $establishment->id,
            'state' => OfferState::VERIFIYING->value,
            'title' => 'Oferta a Aprobar',
        ]);

        // Verificar estado inicial
        $this->assertEquals(OfferState::VERIFIYING->value, $offer->state);

        // Aprobar la oferta
        $response = $this->patchJson("/api/adm-offers/{$offer->id}/approve");

        // Verificar respuesta
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Oferta aprobada exitosamente',
                'data' => [
                    'id' => $offer->id,
                    'state' => OfferState::ACTIVE->value
                ]
            ]);

        // Verificar que el estado cambió en la base de datos
        $offer->refresh();
        $this->assertEquals(OfferState::ACTIVE->value, $offer->state);
    }

    #[Test]
    public function it_cannot_approve_an_offer_that_is_not_in_verifying_state(): void
    {
        $panaderia = EstablishmentType::factory()->create(['name' => 'Panadería']);
        $seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $establishment = FoodEstablishment::factory()->create([
            'user_id' => $seller->id,
            'establishment_type_id' => $panaderia->id,
        ]);

        // Crear oferta que ya está ACTIVE
        $offer = Offer::factory()->create([
            'food_establishment_id' => $establishment->id,
            'state' => OfferState::ACTIVE->value,
        ]);

        $response = $this->patchJson("/api/adm-offers/{$offer->id}/approve");

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'La oferta no está en estado de verificación'
            ]);

        // Verificar que el estado NO cambió
        $offer->refresh();
        $this->assertEquals(OfferState::ACTIVE->value, $offer->state);
    }

    #[Test]
    public function it_returns_error_when_approving_non_existent_offer(): void
    {
        $response = $this->patchJson('/api/adm-offers/99999/approve');

        $response->assertStatus(404);
    }

    #[Test]
    public function it_rejects_an_offer_and_changes_state_to_inactive(): void
    {
        // Crear establecimiento y oferta
        $panaderia = EstablishmentType::factory()->create(['name' => 'Panadería']);
        $seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $establishment = FoodEstablishment::factory()->create([
            'user_id' => $seller->id,
            'establishment_type_id' => $panaderia->id,
        ]);

        $offer = Offer::factory()->create([
            'food_establishment_id' => $establishment->id,
            'state' => OfferState::VERIFIYING->value,
            'title' => 'Oferta a Rechazar',
        ]);

        // Verificar estado inicial
        $this->assertEquals(OfferState::VERIFIYING->value, $offer->state);

        // Rechazar la oferta
        $response = $this->patchJson("/api/adm-offers/{$offer->id}/reject");

        // Verificar respuesta
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Oferta rechazada exitosamente',
                'data' => [
                    'id' => $offer->id,
                    'state' => OfferState::INACTIVE->value
                ]
            ]);

        // Verificar que el estado cambió en la base de datos
        $offer->refresh();
        $this->assertEquals(OfferState::INACTIVE->value, $offer->state);
    }

    #[Test]
    public function it_cannot_reject_an_offer_that_is_not_in_verifying_state(): void
    {
        $panaderia = EstablishmentType::factory()->create(['name' => 'Panadería']);
        $seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $establishment = FoodEstablishment::factory()->create([
            'user_id' => $seller->id,
            'establishment_type_id' => $panaderia->id,
        ]);

        // Crear oferta que ya está INACTIVE
        $offer = Offer::factory()->create([
            'food_establishment_id' => $establishment->id,
            'state' => OfferState::INACTIVE->value,
        ]);

        $response = $this->patchJson("/api/adm-offers/{$offer->id}/reject");

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'La oferta no está en estado de verificación'
            ]);

        // Verificar que el estado NO cambió
        $offer->refresh();
        $this->assertEquals(OfferState::INACTIVE->value, $offer->state);
    }

    #[Test]
    public function it_returns_error_when_rejecting_non_existent_offer(): void
    {
        $response = $this->patchJson('/api/adm-offers/99999/reject');

        $response->assertStatus(404);
    }
}
