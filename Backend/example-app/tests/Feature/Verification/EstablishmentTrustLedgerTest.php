<?php

namespace Tests\Feature\Verification;

use App\Enums\TrustScoreCriterion;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EstablishmentTrustLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected FoodEstablishment $establishment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->create();
        $type = EstablishmentType::factory()->create(['state' => 'active']);

        $this->establishment = FoodEstablishment::create([
            'user_id' => $user->id,
            'name' => 'Test Ledger Store',
            'address' => 'Av. Siempre Viva 742',
            'establishment_type_id' => $type->id,
            'verification_status' => 'pending',
        ]);
    }

    #[Test]
    public function calculates_trust_score_dynamically_from_active_trust_logs()
    {
        $this->assertEquals(0, $this->establishment->trust_score);
        $this->assertEquals('red', $this->establishment->trust_level);

        // Agregamos log de CUIT (+30 pts)
        $this->establishment->trustLogs()->create([
            'source_type' => TrustScoreCriterion::TAX_CERTIFICATE,
            'points' => 30,
            'is_active' => true,
        ]);

        $this->assertEquals(30, $this->establishment->fresh()->trust_score);
        $this->assertEquals('red', $this->establishment->fresh()->trust_level);

        // Agregamos log de Habilitación (+30 pts -> Total 60 pts)
        $this->establishment->trustLogs()->create([
            'source_type' => TrustScoreCriterion::MUNICIPAL_PERMIT,
            'points' => 30,
            'is_active' => true,
        ]);

        $this->assertEquals(60, $this->establishment->fresh()->trust_score);
        $this->assertEquals('yellow', $this->establishment->fresh()->trust_level);

        // Agregamos log de Foto Mostrador (+10 pts -> Total 70 pts)
        $this->establishment->trustLogs()->create([
            'source_type' => TrustScoreCriterion::STORE_COUNTER_PHOTO,
            'points' => 10,
            'is_active' => true,
        ]);

        $this->assertEquals(70, $this->establishment->fresh()->trust_score);
        $this->assertEquals('green', $this->establishment->fresh()->trust_level);
    }

    #[Test]
    public function inactive_logs_do_not_contribute_to_trust_score()
    {
        $this->establishment->trustLogs()->create([
            'source_type' => TrustScoreCriterion::TAX_CERTIFICATE,
            'points' => 30,
            'is_active' => true,
        ]);

        $this->establishment->trustLogs()->create([
            'source_type' => TrustScoreCriterion::MUNICIPAL_PERMIT,
            'points' => 30,
            'is_active' => false, // Inactivo / invalidado
        ]);

        $this->assertEquals(30, $this->establishment->fresh()->trust_score);
        $this->assertEquals('red', $this->establishment->fresh()->trust_level);
    }
}
