<?php

namespace Database\Seeders;

use App\Models\Offer;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $mode = $this->resolveSeedMode();

        // 1. Roles, permisos y usuarios base indispensables
        $this->call(PermissionSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(EstablishmentTypeSeeder::class);

        // 2. Catálogo normal estándar (20+ locales y ofertas generales)
        if (in_array($mode, ['normal', 'all'], true)) {
            $this->call(FoodEstablishmentSeeder::class);
            $this->call(PackTemplateSeeder::class);
            $this->call(PackOfferSeeder::class);
            $this->call(SellSeeder::class);
        }

        // 3. Prueba específica de ofertas en múltiples horarios de retiro (2 locales demo)
        if (in_array($mode, ['multi-window', 'all'], true)) {
            $this->call(MultiWindowOfferDemoSeeder::class);
        }

        // 4. Indexación en motor de búsqueda Typesense
        try {
            Offer::makeAllSearchable();
        } catch (\Throwable $e) {
            $this->command?->warn('No se pudo sincronizar con Typesense: '.$e->getMessage());
        }

        $this->printSummary($mode);
    }

    /**
     * Resuelve el perfil de siembra:
     * 1. Variable de entorno SEED_MODE / SEED_PROFILE (ej. multi-window, normal, all).
     * 2. Pregunta interactiva si la consola es interactiva y no se están corriendo tests.
     * 3. Fallback por defecto: 'normal'.
     */
    private function resolveSeedMode(): string
    {
        $envMode = env('SEED_MODE') ?? env('SEED_PROFILE') ?? $_SERVER['SEED_MODE'] ?? $_ENV['SEED_MODE'] ?? null;

        if ($envMode !== null && $envMode !== '') {
            $normalized = strtolower(trim((string) $envMode));
            if (in_array($normalized, ['multi-window', 'demo', 'test', 'multi'], true)) {
                return 'multi-window';
            }
            if (in_array($normalized, ['all', 'both', 'completo'], true)) {
                return 'all';
            }

            return 'normal';
        }

        // Si se están ejecutando pruebas unitarias o de integración, siempre 'normal' sin bloquear
        if (app()->runningUnitTests()) {
            return 'normal';
        }

        // Si se ejecuta en terminal interactivo y existe consola
        if ($this->command) {
            $isInteractive = false;
            try {
                $isInteractive = $this->command->getOutput()->isInteractive();
            } catch (\Throwable) {
                $isInteractive = false;
            }

            if ($isInteractive) {
                $choice = $this->command->choice(
                    'Selecciona el perfil de datos que deseas sembrar:',
                    [
                        'normal' => 'Normal: Catálogo estándar completo (20+ comercios y ofertas generales)',
                        'multi-window' => 'Prueba específica: Multi-horarios (2 comercios con ofertas en distintos turnos)',
                        'all' => 'Completo: Normal + Comercios demo con multi-horarios',
                    ],
                    'normal'
                );

                if (str_contains($choice, 'multi-window') || $choice === 'multi-window') {
                    return 'multi-window';
                }

                if (str_contains($choice, 'Completo') || $choice === 'all') {
                    return 'all';
                }

                return 'normal';
            }
        }

        return 'normal';
    }

    /**
     * Muestra en consola el resumen del perfil sembrado y credenciales útiles.
     */
    private function printSummary(string $mode): void
    {
        if (! $this->command) {
            return;
        }

        $this->command->newLine();
        $this->command->info("=== DatabaseSeeder completado [Perfil: {$mode}] ===");

        if (in_array($mode, ['multi-window', 'all'], true)) {
            $this->command->line('<comment>Credenciales para probar ofertas multi-horario:</comment>');
            $this->command->line('- Vendedor 1 (3 turnos): <info>multiturno1@gmail.com</info> / <info>12345678</info>');
            $this->command->line('- Vendedor 2 (2 turnos): <info>multiturno2@gmail.com</info> / <info>12345678</info>');
            $this->command->line('- Cliente demo: <info>lucascabjnmro2@gmail.com</info> / <info>12345678</info>');
        }

        $this->command->newLine();
    }
}
