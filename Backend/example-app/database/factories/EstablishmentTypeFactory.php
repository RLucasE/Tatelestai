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
        $establishmentTypes = [
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
        ];

        $selectedType = fake()->randomElement($establishmentTypes);

        return [
            'name' => $selectedType,
            'slug' => strtolower(str_replace(' ', '-', $selectedType)) . '-' . fake()->unique()->numberBetween(1000, 9999),
            'description' => fake()->sentence(),
        ];
    }
}
