<?php

namespace Database\Seeders;

use App\Models\FoodEstablishment;
use App\Models\Offer;
use Illuminate\Database\Seeder;

class OfferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $establishments = FoodEstablishment::all();

        foreach ($establishments as $establishment) {
            // Ofertas activas estándar con productos del establecimiento
            Offer::factory()
                ->count(3)
                ->active()
                ->withProducts(random_int(1, 3))
                ->for($establishment)
                ->create();

            // Oferta activa que vence pronto (urgencia / próximas horas)
            Offer::factory()
                ->expiringSoon()
                ->withProducts(random_int(1, 2))
                ->for($establishment)
                ->create();

            // Oferta adicional activa
            Offer::factory()
                ->active()
                ->withProducts(1)
                ->for($establishment)
                ->create();

            // Oferta agotada/comprada para pruebas de estados
            Offer::factory()
                ->purchased()
                ->withProducts(1)
                ->for($establishment)
                ->create();
        }

        $this->command?->info("Ofertas creadas exitosamente para {$establishments->count()} locales en Salta Capital.");
    }
}
