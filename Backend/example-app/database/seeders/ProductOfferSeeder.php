<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\productOffer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductOfferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        productOffer::factory()->create(
            [
                'product_id' => 1,
                'offer_id' => 1,
                'quantity' => 2,
            ]
        );
        productOffer::factory()->create(
            [
                'product_id' => 2,
                'offer_id' => 1,
            ]
        );
    }
}
