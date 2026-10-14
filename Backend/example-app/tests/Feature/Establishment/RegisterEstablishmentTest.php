<?php

namespace Tests\Feature\Establishment;

use App\Enums\TrustScoreCriterion;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentType;
use App\Models\User;
use App\Services\GooglePlacesService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegisterEstablishmentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected EstablishmentType $establishmentType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);

        $this->user = User::factory()->create([
            'state' => UserState::REGISTERING->value,
        ]);
        $this->user->assignRole(UserRole::SELLER->value);
        $this->actingAs($this->user);

        $this->establishmentType = EstablishmentType::factory()->create([
            'state' => 'active',
        ]);
    }

    #[Test]
    public function seller_can_register_establishment_using_google_places()
    {
        $googlePlaceId = 'ChIJplaces123';
        $placeData = [
            'status' => 'OK',
            'result' => [
                'name' => 'Panadería San Martín',
                'formatted_address' => 'Av. San Martín 100, Salta',
                'formatted_phone_number' => '+54 387 4123456',
                'geometry' => [
                    'location' => [
                        'lat' => -24.7859,
                        'lng' => -65.4116,
                    ],
                ],
                'place_id' => $googlePlaceId,
            ],
        ];

        $this->mock(GooglePlacesService::class)
            ->shouldReceive('getPlaceDetails')
            ->with($googlePlaceId)
            ->andReturn($placeData);

        $file = UploadedFile::fake()->create('cuit.pdf', 500, 'application/pdf');

        $requestData = [
            'google_place_id' => $googlePlaceId,
            'establishment_type_id' => $this->establishmentType->id,
            'verification_files' => [
                [
                    'file' => $file,
                    'document_category' => TrustScoreCriterion::TAX_CERTIFICATE->value,
                ],
            ],
        ];

        $response = $this->postJson('/api/food-establishment', $requestData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('food_establishments', [
            'user_id' => $this->user->id,
            'name' => 'Panadería San Martín',
            'google_place_id' => $googlePlaceId,
            'verification_status' => 'pending',
            'latitude' => -24.7859,
            'longitude' => -65.4116,
        ]);

        $establishment = $this->user->fresh()->foodEstablishment;
        $this->assertEquals(0, $establishment->trust_score); // Inicia en 0 hasta revisión manual
        $this->assertEquals('red', $establishment->trust_level);

        $this->assertDatabaseHas('establishment_verification_files', [
            'food_establishment_id' => $establishment->id,
            'document_category' => TrustScoreCriterion::TAX_CERTIFICATE->value,
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function seller_can_register_establishment_manually_without_google_places()
    {
        $file = UploadedFile::fake()->image('mostrador.jpg');

        $requestData = [
            'name' => 'Rotisería Doña Rosa',
            'address' => 'Calle Mitre 450, Salta',
            'latitude' => -24.7880,
            'longitude' => -65.4100,
            'phone' => '3875551234',
            'establishment_type_id' => $this->establishmentType->id,
            'verification_files' => [
                [
                    'file' => $file,
                    'document_category' => TrustScoreCriterion::STORE_COUNTER_PHOTO->value,
                ],
            ],
        ];

        $response = $this->postJson('/api/food-establishment', $requestData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('food_establishments', [
            'user_id' => $this->user->id,
            'name' => 'Rotisería Doña Rosa',
            'address' => 'Calle Mitre 450, Salta',
            'google_place_id' => null,
            'latitude' => -24.7880,
            'longitude' => -65.4100,
            'phone' => '3875551234',
            'verification_status' => 'pending',
        ]);

        $establishment = $this->user->fresh()->foodEstablishment;
        $this->assertNull($establishment->google_place_id);
        $this->assertEquals(0, $establishment->trust_score);
    }

    #[Test]
    public function registration_fails_if_neither_google_place_nor_manual_coordinates_are_provided()
    {
        $file = UploadedFile::fake()->image('fachada.jpg');

        $requestData = [
            'establishment_type_id' => $this->establishmentType->id,
            'verification_files' => [
                ['file' => $file],
            ],
        ];

        $response = $this->postJson('/api/food-establishment', $requestData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'address', 'latitude', 'longitude']);
    }

    #[Test]
    public function registration_fails_if_document_category_is_invalid()
    {
        $file = UploadedFile::fake()->image('fachada.jpg');

        $requestData = [
            'name' => 'Rotisería Test',
            'address' => 'Calle Mitre 450, Salta',
            'latitude' => -24.7880,
            'longitude' => -65.4100,
            'establishment_type_id' => $this->establishmentType->id,
            'verification_files' => [
                [
                    'file' => $file,
                    'document_category' => 'categoria_inventada_invalida',
                ],
            ],
        ];

        $response = $this->postJson('/api/food-establishment', $requestData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['verification_files.0.document_category']);
    }

    #[Test]
    public function resubmitting_registration_soft_deletes_previous_files_and_preserves_audit_trail()
    {
        Storage::fake('private');

        // Primer envío
        $initialFile = UploadedFile::fake()->image('fachada_inicial.jpg');
        $this->postJson('/api/food-establishment', [
            'name' => 'Comercio Original',
            'address' => 'Calle Mitre 100, Salta',
            'latitude' => -24.7880,
            'longitude' => -65.4100,
            'establishment_type_id' => $this->establishmentType->id,
            'verification_files' => [
                [
                    'file' => $initialFile,
                    'document_category' => TrustScoreCriterion::FACADE_PHOTO->value,
                ],
            ],
        ])->assertStatus(201);

        $establishment = $this->user->fresh()->foodEstablishment;
        $firstFileRecord = $establishment->verificationFiles()->first();
        $this->assertNotNull($firstFileRecord);
        $this->assertNull($firstFileRecord->deleted_at);

        // Simulamos que el admin rechaza y el usuario vuelve a estado registering
        $this->user->state = UserState::REGISTERING->value;
        $this->user->save();
        $this->actingAs($this->user->fresh());

        // Segundo envío (corrección)
        $newFile = UploadedFile::fake()->create('habilitacion.pdf', 500, 'application/pdf');
        $this->postJson('/api/food-establishment', [
            'name' => 'Comercio Corregido',
            'address' => 'Calle Mitre 100, Salta',
            'latitude' => -24.7880,
            'longitude' => -65.4100,
            'establishment_type_id' => $this->establishmentType->id,
            'verification_files' => [
                [
                    'file' => $newFile,
                    'document_category' => TrustScoreCriterion::MUNICIPAL_PERMIT->value,
                ],
            ],
        ])->assertStatus(200);

        // Verificar que el archivo anterior está soft deleted en base de datos
        $this->assertSoftDeleted('establishment_verification_files', [
            'id' => $firstFileRecord->id,
        ]);

        // Verificar que la relación normal solo lista el nuevo archivo activo
        $freshEstablishment = $this->user->fresh()->foodEstablishment;
        $activeFiles = $freshEstablishment->verificationFiles;
        $this->assertCount(1, $activeFiles);
        $this->assertEquals(TrustScoreCriterion::MUNICIPAL_PERMIT, $activeFiles->first()->document_category);

        // Verificar que con withTrashed() podemos acceder a ambos (historial completo de auditoría)
        $allFilesIncludingHistory = $freshEstablishment->verificationFiles()->withTrashed()->get();
        $this->assertCount(2, $allFilesIncludingHistory);
    }
}
