<?php

namespace Tests\Unit;

use App\Actions\Packs\PublishPackAction;
use App\DTOs\PackDTO;
use App\Enums\OfferState;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Exceptions\Pack\PackTemplateOwnershipException;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\PackTemplate;
use App\Models\User;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublishPackActionTest extends TestCase
{
    use RefreshDatabase;

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

    private function dtoFor(int $templateId, array $overrides = []): PackDTO
    {
        return new PackDTO(
            packTemplateId: $templateId,
            price: $overrides['price'] ?? 2000,
            minimumValue: $overrides['minimum_value'] ?? 6000,
            quantity: $overrides['quantity'] ?? 4,
            pickupStartDatetime: now()->addHour()->toDateTimeString(),
            pickupEndDatetime: now()->addHours(3)->toDateTimeString(),
        );
    }

    #[Test]
    public function it_publishes_an_active_pack_snapshotting_the_template(): void
    {
        $offer = app(PublishPackAction::class)->execute($this->dtoFor($this->template->id));

        $this->assertSame(OfferState::ACTIVE->value, $offer->state);
        $this->assertSame($this->template->id, $offer->pack_template_id);
        $this->assertSame($this->establishment->id, $offer->food_establishment_id);
        $this->assertSame($this->template->title, $offer->title);
        $this->assertSame($this->template->description, $offer->description);
        $this->assertEquals($this->template->allergens, $offer->allergens);
        $this->assertEquals(
            (float) $this->template->estimated_weight_kg,
            (float) $offer->estimated_weight_kg
        );
        $this->assertSame(2000, (int) $offer->price);
        $this->assertSame(6000, (int) $offer->minimum_value);
        $this->assertSame(4, (int) $offer->quantity);
        $this->assertNotNull($offer->pickup_start_datetime);
        $this->assertNotNull($offer->expiration_datetime);
    }

    #[Test]
    public function it_rejects_a_template_from_another_establishment(): void
    {
        $otherSeller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $otherEstablishment = FoodEstablishment::factory()->create([
            'user_id' => $otherSeller->id,
            'establishment_type_id' => EstablishmentType::inRandomOrder()->first()->id,
        ]);

        $otherTemplate = PackTemplate::factory()->create([
            'food_establishment_id' => $otherEstablishment->id,
        ]);

        $this->expectException(PackTemplateOwnershipException::class);

        app(PublishPackAction::class)->execute($this->dtoFor($otherTemplate->id));
    }

    #[Test]
    public function it_publishes_a_pack_and_automatically_creates_a_template_when_no_template_is_provided(): void
    {
        $dto = new PackDTO(
            packTemplateId: null,
            title: 'Bolsa Sorpresa Panadería',
            description: 'Medialunas y panes artesanales del día',
            allergens: ['gluten', 'lacteos'],
            estimatedWeightKg: 1.25,
            price: 2500,
            minimumValue: 7500,
            quantity: 3,
            pickupStartDatetime: now()->addHour()->toDateTimeString(),
            pickupEndDatetime: now()->addHours(2)->toDateTimeString(),
        );

        $offer = app(PublishPackAction::class)->execute($dto);

        $this->assertSame(OfferState::ACTIVE->value, $offer->state);
        $this->assertNotNull($offer->pack_template_id);
        $this->assertSame($this->establishment->id, $offer->food_establishment_id);
        $this->assertSame('Bolsa Sorpresa Panadería', $offer->title);
        $this->assertSame('Medialunas y panes artesanales del día', $offer->description);
        $this->assertEquals(['gluten', 'lacteos'], $offer->allergens);
        $this->assertEquals(1.25, (float) $offer->estimated_weight_kg);
        $this->assertSame(2500, (int) $offer->price);
        $this->assertSame(7500, (int) $offer->minimum_value);
        $this->assertSame(3, (int) $offer->quantity);

        $template = PackTemplate::find($offer->pack_template_id);
        $this->assertNotNull($template);
        $this->assertSame($this->establishment->id, $template->food_establishment_id);
        $this->assertSame('Bolsa Sorpresa Panadería', $template->title);
        $this->assertSame('Medialunas y panes artesanales del día', $template->description);
        $this->assertEquals(['gluten', 'lacteos'], $template->allergens);
        $this->assertEquals(1.25, (float) $template->estimated_weight_kg);
    }
}

