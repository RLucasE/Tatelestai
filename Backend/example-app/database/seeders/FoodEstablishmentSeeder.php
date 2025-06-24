<?php

namespace Database\Seeders;

use App\Models\FoodEstablishment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FoodEstablishmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        FoodEstablishment::factory()->create([
            'user_id' => 1,
            'establishment_type_id' => 1,
        ]);
    }
}
