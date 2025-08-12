<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;


class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Role::create(['name' => UserRole::DEFAULT->value]);
        Role::create(['name' => UserRole::CUSTOMER->value]);
        Role::create(['name' => UserRole::SELLER->value]);
        Role::create(['name' => UserRole::ADMIN->value]);
        Role::create(['name' => 'super_admin']);

    }
}
