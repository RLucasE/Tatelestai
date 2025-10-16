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

        $response->dump();

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
}
