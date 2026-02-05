<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserState;
use App\Enums\VerificationFileType;
use App\Models\EstablishmentType;
use App\Models\User;
use App\Services\GooglePlacesService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    #[Test]
    public function it_can_register_establishment_successfully()
    {
        // Arrange
        $this->user = User::factory()->create([
            'state' => UserState::REGISTERING->value,
        ]);
        $this->user->assignRole(UserRole::SELLER->value);
        $this->actingAs($this->user);

        $establishmentType = EstablishmentType::factory()->create([
            'state' => 'active',
        ]);

        $googlePlaceId = 'ChIJtest123';
        $placeData = [
            'status' => 'OK',
            'result' => [
                'name' => 'Test Restaurant',
                'formatted_address' => '123 Main St, Test City',
                'formatted_phone_number' => '+1234567890',
                'geometry' => [
                    'location' => [
                        'lat' => 40.7128,
                        'lng' => -74.0060,
                    ],
                ],
                'place_id' => $googlePlaceId,
            ],
        ];

        $this->mock(GooglePlacesService::class)
            ->shouldReceive('getPlaceDetails')
            ->with($googlePlaceId)
            ->andReturn($placeData);

        $file1 = UploadedFile::fake()->image('photo1.jpg');
        $file2 = UploadedFile::fake()->image('photo2.png');

        $requestData = [
            'google_place_id' => $googlePlaceId,
            'establishment_type_id' => $establishmentType->id,
            'verification_files' => [
                ['file' => $file1],
                ['file' => $file2],
            ],
        ];

        // Act
        $response = $this->postJson('/api/food-establishment', $requestData);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Establecimiento registrado exitosamente. Pendiente de verificación.',
            ]);

        $this->user->refresh();
        $this->assertEquals(UserState::WAITING_FOR_CONFIRMATION->value, $this->user->state);

        $this->assertDatabaseHas('food_establishments', [
            'user_id' => $this->user->id,
            'name' => 'Test Restaurant',
            'address' => '123 Main St, Test City',
            'phone' => '+1234567890',
            'google_place_id' => $googlePlaceId,
            'establishment_type_id' => $establishmentType->id,
            'verification_status' => 'pending',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $establishment = $this->user->foodEstablishment;
        $this->assertNotNull($establishment);
        $this->assertCount(2, $establishment->verificationFiles);
        foreach ($establishment->verificationFiles as $verificationFile) {
            $this->assertContains($verificationFile->file_type, [VerificationFileType::JPG->value, VerificationFileType::PNG->value]);
        }
    }
}
