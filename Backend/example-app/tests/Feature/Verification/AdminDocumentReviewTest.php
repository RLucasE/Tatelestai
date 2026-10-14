<?php

namespace Tests\Feature\Verification;

use App\Enums\TrustScoreCriterion;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentType;
use App\Models\EstablishmentVerificationFile;
use App\Models\FoodEstablishment;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminDocumentReviewTest extends TestCase
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
            'name' => 'Café Salteño',
            'address' => 'Balcarce 200',
            'establishment_type_id' => $type->id,
            'verification_status' => 'pending',
        ]);
    }

    #[Test]
    public function admin_can_accept_documents_and_accumulate_trust_score_dynamically()
    {
        $this->actingAs($this->admin);

        $taxFile = EstablishmentVerificationFile::create([
            'food_establishment_id' => $this->establishment->id,
            'file_path' => 'establishment_verification/test/tax.pdf',
            'file_type' => 'pdf',
            'document_category' => TrustScoreCriterion::TAX_CERTIFICATE,
            'status' => 'pending',
        ]);

        $permitFile = EstablishmentVerificationFile::create([
            'food_establishment_id' => $this->establishment->id,
            'file_path' => 'establishment_verification/test/permit.pdf',
            'file_type' => 'pdf',
            'document_category' => TrustScoreCriterion::MUNICIPAL_PERMIT,
            'status' => 'pending',
        ]);

        $photoFile = EstablishmentVerificationFile::create([
            'food_establishment_id' => $this->establishment->id,
            'file_path' => 'establishment_verification/test/photo.jpg',
            'file_type' => 'jpg',
            'document_category' => TrustScoreCriterion::STORE_COUNTER_PHOTO,
            'status' => 'pending',
        ]);

        // 1. Aceptar CUIT (+30 pts)
        $res1 = $this->postJson("/api/adm/establishments/{$this->establishment->id}/files/{$taxFile->id}/review", [
            'decision' => 'accept',
        ]);
        $res1->assertStatus(200)
            ->assertJsonPath('trust_score', 30)
            ->assertJsonPath('trust_level', 'red');

        // 2. Aceptar Habilitación (+30 pts -> Total 60)
        $res2 = $this->postJson("/api/adm/establishments/{$this->establishment->id}/files/{$permitFile->id}/review", [
            'decision' => 'accept',
        ]);
        $res2->assertStatus(200)
            ->assertJsonPath('trust_score', 60)
            ->assertJsonPath('trust_level', 'yellow');

        // 3. Aceptar Foto mostrador (+10 pts -> Total 70 -> Verde)
        $res3 = $this->postJson("/api/adm/establishments/{$this->establishment->id}/files/{$photoFile->id}/review", [
            'decision' => 'accept',
        ]);
        $res3->assertStatus(200)
            ->assertJsonPath('trust_score', 70)
            ->assertJsonPath('trust_level', 'green');

        $this->assertEquals(70, $this->establishment->fresh()->trust_score);
        $this->assertEquals('green', $this->establishment->fresh()->trust_level);
    }

    #[Test]
    public function admin_can_reject_a_document_with_reason_without_adding_points()
    {
        $this->actingAs($this->admin);

        $badFile = EstablishmentVerificationFile::create([
            'food_establishment_id' => $this->establishment->id,
            'file_path' => 'establishment_verification/test/borroso.jpg',
            'file_type' => 'jpg',
            'document_category' => TrustScoreCriterion::TAX_CERTIFICATE,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/adm/establishments/{$this->establishment->id}/files/{$badFile->id}/review", [
            'decision' => 'reject',
            'rejection_reason' => 'La imagen está borrosa y no se lee el CUIT',
        ]);

        $response->assertStatus(200);

        $badFile->refresh();
        $this->assertEquals('rejected', $badFile->status);
        $this->assertEquals('La imagen está borrosa y no se lee el CUIT', $badFile->rejection_reason);
        $this->assertEquals(0, $this->establishment->fresh()->trust_score);
    }

    #[Test]
    public function non_admin_cannot_review_documents()
    {
        $this->actingAs($this->seller);

        $file = EstablishmentVerificationFile::create([
            'food_establishment_id' => $this->establishment->id,
            'file_path' => 'establishment_verification/test/doc.pdf',
            'file_type' => 'pdf',
            'document_category' => TrustScoreCriterion::TAX_CERTIFICATE,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/adm/establishments/{$this->establishment->id}/files/{$file->id}/review", [
            'decision' => 'accept',
        ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function admin_can_serve_verification_file_inline()
    {
        $relativePath = 'establishment_verification/test/sample.pdf';
        $fullPath = storage_path('app/private/'.$relativePath);
        if (! file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }
        file_put_contents($fullPath, '%PDF-1.4 dummy pdf content');

        $file = EstablishmentVerificationFile::create([
            'food_establishment_id' => $this->establishment->id,
            'file_path' => $relativePath,
            'file_type' => 'pdf',
            'document_category' => TrustScoreCriterion::TAX_CERTIFICATE,
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin);

        $response = $this->get("/api/admin/verification-files/{$file->id}");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    #[Test]
    public function unauthenticated_access_to_verification_file_returns_401_without_login_route_error()
    {
        $response = $this->get('/api/admin/verification-files/1');

        $response->assertStatus(401);
    }

    #[Test]
    public function admin_can_batch_save_document_reviews_and_category_overrides()
    {
        $file1 = EstablishmentVerificationFile::create([
            'food_establishment_id' => $this->establishment->id,
            'file_path' => 'establishment_verification/test/doc1.pdf',
            'file_type' => 'pdf',
            'document_category' => TrustScoreCriterion::OTHER,
            'status' => 'pending',
        ]);

        $file2 = EstablishmentVerificationFile::create([
            'food_establishment_id' => $this->establishment->id,
            'file_path' => 'establishment_verification/test/doc2.pdf',
            'file_type' => 'pdf',
            'document_category' => TrustScoreCriterion::MUNICIPAL_PERMIT,
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin);

        $response = $this->postJson("/api/adm/establishments/{$this->establishment->id}/review-documents", [
            'files' => [
                [
                    'id' => $file1->id,
                    'status' => 'accepted',
                    'document_category' => TrustScoreCriterion::TAX_CERTIFICATE->value,
                    'rejection_reason' => null,
                ],
                [
                    'id' => $file2->id,
                    'status' => 'rejected',
                    'document_category' => TrustScoreCriterion::MUNICIPAL_PERMIT->value,
                    'rejection_reason' => 'Permiso municipal vencido',
                ],
            ],
        ]);

        $response->assertStatus(200);

        $file1->refresh();
        $this->assertEquals('accepted', $file1->status);
        $this->assertEquals(TrustScoreCriterion::TAX_CERTIFICATE, $file1->document_category);

        $file2->refresh();
        $this->assertEquals('rejected', $file2->status);
        $this->assertEquals('Permiso municipal vencido', $file2->rejection_reason);

        $this->assertEquals(30, $this->establishment->fresh()->trust_score);
    }

    #[Test]
    public function admin_can_assign_manual_points_to_other_document_category_in_batch_and_single_review()
    {
        $fileOther = EstablishmentVerificationFile::create([
            'food_establishment_id' => $this->establishment->id,
            'file_path' => 'establishment_verification/test/other.pdf',
            'file_type' => 'pdf',
            'document_category' => TrustScoreCriterion::OTHER,
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin);

        // 1. Asignar 15 puntos manuales al aceptar en batch
        $response = $this->postJson("/api/adm/establishments/{$this->establishment->id}/review-documents", [
            'files' => [
                [
                    'id' => $fileOther->id,
                    'status' => 'accepted',
                    'document_category' => TrustScoreCriterion::OTHER->value,
                    'points' => 15,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertEquals(15, $this->establishment->fresh()->trust_score);
        $this->assertEquals(15, $fileOther->fresh()->assigned_points);

        // 2. Actualizar los puntos a 25 vía review individual
        $responseSingle = $this->postJson("/api/adm/establishments/{$this->establishment->id}/files/{$fileOther->id}/review", [
            'decision' => 'accept',
            'override_category' => TrustScoreCriterion::OTHER->value,
            'points' => 25,
        ]);

        $responseSingle->assertStatus(200);
        $this->assertEquals(25, $this->establishment->fresh()->trust_score);
        $this->assertEquals(25, $fileOther->fresh()->assigned_points);

        // 3. Si se rechaza el archivo, los puntos se anulan
        $responseReject = $this->postJson("/api/adm/establishments/{$this->establishment->id}/files/{$fileOther->id}/review", [
            'decision' => 'reject',
            'rejection_reason' => 'Documento no cumple requisitos',
        ]);

        $responseReject->assertStatus(200);
        $this->assertEquals(0, $this->establishment->fresh()->trust_score);
        $this->assertNull($fileOther->fresh()->assigned_points);
    }
}
