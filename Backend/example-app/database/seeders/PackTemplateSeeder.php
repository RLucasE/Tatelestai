<?php

namespace Database\Seeders;

use App\Models\FoodEstablishment;
use App\Models\PackTemplate;
use Illuminate\Database\Seeder;

class PackTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Plantillas representativas por tipo de establecimiento para cubrir todas las categorías del marketplace
        $templatesByType = [
            1 => [ // Restaurantes / Rotiserías / Pizzerías
                [
                    'title' => 'Vianda Sorpresa Noche Casera',
                    'description' => 'Platos principales y guarniciones del día preparados al cierre. El contenido varía según el excedente gourmet disponible.',
                    'allergens' => ['gluten', 'lácteos', 'huevo'],
                    'estimated_weight_kg' => 1.50,
                ],
                [
                    'title' => 'Pack Almuerzo Ejecutivo del Día',
                    'description' => 'Almuerzo sorpresa con elaboraciones frescas del mediodía: carnes, pastas caseras o tartas con guarnición.',
                    'allergens' => ['gluten'],
                    'estimated_weight_kg' => 1.20,
                ],
                [
                    'title' => 'Pizza Box Sorpresa Artesanal',
                    'description' => 'Variedad de porciones de pizzas a la piedra y calzones recién horneados al horno de barro con mozzarella de primera.',
                    'allergens' => ['gluten', 'lácteos'],
                    'estimated_weight_kg' => 1.10,
                ],
                [
                    'title' => 'Pack Vegano & Bowls Saludables',
                    'description' => 'Bowls nutritivos de quinoa, vegetales asados de huerta, legumbres, hummus y ensaladas plant-based sin ingredientes de origen animal.',
                    'allergens' => ['frutos secos', 'soja'],
                    'estimated_weight_kg' => 1.30,
                ],
            ],
            2 => [ // Cafeterías / Panaderías / Pastelerías
                [
                    'title' => 'Bolsa Sorpresa Panadería Artesanal',
                    'description' => 'Selección de panificados de masa madre, baguettes, marraquetas y facturas recién horneadas del día.',
                    'allergens' => ['gluten', 'lácteos', 'huevo'],
                    'estimated_weight_kg' => 1.00,
                ],
                [
                    'title' => 'Sweet Box Repostería & Pastelería',
                    'description' => 'Caja sorpresa con delicias dulces artesanales del día: porciones de tortas caseras, muffins de arándanos, alfajores y cookies.',
                    'allergens' => ['gluten', 'lácteos', 'huevo', 'frutos secos'],
                    'estimated_weight_kg' => 0.85,
                ],
                [
                    'title' => 'Pack Merienda Café & Croissants',
                    'description' => 'Croissants de manteca, medialunas rellenas, chipás calientes y pastelería del día para acompañar tu café.',
                    'allergens' => ['gluten', 'lácteos'],
                    'estimated_weight_kg' => 0.90,
                ],
                [
                    'title' => 'Pack Saludable Vegano: Frutas & Budines',
                    'description' => 'Ensaladas de frutas frescas de estación, budines integrales 100% vegetales y snacks naturales.',
                    'allergens' => ['frutos secos'],
                    'estimated_weight_kg' => 0.95,
                ],
            ],
            3 => [ // Supermercados / Almacenes / Dietéticas
                [
                    'title' => 'Canasta Rescate Huerta & Frutas',
                    'description' => 'Canasta surtida con frutas y verduras frescas de huerta próximas a madurar, en óptimo estado de consumo.',
                    'allergens' => [],
                    'estimated_weight_kg' => 3.20,
                ],
                [
                    'title' => 'Pack Almacén & Lácteos Sorpresa',
                    'description' => 'Selección de yogures, quesos, fiambres y productos de almacén envasados con fecha de consumo preferente cercana.',
                    'allergens' => ['lácteos', 'gluten'],
                    'estimated_weight_kg' => 2.50,
                ],
                [
                    'title' => 'Bolsa Sorpresa Panificados de Supermercado',
                    'description' => 'Panes de molde artesanales, pre-pizzas y galletas envasadas con descuento especial de cierre.',
                    'allergens' => ['gluten'],
                    'estimated_weight_kg' => 1.80,
                ],
                [
                    'title' => 'Pack Almacén Vegano & Dietética',
                    'description' => 'Bebidas vegetales, barras de cereal, semillas, granolas y frutos secos para una alimentación consciente.',
                    'allergens' => ['frutos secos', 'soja'],
                    'estimated_weight_kg' => 1.60,
                ],
            ],
        ];

        foreach (FoodEstablishment::all() as $establishment) {
            $templates = $templatesByType[$establishment->establishment_type_id] ?? $templatesByType[1];

            foreach ($templates as $template) {
                PackTemplate::firstOrCreate(
                    [
                        'food_establishment_id' => $establishment->id,
                        'title' => $template['title'],
                    ],
                    [
                        'description' => $template['description'],
                        'allergens' => $template['allergens'],
                        'estimated_weight_kg' => $template['estimated_weight_kg'],
                    ]
                );
            }
        }

        $this->command?->info('Plantillas de packs creadas exitosamente para los locales.');
    }
}
