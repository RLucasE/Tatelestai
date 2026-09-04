<?php

namespace Tests\Unit\Search;

use App\Contracts\Search\SearchServiceInterface;
use App\Enums\OfferState;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\User;
use App\Search\Adapters\TypesenseSearchAdapter;
use App\Search\DTOs\SearchQueryDTO;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TypesenseSearchAdapterTest extends TestCase
{
    use RefreshDatabase;

    private TypesenseSearchAdapter $adapter;

    private FoodEstablishment $establishment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(EstablishmentTypeSeeder::class);

        $seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $establishmentType = EstablishmentType::first();

        $this->establishment = FoodEstablishment::factory()->create([
            'user_id' => $seller->id,
            'establishment_type_id' => $establishmentType->id,
        ]);

        $this->adapter = new TypesenseSearchAdapter;
    }

    #[Test]
    public function it_implements_search_service_interface(): void
    {
        $this->assertInstanceOf(SearchServiceInterface::class, $this->adapter);
    }

    #[Test]
    public function it_searches_offers_and_eager_loads_required_relations(): void
    {
        $offer = Offer::factory()->create([
            'food_establishment_id' => $this->establishment->id,
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(3),
            'title' => 'Special Margherita Pizza',
            'description' => 'Cheesy pizza',
        ]);

        $product = Product::factory()->create([
            'food_establishment_id' => $this->establishment->id,
            'name' => 'Mozzarella Cheese',
            'description' => 'Fresh mozzarella',
        ]);

        ProductOffer::create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'price' => 12,
            'quantity' => 4,
            'expiration_date' => now()->addDays(5),
        ]);

        $offer->searchable();

        $query = new SearchQueryDTO(query: 'Margherita');
        $results = $this->adapter->searchOffers($query);

        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(1, $results);

        $foundOffer = $results->first();
        $this->assertEquals($offer->id, $foundOffer->id);

        // Verificar que las relaciones esenciales para el DTO estén cargadas en memoria (evita consultas N+1)
        $this->assertTrue($foundOffer->relationLoaded('fullProducts'), 'fullProducts debe estar cargada vía eager loading');
        $this->assertTrue($foundOffer->relationLoaded('foodEstablishment'), 'foodEstablishment debe estar cargada vía eager loading');

        $this->assertCount(1, $foundOffer->fullProducts);
        $loadedProduct = $foundOffer->fullProducts->first();
        $this->assertEquals('Mozzarella Cheese', $loadedProduct->name);
        $this->assertEquals(12, $loadedProduct->pivot->price);

        $this->assertEquals(4, $loadedProduct->pivot->quantity);
        $this->assertNotNull($loadedProduct->pivot->expiration_date);

        $this->assertEquals($this->establishment->id, $foundOffer->foodEstablishment->id);
    }

    #[Test]
    public function it_filters_out_expired_and_inactive_offers_during_search(): void
    {
        $validOffer = Offer::factory()->create([
            'food_establishment_id' => $this->establishment->id,
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(2),
            'title' => 'Empanadas Valid',
        ]);

        $expiredOffer = Offer::factory()->create([
            'food_establishment_id' => $this->establishment->id,
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->subDays(1),
            'title' => 'Empanadas Expired',
        ]);

        $inactiveOffer = Offer::factory()->create([
            'food_establishment_id' => $this->establishment->id,
            'state' => OfferState::INACTIVE->value,
            'expiration_datetime' => now()->addDays(2),
            'title' => 'Empanadas Inactive',
        ]);

        $validOffer->searchable();
        $expiredOffer->searchable();
        $inactiveOffer->searchable();

        $query = new SearchQueryDTO(query: 'Empanadas');
        $results = $this->adapter->searchOffers($query);

        $this->assertCount(1, $results);
        $this->assertEquals($validOffer->id, $results->first()->id);
        $this->assertFalse($results->contains('id', $expiredOffer->id));
        $this->assertFalse($results->contains('id', $inactiveOffer->id));
    }

    #[Test]
    public function it_throws_exception_when_indexing_non_existent_offer(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->adapter->indexOffer(999999);
    }

    #[Test]
    public function it_can_index_offers_in_batch_with_relations(): void
    {
        $offer1 = Offer::factory()->create([
            'food_establishment_id' => $this->establishment->id,
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(2),
            'title' => 'Batch Offer 1',
        ]);

        $offer2 = Offer::factory()->create([
            'food_establishment_id' => $this->establishment->id,
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(3),
            'title' => 'Batch Offer 2',
        ]);

        // Ejecutar indexación por lote
        $this->adapter->indexOffers([$offer1->id, $offer2->id]);

        // Si se pasa un arreglo vacío, no debe fallar
        $this->adapter->indexOffers([]);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_throws_exception_when_removing_non_existent_offer(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->adapter->removeOfferFromIndex(999999);
    }

    #[Test]
    public function it_can_flush_and_reindex_all_offers(): void
    {
        Offer::factory()->count(2)->create([
            'food_establishment_id' => $this->establishment->id,
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(2),
        ]);

        $this->adapter->flushIndex();
        $this->adapter->reindexAll();

        $this->assertTrue(true);
    }

    #[Test]
    public function it_handles_exceptions_gracefully_during_search_and_logs_error(): void
    {
        Log::shouldReceive('error')
            ->once();

        // Adaptador con método performSearch que arroja excepción para simular fallo de red en Typesense
        $mockAdapter = $this->getMockBuilder(TypesenseSearchAdapter::class)
            ->onlyMethods(['performSearch'])
            ->getMock();

        $mockAdapter->method('performSearch')
            ->willThrowException(new \RuntimeException('Connection timeout with Typesense server'));

        $query = new SearchQueryDTO(query: 'Burger');
        $result = $mockAdapter->searchOffers($query);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    #[Test]
    public function it_searches_offers_within_a_given_geolocation_and_radius(): void
    {
        $establishmentType = EstablishmentType::first();

        // Establecimiento A (cercano, Obelisco CABA: -34.6037, -58.3816)
        $sellerA = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $establishmentA = FoodEstablishment::factory()->create([
            'user_id' => $sellerA->id,
            'establishment_type_id' => $establishmentType->id,
            'latitude' => -34.6037,
            'longitude' => -58.3816,
        ]);

        // Establecimiento B (lejano, La Plata: -34.9214, -57.9545, ~55 km)
        $sellerB = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $establishmentB = FoodEstablishment::factory()->create([
            'user_id' => $sellerB->id,
            'establishment_type_id' => $establishmentType->id,
            'latitude' => -34.9214,
            'longitude' => -57.9545,
        ]);

        $offerA = Offer::factory()->create([
            'food_establishment_id' => $establishmentA->id,
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(3),
            'title' => 'Pizza Napolitana',
            'description' => 'Deliciosa pizza napolitana',
        ]);

        $productA = Product::factory()->create([
            'food_establishment_id' => $establishmentA->id,
        ]);
        ProductOffer::create([
            'offer_id' => $offerA->id,
            'product_id' => $productA->id,
            'price' => 15,
            'quantity' => 2,
            'expiration_date' => now()->addDays(5),
        ]);

        $offerB = Offer::factory()->create([
            'food_establishment_id' => $establishmentB->id,
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(3),
            'title' => 'Pizza Fugazzeta',
            'description' => 'Deliciosa pizza fugazzeta',
        ]);

        $productB = Product::factory()->create([
            'food_establishment_id' => $establishmentB->id,
        ]);
        ProductOffer::create([
            'offer_id' => $offerB->id,
            'product_id' => $productB->id,
            'price' => 18,
            'quantity' => 3,
            'expiration_date' => now()->addDays(5),
        ]);

        $offerA->searchable();
        $offerB->searchable();

        $query = new SearchQueryDTO(
            query: 'Pizza',
            latitude: -34.6037,
            longitude: -58.3816,
            radiusKm: 10.0,
        );

        $results = $this->adapter->searchOffers($query);

        $this->assertInstanceOf(Collection::class, $results);
        $this->assertTrue($results->contains('id', $offerA->id), 'El resultado debe contener la oferta cercana A (dentro del radio)');
        $this->assertFalse($results->contains('id', $offerB->id), 'El resultado NO debe contener la oferta lejana B (fuera del radio)');
    }

    #[Test]
    public function it_orders_offers_by_distance_when_geofilter_is_applied(): void
    {
        $establishmentType = EstablishmentType::first();

        // Punto de referencia: Obelisco (-34.6037, -58.3816)
        // Establecimiento cercano (~1 km: delta latitud ~0.009)
        $sellerNear = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $establishmentNear = FoodEstablishment::factory()->create([
            'user_id' => $sellerNear->id,
            'establishment_type_id' => $establishmentType->id,
            'latitude' => -34.6127,
            'longitude' => -58.3816,
        ]);

        // Establecimiento más lejano dentro del radio (~4 km: delta latitud ~0.036)
        $sellerFar = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $establishmentFar = FoodEstablishment::factory()->create([
            'user_id' => $sellerFar->id,
            'establishment_type_id' => $establishmentType->id,
            'latitude' => -34.6397,
            'longitude' => -58.3816,
        ]);

        $offerFar = Offer::factory()->create([
            'food_establishment_id' => $establishmentFar->id,
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(3),
            'title' => 'Pizza Lejana',
        ]);

        $productFar = Product::factory()->create([
            'food_establishment_id' => $establishmentFar->id,
        ]);
        ProductOffer::create([
            'offer_id' => $offerFar->id,
            'product_id' => $productFar->id,
            'price' => 12,
            'quantity' => 1,
            'expiration_date' => now()->addDays(5),
        ]);

        $offerNear = Offer::factory()->create([
            'food_establishment_id' => $establishmentNear->id,
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(3),
            'title' => 'Pizza Cercana',
        ]);

        $productNear = Product::factory()->create([
            'food_establishment_id' => $establishmentNear->id,
        ]);
        ProductOffer::create([
            'offer_id' => $offerNear->id,
            'product_id' => $productNear->id,
            'price' => 14,
            'quantity' => 1,
            'expiration_date' => now()->addDays(5),
        ]);

        // Indexamos primero la lejana para verificar que el orden responda a la distancia y no al orden de inserción
        $offerFar->searchable();
        $offerNear->searchable();

        $query = new SearchQueryDTO(
            query: 'Pizza',
            latitude: -34.6037,
            longitude: -58.3816,
            radiusKm: 10.0,
        );

        $results = $this->adapter->searchOffers($query);

        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(2, $results);
        $this->assertEquals($offerNear->id, $results->first()->id, 'La primera oferta devuelta debe ser la más cercana');
        $this->assertEquals($offerFar->id, $results->last()->id, 'La última oferta devuelta debe ser la más lejana');
    }
}
