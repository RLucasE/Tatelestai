<?php

namespace Database\Factories;

use App\Actions\Sell\GeneratePickupCodeAction;
use App\DTOs\PreparePurchaseDTO;
use App\Enums\PaymentStatus;
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
            'max_pickup_datetime' => now()->addHours(4),
            'state' => \App\Enums\SellState::PENDING->value,
            'payment_status' => PaymentStatus::APPROVED->value,
            'payment_method' => 'mercadopago',
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

            $grossAmount = 0;

            foreach ($offers as $offer) {
                $quantity = $this->faker->numberBetween(1, 3);
                $price = (float) ($offer->price ?? $this->faker->numberBetween(1500, 4000));
                $grossAmount += $quantity * $price;

                \App\Models\SellDetail::factory()->create([
                    'sell_id' => $sell->id,
                    'offer_id' => $offer->id,
                    'offer_quantity' => $quantity,
                    'pack_price' => $price,
                    'pack_name' => $offer->title,
                    'pack_description' => $offer->description,
                    'allergens' => $offer->allergens,
                    'estimated_weight_kg' => $offer->estimated_weight_kg,
                    'minimum_value' => $offer->minimum_value,
                ]);
            }

            $grossAmount = round($grossAmount, 2);
            $commissionPercentage = (float) config('services.mercadopago.commission_percentage', 15.0);
            $commissionAmount = round($grossAmount * ($commissionPercentage / 100), 2);
            $netAmount = round($grossAmount - $commissionAmount, 2);

            $sell->update([
                'gross_amount' => $grossAmount,
                'commission_percentage' => $commissionPercentage,
                'commission_amount' => $commissionAmount,
                'net_amount' => $netAmount,
            ]);
        });
    }
}
