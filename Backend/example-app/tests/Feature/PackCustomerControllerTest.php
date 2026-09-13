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

class PackCustomerControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected User $seller;

    protected FoodEstablishment $establishment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(EstablishmentTypeSeeder::class);

        $this->user = User::factory()->withRole(UserRole::CUSTOMER->value)->create([
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

        $this->actingAs($this->user);
    }

    #[Test]
    public function it_can_list_active_packs_without_search(): void
    {
        $activePack1 = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(1),
            'food_establishment_id' => $this->establishment->id,
            'title' => 'Active Pack 1',
            'description' => 'Description for active pack 1',
            'price' => 2000,
            'minimum_value' => 6000,
            'allergens' => ['gluten'],
            'estimated_weight_kg' => 1.5,
            'quantity' => 5,
        ]);

        $activePack2 = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(2),
            'food_establishment_id' => $this->establishment->id,
            'title' => 'Active Pack 2',
            'description' => 'Description for active pack 2',
            'price' => 3000,
            'minimum_value' => 9000,
            'allergens' => ['lácteos'],
            'estimated_weight_kg' => 2.0,
            'quantity' => 3,
        ]);

        Offer::factory()->create([
            'state' => OfferState::INACTIVE->value,
            'expiration_datetime' => now()->addDays(1),
            'food_establishment_id' => $this->establishment->id,
        ]);

        Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->subDays(1),
            'food_establishment_id' => $this->establishment->id,
        ]);

        $response = $this->getJson('/api/packs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'price',
                        'minimum_value',
                        'allergens',
                        'estimated_weight_kg',
                        'quantity',
                        'state',
                        'pickup_start_datetime',
                        'expiration_datetime',
                        'establishment' => [
                            'id',
                            'name',
                            'address',
                            'latitude',
                            'longitude',
                        ],
                    ],
                ],
                'current_page',
                'per_page',
                'has_more',
            ]);

        $responseData = $response->json();
        $this->assertCount(2, $responseData['data']);
        $this->assertEquals(1, $responseData['current_page']);
        $this->assertEquals(20, $responseData['per_page']);
        $this->assertFalse($responseData['has_more']);
    }

    #[Test]
    public function it_can_search_packs_by_title(): void
    {
        $searchablePack = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(1),
            'food_establishment_id' => $this->establishment->id,
            'title' => 'Pizza Sorpresa Especial',
            'description' => 'Pack de pizza variada',
        ]);

        $otherPack = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(1),
            'food_establishment_id' => $this->establishment->id,
            'title' => 'Hamburguesa Sorpresa',
            'description' => 'Pack de hamburguesas',
        ]);

        $searchablePack->searchable();
        $otherPack->searchable();

        $response = $this->getJson('/api/packs?search=Pizza');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'has_more',
            ]);

        $responseData = $response->json();
        $this->assertCount(1, $responseData['data']);
        $this->assertEquals($searchablePack->id, $responseData['data'][0]['id']);
        $this->assertEquals('Pizza Sorpresa Especial', $responseData['data'][0]['title']);
    }

    #[Test]
    public function it_can_search_packs_by_description(): void
    {
        $searchablePack = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(1),
            'food_establishment_id' => $this->establishment->id,
            'title' => 'Pack Verde',
            'description' => 'Contiene selección de pastelería vegana deliciosa',
        ]);

        $otherPack = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(1),
            'food_establishment_id' => $this->establishment->id,
            'title' => 'Pack Carnes',
            'description' => 'Cortes tradicionales',
        ]);

        $searchablePack->searchable();
        $otherPack->searchable();

        $response = $this->getJson('/api/packs?search=vegana');

        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertCount(1, $responseData['data']);
        $this->assertEquals($searchablePack->id, $responseData['data'][0]['id']);
    }

    #[Test]
    public function it_handles_pagination_correctly(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            Offer::factory()->create([
                'state' => OfferState::ACTIVE->value,
                'expiration_datetime' => now()->addDays(1),
                'food_establishment_id' => $this->establishment->id,
                'title' => "Pack $i",
            ]);
        }

        $response = $this->getJson('/api/packs?page=1');
        $response->assertStatus(200);
        $responseData = $response->json();

        $this->assertCount(20, $responseData['data']);
        $this->assertEquals(1, $responseData['current_page']);
        $this->assertEquals(20, $responseData['per_page']);
        $this->assertTrue($responseData['has_more']);

        $response = $this->getJson('/api/packs?page=2');
        $response->assertStatus(200);
        $responseData = $response->json();

        $this->assertCount(5, $responseData['data']);
        $this->assertEquals(2, $responseData['current_page']);
        $this->assertFalse($responseData['has_more']);
    }

    #[Test]
    public function it_only_returns_active_and_non_expired_packs(): void
    {
        $activeValidPack = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(1),
            'food_establishment_id' => $this->establishment->id,
        ]);

        Offer::factory()->create([
            'state' => OfferState::INACTIVE->value,
            'expiration_datetime' => now()->addDays(1),
            'food_establishment_id' => $this->establishment->id,
        ]);

        Offer::factory()->create([
            'state' => OfferState::VERIFIYING->value,
            'expiration_datetime' => now()->addDays(1),
            'food_establishment_id' => $this->establishment->id,
        ]);

        Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->subDays(1),
            'food_establishment_id' => $this->establishment->id,
        ]);

        $response = $this->getJson('/api/packs');
        $response->assertStatus(200);
        $responseData = $response->json();

        $this->assertCount(1, $responseData['data']);
        $this->assertEquals($activeValidPack->id, $responseData['data'][0]['id']);
    }

    #[Test]
    public function it_returns_empty_data_when_no_packs_available(): void
    {
        $response = $this->getJson('/api/packs');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
                'current_page' => 1,
                'per_page' => 20,
                'has_more' => false,
            ]);
    }

    #[Test]
    public function it_can_search_packs_with_geolocation_and_radius(): void
    {
        $establishmentType = EstablishmentType::first();

        $sellerNear = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $nearEstablishment = FoodEstablishment::factory()->create([
            'user_id' => $sellerNear->id,
            'establishment_type_id' => $establishmentType->id,
            'latitude' => -34.6037,
            'longitude' => -58.3816,
        ]);

        $sellerFar = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $farEstablishment = FoodEstablishment::factory()->create([
            'user_id' => $sellerFar->id,
            'establishment_type_id' => $establishmentType->id,
            'latitude' => -34.9214,
            'longitude' => -57.9545,
        ]);

        $nearPack = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(2),
            'food_establishment_id' => $nearEstablishment->id,
            'title' => 'Pack Cercano Obelisco',
            'description' => 'Comida cerca en CABA',
        ]);

        $farPack = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(2),
            'food_establishment_id' => $farEstablishment->id,
            'title' => 'Pack Lejano La Plata',
            'description' => 'Comida lejos',
        ]);

        $nearPack->searchable();
        $farPack->searchable();

        $response = $this->getJson('/api/packs?search=Pack&lat=-34.6037&lng=-58.3816&radius=5');

        $response->assertStatus(200);
        $responseData = $response->json();

        $this->assertTrue(
            collect($responseData['data'])->contains('id', $nearPack->id),
            'El pack cercano debe estar presente en los resultados'
        );
        $this->assertFalse(
            collect($responseData['data'])->contains('id', $farPack->id),
            'El pack lejano no debe estar presente en los resultados'
        );
    }

    #[Test]
    public function it_can_search_packs_with_geolocation_and_radius_without_search_text(): void
    {
        $establishmentType = EstablishmentType::first();

        $sellerNear = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $nearEstablishment = FoodEstablishment::factory()->create([
            'user_id' => $sellerNear->id,
            'establishment_type_id' => $establishmentType->id,
            'latitude' => -34.6037,
            'longitude' => -58.3816,
        ]);

        $sellerFar = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $farEstablishment = FoodEstablishment::factory()->create([
            'user_id' => $sellerFar->id,
            'establishment_type_id' => $establishmentType->id,
            'latitude' => -34.9214,
            'longitude' => -57.9545,
        ]);

        $nearPack = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(2),
            'food_establishment_id' => $nearEstablishment->id,
            'title' => 'Empanadas CABA',
            'description' => 'Comida cerca',
        ]);

        $farPack = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(2),
            'food_establishment_id' => $farEstablishment->id,
            'title' => 'Asado La Plata',
            'description' => 'Comida lejos',
        ]);

        $nearPack->searchable();
        $farPack->searchable();

        $response = $this->getJson('/api/packs?lat=-34.6037&lng=-58.3816&radius=5');

        $response->assertStatus(200);
        $responseData = $response->json();

        $this->assertTrue(
            collect($responseData['data'])->contains('id', $nearPack->id),
            'El pack cercano debe estar presente en los resultados geo sin texto de búsqueda'
        );
        $this->assertFalse(
            collect($responseData['data'])->contains('id', $farPack->id),
            'El pack lejano no debe estar presente en los resultados geo sin texto de búsqueda'
        );
    }

    #[Test]
    public function it_can_get_single_active_pack(): void
    {
        $pack = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(2),
            'food_establishment_id' => $this->establishment->id,
            'title' => 'Pack Individual',
            'description' => 'Pack de prueba show',
            'price' => 2500,
            'minimum_value' => 7000,
        ]);

        $response = $this->getJson("/api/packs/{$pack->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Pack obtenido exitosamente',
                'data' => [
                    'id' => $pack->id,
                    'title' => 'Pack Individual',
                    'description' => 'Pack de prueba show',
                    'price' => 2500,
                    'minimum_value' => 7000,
                ],
            ]);
    }

    #[Test]
    public function it_returns_404_for_non_existent_or_inactive_pack(): void
    {
        $response = $this->getJson('/api/packs/99999');
        $response->assertStatus(404);

        $inactivePack = Offer::factory()->create([
            'state' => OfferState::INACTIVE->value,
            'expiration_datetime' => now()->addDays(2),
            'food_establishment_id' => $this->establishment->id,
        ]);

        $responseInactive = $this->getJson("/api/packs/{$inactivePack->id}");
        $responseInactive->assertStatus(404);
    }
}
