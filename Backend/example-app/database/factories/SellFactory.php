<?php

namespace Database\Factories;

use App\Models\Sell;
use App\Models\User;
use App\Models\FoodEstablishment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Sell> */

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
        return [
            'bought_by' => User::factory(),
            'sold_by' => FoodEstablishment::factory(),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_at' => now(),
        ];
    }
}
