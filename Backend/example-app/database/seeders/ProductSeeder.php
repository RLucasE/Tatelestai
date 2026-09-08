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
        $establishments = FoodEstablishment::all();

        foreach ($establishments as $establishment) {
            // Entre 8 y 12 productos por establecimiento
            $count = $establishment->user?->email === 'seller@gmail.com' ? 25 : 8;
            Product::factory()->count($count)->create([
                'food_establishment_id' => $establishment->id,
            ]);
        }
    }
}
