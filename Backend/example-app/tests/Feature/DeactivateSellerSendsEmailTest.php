<?php

namespace Tests\Feature;

use App\DTOs\BasicUserDTO;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Events\SellerDeactivated;
use App\Listeners\SendSellerDeactivatedEmail;
use App\Mail\SellerDeactivated as SellerDeactivatedMail;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeactivateSellerSendsEmailTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $seller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);

        $this->admin = User::factory()->withRole(UserRole::ADMIN->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $this->seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
            'email' => 'seller@test.com',
        ]);
    }

    #[Test]
    public function it_dispatches_seller_deactivated_event_when_seller_is_deactivated(): void
    {
        Event::fake([SellerDeactivated::class]);

        $this->actingAs($this->admin);

        $response = $this->patchJson('/api/users/' . $this->seller->id . '/deactivate-seller');

        $response->assertStatus(200);

        Event::assertDispatched(SellerDeactivated::class, function (SellerDeactivated $event) {
            return $event->user->id === $this->seller->id
                && $event->user->email === 'seller@test.com';
        });
    }

    #[Test]
    public function it_sends_email_when_seller_deactivated_listener_handles_event(): void
    {
        Mail::fake();

        $dto = BasicUserDTO::fromModel($this->seller);
        $event = new SellerDeactivated($dto);
        $listener = new SendSellerDeactivatedEmail();

        $listener->handle($event);

        Mail::assertSent(SellerDeactivatedMail::class, function ($mail) {
            return $mail->hasTo('seller@test.com')
                && $mail->user->id === $this->seller->id;
        });
    }

    #[Test]
    public function it_sends_deactivation_email_to_seller_on_endpoint_call(): void
    {
        Mail::fake();

        $this->actingAs($this->admin);

        $response = $this->patchJson('/api/users/' . $this->seller->id . '/deactivate-seller');

        $response->assertStatus(200);

        Mail::assertSent(SellerDeactivatedMail::class, function ($mail) {
            return $mail->hasTo('seller@test.com')
                && $mail->user->id === $this->seller->id;
        });
    }
}
