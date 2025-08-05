<?php

namespace Database\Seeders;

use App\Models\FoodEstablishment;
use App\Models\Product;
use Illuminate\Database\Seeder;


class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::factory()->create([
            'name' => 'Papas Fritas',
            'description' => 'Classic pizza with tomato sauce, mozzarella cheese, and fresh basil.',
            'food_establishment_id' => 1, // Assuming food_establishment_id 1 exists
        ]);

        Product::factory()->create([
            'name' => 'Pancho',
            'description' => 'Pancho con pan fresco, salchicha y condimentos.',
            'food_establishment_id' => 1, // Assuming food_establishment_id 1 exists
        ]);
    }
}
