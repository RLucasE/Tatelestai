<?php

namespace Database\Seeders;

use App\Enums\UserState;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Http\Controllers\CustomerCartController;
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $customerCartController = new CustomerCartController();
        $user = User::factory()->create([
            'name' => 'Lucas',
            'last_name' => 'Ricalde',
            'email' => 'customer@gmail.com',
            'state' => UserState::ACTIVE->value,
            'password' => 12345678,
        ]);
        $user->assignRole('customer');
        $customerCartController->asingFirstCart($user);
        $user = User::factory()->create([
            'name' => 'Lucas',
            'last_name' => 'Ricalde',
            'email' => 'seller@gmail.com',
            'state' => UserState::ACTIVE->value,
            'password' => 12345678,
        ]);
        $user->assignRole('seller');
    }
}
