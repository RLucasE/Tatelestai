<?php

namespace Tests\Unit;

use App\Actions\Offers\RestoreOfferStockAction;
use App\Enums\OfferState;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\User;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestoreOfferStockActionTest extends TestCase
{
    use RefreshDatabase;

    protected RestoreOfferStockAction $action;

    protected FoodEstablishment $establishment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(EstablishmentTypeSeeder::class);

        $seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $establishmentType = EstablishmentType::first();

        $this->establishment = FoodEstablishment::factory()->create([
            'user_id' => $seller->id,
            'establishment_type_id' => $establishmentType->id,
            'name' => 'Comercio Test Stock',
        ]);

        $this->action = new RestoreOfferStockAction;
    }

    #[Test]
    public function it_returns_null_if_offer_does_not_exist(): void
    {
        $result = $this->action->execute(999999, 2);

        expect($result)->toBeNull();
    }

    #[Test]
    public function it_restores_stock_and_reactivates_purchased_offer_when_window_is_valid(): void
    {
        $offer = Offer::factory()->create([
            'food_establishment_id' => $this->establishment->id,
            'quantity' => 0,
            'state' => OfferState::PURCHASED->value,
            'pickup_start_datetime' => now()->addHours(1),
            'expiration_datetime' => now()->addHours(2),
        ]);

        $updatedOffer = $this->action->execute($offer->id, 3);

        expect($updatedOffer)->not->toBeNull()
            ->and($updatedOffer->quantity)->toBe(3)
            ->and($updatedOffer->state)->toBe(OfferState::ACTIVE->value);

        expect($offer->fresh()->quantity)->toBe(3)
            ->and($offer->fresh()->state)->toBe(OfferState::ACTIVE->value);
    }

    #[Test]
    public function it_restores_stock_without_reactivating_if_offer_is_expired(): void
    {
        // Oferta con ventana de retiro vencida hace 1 hora
        $offer = Offer::factory()->create([
            'food_establishment_id' => $this->establishment->id,
            'quantity' => 0,
            'state' => OfferState::PURCHASED->value,
            'pickup_start_datetime' => now()->subHours(2),
            'expiration_datetime' => now()->subHours(1),
        ]);

        $updatedOffer = $this->action->execute($offer->id, 2);

        expect($updatedOffer)->not->toBeNull()
            ->and($updatedOffer->quantity)->toBe(2)
            ->and($updatedOffer->state)->toBe(OfferState::PURCHASED->value); // Mantiene 'purchased', no reaparece vencida en catálogo

        expect($offer->fresh()->state)->toBe(OfferState::PURCHASED->value);
    }

    #[Test]
    public function it_restores_stock_without_reactivating_if_merchant_manually_paused_it(): void
    {
        // Oferta que el comerciante pausó deliberadamente a 'inactive'
        $offer = Offer::factory()->create([
            'food_establishment_id' => $this->establishment->id,
            'quantity' => 0,
            'state' => OfferState::INACTIVE->value,
            'pickup_start_datetime' => now()->addHours(1),
            'expiration_datetime' => now()->addHours(2),
        ]);

        $updatedOffer = $this->action->execute($offer->id, 1);

        expect($updatedOffer)->not->toBeNull()
            ->and($updatedOffer->quantity)->toBe(1)
            ->and($updatedOffer->state)->toBe(OfferState::INACTIVE->value); // Respeta la pausa manual del vendedor

        expect($offer->fresh()->state)->toBe(OfferState::INACTIVE->value);
    }

    #[Test]
    public function it_correctly_increments_stock_on_already_active_offer(): void
    {
        $offer = Offer::factory()->active()->create([
            'food_establishment_id' => $this->establishment->id,
            'quantity' => 4,
            'expiration_datetime' => now()->addHours(3),
        ]);

        $updatedOffer = $this->action->execute($offer->id, 2);

        expect($updatedOffer)->not->toBeNull()
            ->and($updatedOffer->quantity)->toBe(6)
            ->and($updatedOffer->state)->toBe(OfferState::ACTIVE->value);

        expect($offer->fresh()->quantity)->toBe(6);
    }
}
