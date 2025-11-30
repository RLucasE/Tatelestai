<?php

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {

    $this->seed(PermissionSeeder::class);
    // Crear un administrador
    $this->admin = User::factory()->withRole(UserRole::ADMIN->value)->create([
        'state' => UserState::ACTIVE->value
    ]);

    // Crear un usuario customer
    $this->customer = User::factory()->withRole(UserRole::CUSTOMER->value)->create([
    ]);

    // Crear un usuario seller
    $this->seller = User::factory()->withRole(UserRole::SELLER->value)->create();
});

// ===================== TESTS PARA INDEX =====================

test('admin can retrieve all reports with details', function () {
    actingAs($this->admin);

    // Crear reportes de diferentes tipos
    $offer = Offer::factory()->create();
    $establishment = FoodEstablishment::factory()->create();

    Report::factory()->create([
        'reportable_type' => Offer::class,
        'reportable_id' => $offer->id,
        'reported_by' => $this->customer->id,
        'reason' => ReportReason::INAPPROPRIATE,
        'status' => ReportStatus::PENDING
    ]);

    Report::factory()->create([
        'reportable_type' => FoodEstablishment::class,
        'reportable_id' => $establishment->id,
        'reported_by' => $this->customer->id,
        'reason' => ReportReason::FRAUD,
        'status' => ReportStatus::RESOLVED
    ]);

    $response = $this->getJson('/api/adm/reports');


    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'reportable_type',
                    'reportable_id',
                    'reported_by',
                    'reason',
                    'status',
                    'description',
                    'admin_notes',
                    'reviewed_by',
                    'reviewed_at',
                    'created_at',
                    'updated_at',
                    'reportable',
                    'reporter' => ['id', 'name', 'email'],
                    'reviewer'
                ]
            ],
            'current_page',
            'per_page',
            'total'
        ]);

    expect($response->json('data'))->toHaveCount(2);
});

test('admin can filter reports by status', function () {
    actingAs($this->admin);

    // Crear reportes con diferentes estados
    Report::factory()->count(3)->create([
        'reported_by' => $this->customer->id,
        'status' => ReportStatus::PENDING
    ]);

    Report::factory()->count(2)->create([
        'reported_by' => $this->customer->id,
        'status' => ReportStatus::RESOLVED
    ]);

    $response = $this->getJson('/api/adm/reports?status=pending');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(3);

    // Verificar que todos son pending
    foreach ($response->json('data') as $report) {
        expect($report['status'])->toBe('pending');
    }
});

test('admin can filter reports by reportable type', function () {
    actingAs($this->admin);

    $offer = Offer::factory()->create();
    $establishment = FoodEstablishment::factory()->create();

    Report::factory()->count(2)->create([
        'reportable_type' => Offer::class,
        'reportable_id' => $offer->id,
        'reported_by' => $this->customer->id
    ]);

    Report::factory()->create([
        'reportable_type' => FoodEstablishment::class,
        'reportable_id' => $establishment->id,
        'reported_by' => $this->customer->id
    ]);

    $response = $this->getJson('/api/adm/reports?reportable_type=App\Models\Offer');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(2);

    // Verificar que todos son de tipo Offer
    foreach ($response->json('data') as $report) {
        expect($report['reportable_type'])->toBe('App\Models\Offer');
    }
});

test('admin can filter reports by both status and reportable type', function () {
    actingAs($this->admin);

    $offer = Offer::factory()->create();
    $establishment = FoodEstablishment::factory()->create();

    // Crear reportes de ofertas pendientes
    Report::factory()->count(2)->create([
        'reportable_type' => Offer::class,
        'reportable_id' => $offer->id,
        'reported_by' => $this->customer->id,
        'status' => ReportStatus::PENDING
    ]);

    // Crear reportes de ofertas resueltos
    Report::factory()->create([
        'reportable_type' => Offer::class,
        'reportable_id' => $offer->id,
        'reported_by' => $this->customer->id,
        'status' => ReportStatus::RESOLVED
    ]);

    // Crear reportes de establecimientos pendientes
    Report::factory()->create([
        'reportable_type' => FoodEstablishment::class,
        'reportable_id' => $establishment->id,
        'reported_by' => $this->customer->id,
        'status' => ReportStatus::PENDING
    ]);

    $response = $this->getJson('/api/adm/reports?status=pending&reportable_type=App\Models\Offer');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(2);

    // Verificar que todos cumplen ambos filtros
    foreach ($response->json('data') as $report) {
        expect($report['status'])->toBe('pending')
            ->and($report['reportable_type'])->toBe('App\Models\Offer');
    }
});

test('reports are ordered by creation date descending', function () {
    actingAs($this->admin);

    // Crear reportes con diferentes fechas
    $report1 = Report::factory()->create([
        'reported_by' => $this->customer->id,
        'created_at' => now()->subDays(3)
    ]);

    $report2 = Report::factory()->create([
        'reported_by' => $this->customer->id,
        'created_at' => now()->subDays(1)
    ]);

    $report3 = Report::factory()->create([
        'reported_by' => $this->customer->id,
        'created_at' => now()
    ]);

    $response = $this->getJson('/api/adm/reports');

    $response->assertStatus(200);
    $data = $response->json('data');

    // El primer reporte debe ser el más reciente
    expect($data[0]['id'])->toBe($report3->id)
        ->and($data[1]['id'])->toBe($report2->id)
        ->and($data[2]['id'])->toBe($report1->id);
});

test('admin can customize pagination per page', function () {
    actingAs($this->admin);

    Report::factory()->count(20)->create([
        'reported_by' => $this->customer->id
    ]);

    $response = $this->getJson('/api/adm/reports?per_page=5');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(5)
        ->and($response->json('per_page'))->toBe(5)
        ->and($response->json('total'))->toBe(20);
});

test('non-admin users cannot access reports index', function () {
    actingAs($this->customer);

    $response = $this->getJson('/api/adm/reports');
    $response->assertStatus(403);
});

test('seller cannot access reports index', function () {
    actingAs($this->seller);

    $response = $this->getJson('/api/adm/reports');
    $response->assertStatus(403);
});

test('unauthenticated users cannot access reports index', function () {
    $response = $this->getJson('/api/adm/reports');
    $response->assertStatus(401);
});

// ===================== TESTS PARA UPDATE STATUS =====================

test('admin can update report status to resolved', function () {
    actingAs($this->admin);

    $report = Report::factory()->create([
        'reported_by' => $this->customer->id,
        'status' => ReportStatus::PENDING
    ]);

    $response = $this->patchJson("/api/adm/reports/{$report->id}/status", [
        'status' => 'resolved',
        'admin_notes' => 'Se verificó el reporte y se tomó acción'
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Report status updated successfully',
            'report' => [
                'id' => $report->id,
                'status' => 'resolved',
                'admin_notes' => 'Se verificó el reporte y se tomó acción',
                'reviewed_by' => $this->admin->id
            ]
        ]);

    // Verificar que se actualizó en la base de datos
    $report->refresh();
    expect($report->status)->toBe(ReportStatus::RESOLVED)
        ->and($report->reviewed_by)->toBe($this->admin->id)
        ->and($report->reviewed_at)->not->toBeNull()
        ->and($report->admin_notes)->toBe('Se verificó el reporte y se tomó acción');
});

test('admin can update report status to dismissed', function () {
    actingAs($this->admin);

    $report = Report::factory()->create([
        'reported_by' => $this->customer->id,
        'status' => ReportStatus::PENDING
    ]);

    $response = $this->patchJson("/api/adm/reports/{$report->id}/status", [
        'status' => 'dismissed',
        'admin_notes' => 'El reporte no es válido'
    ]);

    $response->assertStatus(200);

    $report->refresh();
    expect($report->status)->toBe(ReportStatus::DISMISSED)
        ->and($report->admin_notes)->toBe('El reporte no es válido')
        ->and($report->reviewed_by)->toBe($this->admin->id);
});

test('admin can update report status to reviewing', function () {
    actingAs($this->admin);

    $report = Report::factory()->create([
        'reported_by' => $this->customer->id,
        'status' => ReportStatus::PENDING
    ]);

    $response = $this->patchJson("/api/adm/reports/{$report->id}/status", [
        'status' => 'reviewing'
    ]);

    $response->assertStatus(200);

    $report->refresh();
    expect($report->status)->toBe(ReportStatus::REVIEWING)
        ->and($report->reviewed_by)->toBe($this->admin->id)
        ->and($report->reviewed_at)->not->toBeNull();
});

test('admin can update report status without admin notes', function () {
    actingAs($this->admin);

    $report = Report::factory()->create([
        'reported_by' => $this->customer->id,
        'status' => ReportStatus::PENDING
    ]);

    $response = $this->patchJson("/api/adm/reports/{$report->id}/status", [
        'status' => 'resolved'
    ]);

    $response->assertStatus(200);

    $report->refresh();
    expect($report->status)->toBe(ReportStatus::RESOLVED)
        ->and($report->admin_notes)->toBeNull();
});

test('admin can change status from reviewing to resolved', function () {
    actingAs($this->admin);

    $report = Report::factory()->create([
        'reported_by' => $this->customer->id,
        'status' => ReportStatus::REVIEWING,
        'reviewed_by' => $this->admin->id,
        'reviewed_at' => now()->subHours(2)
    ]);

    $response = $this->patchJson("/api/adm/reports/{$report->id}/status", [
        'status' => 'resolved',
        'admin_notes' => 'Caso resuelto satisfactoriamente'
    ]);

    $response->assertStatus(200);

    $report->refresh();
    expect($report->status)->toBe(ReportStatus::RESOLVED)
        ->and($report->admin_notes)->toBe('Caso resuelto satisfactoriamente');
});

test('it fails to update report status with invalid status', function () {
    actingAs($this->admin);

    $report = Report::factory()->create([
        'reported_by' => $this->customer->id,
        'status' => ReportStatus::PENDING
    ]);

    $response = $this->patchJson("/api/adm/reports/{$report->id}/status", [
        'status' => 'invalid_status'
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

test('it fails to update report status without status field', function () {
    actingAs($this->admin);

    $report = Report::factory()->create([
        'reported_by' => $this->customer->id,
        'status' => ReportStatus::PENDING
    ]);

    $response = $this->patchJson("/api/adm/reports/{$report->id}/status", []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

test('it fails when admin notes exceed max length', function () {
    actingAs($this->admin);

    $report = Report::factory()->create([
        'reported_by' => $this->customer->id,
        'status' => ReportStatus::PENDING
    ]);

    $tooLongNotes = str_repeat('a', 1001);

    $response = $this->patchJson("/api/adm/reports/{$report->id}/status", [
        'status' => 'resolved',
        'admin_notes' => $tooLongNotes
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['admin_notes']);
});

test('admin notes can be exactly 1000 characters', function () {
    actingAs($this->admin);

    $report = Report::factory()->create([
        'reported_by' => $this->customer->id,
        'status' => ReportStatus::PENDING
    ]);

    $maxLengthNotes = str_repeat('a', 1000);

    $response = $this->patchJson("/api/adm/reports/{$report->id}/status", [
        'status' => 'resolved',
        'admin_notes' => $maxLengthNotes
    ]);

    $response->assertStatus(200);

    $report->refresh();
    expect($report->admin_notes)->toBe($maxLengthNotes)
        ->and(strlen($report->admin_notes))->toBe(1000);
});

test('it fails to update non-existent report', function () {
    actingAs($this->admin);

    $response = $this->patchJson("/api/adm/reports/99999/status", [
        'status' => 'resolved'
    ]);

    $response->assertStatus(404);
});

test('non-admin users cannot update report status', function () {
    actingAs($this->customer);

    $report = Report::factory()->create([
        'reported_by' => $this->customer->id,
        'status' => ReportStatus::PENDING
    ]);

    $response = $this->patchJson("/api/adm/reports/{$report->id}/status", [
        'status' => 'resolved'
    ]);

    $response->assertStatus(403);

    // Verificar que no se actualizó
    $report->refresh();
    expect($report->status)->toBe(ReportStatus::PENDING);
});

test('seller cannot update report status', function () {
    actingAs($this->seller);

    $report = Report::factory()->create([
        'reported_by' => $this->customer->id,
        'status' => ReportStatus::PENDING
    ]);

    $response = $this->patchJson("/api/adm/reports/{$report->id}/status", [
        'status' => 'resolved'
    ]);

    $response->assertStatus(403);
});

test('unauthenticated users cannot update report status', function () {
    $report = Report::factory()->create([
        'reported_by' => $this->customer->id,
        'status' => ReportStatus::PENDING
    ]);

    $response = $this->patchJson("/api/adm/reports/{$report->id}/status", [
        'status' => 'resolved'
    ]);

    $response->assertStatus(401);
});

test('reviewed_at is automatically set when status is updated', function () {
    actingAs($this->admin);

    $report = Report::factory()->create([
        'reported_by' => $this->customer->id,
        'status' => ReportStatus::PENDING,
        'reviewed_at' => null
    ]);

    expect($report->reviewed_at)->toBeNull();

    $response = $this->patchJson("/api/adm/reports/{$report->id}/status", [
        'status' => 'reviewing'
    ]);

    $response->assertStatus(200);

    $report->refresh();
    expect($report->reviewed_at)->not->toBeNull()
        ->and($report->reviewed_at->diffInSeconds(now()))->toBeLessThan(5);
});

test('response includes all report relationships after status update', function () {
    actingAs($this->admin);

    $offer = Offer::factory()->create();
    $report = Report::factory()->create([
        'reportable_type' => Offer::class,
        'reportable_id' => $offer->id,
        'reported_by' => $this->customer->id,
        'status' => ReportStatus::PENDING
    ]);

    $response = $this->patchJson("/api/adm/reports/{$report->id}/status", [
        'status' => 'resolved',
        'admin_notes' => 'Test notes'
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'report' => [
                'id',
                'status',
                'admin_notes',
                'reviewed_by',
                'reviewed_at',
                'reportable',
                'reporter' => ['id', 'name', 'email'],
                'reviewer' => ['id', 'name', 'email']
            ]
        ]);

    // Verificar que el reviewer es el admin actual
    expect($response->json('report.reviewer.id'))->toBe($this->admin->id)
        ->and($response->json('report.reviewer.name'))->toBe($this->admin->name);
});

