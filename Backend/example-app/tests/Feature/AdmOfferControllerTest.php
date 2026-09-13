<?php

namespace Tests\Feature;

use App\Enums\OfferState;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
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

        $this->admin = User::factory()->withRole(UserRole::ADMIN->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $this->actingAs($this->admin);
    }

    #[Test]
    public function it_returns_active_offers_stats_grouped_by_establishment_type_from_last_7_days(): void
    {
        $panaderia = EstablishmentType::where('name', 'Panadería')->first()
            ?? EstablishmentType::factory()->create(['name' => 'Panadería']);
        $restaurante = EstablishmentType::where('name', 'Restaurante')->first()
            ?? EstablishmentType::factory()->create(['name' => 'Restaurante']);

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

        for ($i = 0; $i < 20; $i++) {
            Offer::factory()->create([
                'food_establishment_id' => $establishment1->id,
                'state' => OfferState::ACTIVE->value,
                'created_at' => now()->subDays(rand(0, 6)),
            ]);
        }

        for ($i = 0; $i < 22; $i++) {
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

        $response = $this->getJson('/api/adm/offer-stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'establishment_type',
                        'count',
                    ],
                ],
                'message',
            ])
            ->assertJson([
                'message' => 'Estadísticas de ofertas obtenidas exitosamente',
                'data' => [
                    [
                        'establishment_type' => 'Panadería',
                        'count' => 20,
                    ],
                    [
                        'establishment_type' => 'Restaurante',
                        'count' => 22,
                    ],
                ],
            ]);
    }

    #[Test]
    public function it_returns_empty_stats_when_no_active_offers_in_last_7_days(): void
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

        $response = $this->getJson('/api/adm/offer-stats');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Estadísticas de ofertas obtenidas exitosamente',
                'data' => [],
            ]);
    }

    #[Test]
    public function it_returns_active_offers_count_grouped_by_establishment_type_from_last_7_days(): void
    {
        $panaderia = EstablishmentType::where('name', 'Panadería')->first()
            ?? EstablishmentType::factory()->create(['name' => 'Panadería']);
        $restaurante = EstablishmentType::where('name', 'Restaurante')->first()
            ?? EstablishmentType::factory()->create(['name' => 'Restaurante']);

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
                        'count',
                    ],
                ],
                'message',
            ])
            ->assertJson([
                'message' => 'Cantidad de ofertas activas obtenidas exitosamente',
                'data' => [
                    [
                        'establishment_type' => 'Panadería',
                        'count' => 15,
                    ],
                    [
                        'establishment_type' => 'Restaurante',
                        'count' => 10,
                    ],
                ],
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
                'data' => [],
            ]);
    }

    #[Test]
    public function it_returns_expiring_offers_count_grouped_by_day_of_week(): void
    {
        $panaderia = EstablishmentType::where('name', 'Panadería')->first()
            ?? EstablishmentType::factory()->create(['name' => 'Panadería']);

        $seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $establishment = FoodEstablishment::factory()->create([
            'user_id' => $seller->id,
            'establishment_type_id' => $panaderia->id,
        ]);

        $today = now()->startOfDay();

        // Oferta 1: expira mañana
        Offer::factory()->create([
            'food_establishment_id' => $establishment->id,
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => $today->copy()->addDays(1)->setTime(12, 0),
        ]);

        // Oferta 2: expira en 3 días
        Offer::factory()->create([
            'food_establishment_id' => $establishment->id,
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => $today->copy()->addDays(3)->setTime(15, 0),
        ]);

        // Oferta 3: expira fuera del rango (10 días)
        Offer::factory()->create([
            'food_establishment_id' => $establishment->id,
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => $today->copy()->addDays(10)->setTime(12, 0),
        ]);

        $response = $this->getJson('/api/adm/expiring-offers-count');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'day',
                        'count',
                    ],
                ],
                'message',
            ])
            ->assertJson([
                'message' => 'Ofertas que expiran esta semana obtenidas exitosamente',
            ]);

        $data = $response->json('data');
        $this->assertCount(7, $data);

        $daysOfWeek = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        foreach ($data as $index => $dayData) {
            $this->assertEquals($daysOfWeek[$index], $dayData['day']);
            $this->assertIsInt($dayData['count']);
        }

        $totalCount = array_sum(array_column($data, 'count'));
        $this->assertEquals(2, $totalCount);
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

        Offer::factory()->create([
            'food_establishment_id' => $establishment->id,
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(15),
        ]);

        $response = $this->getJson('/api/adm/expiring-offers-count');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Ofertas que expiran esta semana obtenidas exitosamente',
            ]);

        $data = $response->json('data');
        $this->assertCount(7, $data);

        foreach ($data as $dayData) {
            $this->assertEquals(0, $dayData['count']);
        }
    }
}
