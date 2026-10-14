<?php

namespace Tests\Feature\Verification;

use App\Enums\TrustScoreCriterion;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminGooglePlacesAndPhoneReviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $seller;
    protected FoodEstablishment $establishment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);

        $this->admin = User::factory()->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $this->admin->assignRole(UserRole::ADMIN->value);

        $this->seller = User::factory()->create([
            'state' => UserState::WAITING_FOR_CONFIRMATION->value,
        ]);
        $this->seller->assignRole(UserRole::SELLER->value);

        $type = EstablishmentType::factory()->create(['state' => 'active']);

        $this->establishment = FoodEstablishment::create([
            'user_id' => $this->seller->id,
            'name' => 'Pizzería Los Hornos',
            'address' => 'Av. Belgrano 500',
            'phone' => '3874112233',
            'google_place_id' => 'ChIJpizzeria123',
            'google_place_data' => [
                'name' => 'Pizzería Los Hornos',
                'formatted_phone_number' => '+54 387 4112233',
            ],
            'establishment_type_id' => $type->id,
            'verification_status' => 'pending',
        ]);
    }

    #[Test]
    public function admin_can_verify_google_places_profile_and_grant_20_points()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson("/api/adm/establishments/{$this->establishment->id}/verify-google-places", [
            'is_verified' => true,
            'notes' => 'Ficha de Google Maps coincide con la dirección y tiene reseñas reales',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('trust_score', 20)
            ->assertJsonPath('trust_level', 'red');

        $this->assertDatabaseHas('establishment_trust_logs', [
            'food_establishment_id' => $this->establishment->id,
            'source_type' => TrustScoreCriterion::GOOGLE_PLACES_PROFILE->value,
            'points' => 20,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function admin_can_verify_phone_call_and_grant_30_points()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson("/api/adm/establishments/{$this->establishment->id}/verify-phone-call", [
            'call_successful' => true,
            'notes' => 'Hablé con el encargado Mario y confirmó el alta de la pizzería',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('trust_score', 30)
            ->assertJsonPath('trust_level', 'red');

        $this->assertDatabaseHas('establishment_trust_logs', [
            'food_establishment_id' => $this->establishment->id,
            'source_type' => TrustScoreCriterion::PHONE_CALL_VERIFICATION->value,
            'points' => 30,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function seller_can_see_verification_summary_with_score_and_missing_recommendations()
    {
        // Administrador valida llamada
        $this->establishment->trustLogs()->create([
            'source_type' => TrustScoreCriterion::PHONE_CALL_VERIFICATION,
            'points' => 30,
            'verified_by_user_id' => $this->admin->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->seller);

        $response = $this->getJson('/api/seller/verification-summary');

        $response->assertStatus(200)
            ->assertJsonPath('trust_score', 30)
            ->assertJsonPath('trust_level', 'red')
            ->assertJsonPath('is_eligible', false)
            ->assertJsonStructure([
                'trust_score',
                'trust_level',
                'is_eligible',
                'active_logs',
                'missing_recommendations',
            ]);
    }

    #[Test]
    public function admin_can_manually_approve_establishment_and_activate_user()
    {
        $this->actingAs($this->admin);

        $response = $this->patchJson("/api/adm/establishments/{$this->establishment->id}/verify", [
            'notes' => 'Aprobado tras cotejo de documentación',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('establishment.verification_status', 'approved');

        $this->seller->refresh();
        $this->assertEquals(UserState::ACTIVE->value, $this->seller->state);
    }

    #[Test]
    public function admin_can_reject_establishment_with_reason()
    {
        $this->actingAs($this->admin);

        $response = $this->patchJson("/api/adm/establishments/{$this->establishment->id}/reject", [
            'reason' => 'El titular tributario no corresponde al local comercial',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('establishment.verification_status', 'rejected');

        $this->seller->refresh();
        $this->assertEquals(UserState::REGISTERING->value, $this->seller->state);
    }

    #[Test]
    public function admin_cannot_reject_establishment_without_valid_reason()
    {
        $this->actingAs($this->admin);

        $response = $this->patchJson("/api/adm/establishments/{$this->establishment->id}/reject", [
            'reason' => 'Corto',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    #[Test]
    public function admin_cannot_verify_google_places_with_invalid_data()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson("/api/adm/establishments/{$this->establishment->id}/verify-google-places", [
            'is_verified' => 'not-a-boolean',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['is_verified']);
    }

    #[Test]
    public function admin_cannot_verify_phone_call_with_invalid_data()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson("/api/adm/establishments/{$this->establishment->id}/verify-phone-call", [
            'call_successful' => 'not-a-boolean',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['call_successful']);
    }
}
