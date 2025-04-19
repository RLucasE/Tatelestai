<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Category::factory()->create([
            'category_name' => 'Panaderia',
            'parent_id' => null
        ]);

        $panaderiaId = Category::where('category_name', 'Panaderia')->first()->id;

        Category::factory()->create([
            'category_name' => 'Bollos',
            'parent_id' => $panaderiaId
        ]);

        Category::factory()->create([
            'category_name' => 'Facturas',
            'parent_id' => $panaderiaId
        ]);
    }
}
