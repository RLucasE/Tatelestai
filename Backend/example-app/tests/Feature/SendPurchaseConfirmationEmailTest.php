<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserState;
use App\Events\PurchaseCompleted;
use App\Listeners\SendPurchaseConfirmationEmail;
use App\Mail\PurchaseConfirmation;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Sell;
use App\Models\User;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendPurchaseConfirmationEmailTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $seller;
    protected FoodEstablishment $establishment;
    protected Sell $sell;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(EstablishmentTypeSeeder::class);

        $this->customer = User::factory()->withRole(UserRole::CUSTOMER->value)->create([
            'state' => UserState::ACTIVE->value,
            'email' => 'customer@test.com',
        ]);

        $this->seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $establishmentType = EstablishmentType::inRandomOrder()->first();

        $this->establishment = FoodEstablishment::factory()->create([
            'user_id' => $this->seller->id,
            'establishment_type_id' => $establishmentType->id,
        ]);

        $this->sell = Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'TEST-1234-CODE',
        ]);
    }

    #[Test]
    public function it_sends_purchase_confirmation_email_when_purchase_completed_event_is_handled(): void
    {
        Mail::fake();

        $event = new PurchaseCompleted($this->sell);
        $listener = new SendPurchaseConfirmationEmail();

        $listener->handle($event);

        Mail::assertSent(PurchaseConfirmation::class, function ($mail) {
            return $mail->hasTo('customer@test.com') && $mail->sell->id === $this->sell->id;
        });
    }
}

