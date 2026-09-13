<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call(PermissionSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(EstablishmentTypeSeeder::class);
        $this->call(FoodEstablishmentSeeder::class);
        $this->call(PackTemplateSeeder::class);
        $this->call(PackOfferSeeder::class);
        $this->call(SellSeeder::class);

        \App\Models\Offer::makeAllSearchable();
    }
}
