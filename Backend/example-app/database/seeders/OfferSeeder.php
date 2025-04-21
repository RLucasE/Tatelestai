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
            'user_id' => User::first()->id
        ]);

        Offer::factory()->create([
            'user_id' => User::first()->id
        ]);

        Offer::factory()->create([
            'user_id' => User::first()->id
        ]);
    }
}
