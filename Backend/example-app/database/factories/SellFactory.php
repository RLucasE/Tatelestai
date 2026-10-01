<?php

namespace Database\Factories;

use App\Actions\Sell\GeneratePickupCodeAction;
use App\DTOs\PreparePurchaseDTO;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\FoodEstablishment;
use App\Models\Sell;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sell>
 */
class SellFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Sell::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $customer = User::whereHas('roles', function ($query) {
            $query->where('name', UserRole::CUSTOMER->value);
            $query->where('state', UserState::ACTIVE->value);
        })->inRandomOrder()->first();

        $establishment = FoodEstablishment::inRandomOrder()->first();

        $generatePickupCodeAction = new GeneratePickupCodeAction;
        $mockDTO = new PreparePurchaseDTO(
            food_establishment_id: $establishment->id,
            offers: [],
        );

        $pickupCode = $generatePickupCodeAction->execute(
            $customer->id,
            $establishment->id,
            $mockDTO
        );

        return [
            'bought_by' => $customer->id,
            'sold_by' => $establishment->id,
            'pickup_code' => $pickupCode,
            'state' => \App\Enums\SellState::PENDING->value,
            'is_picked_up' => false,
            'picked_up_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the sell is picked up.
     */
    public function pickedUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_picked_up' => true,
            'picked_up_at' => now(),
            'state' => \App\Enums\SellState::PICKED_UP->value,
        ]);
    }

    /**
     * Create sell with details (SellDetail records)
     *
     * @param  int  $count  Number of sell details to create
     */
    public function withDetails(int $count = 3): static
    {
        return $this->afterCreating(function (Sell $sell) use ($count) {
            $establishment = FoodEstablishment::find($sell->sold_by);

            $offers = \App\Models\Offer::where('food_establishment_id', $establishment->id)
                ->where('state', \App\Enums\OfferState::ACTIVE->value)
                ->inRandomOrder()
                ->limit($count)
                ->get();

            if ($offers->isEmpty()) {
                $offers = \App\Models\Offer::factory()
                    ->count($count)
                    ->create([
                        'food_establishment_id' => $establishment->id,
                        'state' => \App\Enums\OfferState::ACTIVE->value,
                    ]);
            }

            foreach ($offers as $offer) {
                \App\Models\SellDetail::factory()->create([
                    'sell_id' => $sell->id,
                    'offer_id' => $offer->id,
                    'offer_quantity' => $this->faker->numberBetween(1, 5),
                    'pack_price' => $offer->price ?? $this->faker->numberBetween(300, 2000),
                    'pack_name' => $offer->title,
                    'pack_description' => $offer->description,
                ]);
            }
        });
    }
}
