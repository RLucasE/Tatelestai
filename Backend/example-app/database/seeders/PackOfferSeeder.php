<?php

namespace Database\Seeders;

use App\Enums\OfferState;
use App\Models\Offer;
use App\Models\PackTemplate;
use Illuminate\Database\Seeder;

class PackOfferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = PackTemplate::all();
        $created = 0;

        // Precios realistas y escalonados
        $prices = [1800, 2200, 2500, 2900, 3200, 3500, 3900, 4500];
        $index = 0;

        foreach ($templates as $template) {
            $index++;
            $price = $prices[$index % count($prices)];

            // Multiplicador para garantizar entre 55% y 65% de ahorro visible
            $multipliers = [2.2, 2.4, 2.5, 2.6, 2.8];
            $multiplier = $multipliers[$index % count($multipliers)];
            $minValue = (int) round($price * $multiplier);

            // Alternar los 3 escenarios temporales de urgencia:
            // 0: Cierre inminente (15 a 35 min) -> Activa pulso rojo/ámbar en frontend
            // 1: Próximo retiro (1h a 1h 45m) -> Urgencia media
            // 2: Retiro estándar de cierre (3h a 5h) -> Estado regular
            $urgencyScenario = $index % 3;

            if ($urgencyScenario === 0) {
                // Escenario Urgente: el retiro comenzó hace un momento y termina muy pronto
                $pickupStart = now()->subMinutes(random_int(15, 30));
                $pickupEnd = now()->addMinutes(random_int(18, 38));
                // Escasez alta: 1 o 2 unidades ("¡Última disponible!" o "¡Solo 2 disponibles!")
                $quantity = random_int(1, 2);
            } elseif ($urgencyScenario === 1) {
                // Escenario Próximo: retiro dentro de 1 a 2 horas
                $pickupStart = now()->addMinutes(random_int(10, 25));
                $pickupEnd = now()->addMinutes(random_int(65, 105));
                $quantity = random_int(2, 4);
            } else {
                // Escenario Estándar: retiro de noche
                $pickupStart = now()->addHours(random_int(2, 4));
                $pickupEnd = (clone $pickupStart)->addHours(random_int(1, 2));
                $quantity = random_int(3, 8);
            }

            // Crear pack activo para los clientes
            Offer::factory()
                ->fromTemplate($template)
                ->create([
                    'price' => $price,
                    'minimum_value' => $minValue,
                    'quantity' => $quantity,
                    'pickup_start_datetime' => $pickupStart,
                    'expiration_datetime' => $pickupEnd,
                    'state' => OfferState::ACTIVE->value,
                ]);
            $created++;

            // Crear packs adicionales en locales seleccionados para mayor volumen
            if ($index % 2 === 0) {
                $altPrice = $prices[($index + 3) % count($prices)];
                $altMin = (int) round($altPrice * 2.5);
                Offer::factory()
                    ->fromTemplate($template)
                    ->create([
                        'price' => $altPrice,
                        'minimum_value' => $altMin,
                        'quantity' => random_int(1, 5),
                        'pickup_start_datetime' => now()->addMinutes(random_int(30, 90)),
                        'expiration_datetime' => now()->addHours(random_int(2, 4)),
                        'state' => OfferState::ACTIVE->value,
                    ]);
                $created++;
            }

            // Crear packs finalizados/comprados para datos históricos
            if ($index % 3 === 0) {
                Offer::factory()
                    ->fromTemplate($template)
                    ->create([
                        'price' => $price,
                        'minimum_value' => $minValue,
                        'quantity' => 0,
                        'pickup_start_datetime' => now()->subHours(3),
                        'expiration_datetime' => now()->subMinutes(15),
                        'state' => OfferState::PURCHASED->value,
                    ]);
                $created++;
            }
        }

        $this->command?->info("Packs sorpresa creados exitosamente ({$created} ofertas preparadas para visualización óptima).");
    }
}
