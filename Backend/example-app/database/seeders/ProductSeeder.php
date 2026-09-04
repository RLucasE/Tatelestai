<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'seller@gmail.com')->first();
        $establishment = $user->foodEstablishment;
        Product::factory()->count(50)->create([
            'food_establishment_id' => $establishment->id,
        ]);
    }
}
