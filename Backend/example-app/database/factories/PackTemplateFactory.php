<?php

namespace Database\Factories;

use App\Models\FoodEstablishment;
use App\Models\PackTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PackTemplate>
 */
class PackTemplateFactory extends Factory
{
    protected $model = PackTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'food_establishment_id' => FoodEstablishment::factory(),
            'title' => $this->faker->randomElement([
                'Bolsa Sorpresa Mediodía',
                'Pack Panadería Artesanal',
                'Vianda Sorpresa Noche',
                'Sweet Box Sorpresa',
                'Pack Verdulería del Día',
                'Bolsa Sorpresa Vegana',
            ]),
            'description' => $this->faker->randomElement([
                'Lote de excedentes del día con valor mínimo garantizado. El contenido exacto varía según la merma disponible.',
                'Selección sorpresa de nuestros productos frescos del día. Ideal para rescatar comida en perfecto estado.',
                'Bolsa con variedad de elaboraciones artesanales del turno. Contenido sujeto al excedente del día.',
                'Pack sorpresa armado con los productos que quedaron en el mostrador al cierre.',
            ]),
            'allergens' => $this->faker->randomElements([
                'gluten',
                'lácteos',
                'huevo',
                'frutos secos',
                'soja',
                'pescado',
            ], $this->faker->numberBetween(0, 3)),
            'estimated_weight_kg' => $this->faker->randomFloat(2, 0.5, 5.0),
        ];
    }
}
