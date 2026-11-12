<?php

use App\Enums\OfferState;
use App\Enums\UserRole;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\User;
use Database\Seeders\MultiWindowOfferDemoSeeder;

test('MultiWindowOfferDemoSeeder creates at most two establishments with offers in multiple distinct pickup windows', function () {
    $this->seed(MultiWindowOfferDemoSeeder::class);

    $seller1 = User::where('email', 'multiturno1@gmail.com')->first();
    $seller2 = User::where('email', 'multiturno2@gmail.com')->first();

    expect($seller1)->not->toBeNull();
    expect($seller2)->not->toBeNull();
    expect($seller1->hasRole(UserRole::SELLER->value))->toBeTrue();
    expect($seller2->hasRole(UserRole::SELLER->value))->toBeTrue();

    $establishments = FoodEstablishment::whereIn('user_id', [$seller1->id, $seller2->id])->get();
    expect($establishments)->toHaveCount(2);

    // Verificar Establecimiento 1: 4 ofertas activas distribuidas en 3 ventanas horarias distintas
    $est1 = $establishments->firstWhere('user_id', $seller1->id);
    expect($est1->verification_status)->toBe('approved');

    $offersEst1 = Offer::where('food_establishment_id', $est1->id)
        ->where('state', OfferState::ACTIVE->value)
        ->get();

    expect($offersEst1)->toHaveCount(4);

    $windowsEst1 = $offersEst1->map(fn (Offer $o) => $o->pickup_start_datetime->format('Y-m-d H:i').'|'.$o->expiration_datetime->format('Y-m-d H:i'))
        ->unique();

    expect($windowsEst1)->toHaveCount(3);

    // Verificar Establecimiento 2: 3 ofertas activas distribuidas en 2 ventanas horarias distintas
    $est2 = $establishments->firstWhere('user_id', $seller2->id);
    expect($est2->verification_status)->toBe('approved');

    $offersEst2 = Offer::where('food_establishment_id', $est2->id)
        ->where('state', OfferState::ACTIVE->value)
        ->get();

    expect($offersEst2)->toHaveCount(3);

    $windowsEst2 = $offersEst2->map(fn (Offer $o) => $o->pickup_start_datetime->format('Y-m-d H:i').'|'.$o->expiration_datetime->format('Y-m-d H:i'))
        ->unique();

    expect($windowsEst2)->toHaveCount(2);
});

test('MultiWindowOfferDemoSeeder is idempotent and preserves offer IDs when executed multiple times', function () {
    $this->seed(MultiWindowOfferDemoSeeder::class);

    $firstRunOfferIds = Offer::whereHas('foodEstablishment.user', function ($q) {
        $q->whereIn('email', ['multiturno1@gmail.com', 'multiturno2@gmail.com']);
    })->orderBy('id')->pluck('id')->all();

    expect($firstRunOfferIds)->toHaveCount(7);

    // Simular que una oferta fue agotada antes de re-ejecutar el seeder
    Offer::where('id', $firstRunOfferIds[0])->update([
        'quantity' => 0,
        'state' => OfferState::PURCHASED->value,
    ]);

    $this->seed(MultiWindowOfferDemoSeeder::class);

    $sellers = User::whereIn('email', ['multiturno1@gmail.com', 'multiturno2@gmail.com'])->get();
    expect($sellers)->toHaveCount(2);

    $establishments = FoodEstablishment::whereIn('user_id', $sellers->pluck('id'))->get();
    expect($establishments)->toHaveCount(2);

    $secondRunOffers = Offer::whereIn('food_establishment_id', $establishments->pluck('id'))
        ->orderBy('id')
        ->get();

    expect($secondRunOffers)->toHaveCount(7);
    expect($secondRunOffers->pluck('id')->all())->toBe($firstRunOfferIds);
    expect($secondRunOffers->first()->state)->toBe(OfferState::ACTIVE->value);
    expect($secondRunOffers->first()->quantity)->toBeGreaterThan(0);
});

test('MultiWindowOfferDemoSeeder seeds claimable picked up purchases and sample claims idempotently', function () {
    $this->seed(MultiWindowOfferDemoSeeder::class);
    $this->seed(MultiWindowOfferDemoSeeder::class);

    $customer = User::where('email', 'lucascabjnmro2@gmail.com')->first();
    expect($customer)->not->toBeNull();

    $customerSells = \App\Models\Sell::with(['sellDetails', 'claim'])
        ->where('bought_by', $customer->id)
        ->get();

    expect($customerSells)->toHaveCount(5);
    expect($customerSells->where('is_picked_up', false))->toHaveCount(2);
    expect($customerSells->where('is_picked_up', true))->toHaveCount(3);

    $policy = app(\App\Policies\PurchaseClaimPolicy::class);
    $claimableCount = $customerSells
        ->where('is_picked_up', true)
        ->filter(fn ($sell) => $policy->canClaim($sell, $customer))
        ->count();

    expect($claimableCount)->toBe(2);
    expect(\App\Models\PurchaseClaim::count())->toBe(2);
});
