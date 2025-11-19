<?php

namespace Tests\Feature;

use App\Enums\OfferState;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CustomerReportControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $customer;
    protected User $seller;
    protected FoodEstablishment $establishment;
    protected Offer $offer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(EstablishmentTypeSeeder::class);

        // Crear customer
        $this->customer = User::factory()->withRole(UserRole::CUSTOMER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        // Crear seller y establishment
        $this->seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $establishmentType = EstablishmentType::inRandomOrder()->first();

        $this->establishment = FoodEstablishment::factory()->create([
            'user_id' => $this->seller->id,
            'establishment_type_id' => $establishmentType->id,
        ]);

        // Crear una oferta activa
        $this->offer = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(5),
            'food_establishment_id' => $this->establishment->id,
        ]);

        // Crear productos para la oferta
        $product = Product::factory()->create([
            'food_establishment_id' => $this->establishment->id,
        ]);

        ProductOffer::create([
            'quantity' => 10,
            'price' => 5,
            'product_id' => $product->id,
            'offer_id' => $this->offer->id,
        ]);

        $this->actingAs($this->customer);
    }

    #[Test]
    public function it_can_create_a_report_for_an_offer(): void
    {
        $reportData = [
            'reportable_type' => 'offer',
            'reportable_id' => $this->offer->id,
            'reason' => ReportReason::INAPPROPRIATE->value,
            'description' => 'This offer contains inappropriate content that violates community guidelines.',
        ];

        $response = $this->postJson('/api/customer/reports', $reportData);


        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'reportable_type',
                    'reportable_id',
                    'reason',
                    'status',
                    'created_at',
                ]
            ])
            ->assertJson([
                'message' => 'Report created successfully',
                'data' => [
                    'reportable_type' => 'offer',
                    'reportable_id' => $this->offer->id,
                    'reason' => ReportReason::INAPPROPRIATE->label(),
                    'status' => ReportStatus::PENDING->label(),
                ]
            ]);

        // Verificar que el reporte existe en la base de datos
        $this->assertDatabaseHas('reports', [
            'reportable_type' => Offer::class,
            'reportable_id' => $this->offer->id,
            'reported_by' => $this->customer->id,
            'reason' => ReportReason::INAPPROPRIATE->value,
            'status' => ReportStatus::PENDING->value,
            'description' => 'This offer contains inappropriate content that violates community guidelines.',
        ]);
    }

    #[Test]
    public function it_can_create_a_report_for_an_establishment(): void
    {
        $reportData = [
            'reportable_type' => 'establishment',
            'reportable_id' => $this->establishment->id,
            'reason' => ReportReason::HYGIENE_ISSUES->value,
            'description' => 'This establishment has serious hygiene issues that need to be addressed.',
        ];

        $response = $this->postJson('/api/customer/reports', $reportData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'reportable_type',
                    'reportable_id',
                    'reason',
                    'status',
                    'created_at',
                ]
            ])
            ->assertJson([
                'message' => 'Report created successfully',
                'data' => [
                    'reportable_type' => 'establishment',
                    'reportable_id' => $this->establishment->id,
                    'reason' => ReportReason::HYGIENE_ISSUES->label(),
                    'status' => ReportStatus::PENDING->label(),
                ]
            ]);

        // Verificar que el reporte existe en la base de datos
        $this->assertDatabaseHas('reports', [
            'reportable_type' => FoodEstablishment::class,
            'reportable_id' => $this->establishment->id,
            'reported_by' => $this->customer->id,
            'reason' => ReportReason::HYGIENE_ISSUES->value,
            'status' => ReportStatus::PENDING->value,
            'description' => 'This establishment has serious hygiene issues that need to be addressed.',
        ]);
    }

    #[Test]
    public function it_fails_to_create_duplicate_report(): void
    {
        // Crear primer reporte
        Report::create([
            'reportable_type' => Offer::class,
            'reportable_id' => $this->offer->id,
            'reported_by' => $this->customer->id,
            'reason' => ReportReason::SPAM->value,
            'status' => ReportStatus::PENDING->value,
            'description' => 'This is spam content.',
        ]);

        // Intentar crear el mismo reporte otra vez
        $reportData = [
            'reportable_type' => 'offer',
            'reportable_id' => $this->offer->id,
            'reason' => ReportReason::SPAM->value,
            'description' => 'This is spam content again.',
        ];

        $response = $this->postJson('/api/customer/reports', $reportData);

        $response->assertStatus(409)
            ->assertJson([
                'error' => 'You have already reported this element'
            ]);
    }

    #[Test]
    public function it_fails_to_create_report_for_non_existent_offer(): void
    {
        $reportData = [
            'reportable_type' => 'offer',
            'reportable_id' => 99999, // ID que no existe
            'reason' => ReportReason::FRAUD->value,
            'description' => 'This is a fraudulent offer.',
        ];

        $response = $this->postJson('/api/customer/reports', $reportData);

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'The element you are trying to report does not exist'
            ]);
    }

    #[Test]
    public function it_fails_to_create_report_for_non_existent_establishment(): void
    {
        $reportData = [
            'reportable_type' => 'establishment',
            'reportable_id' => 99999, // ID que no existe
            'reason' => ReportReason::FALSE_INFORMATION->value,
            'description' => 'This establishment provides false information.',
        ];

        $response = $this->postJson('/api/customer/reports', $reportData);

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'The element you are trying to report does not exist'
            ]);
    }

    #[Test]
    public function it_fails_with_invalid_reportable_type(): void
    {
        $reportData = [
            'reportable_type' => 'invalid_type',
            'reportable_id' => $this->offer->id,
            'reason' => ReportReason::FRAUD->value,
            'description' => 'This is a test description.',
        ];

        $response = $this->postJson('/api/customer/reports', $reportData);


        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reportable_type']);
    }

    #[Test]
    public function it_fails_with_invalid_reason(): void
    {
        $reportData = [
            'reportable_type' => 'offer',
            'reportable_id' => $this->offer->id,
            'reason' => 'invalid_reason',
            'description' => 'This is a test description.',
        ];

        $response = $this->postJson('/api/customer/reports', $reportData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    #[Test]
    public function it_fails_with_short_description(): void
    {
        $reportData = [
            'reportable_type' => 'offer',
            'reportable_id' => $this->offer->id,
            'reason' => ReportReason::SPAM->value,
            'description' => 'Short', // Menor a 10 caracteres
        ];

        $response = $this->postJson('/api/customer/reports', $reportData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }

    #[Test]
    public function it_fails_with_long_description(): void
    {
        $reportData = [
            'reportable_type' => 'offer',
            'reportable_id' => $this->offer->id,
            'reason' => ReportReason::SPAM->value,
            'description' => str_repeat('a', 1001), // Más de 1000 caracteres
        ];

        $response = $this->postJson('/api/customer/reports', $reportData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }

    #[Test]
    public function it_fails_with_missing_required_fields(): void
    {
        $response = $this->postJson('/api/customer/reports', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'reportable_type',
                'reportable_id',
                'reason',
                'description'
            ]);
    }

    #[Test]
    public function it_fails_with_invalid_reportable_id_type(): void
    {
        $reportData = [
            'reportable_type' => 'offer',
            'reportable_id' => 'not_an_integer',
            'reason' => ReportReason::SPAM->value,
            'description' => 'This is a test description.',
        ];

        $response = $this->postJson('/api/customer/reports', $reportData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reportable_id']);
    }

    #[Test]
    public function it_fails_with_negative_reportable_id(): void
    {
        $reportData = [
            'reportable_type' => 'offer',
            'reportable_id' => -1,
            'reason' => ReportReason::SPAM->value,
            'description' => 'This is a test description.',
        ];

        $response = $this->postJson('/api/customer/reports', $reportData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reportable_id']);
    }

    #[Test]
    public function it_can_report_different_offers_with_same_reason(): void
    {
        // Crear segunda oferta
        $secondOffer = Offer::factory()->create([
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addDays(5),
            'food_establishment_id' => $this->establishment->id,
        ]);

        // Reportar primera oferta
        $firstReportData = [
            'reportable_type' => 'offer',
            'reportable_id' => $this->offer->id,
            'reason' => ReportReason::FRAUD->value,
            'description' => 'This is a fraudulent offer.',
        ];

        $response1 = $this->postJson('/api/customer/reports', $firstReportData);
        $response1->assertStatus(201);

        // Reportar segunda oferta con la misma razón
        $secondReportData = [
            'reportable_type' => 'offer',
            'reportable_id' => $secondOffer->id,
            'reason' => ReportReason::FRAUD->value,
            'description' => 'This is another fraudulent offer.',
        ];

        $response2 = $this->postJson('/api/customer/reports', $secondReportData);
        $response2->assertStatus(201);

        // Verificar que ambos reportes existen
        $this->assertDatabaseCount('reports', 2);
    }

    #[Test]
    public function it_requires_authentication(): void
    {
        // Cerrar sesión
        auth()->logout();

        $reportData = [
            'reportable_type' => 'offer',
            'reportable_id' => $this->offer->id,
            'reason' => ReportReason::SPAM->value,
            'description' => 'This is spam content.',
        ];

        $response = $this->postJson('/api/customer/reports', $reportData);

        $response->assertStatus(401);
    }

    #[Test]
    public function it_accepts_all_valid_report_reasons(): void
    {
        $reasons = [
            ReportReason::INAPPROPRIATE,
            ReportReason::FRAUD,
            ReportReason::FALSE_INFORMATION,
            ReportReason::SPAM,
            ReportReason::POOR_QUALITY,
            ReportReason::HYGIENE_ISSUES,
            ReportReason::MISLEADING_PRICING,
            ReportReason::EXPIRED_PRODUCTS,
            ReportReason::OTHER,
        ];

        foreach ($reasons as $index => $reason) {
            // Crear una nueva oferta para cada razón
            $offer = Offer::factory()->create([
                'state' => OfferState::ACTIVE->value,
                'expiration_datetime' => now()->addDays(5),
                'food_establishment_id' => $this->establishment->id,
            ]);

            $reportData = [
                'reportable_type' => 'offer',
                'reportable_id' => $offer->id,
                'reason' => $reason->value,
                'description' => "Test description for reason: {$reason->value}",
            ];

            $response = $this->postJson('/api/customer/reports', $reportData);

            $response->assertStatus(201)
                ->assertJson([
                    'message' => 'Report created successfully',
                    'data' => [
                        'reason' => $reason->label(),
                    ]
                ]);
        }

        // Verificar que se crearon todos los reportes
        $this->assertDatabaseCount('reports', count($reasons));
    }
}

