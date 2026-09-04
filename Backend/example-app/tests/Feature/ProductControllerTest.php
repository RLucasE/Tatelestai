<?php

namespace Tests\Feature;

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
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;

    protected FoodEstablishment $establishment;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(EstablishmentTypeSeeder::class);

        $this->seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $establishmentType = EstablishmentType::first();

        $this->establishment = FoodEstablishment::factory()->create([
            'user_id' => $this->seller->id,
            'establishment_type_id' => $establishmentType->id,
        ]);

        $this->product = Product::factory()->create([
            'food_establishment_id' => $this->establishment->id,
            'name' => 'Original Product',
            'description' => 'Original Description',
        ]);

        $this->actingAs($this->seller);
    }

    #[Test]
    public function it_reindexes_associated_offers_when_product_is_updated(): void
    {
        $offer = Offer::factory()->create([
            'food_establishment_id' => $this->establishment->id,
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(2),
        ]);

        ProductOffer::create([
            'offer_id' => $offer->id,
            'product_id' => $this->product->id,
            'price' => 15,
            'quantity' => 2,
            'expiration_date' => now()->addDays(5),
        ]);

        $searchServiceMock = Mockery::mock(SearchServiceInterface::class);
        $searchServiceMock->shouldReceive('indexOffer')
            ->once()
            ->with($offer->id);

        $this->app->instance(SearchServiceInterface::class, $searchServiceMock);

        $response = $this->patchJson("/api/products/{$this->product->id}", [
            'name' => 'Updated Product Name',
            'description' => 'Updated Product Description',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Producto actualizado exitosamente',
                'product' => [
                    'id' => $this->product->id,
                    'name' => 'Updated Product Name',
                    'description' => 'Updated Product Description',
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'name' => 'Updated Product Name',
        ]);
    }
}
