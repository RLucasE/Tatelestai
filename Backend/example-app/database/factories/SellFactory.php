<?php

namespace Database\Factories;

use App\Actions\Sell\GeneratePickupCodeAction;
use App\DTOs\PreparePurchaseDTO;
use App\Enums\UserState;
use App\Models\FoodEstablishment;
use App\Models\Sell;
use App\Models\User;
use App\Enums\UserRole;
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

        $isPickedUp = $this->faker->boolean();

        $pickedUpAt = null;
        $createdAt = $this->faker->dateTimeBetween('-1 month', 'now');

        if ($isPickedUp) {
            $pickedUpAt = $this->faker->dateTimeBetween('now', '+10 days');
            $createdAt = $this->faker->dateTimeBetween('-1 month', $pickedUpAt);
        }

        $generatePickupCodeAction = new GeneratePickupCodeAction();
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
            'is_picked_up' => $isPickedUp,
            'picked_up_at' => $pickedUpAt,
            'created_at' => $createdAt,
            'updated_at' => now(),
        ];
    }

    /**
     * Create sell with details (SellDetail records)
     *
     * @param int $count Number of sell details to create
     * @return static
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
                    'product_quantity' => $this->faker->numberBetween(1, 3),
                    'product_price' => $this->faker->numberBetween(300, 2000),
                    'product_name' => $this->faker->words(3, true),
                    'product_description' => $this->faker->sentence(),
                ]);
            }
        });
    }
}
