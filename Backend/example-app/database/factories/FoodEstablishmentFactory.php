<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\foodEstablishment>
 */
class FoodEstablishmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'establishment_type_id' => \App\Models\EstablishmentType::inRandomOrder()->first()?->id
                ?? \App\Models\EstablishmentType::factory(),
            'name' => $this->faker->company(),
            'address' => $this->faker->address(),
        ];
    }
}
