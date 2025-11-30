<?php

use App\Enums\OfferState;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\FoodEstablishmentSeeder;
use Database\Seeders\OfferSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ProductCategorySeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\SellSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(UserSeeder::class);
    $this->seed(CategorySeeder::class);
    $this->seed(EstablishmentTypeSeeder::class);
    // Crear admin
    $this->admin = User::where('email', 'admin@gmail.com')->first();
    actingAs($this->admin);
});

test('admin can take action on offer report and deactivate it', function () {

    $seller = User::factory()->create([
        'state' => UserState::ACTIVE,
    ]);
    $seller->assignRole(UserRole::SELLER->value);


    $establishment = FoodEstablishment::factory()->create([
        'user_id' => $seller->id,
        'establishment_type_id' => EstablishmentType::inRandomOrder()->first()?->id
    ]);

    // Crear una oferta activa
    $offer = Offer::factory()->create([
        'food_establishment_id' => $establishment->id,
        'state' => OfferState::ACTIVE,
    ]);

    // Crear un customer que reporta
    $customer = User::factory()->create();
    $customer->assignRole(UserRole::CUSTOMER->value);

    // Crear un reporte sobre la oferta
    $report = Report::factory()->create([
        'reportable_type' => 'App\\Models\\Offer',
        'reportable_id' => $offer->id,
        'reported_by' => $customer->id,
        'reason' => ReportReason::INAPPROPRIATE,
        'status' => ReportStatus::PENDING,
    ]);

    // Tomar acción sobre el reporte
    $response = postJson("/api/adm/reports/{$report->id}/take-action");

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Action taken successfully',
            'result' => [
                'action' => 'offer_deactivated',
                'offer_id' => $offer->id,
            ],
        ]);

    // Verificar que la oferta fue desactivada
    $offer->refresh();
    expect($offer->state)->toBe(OfferState::INACTIVE->value);

    // Verificar que el reporte fue marcado como resolved
    $report->refresh();
    $admin = User::where('email', 'admin@gmail.com')->first();
    expect($report->status)->toBe(ReportStatus::RESOLVED)
        ->and($report->reviewed_by)->toBe($admin->id)
        ->and($report->reviewed_at)->not->toBeNull();
});

test('admin can take action on establishment report and deactivate seller with all offers', function () {
    // Crear un seller con establecimiento
    $seller = User::factory()->create([
        'state' => UserState::ACTIVE,
    ]);
    $seller->assignRole(UserRole::SELLER->value);

    $establishment = FoodEstablishment::factory()->create([
        'user_id' => $seller->id,
    ]);

    // Crear varias ofertas activas
    $offers = Offer::factory()->count(3)->create([
        'food_establishment_id' => $establishment->id,
        'state' => OfferState::ACTIVE,
    ]);

    // Crear un customer que reporta
    $customer = User::factory()->create();
    $customer->assignRole(UserRole::CUSTOMER->value);

    // Crear un reporte sobre el establecimiento
    $report = Report::factory()->create([
        'reportable_type' => 'App\\Models\\FoodEstablishment',
        'reportable_id' => $establishment->id,
        'reported_by' => $customer->id,
        'reason' => ReportReason::FRAUD,
        'status' => ReportStatus::PENDING,
    ]);

    // Tomar acción sobre el reporte
    $response = postJson("/api/adm/reports/{$report->id}/take-action");

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Action taken successfully',
            'result' => [
                'action' => 'seller_deactivated',
                'user_id' => $seller->id,
                'establishment_id' => $establishment->id,
                'offers_deactivated' => 3,
            ],
        ]);

    // Verificar que el seller fue desactivado
    $seller->refresh();
    expect($seller->state)->toBe(UserState::INACTIVE->value);

    // Verificar que todas las ofertas fueron desactivadas
    foreach ($offers as $offer) {
        $offer->refresh();
        expect($offer->state)->toBe(OfferState::INACTIVE->value);
    }

    // Verificar que el reporte fue marcado como resolved
    $report->refresh();
    $admin = User::where('email', 'admin@gmail.com')->first();
    expect($report->status)->toBe(ReportStatus::RESOLVED)
        ->and($report->reviewed_by)->toBe($admin->id)
        ->and($report->reviewed_at)->not->toBeNull();
});

test('admin can take action on user report and deactivate user with all offers', function () {
    // Crear un seller con establecimiento
    $seller = User::factory()->create([
        'state' => UserState::ACTIVE,
    ]);
    $seller->assignRole(UserRole::SELLER->value);

    $establishment = FoodEstablishment::factory()->create([
        'user_id' => $seller->id,
    ]);

    // Crear ofertas activas
    Offer::factory()->count(2)->create([
        'food_establishment_id' => $establishment->id,
        'state' => OfferState::ACTIVE,
    ]);

    // Crear un customer que reporta
    $customer = User::factory()->create();
    $customer->assignRole(UserRole::CUSTOMER->value);

    // Crear un reporte sobre el usuario
    $report = Report::factory()->create([
        'reportable_type' => 'App\\Models\\User',
        'reportable_id' => $seller->id,
        'reported_by' => $customer->id,
        'reason' => ReportReason::SPAM,
        'status' => ReportStatus::REVIEWING,
    ]);

    // Tomar acción sobre el reporte
    $response = postJson("/api/adm/reports/{$report->id}/take-action");

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Action taken successfully',
            'result' => [
                'action' => 'user_deactivated',
                'user_id' => $seller->id,
                'offers_deactivated' => 2,
            ],
        ]);

    // Verificar que el usuario fue desactivado
    $seller->refresh();
    expect($seller->state)->toBe(UserState::INACTIVE->value);

    // Verificar que el reporte fue marcado como resolved
    $report->refresh();
    expect($report->status)->toBe(ReportStatus::RESOLVED);
});

test('it fails to take action on non existent report', function () {
    $response = postJson("/api/adm/reports/99999/take-action");

    $response->assertStatus(404);
});

test('it requires authentication to take action on report', function () {
    // Crear un reporte
    $customer = User::factory()->create();
    $customer->assignRole(UserRole::CUSTOMER->value);

    $seller = User::factory()->create();
    $seller->assignRole(UserRole::SELLER->value);

    $establishment = FoodEstablishment::factory()->create([
        'user_id' => $seller->id,
    ]);

    $offer = Offer::factory()->create([
        'food_establishment_id' => $establishment->id,
    ]);

    $report = Report::factory()->create([
        'reportable_type' => 'App\\Models\\Offer',
        'reportable_id' => $offer->id,
        'reported_by' => $customer->id,
    ]);

    // Intentar sin autenticación
    auth()->logout();

    $response = postJson("/api/adm/reports/{$report->id}/take-action");

    $response->assertStatus(401);
});

