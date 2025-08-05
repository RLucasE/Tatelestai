<?php

namespace Database\Seeders;

use App\Models\FoodEstablishment;
use App\Models\User;
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
            'user_id' =>User::where('email','seller@gmail.com')->first()->id,
            'establishment_type_id' => 1,
        ]);
    }
}
