<?php

use App\Models\FoodEstablishment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

afterEach(function () {
    putenv('SEED_MODE');
    unset($_ENV['SEED_MODE'], $_SERVER['SEED_MODE']);
});

test('DatabaseSeeder seeds normal profile by default in automated environments', function () {
    $this->seed(DatabaseSeeder::class);

    // Debe existir el catálogo normal (ej. Café del Convento)
    expect(FoodEstablishment::where('name', 'Café del Convento')->exists())->toBeTrue();

    // No deben existir los comercios demo específicos de multiturno
    expect(User::where('email', 'multiturno1@gmail.com')->exists())->toBeFalse();
    expect(User::where('email', 'multiturno2@gmail.com')->exists())->toBeFalse();
});

test('DatabaseSeeder seeds multi-window profile when SEED_MODE is configured to multi-window', function () {
    putenv('SEED_MODE=multi-window');
    $_ENV['SEED_MODE'] = 'multi-window';
    $_SERVER['SEED_MODE'] = 'multi-window';

    $this->seed(DatabaseSeeder::class);

    // Deben existir los comercios de prueba multi-horario
    expect(User::where('email', 'multiturno1@gmail.com')->exists())->toBeTrue();
    expect(User::where('email', 'multiturno2@gmail.com')->exists())->toBeTrue();
    expect(FoodEstablishment::where('name', 'La Boulangerie Multiturno (Demo Horarios)')->exists())->toBeTrue();
    expect(FoodEstablishment::where('name', 'Bistró del Paseo (Demo 2 Turnos)')->exists())->toBeTrue();

    // NO debe haberse cargado el catálogo masivo normal (ej. Café del Convento)
    expect(FoodEstablishment::where('name', 'Café del Convento')->exists())->toBeFalse();
});

test('DatabaseSeeder seeds both normal and multi-window profiles when SEED_MODE is all', function () {
    putenv('SEED_MODE=all');
    $_ENV['SEED_MODE'] = 'all';
    $_SERVER['SEED_MODE'] = 'all';

    $this->seed(DatabaseSeeder::class);

    // Deben coexistir ambos catálogos
    expect(FoodEstablishment::where('name', 'Café del Convento')->exists())->toBeTrue();
    expect(User::where('email', 'multiturno1@gmail.com')->exists())->toBeTrue();
    expect(FoodEstablishment::where('name', 'La Boulangerie Multiturno (Demo Horarios)')->exists())->toBeTrue();
});
