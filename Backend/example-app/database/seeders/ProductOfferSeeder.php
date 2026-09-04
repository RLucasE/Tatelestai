<?php

namespace Database\Seeders;

use App\Models\productOffer;
use Illuminate\Database\Seeder;

class ProductOfferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProductOffer::factory()->count(50)->create(
            ['offer_id' => rand(50, 200)]
        );
    }
}
