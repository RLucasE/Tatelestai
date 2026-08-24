<?php

namespace Tests\Feature;

use App\DTOs\BasicUserDTO;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Events\SellerDenied;
use App\Listeners\SendSellerDeniedEmail;
use App\Mail\SellerDenied as SellerDeniedMail;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\User;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DenySellerSendsEmailTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $seller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(EstablishmentTypeSeeder::class);

        $this->admin = User::factory()->withRole(UserRole::ADMIN->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $this->seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::WAITING_FOR_CONFIRMATION->value,
            'email' => 'seller@test.com',
        ]);
    }

    #[Test]
    public function it_dispatches_seller_denied_event_when_seller_is_denied(): void
    {
        Event::fake([SellerDenied::class]);

        $this->actingAs($this->admin);

        $response = $this->patchJson('/api/users/' . $this->seller->id . '/denie-seller');

        $response->assertStatus(200);

        Event::assertDispatched(SellerDenied::class, function (SellerDenied $event) {
            return $event->user->id === $this->seller->id
                && $event->user->email === 'seller@test.com';
        });
    }

    #[Test]
    public function it_sends_email_when_seller_denied_listener_handles_event(): void
    {
        Mail::fake();

        $dto = BasicUserDTO::fromModel($this->seller);
        $event = new SellerDenied($dto);
        $listener = new SendSellerDeniedEmail();

        $listener->handle($event);

        Mail::assertSent(SellerDeniedMail::class, function ($mail) {
            return $mail->hasTo('seller@test.com')
                && $mail->user->id === $this->seller->id;
        });
    }

    #[Test]
    public function it_sends_denial_email_to_seller_on_endpoint_call(): void
    {
        Mail::fake();

        $this->actingAs($this->admin);

        $response = $this->patchJson('/api/users/' . $this->seller->id . '/denie-seller');

        $response->assertStatus(200);

        Mail::assertSent(SellerDeniedMail::class, function ($mail) {
            return $mail->hasTo('seller@test.com')
                && $mail->user->id === $this->seller->id;
        });
    }

    #[Test]
    public function it_dispatches_seller_denied_event_when_establishment_is_rejected(): void
    {
        Event::fake([SellerDenied::class]);

        $establishmentType = EstablishmentType::inRandomOrder()->first();

        $establishment = FoodEstablishment::factory()->create([
            'user_id' => $this->seller->id,
            'establishment_type_id' => $establishmentType->id,
            'verification_status' => 'pending',
        ]);

        $this->actingAs($this->admin);

        $response = $this->patchJson('/api/adm/establishments/' . $establishment->id . '/reject', [
            'reason' => 'Los documentos adjuntos no son legibles.',
        ]);

        $response->assertStatus(200);

        Event::assertDispatched(SellerDenied::class, function (SellerDenied $event) {
            return $event->user->id === $this->seller->id
                && $event->user->email === 'seller@test.com';
        });
    }
}
