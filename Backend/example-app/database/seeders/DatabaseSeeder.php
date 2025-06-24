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

        $user = User::factory()->create([
            'name' => 'Lucas',
            'last_name' => 'Ricalde',
            'email' => 'lucascabjnmro2@gmail.com',
            'password' => 12345678,
        ]);
        $this->call(PermissionSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(EstablishmentTypeSeeder::class);
        $this->call(FoodEstablishmentSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(ProductCategorySeeder::class);
        $this->call(OfferSeeder::class);
        $this->call(ProductOfferSeeder::class);
    }
}
