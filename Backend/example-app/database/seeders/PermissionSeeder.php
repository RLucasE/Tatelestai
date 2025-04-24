<?php

namespace Database\Seeders;

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
        Role::create(['name' => 'default']);
        Role::create(['name' => 'customer']);
        Role::create(['name' => 'pending_seller']);
        Role::create(['name' => 'seller']);
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'super_admin']);
    }
}
