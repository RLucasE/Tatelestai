<?php

namespace Database\Factories;

use App\Models\Offer;
use App\Models\Sell;
use Illuminate\Database\Eloquent\Factories\Factory;

class SellDetailFactory extends Factory
{
    public function definition(): array
    {
        return [
            'offer_id' => Offer::factory(),
            'sell_id' => Sell::factory(),
            'offer_quantity' => $this->faker->numberBetween(1, 5),
            'pack_price' => $this->faker->numberBetween(1500, 5000),
            'pack_name' => $this->faker->words(3, true),
            'pack_description' => $this->faker->sentence(),
        ];
    }
}
