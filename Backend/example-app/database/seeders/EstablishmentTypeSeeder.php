<?php

namespace Database\Seeders;

use App\Models\EstablishmentType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EstablishmentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        EstablishmentType::factory()->create([
            'name' => 'Restaurante',
            'slug' => 'restaurante',
            'description' => 'Un lugar donde se sirven comidas y bebidas.',
        ]);
        EstablishmentType::factory()->create([
            'name' => 'Cafetería',
            'slug' => 'cafetería',
            'description' => 'Un establecimiento que sirve café y otros alimentos ligeros.',
        ]);
        EstablishmentType::factory()->create([
            'name' => 'Supermercado',
            'slug' => 'supermercado',
            'description' => 'Un gran establecimiento que vende alimentos y otros productos.',
        ]);
    }
}
