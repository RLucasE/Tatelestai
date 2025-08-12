<?php

namespace Database\Seeders;

use App\Actions\Cart\GetCustomerCartAction;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\User;
use Illuminate\Database\Seeder;
use App\Http\Controllers\CustomerCartController;
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customerCartController = new CustomerCartController(app(GetCustomerCartAction::class));
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

        User::factory()->withRole(UserRole::ADMIN->value)->create([
            'name' => 'Admin',
            'last_name' => 'Admin',
            'email' => 'admin@gmail.com',
            'state' => UserState::ACTIVE->value,
            'password' => 12345678,
        ]);
    }
}
