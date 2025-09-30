<?php

namespace Tests\Feature;

use App\Enums\OfferState;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentType;
use App\Models\Offer;
use App\Models\User;
use App\Models\Product;
use App\Models\FoodEstablishment;
use App\Models\ProductOffer;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OfferCustomerControllerTest extends TestCase
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
    public function it_can_list_active_offers_without_search(): void
    {
        $activeOffer1 = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(1),
            'food_establishment_id' => $this->establishment->id,
            'title' => 'Active Offer 1',
            'description' => 'Description for active offer 1'
        ]);

        $activeOffer2 = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(2),
            'food_establishment_id' => $this->establishment->id,
            'title' => 'Active Offer 2',
            'description' => 'Description for active offer 2'
        ]);

        $product1 = Product::factory()->create([
                'food_establishment_id' => $this->establishment->id,
            ]
        );
        $product2 = Product::factory()->create([
                'food_establishment_id' => $this->establishment->id,
            ]
        );

        ProductOffer::create([
            'offer_id' => $activeOffer1->id,
            'product_id' => $product1->id,
            'price' => 10,
            'quantity' => 5,
            'expiration_date' => now()->addDays(10)
        ]);

        ProductOffer::create([
            'offer_id' => $activeOffer2->id,
            'product_id' => $product2->id,
            'price' => 15,
            'quantity' => 3,
            'expiration_date' => now()->addDays(15)
        ]);

        Offer::factory()->create([
            'state' => OfferState::INACTIVE->value,
            'expiration_datetime' => now()->addDays(1),
            'food_establishment_id' => $this->establishment->id
        ]);

        Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->subDays(1),
            'food_establishment_id' => $this->establishment->id
        ]);

        $response = $this->getJson('/api/offers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'offer_quantity',
                        'title',
                        'description',
                        'expiration_datetime',
                        'establishment_id',
                        'establishment_name',
                        'establishment_address',
                        'food_establishment_id',
                        'products' => [
                            '*' => [
                                'name',
                                'description',
                                'quantity',
                                'price',
                                'expiration_date'
                            ]
                        ]
                    ]
                ],
                'current_page',
                'per_page',
                'has_more'
            ]);

        $responseData = $response->json();

        $this->assertCount(2, $responseData['data']);

        $this->assertEquals(1, $responseData['current_page']);
        $this->assertEquals(20, $responseData['per_page']);
        $this->assertFalse($responseData['has_more']);

        $firstOffer = $responseData['data'][0];
        $this->assertEquals($activeOffer1->id, $firstOffer['id']);
        $this->assertEquals($activeOffer1->title, $firstOffer['title']);
        $this->assertEquals($activeOffer1->description, $firstOffer['description']);
        $this->assertEquals($this->establishment->id, $firstOffer['establishment_id']);
        $this->assertEquals($this->establishment->name, $firstOffer['establishment_name']);
        $this->assertEquals($this->establishment->address, $firstOffer['establishment_address']);

        $this->assertCount(1, $firstOffer['products']);
        $firstProduct = $firstOffer['products'][0];
        $this->assertEquals($product1->name, $firstProduct['name']);
        $this->assertEquals($product1->description, $firstProduct['description']);
        $this->assertEquals(10, $firstProduct['price']);
        $this->assertEquals(5, $firstProduct['quantity']);
    }

    #[Test]
    public function it_can_search_offers_by_title(): void
    {
        $searchableOffer = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(1),
            'food_establishment_id' => $this->establishment->id,
            'title' => 'Pizza Special Offer',
            'description' => 'Special pizza discount'
        ]);

        $nonSearchableOffer = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(1),
            'food_establishment_id' => $this->establishment->id,
            'title' => 'Burger Combo',
            'description' => 'Burger with fries'
        ]);

        $product = Product::factory()->create([
            'name' => 'Coconut Water',
            'food_establishment_id' => $this->establishment->id,
        ]);

        ProductOffer::create([
            'offer_id' => $searchableOffer->id,
            'product_id' => $product->id,
            'price' => 12,
            'quantity' => 2,
            'expiration_date' => now()->addDays(10)
        ]);

        ProductOffer::create([
            'offer_id' => $nonSearchableOffer->id,
            'product_id' => $product->id,
            'price' => 8,
            'quantity' => 1,
            'expiration_date' => now()->addDays(10)
        ]);

        $searchableOffer->searchable();

        $response = $this->getJson('/api/offers?search=Pizza');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'has_more'
            ]);

        $responseData = $response->json();

        $this->assertCount(1, $responseData['data']);

        $offerFound = collect($responseData['data'])->contains('id', $searchableOffer->id);
        $this->assertTrue($offerFound, 'La oferta con "Pizza" en el título no se encontró en los datos de respuesta');

        $nonSearchableOfferFound = collect($responseData['data'])->contains('id', $nonSearchableOffer->id);
        $this->assertFalse($nonSearchableOfferFound, 'La oferta "Burger Combo" no debería estar en los resultados de búsqueda');

        $foundOffer = $responseData['data'][0];
        $this->assertEquals($searchableOffer->id, $foundOffer['id']);
        $this->assertEquals('Pizza Special Offer', $foundOffer['title']);
        $this->assertEquals('Special pizza discount', $foundOffer['description']);

        // Esto está para verificar que la búsqueda también funcione por el nombre del producto
        $responseCoconut = $this->getJson('/api/offers?search=Coconut');

        $responseCoconut->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'has_more'
            ]);

        $responseCoconutData = $responseCoconut->json();

        $this->assertCount(1, $responseCoconutData['data']);

        $offerFoundByProduct = collect($responseCoconutData['data'])->contains('id', $searchableOffer->id);
        $this->assertTrue($offerFoundByProduct, 'La oferta con producto "Coconut Water" no se encontró en los datos de respuesta');

        // Verificar que es la misma oferta en ambas búsquedas
        $foundOfferByProduct = $responseCoconutData['data'][0];
        $this->assertEquals($searchableOffer->id, $foundOfferByProduct['id']);
        $this->assertEquals('Pizza Special Offer', $foundOfferByProduct['title']);
        $this->assertEquals('Special pizza discount', $foundOfferByProduct['description']);
    }

    #[Test]
    public function it_handles_pagination_correctly(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            $offer = Offer::factory()->create([
                'state' => OfferState::ACTIVE->value,
                'expiration_datetime' => now()->addDays(1),
                'food_establishment_id' => $this->establishment->id,
                'title' => "Offer $i"
            ]);

            $product = Product::factory()->create([
                'food_establishment_id' => $this->establishment->id,
            ]);
            ProductOffer::create([
                'offer_id' => $offer->id,
                'product_id' => $product->id,
                'price' => 10,
                'quantity' => 1,
                'expiration_date' => now()->addDays(10)
            ]);
        }

        $response = $this->getJson('/api/offers?page=1');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'offer_quantity',
                        'title',
                        'description',
                        'expiration_datetime',
                        'establishment_id',
                        'establishment_name',
                        'establishment_address',
                        'food_establishment_id',
                        'products' => [
                            '*' => [
                                'name',
                                'description',
                                'quantity',
                                'price',
                                'expiration_date'
                            ]
                        ]
                    ]
                ],
                'current_page',
                'per_page',
                'has_more'
            ]);
        $responseData = $response->json();

        $this->assertCount(20, $responseData['data']);
        $this->assertEquals(1, $responseData['current_page']);
        $this->assertEquals(20, $responseData['per_page']);
        $this->assertTrue($responseData['has_more']);

        $response = $this->getJson('/api/offers?page=2');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'offer_quantity',
                        'title',
                        'description',
                        'expiration_datetime',
                        'establishment_id',
                        'establishment_name',
                        'establishment_address',
                        'food_establishment_id',
                        'products' => [
                            '*' => [
                                'name',
                                'description',
                                'quantity',
                                'price',
                                'expiration_date'
                            ]
                        ]
                    ]
                ],
                'current_page',
                'per_page',
                'has_more'
            ]);
        $responseData = $response->json();

        $this->assertCount(5, $responseData['data']);
        $this->assertEquals(2, $responseData['current_page']);
        $this->assertEquals(20, $responseData['per_page']);
        $this->assertFalse($responseData['has_more']);
    }

    #[Test]
    public function it_only_returns_active_and_non_expired_offers(): void
    {
        $activeValidOffer = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(1),
            'food_establishment_id' => $this->establishment->id
        ]);

        $inactiveOffer = Offer::factory()->create([
            'state' => OfferState::INACTIVE->value,
            'expiration_datetime' => now()->addDays(1),
            'food_establishment_id' => $this->establishment->id
        ]);

        $expiredOffer = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->subDays(1),
            'food_establishment_id' => $this->establishment->id
        ]);

        $product = Product::factory()->create([
            'food_establishment_id' => $this->establishment->id,
        ]);
        ProductOffer::create([
            'offer_id' => $activeValidOffer->id,
            'product_id' => $product->id,
            'price' => 10,
            'quantity' => 1,
            'expiration_date' => now()->addDays(10)
        ]);

        $response = $this->getJson('/api/offers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'offer_quantity',
                        'title',
                        'description',
                        'expiration_datetime',
                        'establishment_id',
                        'establishment_name',
                        'establishment_address',
                        'food_establishment_id',
                        'products' => [
                            '*' => [
                                'name',
                                'description',
                                'quantity',
                                'price',
                                'expiration_date'
                            ]
                        ]
                    ]
                ],
                'current_page',
                'per_page',
                'has_more'
            ]);
        $responseData = $response->json();

        $this->assertCount(1, $responseData['data']);
        $this->assertEquals($activeValidOffer->id, $responseData['data'][0]['id']);
    }

    #[Test]
    public function it_returns_empty_data_when_no_offers_available(): void
    {
        $response = $this->getJson('/api/offers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'has_more'
            ])
            ->assertJson([
                'data' => [],
                'current_page' => 1,
                'per_page' => 20,
                'has_more' => false
            ]);
    }
}
