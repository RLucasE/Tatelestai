<?php

namespace Tests\Feature;

use App\Enums\OfferState;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\PackTemplate;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PackSellerControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $seller;

    protected FoodEstablishment $establishment;

    protected PackTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(EstablishmentTypeSeeder::class);

        $this->seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $this->establishment = FoodEstablishment::factory()->create([
            'user_id' => $this->seller->id,
            'establishment_type_id' => EstablishmentType::inRandomOrder()->first()->id,
        ]);

        $this->template = PackTemplate::factory()->create([
            'food_establishment_id' => $this->establishment->id,
        ]);

        $this->actingAs($this->seller);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'pack_template_id' => $this->template->id,
            'price' => 3000,
            'minimum_value' => 9000,
            'quantity' => 5,
            'pickup_start_datetime' => now()->addHour()->format('Y-m-d H:i:s'),
            'pickup_end_datetime' => now()->addHours(3)->format('Y-m-d H:i:s'),
        ], $overrides);
    }

    private function createOtherEstablishment(): FoodEstablishment
    {
        $otherSeller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        return FoodEstablishment::factory()->create([
            'user_id' => $otherSeller->id,
            'establishment_type_id' => EstablishmentType::inRandomOrder()->first()->id,
        ]);
    }

    private function createPackFor(FoodEstablishment $establishment, array $overrides = []): Offer
    {
        $template = PackTemplate::factory()->create([
            'food_establishment_id' => $establishment->id,
        ]);

        return Offer::factory()
            ->fromTemplate($template)
            ->create(array_merge([
                'quantity' => 4,
                'state' => OfferState::ACTIVE->value,
            ], $overrides));
    }

    #[Test]
    public function it_can_publish_a_pack_from_a_template(): void
    {
        $response = $this->postJson('/api/packs', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Pack publicado exitosamente')
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'pack_template_id',
                    'food_establishment_id',
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
                ],
            ]);

        $offer = Offer::where('pack_template_id', $this->template->id)->first();

        $this->assertNotNull($offer);
        $this->assertSame(OfferState::ACTIVE->value, $offer->state);
        $this->assertSame(3000, (int) $offer->price);
        $this->assertSame(9000, (int) $offer->minimum_value);
        $this->assertSame(5, (int) $offer->quantity);
    }

    #[Test]
    public function it_publishes_a_snapshot_of_the_template(): void
    {
        $this->postJson('/api/packs', $this->validPayload());

        $offer = Offer::where('pack_template_id', $this->template->id)->first();

        $this->assertSame($this->template->title, $offer->title);
        $this->assertSame($this->template->description, $offer->description);
        $this->assertEquals($this->template->allergens, $offer->allergens);
        $this->assertEquals(
            (float) $this->template->estimated_weight_kg,
            (float) $offer->estimated_weight_kg
        );
    }

    #[Test]
    public function it_maps_the_pickup_window_to_the_offer_datetimes(): void
    {
        $pickupStart = now()->addHour()->startOfSecond();
        $pickupEnd = now()->addHours(3)->startOfSecond();

        $this->postJson('/api/packs', $this->validPayload([
            'pickup_start_datetime' => $pickupStart->format('Y-m-d H:i:s'),
            'pickup_end_datetime' => $pickupEnd->format('Y-m-d H:i:s'),
        ]));

        $offer = Offer::where('pack_template_id', $this->template->id)->first();

        $this->assertTrue($pickupStart->equalTo(Carbon::parse($offer->pickup_start_datetime)));
        $this->assertTrue($pickupEnd->equalTo(Carbon::parse($offer->expiration_datetime)));
    }

    #[Test]
    public function it_validates_that_minimum_value_is_greater_or_equal_to_price(): void
    {
        $response = $this->postJson('/api/packs', $this->validPayload([
            'price' => 5000,
            'minimum_value' => 3000,
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['minimum_value']);
    }

    #[Test]
    public function it_validates_the_pickup_window(): void
    {
        $response = $this->postJson('/api/packs', $this->validPayload([
            'pickup_start_datetime' => now()->addHours(3)->format('Y-m-d H:i:s'),
            'pickup_end_datetime' => now()->addHour()->format('Y-m-d H:i:s'),
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pickup_end_datetime']);
    }

    #[Test]
    public function it_cannot_publish_a_pack_from_another_sellers_template(): void
    {
        $otherEstablishment = $this->createOtherEstablishment();
        $otherTemplate = PackTemplate::factory()->create([
            'food_establishment_id' => $otherEstablishment->id,
        ]);

        $response = $this->postJson('/api/packs', $this->validPayload([
            'pack_template_id' => $otherTemplate->id,
        ]));

        $response->assertStatus(403)
            ->assertJsonPath('message', 'La plantilla de pack no pertenece a tu establecimiento');

        $this->assertDatabaseMissing('offers', [
            'pack_template_id' => $otherTemplate->id,
        ]);
    }

    #[Test]
    public function it_can_list_only_its_own_packs(): void
    {
        $this->createPackFor($this->establishment);

        $otherEstablishment = $this->createOtherEstablishment();
        $this->createPackFor($otherEstablishment);

        $response = $this->getJson('/api/my-packs');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.food_establishment_id', $this->establishment->id);
    }

    #[Test]
    public function it_can_show_its_own_pack(): void
    {
        $pack = $this->createPackFor($this->establishment);

        $response = $this->getJson("/api/my-packs/{$pack->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $pack->id);
    }

    #[Test]
    public function it_cannot_show_another_sellers_pack(): void
    {
        $otherEstablishment = $this->createOtherEstablishment();
        $pack = $this->createPackFor($otherEstablishment);

        $response = $this->getJson("/api/my-packs/{$pack->id}");

        $response->assertStatus(403);
    }

    #[Test]
    public function it_can_update_its_own_pack(): void
    {
        $pack = $this->createPackFor($this->establishment);
        $pickupStart = now()->addHours(2)->startOfSecond();
        $pickupEnd = now()->addHours(5)->startOfSecond();

        $response = $this->patchJson("/api/packs/{$pack->id}", [
            'quantity' => 12,
            'price' => 2500,
            'minimum_value' => 8000,
            'pickup_start_datetime' => $pickupStart->format('Y-m-d H:i:s'),
            'pickup_end_datetime' => $pickupEnd->format('Y-m-d H:i:s'),
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Pack actualizado exitosamente');

        $pack->refresh();
        $this->assertSame(12, (int) $pack->quantity);
        $this->assertSame(2500, (int) $pack->price);
        $this->assertSame(8000, (int) $pack->minimum_value);
        $this->assertTrue($pickupStart->equalTo(Carbon::parse($pack->pickup_start_datetime)));
        $this->assertTrue($pickupEnd->equalTo(Carbon::parse($pack->expiration_datetime)));
    }

    #[Test]
    public function it_cannot_update_another_sellers_pack(): void
    {
        $otherEstablishment = $this->createOtherEstablishment();
        $pack = $this->createPackFor($otherEstablishment);

        $response = $this->patchJson("/api/packs/{$pack->id}", [
            'quantity' => 99,
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('offers', [
            'id' => $pack->id,
            'quantity' => 99,
        ]);
    }

    #[Test]
    public function it_can_deactivate_its_own_pack(): void
    {
        $pack = $this->createPackFor($this->establishment);

        $response = $this->deleteJson("/api/packs/{$pack->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Pack dado de baja correctamente');

        $this->assertDatabaseHas('offers', [
            'id' => $pack->id,
            'state' => OfferState::INACTIVE->value,
        ]);
    }

    #[Test]
    public function it_can_publish_a_pack_without_template_id_creating_a_template_automatically(): void
    {
        $payload = [
            'title' => 'Bolsa Sorpresa Pastelería',
            'description' => 'Tortas, budines y tartas del día',
            'allergens' => ['gluten', 'lacteos', 'huevo'],
            'estimated_weight_kg' => 1.5,
            'price' => 3500,
            'minimum_value' => 10500,
            'quantity' => 6,
            'pickup_start_datetime' => now()->addHour()->format('Y-m-d H:i:s'),
            'pickup_end_datetime' => now()->addHours(3)->format('Y-m-d H:i:s'),
        ];

        $response = $this->postJson('/api/packs', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Pack publicado exitosamente')
            ->assertJsonPath('data.title', 'Bolsa Sorpresa Pastelería')
            ->assertJsonPath('data.description', 'Tortas, budines y tartas del día');

        $this->assertDatabaseHas('pack_templates', [
            'food_establishment_id' => $this->establishment->id,
            'title' => 'Bolsa Sorpresa Pastelería',
            'description' => 'Tortas, budines y tartas del día',
        ]);

        $createdTemplate = PackTemplate::where('title', 'Bolsa Sorpresa Pastelería')->first();
        $this->assertNotNull($createdTemplate);

        $this->assertDatabaseHas('offers', [
            'pack_template_id' => $createdTemplate->id,
            'food_establishment_id' => $this->establishment->id,
            'title' => 'Bolsa Sorpresa Pastelería',
            'price' => 3500,
            'minimum_value' => 10500,
            'quantity' => 6,
        ]);
    }

    #[Test]
    public function it_validates_title_and_description_when_no_template_id_is_provided(): void
    {
        $payload = [
            'price' => 3500,
            'minimum_value' => 10500,
            'quantity' => 6,
            'pickup_start_datetime' => now()->addHour()->format('Y-m-d H:i:s'),
            'pickup_end_datetime' => now()->addHours(3)->format('Y-m-d H:i:s'),
        ];

        $response = $this->postJson('/api/packs', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description']);
    }
}

