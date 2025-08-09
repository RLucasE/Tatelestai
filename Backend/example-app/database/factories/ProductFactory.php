<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\product>
 */
class ProductFactory extends Factory
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
                'Pizza Margherita',
                'Hamburguesa Clásica',
                'Empanadas de Carne',
                'Milanesa Napolitana',
                'Ensalada César',
                'Pasta Bolognesa',
                'Sandwich de Jamón y Queso',
                'Pollo Grillado',
                'Papas Fritas',
                'Helado de Vainilla',
                'Café Cortado',
                'Medialunas',
                'Tarta de Manzana',
                'Lomito Completo',
                'Sushi Roll',
                'Paella',
                'Asado',
                'Choripán',
                'Provoleta',
                'Flan Casero'
            ]),
            'description' => $this->faker->randomElement([
                'Deliciosa preparación con ingredientes frescos',
                'Especialidad de la casa con receta tradicional',
                'Producto artesanal elaborado diariamente',
                'Opción saludable y nutritiva',
                'Clásico de la gastronomía argentina',
                'Preparado con ingredientes de primera calidad',
                'Ideal para compartir en familia',
                'Recomendado por nuestros clientes',
                'Elaborado con amor y dedicación',
                'La mejor calidad al mejor precio'
            ]),
        ];
    }
}
