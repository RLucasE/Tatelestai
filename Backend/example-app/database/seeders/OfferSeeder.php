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
        $seller = User::where('email', 'seller@gmail.com')->first();
        if ($seller) {

            Offer::factory()
                ->count(500)
                ->withProducts(3)
                ->for($seller->foodEstablishment)
                ->create();

            echo "Mitad del OfferSeeder completada - Primera parte de ofertas creada\n";

            Offer::factory()
                ->count(500)
                ->withProducts(random_int(1,3))
                ->for($seller->foodEstablishment)
                ->create();
        }
    }
}
