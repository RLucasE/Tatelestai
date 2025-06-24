<?php

namespace Database\Seeders;

use App\Models\Offer;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


class OfferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Offer::factory()->create([
            'food_establishment_id' => 1,
            'title' => 'Papas Fritas y Pancho',
            'description' => 'Papas fritas preparadas con sal más un super pancho'
        ]);
    }
}
