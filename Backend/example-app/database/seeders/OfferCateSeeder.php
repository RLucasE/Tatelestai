<?php

namespace Database\Seeders;

use App\Models\OfferCate;
use Illuminate\Database\Seeder;

class OfferCateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        OfferCate::create([
            'offer_id' => 1,
            'category_id' => 1,
        ]);

        OfferCate::create([
            'offer_id' => 1,
            'category_id' => 2,
        ]);

        OfferCate::create([
            'offer_id' => 1,
            'category_id' => 3,
        ]);

        OfferCate::create([
            'offer_id' => 2,
            'category_id' => 1,
        ]);

        OfferCate::create([
            'offer_id' => 2,
            'category_id' => 2,
        ]);

        OfferCate::create([
            'offer_id' => 3,
            'category_id' => 3,
        ]);

        OfferCate::create([
            'offer_id' => 3,
            'category_id' => 2,
        ]);
    }
}
