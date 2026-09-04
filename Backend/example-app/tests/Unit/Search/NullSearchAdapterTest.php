<?php

namespace Tests\Unit\Search;

use App\Contracts\Search\SearchServiceInterface;
use App\Enums\OfferState;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\User;
use App\Search\Adapters\NullSearchAdapter;
use App\Search\DTOs\SearchQueryDTO;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NullSearchAdapterTest extends TestCase
{
    use RefreshDatabase;

    private SearchServiceInterface $searchAdapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->searchAdapter = new NullSearchAdapter;
    }

    #[Test]
    public function it_implements_search_service_interface(): void
    {
        $this->assertInstanceOf(SearchServiceInterface::class, $this->searchAdapter);
    }

    #[Test]
    public function it_returns_an_empty_collection_regardless_of_query(): void
    {
        $queries = [
            new SearchQueryDTO(query: 'Pizza'),
            new SearchQueryDTO(query: '', state: OfferState::ACTIVE->value),
            new SearchQueryDTO(query: 'NonExistent', page: 2, perPage: 10),
        ];

        foreach ($queries as $query) {
            $result = $this->searchAdapter->searchOffers($query);

            $this->assertInstanceOf(Collection::class, $result);
            $this->assertTrue($result->isEmpty());
            $this->assertCount(0, $result);
        }
    }

    #[Test]
    public function it_safely_executes_indexing_methods_as_no_ops_without_side_effects(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(EstablishmentTypeSeeder::class);

        $seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $establishmentType = EstablishmentType::first();
        $establishment = FoodEstablishment::factory()->create([
            'user_id' => $seller->id,
            'establishment_type_id' => $establishmentType->id,
        ]);

        $offer = Offer::factory()->create([
            'food_establishment_id' => $establishment->id,
            'state' => OfferState::ACTIVE->value,
            'title' => 'Original Offer Title',
        ]);

        // Verificar que la invocación de ningún método altere los datos ni lance excepciones
        $this->searchAdapter->indexOffer($offer->id);
        $this->searchAdapter->indexOffers([$offer->id, 99999]);
        $this->searchAdapter->removeOfferFromIndex($offer->id);
        $this->searchAdapter->flushIndex();
        $this->searchAdapter->reindexAll();

        // El registro de la BD permanece intacto
        $this->assertDatabaseHas('offers', [
            'id' => $offer->id,
            'title' => 'Original Offer Title',
        ]);
    }
}
