<?php

namespace Database\Seeders;

use App\Enums\UserState;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $user = User::factory()->create([
            'name' => 'Lucas',
            'last_name' => 'Ricalde',
            'email' => 'customer@gmail.com',
            'state' => UserState::SELECTING,
            'password' => 12345678,
        ]);
        $user->assignRole('customer');
        $user = User::factory()->create([
            'name' => 'Lucas',
            'last_name' => 'Ricalde',
            'email' => 'seller@gmail.com',
            'state' => UserState::SELECTING,
            'password' => 12345678,
        ]);
        $user->assignRole('seller');
    }
}
