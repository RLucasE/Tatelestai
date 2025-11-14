<?php

namespace Database\Seeders;

use App\Enums\SellState;
use App\Models\Sell;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SellSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creando ventas de los últimos 5 días...');

        $now = Carbon::now();
        $sellsPerDay = 15;
        $totalSells = 0;

        for ($day = 4; $day >= 0; $day--) {
            $targetDate = $now->copy()->subDays($day);

            $this->command->info("Generando ventas para: {$targetDate->format('Y-m-d')}");

            $dailySells = rand(3, $sellsPerDay);

            for ($i = 0; $i < $dailySells; $i++) {
                $randomHour = rand(8, 22);
                $randomMinute = rand(0, 59);

                $createdAt = $targetDate->copy()->setTime($randomHour, $randomMinute);

                // Max pickup time: entre 2 y 8 horas después de la creación
                $maxPickupDatetime = $createdAt->copy()->addHours(rand(2, 8));

                $isPickedUp = fake()->boolean(70);
                $pickedUpAt = null;
                $state = SellState::PENDING;

                if ($isPickedUp && $createdAt->isBefore($now)) {
                    $pickedUpAt = $createdAt->copy()->addMinutes(rand(30, 480));
                    if ($pickedUpAt->isAfter($now)) {
                        $pickedUpAt = null;
                        $isPickedUp = false;
                    } else {
                        $state = SellState::PICKED_UP;
                    }
                }

                // Si no fue retirado, determinar el estado según max_pickup_datetime
                if (!$isPickedUp) {
                    if ($maxPickupDatetime->isBefore($now)) {
                        // Si ya pasó la fecha máxima de retiro, está expirado
                        $state = SellState::EXPIRED;
                    } else {
                        // Si aún está dentro del plazo, elegir estado aleatorio
                        $states = [SellState::PENDING, SellState::CONFIRMED, SellState::READY];
                        $state = fake()->randomElement($states);
                    }
                }

                Sell::factory()
                    ->withDetails()
                    ->create([
                    'created_at' => $createdAt,
                    'updated_at' => $pickedUpAt ?? $createdAt->copy()->addMinutes(rand(1, 30)),
                    'is_picked_up' => $isPickedUp,
                    'picked_up_at' => $pickedUpAt,
                    'max_pickup_datetime' => $maxPickupDatetime,
                    'state' => $state,
                ]);

                $totalSells++;
            }
        }

    }
}
