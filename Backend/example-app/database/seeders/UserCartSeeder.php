<?php

namespace Database\Seeders;

use App\Enums\CartState;
use App\Models\UserCart;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserCartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UserCart::factory()->count(10)->create([
            'user_id' => 1, // Assuming user with ID 1 exists
            'state' => CartState::ACTIVE, // Default state for the cart
        ]);
    }
}
