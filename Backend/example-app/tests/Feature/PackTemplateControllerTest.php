<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\PackTemplate;
use App\Models\User;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PackTemplateControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $seller;

    protected FoodEstablishment $establishment;

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

        $this->actingAs($this->seller);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Bolsa Sorpresa Panadería',
            'description' => 'Panificados del día con valor mínimo garantizado.',
            'allergens' => ['gluten', 'lácteos'],
            'estimated_weight_kg' => 1.25,
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

    #[Test]
    public function it_can_create_a_pack_template(): void
    {
        $response = $this->postJson('/api/pack-templates', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Plantilla de pack creada exitosamente')
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'food_establishment_id',
                    'title',
                    'description',
                    'allergens',
                    'estimated_weight_kg',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('pack_templates', [
            'food_establishment_id' => $this->establishment->id,
            'title' => 'Bolsa Sorpresa Panadería',
        ]);
    }

    #[Test]
    public function it_validates_required_fields_when_creating_a_pack_template(): void
    {
        $response = $this->postJson('/api/pack-templates', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description']);
    }

    #[Test]
    public function it_can_list_own_pack_templates(): void
    {
        PackTemplate::factory()->count(2)->create([
            'food_establishment_id' => $this->establishment->id,
        ]);

        $otherEstablishment = $this->createOtherEstablishment();
        PackTemplate::factory()->create([
            'food_establishment_id' => $otherEstablishment->id,
        ]);

        $response = $this->getJson('/api/pack-templates');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.food_establishment_id', $this->establishment->id);
    }

    #[Test]
    public function it_cannot_view_another_sellers_pack_template(): void
    {
        $otherEstablishment = $this->createOtherEstablishment();
        $template = PackTemplate::factory()->create([
            'food_establishment_id' => $otherEstablishment->id,
        ]);

        $response = $this->getJson("/api/pack-templates/{$template->id}");

        $response->assertStatus(403);
    }

    #[Test]
    public function it_can_update_own_pack_template(): void
    {
        $template = PackTemplate::factory()->create([
            'food_establishment_id' => $this->establishment->id,
        ]);

        $response = $this->patchJson("/api/pack-templates/{$template->id}", [
            'title' => 'Bolsa actualizada',
            'estimated_weight_kg' => 2.5,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Plantilla de pack actualizada exitosamente');

        $this->assertDatabaseHas('pack_templates', [
            'id' => $template->id,
            'title' => 'Bolsa actualizada',
        ]);
    }

    #[Test]
    public function it_cannot_update_another_sellers_pack_template(): void
    {
        $otherEstablishment = $this->createOtherEstablishment();
        $template = PackTemplate::factory()->create([
            'food_establishment_id' => $otherEstablishment->id,
        ]);

        $response = $this->patchJson("/api/pack-templates/{$template->id}", [
            'title' => 'Intento ajeno',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('pack_templates', ['title' => 'Intento ajeno']);
    }

    #[Test]
    public function it_can_delete_own_pack_template(): void
    {
        $template = PackTemplate::factory()->create([
            'food_establishment_id' => $this->establishment->id,
        ]);

        $response = $this->deleteJson("/api/pack-templates/{$template->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('pack_templates', ['id' => $template->id]);
    }

    #[Test]
    public function it_returns_not_found_for_unknown_pack_template(): void
    {
        $response = $this->getJson('/api/pack-templates/999999');

        $response->assertStatus(404);
    }
}
