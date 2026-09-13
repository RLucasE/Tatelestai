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
        $templatesByType = [
            1 => [
                [
                    'title' => 'Vianda Sorpresa Noche',
                    'description' => 'Pack de comida casera del día: platos principales y guarniciones que quedaron al cierre del turno. El contenido exacto varía según la merma disponible.',
                    'allergens' => ['gluten', 'lácteos', 'huevo'],
                    'estimated_weight_kg' => 1.50,
                ],
                [
                    'title' => 'Pack Mediodía',
                    'description' => 'Almuerzo sorpresa con elaboraciones frescas del mediodía. Ideal para rescatar comida en perfecto estado a precio reducido.',
                    'allergens' => ['gluten'],
                    'estimated_weight_kg' => 1.20,
                ],
            ],
            2 => [
                [
                    'title' => 'Bolsa Sorpresa Panadería',
                    'description' => 'Selección de panificados y facturas recién horneados que no se vendieron durante el día. Valor real muy superior al precio del pack.',
                    'allergens' => ['gluten', 'lácteos', 'huevo'],
                    'estimated_weight_kg' => 1.00,
                ],
                [
                    'title' => 'Sweet Box Sorpresa',
                    'description' => 'Caja sorpresa con pastelería artesanal del día: tortas, alfajores, cookies y más. Contenido sujeto al excedente disponible.',
                    'allergens' => ['gluten', 'lácteos', 'huevo', 'frutos secos'],
                    'estimated_weight_kg' => 0.80,
                ],
            ],
            3 => [
                [
                    'title' => 'Canasta Rescate',
                    'description' => 'Canasta con frutas, verduras y productos frescos próximos a vencer, en perfecto estado de consumo.',
                    'allergens' => [],
                    'estimated_weight_kg' => 3.00,
                ],
                [
                    'title' => 'Pack Almacén Sorpresa',
                    'description' => 'Productos de almacén y lácteos con fecha cercana: lácteos, panificados envasados y más.',
                    'allergens' => ['lácteos', 'gluten'],
                    'estimated_weight_kg' => 2.50,
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
