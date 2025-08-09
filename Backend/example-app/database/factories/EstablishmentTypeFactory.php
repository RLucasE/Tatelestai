<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\establishmentType>
 */
class EstablishmentTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Restaurante',
                'Pizzería',
                'Cafetería',
                'Panadería',
                'Heladería',
                'Bar',
                'Comida Rápida',
                'Pastelería',
                'Marisquería',
                'Asadero'
            ]),
            'slug' => $this->faker->unique()->slug(),
            'description' => $this->faker->sentence(10),
        ];
    }
}
