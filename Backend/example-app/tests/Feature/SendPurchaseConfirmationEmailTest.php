<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserState;
use App\Events\PurchaseCompleted;
use App\Listeners\SendPurchaseConfirmationEmail;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\Sell;
use App\Models\User;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
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
    public function it_dispatches_purchase_completed_event_correctly(): void
    {
        Event::fake([PurchaseCompleted::class]);

        PurchaseCompleted::dispatch($this->sell);

        Event::assertDispatched(PurchaseCompleted::class, function (PurchaseCompleted $event) {
            return $event->sell->id === $this->sell->id
                && $event->sell->bought_by === $this->customer->id
                && $event->sell->sold_by === $this->establishment->id
                && $event->sell->pickup_code === 'TEST-1234-CODE';
        });

        Event::assertListening(
            PurchaseCompleted::class,
            SendPurchaseConfirmationEmail::class
        );
    }

    #[Test]
    public function it_dispatches_purchase_completed_event_when_customer_buys_offers(): void
    {
        Event::fake([PurchaseCompleted::class]);

        $offer = Offer::factory()->active()->withProducts(2)->create([
            'food_establishment_id' => $this->establishment->id,
            'quantity' => 5,
            'expiration_datetime' => now()->addDays(2),
        ]);

        $this->actingAs($this->customer);

        $prepareResponse = $this->postJson('/api/prepare-purchase', [
            'food_establishment_id' => $this->establishment->id,
            'offers' => [
                [
                    'id' => $offer->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $prepareResponse->assertStatus(200);
        $purchaseToken = $prepareResponse->json('data.purchase_token');

        $buyResponse = $this->postJson('/api/buy-offers', [
            'purchase_token' => $purchaseToken,
        ]);

        $buyResponse->assertStatus(200);

        Event::assertDispatched(PurchaseCompleted::class, function (PurchaseCompleted $event) {
            return $event->sell->bought_by === $this->customer->id
                && $event->sell->sold_by === $this->establishment->id;
        });
    }
}
