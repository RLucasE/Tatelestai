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
        $prices = [1500, 2000, 2500, 3000, 3500, 4000];
        $created = 0;

        foreach ($templates as $template) {
            $pickupStart = now()->addMinutes(random_int(30, 180));
            $pickupEnd = (clone $pickupStart)->addHours(random_int(1, 3));
            $price = fake()->randomElement($prices);
            $activeCount = random_int(1, 2);

            Offer::factory()
                ->fromTemplate($template)
                ->count($activeCount)
                ->create([
                    'price' => $price,
                    'minimum_value' => $price * random_int(2, 3),
                    'quantity' => random_int(2, 12),
                    'pickup_start_datetime' => $pickupStart,
                    'expiration_datetime' => $pickupEnd,
                    'state' => OfferState::ACTIVE->value,
                ]);
            $created += $activeCount;

            Offer::factory()
                ->fromTemplate($template)
                ->create([
                    'price' => $price,
                    'minimum_value' => $price * 3,
                    'quantity' => 0,
                    'pickup_start_datetime' => $pickupStart,
                    'expiration_datetime' => $pickupEnd,
                    'state' => OfferState::PURCHASED->value,
                ]);
            $created++;
        }

        $this->command?->info("Packs sorpresa creados exitosamente ({$created} ofertas).");
    }
}
