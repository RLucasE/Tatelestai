<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\User;
use Couchbase\KeyLockedException;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdmUserControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);

        // Create and authenticate admin for all tests
        $this->admin = User::factory()->withRole(UserRole::ADMIN->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $this->actingAs($this->admin);
    }

    #[Test]
    public function it_can_activate_a_seller_waiting_for_confirmation()
    {
        $seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::WAITING_FOR_CONFIRMATION->value,
        ]);

        $response = $this->patchJson('/api/users/'.$seller->id.'/activate-seller');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Seller activado correctamente.',
                'user' => [
                    'id' => $seller->id,
                    'name' => $seller->name,
                    'last_name' => $seller->last_name,
                    'email' => $seller->email,
                ],
            ]);

        $seller->refresh();
        $this->assertEquals(UserState::ACTIVE->value, $seller->state);
    }

    #[Test]
    public function it_can_activate_an_inactive_seller()
    {
        // Arrange
        $seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::INACTIVE->value,
        ]);

        $response = $this->patchJson('/api/users/'.$seller->id.'/activate-seller');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Seller activado correctamente.',
            ]);

        $seller->refresh();
        $this->assertEquals(UserState::ACTIVE->value, $seller->state);
    }

    #[Test]
    public function it_cannot_activate_seller_without_seller_role()
    {
        // Arrange
        $user = User::factory()->create([
            'state' => UserState::WAITING_FOR_CONFIRMATION->value,
        ]);
        $user->assignRole('customer');
        // Act
        $response = $this->patchJson("/api/users/{$user->id}/activate-seller");
        // Assert
        $response->assertStatus(500)
            ->assertJson([
                'error' => 'El usuario no tiene rol seller.',
            ]);
        $user->refresh();
        $this->assertEquals(UserState::WAITING_FOR_CONFIRMATION->value, $user->state);
    }

    #[Test]
    public function it_cannot_activate_seller_already_active()
    {
        // Arrange
        $seller = User::factory()->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $seller->assignRole('seller');

        // Act
        $response = $this->patchJson("/api/users/{$seller->id}/activate-seller");

        // Assert
        $response->assertStatus(200);

        $seller->refresh();
        $this->assertEquals(UserState::ACTIVE->value, $seller->state); // Se mantiene igual
    }

    #[Test]
    public function it_returns_error_when_seller_not_found_for_activation()
    {
        // Act
        $response = $this->patchJson('/api/users/999/activate-seller');

        // Assert
        $response->assertStatus(404);
    }

    #[Test]
    public function it_can_deactivate_an_active_seller()
    {
        // Arrange
        $seller = User::factory()->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $seller->assignRole('seller');

        // Act
        $response = $this->patchJson("/api/users/{$seller->id}/deactivate-seller");


        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Seller desactivado correctamente.',
                'user' => [
                    'id' => $seller->id,
                    'name' => $seller->name,
                    'last_name' => $seller->last_name,
                    'email' => $seller->email,
                ],
                'offers_deactivated' => true,
            ]);

        $seller->refresh();
        $this->assertEquals(UserState::INACTIVE->value, $seller->state);
    }

    #[Test]
    public function it_can_deactivate_a_denied_confirmation_seller()
    {
        // Arrange
        $seller = User::factory()->create([
            'state' => UserState::DENIED_CONFIRMATION->value,
        ]);
        $seller->assignRole('seller');

        // Act
        $response = $this->patchJson("/api/users/{$seller->id}/deactivate-seller");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Seller desactivado correctamente.',
            ]);

        $seller->refresh();
        $this->assertEquals(UserState::INACTIVE->value, $seller->state);
    }

    #[Test]
    public function it_cannot_deactivate_seller_without_seller_role()
    {
        // Arrange
        $user = User::factory()->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $user->assignRole('customer');

        // Act
        $response = $this->patchJson("/api/users/{$user->id}/deactivate-seller");

        // Assert
        $response->assertStatus(500)
            ->assertJsonStructure([
                'error',
                'trace'
            ]);

        $user->refresh();
        $this->assertEquals(UserState::ACTIVE->value, $user->state);
    }

    #[Test]
    public function it_cannot_deactivate_seller_in_waiting_state()
    {
        // Arrange
        $seller = User::factory()->create([
            'state' => UserState::WAITING_FOR_CONFIRMATION->value,
        ]);
        $seller->assignRole('seller');

        // Act
        $response = $this->patchJson("/api/users/{$seller->id}/deactivate-seller");

        // Assert
        $response->assertStatus(200); // No hace cambios pero no arroja error

        $seller->refresh();
        $this->assertEquals(UserState::WAITING_FOR_CONFIRMATION->value, $seller->state);
    }

    #[Test]
    public function it_cannot_deactivate_seller_already_inactive()
    {
        // Arrange
        $seller = User::factory()->create([
            'state' => UserState::INACTIVE->value,
        ]);
        $seller->assignRole('seller');

        // Act
        $response = $this->patchJson("/api/users/{$seller->id}/deactivate-seller");

        // Assert
        $response->assertStatus(200); // No hace cambios pero no arroja error

        $seller->refresh();
        $this->assertEquals(UserState::INACTIVE->value, $seller->state);
    }

    #[Test]
    public function it_returns_error_when_seller_not_found_for_deactivation()
    {
        // Act
        $response = $this->patchJson('/api/users/999/deactivate-seller');

        // Assert
        $response->assertStatus(500)
            ->assertJsonStructure([
                'error',
            ]);
    }

    #[Test]
    public function activate_seller_maintains_database_consistency()
    {
        // Arrange
        $seller = User::factory()->create([
            'state' => UserState::WAITING_FOR_CONFIRMATION->value,
        ]);
        $seller->assignRole('seller');

        $originalCount = User::count();

        // Act
        $response = $this->patchJson("/api/users/{$seller->id}/activate-seller");

        // Assert
        $response->assertStatus(200);
        $this->assertEquals($originalCount, User::count()); // No se crean/eliminan usuarios

        $seller->refresh();
        $this->assertEquals(UserState::ACTIVE->value, $seller->state);
        $this->assertTrue($seller->hasRole('seller')); // Mantiene el rol
    }

    #[Test]
    public function deactivate_seller_maintains_database_consistency()
    {
        // Arrange
        $seller = User::factory()->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $seller->assignRole('seller');

        $originalCount = User::count();

        // Act
        $response = $this->patchJson("/api/users/{$seller->id}/deactivate-seller");

        // Assert
        $response->assertStatus(200);
        $this->assertEquals($originalCount, User::count()); // No se crean/eliminan usuarios

        $seller->refresh();
        $this->assertEquals(UserState::INACTIVE->value, $seller->state);
        $this->assertTrue($seller->hasRole('seller')); // Mantiene el rol
    }

    #[Test]
    public function it_returns_user_statistics_grouped_by_state()
    {
        User::factory()->count(5)->create(['state' => UserState::ACTIVE->value]);
        User::factory()->count(3)->create(['state' => UserState::INACTIVE->value]);
        User::factory()->count(2)->create(['state' => UserState::WAITING_FOR_CONFIRMATION->value]);
        User::factory()->count(1)->create(['state' => UserState::DENIED_CONFIRMATION->value]);
        User::factory()->count(4)->create(['state' => UserState::SELECTING->value]);
        User::factory()->count(2)->create(['state' => UserState::REGISTERING->value]);

        $response = $this->getJson('/api/adm/user-stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'total',
                'data' => [
                    '*' => [
                        'state',
                        'count'
                    ]
                ]
            ]);

        $response->dump();

        $data = $response->json('data');
        $total = $response->json('total');

        $activeCount = collect($data)->firstWhere('state', UserState::ACTIVE->value)['count'] ?? 0;
        $inactiveCount = collect($data)->firstWhere('state', UserState::INACTIVE->value)['count'] ?? 0;
        $waitingCount = collect($data)->firstWhere('state', UserState::WAITING_FOR_CONFIRMATION->value)['count'] ?? 0;
        $deniedCount = collect($data)->firstWhere('state', UserState::DENIED_CONFIRMATION->value)['count'] ?? 0;
        $selectingCount = collect($data)->firstWhere('state', UserState::SELECTING->value)['count'] ?? 0;
        $registeringCount = collect($data)->firstWhere('state', UserState::REGISTERING->value)['count'] ?? 0;

        $this->assertEquals(6, $activeCount);
        $this->assertEquals(3, $inactiveCount);
        $this->assertEquals(2, $waitingCount);
        $this->assertEquals(1, $deniedCount);
        $this->assertEquals(4, $selectingCount);
        $this->assertEquals(2, $registeringCount);

        $this->assertEquals(18, $total);
        $this->assertEquals($activeCount + $inactiveCount + $waitingCount + $deniedCount + $selectingCount + $registeringCount, $total);
    }
}
