<?php

namespace Tests\Feature;

use App\Enums\OfferState;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\User;
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
            'description' => 'Description for active offer 1',
        ]);

        $activeOffer2 = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(2),
            'food_establishment_id' => $this->establishment->id,
            'title' => 'Active Offer 2',
            'description' => 'Description for active offer 2',
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
            'expiration_date' => now()->addDays(10),
        ]);

        ProductOffer::create([
            'offer_id' => $activeOffer2->id,
            'product_id' => $product2->id,
            'price' => 15,
            'quantity' => 3,
            'expiration_date' => now()->addDays(15),
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
                                'expiration_date',
                            ],
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
            'description' => 'Special pizza discount',
        ]);

        $nonSearchableOffer = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(1),
            'food_establishment_id' => $this->establishment->id,
            'title' => 'Burger Combo',
            'description' => 'Burger with fries',
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
            'expiration_date' => now()->addDays(10),
        ]);

        ProductOffer::create([
            'offer_id' => $nonSearchableOffer->id,
            'product_id' => $product->id,
            'price' => 8,
            'quantity' => 1,
            'expiration_date' => now()->addDays(10),
        ]);

        $searchableOffer->searchable();

        $response = $this->getJson('/api/offers?search=Pizza');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'has_more',
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
                'has_more',
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
                'title' => "Offer $i",
            ]);

            $product = Product::factory()->create([
                'food_establishment_id' => $this->establishment->id,
            ]);
            ProductOffer::create([
                'offer_id' => $offer->id,
                'product_id' => $product->id,
                'price' => 10,
                'quantity' => 1,
                'expiration_date' => now()->addDays(10),
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
                                'expiration_date',
                            ],
                        ],
                    ],
                ],
                'current_page',
                'per_page',
                'has_more',
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
                                'expiration_date',
                            ],
                        ],
                    ],
                ],
                'current_page',
                'per_page',
                'has_more',
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
            'food_establishment_id' => $this->establishment->id,
        ]);

        $inactiveOffer = Offer::factory()->create([
            'state' => OfferState::INACTIVE->value,
            'expiration_datetime' => now()->addDays(1),
            'food_establishment_id' => $this->establishment->id,
        ]);

        $verifyingOffer = Offer::factory()->create([
            'state' => OfferState::VERIFIYING->value,
            'expiration_datetime' => now()->addDays(1),
            'food_establishment_id' => $this->establishment->id,
        ]);

        $expiredOffer = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->subDays(1),
            'food_establishment_id' => $this->establishment->id,
        ]);

        $product = Product::factory()->create([
            'food_establishment_id' => $this->establishment->id,
        ]);
        ProductOffer::create([
            'offer_id' => $activeValidOffer->id,
            'product_id' => $product->id,
            'price' => 10,
            'quantity' => 1,
            'expiration_date' => now()->addDays(10),
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
                                'expiration_date',
                            ],
                        ],
                    ],
                ],
                'current_page',
                'per_page',
                'has_more',
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
                'has_more',
            ])
            ->assertJson([
                'data' => [],
                'current_page' => 1,
                'per_page' => 20,
                'has_more' => false,
            ]);
    }

    #[Test]
    public function it_does_not_return_verifying_offers_in_search_results(): void
    {
        $activeOffer = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(1),
            'food_establishment_id' => $this->establishment->id,
            'title' => 'Pizza Special Active',
            'description' => 'Active pizza offer',
        ]);

        $verifyingOffer = Offer::factory()->create([
            'state' => OfferState::VERIFIYING->value,
            'expiration_datetime' => now()->addDays(1),
            'food_establishment_id' => $this->establishment->id,
            'title' => 'Pizza Special Verifying',
            'description' => 'Verifying pizza offer',
        ]);

        $product = Product::factory()->create([
            'food_establishment_id' => $this->establishment->id,
        ]);

        ProductOffer::create([
            'offer_id' => $activeOffer->id,
            'product_id' => $product->id,
            'price' => 10,
            'quantity' => 1,
            'expiration_date' => now()->addDays(10),
        ]);

        ProductOffer::create([
            'offer_id' => $verifyingOffer->id,
            'product_id' => $product->id,
            'price' => 12,
            'quantity' => 1,
            'expiration_date' => now()->addDays(10),
        ]);

        $activeOffer->searchable();
        $verifyingOffer->searchable();

        $response = $this->getJson('/api/offers?search=Pizza');

        $response->assertStatus(200);
        $responseData = $response->json();

        $this->assertCount(1, $responseData['data']);
        $this->assertEquals($activeOffer->id, $responseData['data'][0]['id']);
        $this->assertEquals('Pizza Special Active', $responseData['data'][0]['title']);

        $verifyingOfferFound = collect($responseData['data'])->contains('id', $verifyingOffer->id);
        $this->assertFalse($verifyingOfferFound, 'La oferta en estado VERIFYING no debería aparecer en los resultados de búsqueda');
    }

    #[Test]
    public function it_does_not_return_expired_offers_in_search_results(): void
    {
        $validOffer = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(2),
            'food_establishment_id' => $this->establishment->id,
            'title' => 'Delicious Pizza Active',
            'description' => 'Active pizza',
        ]);

        $expiredOffer = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->subDays(2),
            'food_establishment_id' => $this->establishment->id,
            'title' => 'Delicious Pizza Expired',
            'description' => 'Expired pizza',
        ]);

        $product = Product::factory()->create([
            'food_establishment_id' => $this->establishment->id,
        ]);

        ProductOffer::create([
            'offer_id' => $validOffer->id,
            'product_id' => $product->id,
            'price' => 10,
            'quantity' => 1,
            'expiration_date' => now()->addDays(10),
        ]);

        ProductOffer::create([
            'offer_id' => $expiredOffer->id,
            'product_id' => $product->id,
            'price' => 10,
            'quantity' => 1,
            'expiration_date' => now()->addDays(10),
        ]);

        $validOffer->searchable();
        $expiredOffer->searchable();

        $response = $this->getJson('/api/offers?search=Pizza');

        $response->assertStatus(200);
        $responseData = $response->json();

        $this->assertCount(1, $responseData['data']);
        $this->assertEquals($validOffer->id, $responseData['data'][0]['id']);

        $expiredFound = collect($responseData['data'])->contains('id', $expiredOffer->id);
        $this->assertFalse($expiredFound, 'La oferta expirada no debería aparecer en los resultados de búsqueda');
    }

    #[Test]
    public function it_handles_pagination_correctly_with_search(): void
    {
        $product = Product::factory()->create([
            'food_establishment_id' => $this->establishment->id,
        ]);

        for ($i = 1; $i <= 25; $i++) {
            $offer = Offer::factory()->create([
                'state' => OfferState::ACTIVE->value,
                'expiration_datetime' => now()->addDays(1),
                'food_establishment_id' => $this->establishment->id,
                'title' => "Tacos Batch $i",
                'description' => "Description for taco $i",
            ]);

            ProductOffer::create([
                'offer_id' => $offer->id,
                'product_id' => $product->id,
                'price' => 10,
                'quantity' => 1,
                'expiration_date' => now()->addDays(10),
            ]);

            $offer->searchable();
        }

        // Página 1 de la búsqueda
        $responsePage1 = $this->getJson('/api/offers?search=Tacos&page=1');
        $responsePage1->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'has_more',
            ]);

        $dataPage1 = $responsePage1->json();
        $this->assertCount(20, $dataPage1['data']);
        $this->assertEquals(1, $dataPage1['current_page']);
        $this->assertEquals(20, $dataPage1['per_page']);
        $this->assertTrue($dataPage1['has_more']);

        // Página 2 de la búsqueda
        $responsePage2 = $this->getJson('/api/offers?search=Tacos&page=2');
        $responsePage2->assertStatus(200);

        $dataPage2 = $responsePage2->json();
        $this->assertCount(5, $dataPage2['data']);
        $this->assertEquals(2, $dataPage2['current_page']);
        $this->assertEquals(20, $dataPage2['per_page']);
        $this->assertFalse($dataPage2['has_more']);
    }

    #[Test]
    public function it_can_search_offers_with_geolocation_and_radius(): void
    {
        $establishmentType = EstablishmentType::first();

        // Establecimiento cercano (Obelisco CABA)
        $sellerNear = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $nearEstablishment = FoodEstablishment::factory()->create([
            'user_id' => $sellerNear->id,
            'establishment_type_id' => $establishmentType->id,
            'latitude' => -34.6037,
            'longitude' => -58.3816,
        ]);

        // Establecimiento lejano (La Plata, ~55 km)
        $sellerFar = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $farEstablishment = FoodEstablishment::factory()->create([
            'user_id' => $sellerFar->id,
            'establishment_type_id' => $establishmentType->id,
            'latitude' => -34.9214,
            'longitude' => -57.9545,
        ]);

        $nearOffer = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(2),
            'food_establishment_id' => $nearEstablishment->id,
            'title' => 'Pizza Napolitana Cercana',
            'description' => 'Pizza en CABA',
        ]);

        $productNear = Product::factory()->create([
            'food_establishment_id' => $nearEstablishment->id,
        ]);
        ProductOffer::create([
            'offer_id' => $nearOffer->id,
            'product_id' => $productNear->id,
            'price' => 15,
            'quantity' => 2,
            'expiration_date' => now()->addDays(5),
        ]);

        $farOffer = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(2),
            'food_establishment_id' => $farEstablishment->id,
            'title' => 'Pizza Fugazzeta Lejana',
            'description' => 'Pizza en La Plata',
        ]);

        $productFar = Product::factory()->create([
            'food_establishment_id' => $farEstablishment->id,
        ]);
        ProductOffer::create([
            'offer_id' => $farOffer->id,
            'product_id' => $productFar->id,
            'price' => 18,
            'quantity' => 1,
            'expiration_date' => now()->addDays(5),
        ]);

        $nearOffer->searchable();
        $farOffer->searchable();

        $response = $this->getJson('/api/offers?search=Pizza&lat=-34.6037&lng=-58.3816&radius=5');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'establishment_latitude',
                        'establishment_longitude',
                    ],
                ],
                'current_page',
                'per_page',
                'has_more',
            ]);

        $responseData = $response->json();

        $this->assertTrue(
            collect($responseData['data'])->contains('id', $nearOffer->id),
            'La oferta cercana debe estar presente en los resultados'
        );
        $this->assertFalse(
            collect($responseData['data'])->contains('id', $farOffer->id),
            'La oferta lejana no debe estar presente en los resultados'
        );

        $foundOffer = collect($responseData['data'])->firstWhere('id', $nearOffer->id);
        $this->assertEquals(-34.6037, (float) $foundOffer['establishment_latitude']);
        $this->assertEquals(-58.3816, (float) $foundOffer['establishment_longitude']);
    }
}
