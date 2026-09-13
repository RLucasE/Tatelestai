<?php

use App\Enums\CartState;
use App\Enums\UserState;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\OfferCart;
use App\Models\User;
use App\Models\UserCart;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->establishmentType = EstablishmentType::factory()->create();
    \Spatie\Permission\Models\Role::create(['name' => 'customer']);
    \Spatie\Permission\Models\Role::create(['name' => 'seller']);
});

test('customer can retrieve their active cart grouped by establishment', function () {
    $customer = User::factory()->withRole('customer')->create([
        'state' => UserState::ACTIVE->value,
    ]);

    $seller = User::factory()->withRole('seller')->create([
        'state' => UserState::ACTIVE->value,
    ]);

    $establishment = FoodEstablishment::factory()->create([
        'user_id' => $seller->id,
        'establishment_type_id' => $this->establishmentType->id,
    ]);

    $offer1 = Offer::factory()->active()->create([
        'food_establishment_id' => $establishment->id,
        'price' => 1500,
        'minimum_value' => 4500,
        'allergens' => ['gluten'],
    ]);

    $offer2 = Offer::factory()->active()->create([
        'food_establishment_id' => $establishment->id,
        'price' => 800,
        'minimum_value' => 2400,
        'allergens' => ['lácteos'],
    ]);

    // Create active cart for the customer
    $cart = UserCart::create([
        'user_id' => $customer->id,
        'state' => CartState::ACTIVE->value,
    ]);

    // Add offers to the cart
    OfferCart::create([
        'offer_id' => $offer1->id,
        'user_cart_id' => $cart->id,
        'quantity' => 2,
    ]);

    OfferCart::create([
        'offer_id' => $offer2->id,
        'user_cart_id' => $cart->id,
        'quantity' => 1,
    ]);

    $response = $this->actingAs($customer)->getJson('/api/customer-cart');

    $response->assertStatus(200)
        ->assertJsonStructure([
            '*' => [
                '*' => [
                    'offer_id',
                    'establishment_id',
                    'establishment_name',
                    'establishment_address',
                    'offer_title',
                    'offer_description',
                    'offer_price',
                    'minimum_value',
                    'allergens',
                    'estimated_weight_kg',
                    'offer_max_quantity',
                    'offer_state',
                    'pickup_start_datetime',
                    'offer_expiration_datetime',
                    'quantity',
                ],
            ],
        ]);

    $data = $response->json();

    expect($data)->toBeArray();
    expect($data)->toHaveCount(1);

    $group = $data[0];
    expect($group)->toBeArray();
    expect($group)->toHaveCount(2);

    // Validate structure of an item
    expect($group[0])->toHaveKeys([
        'offer_id',
        'establishment_id',
        'offer_title',
        'offer_description',
        'offer_price',
        'offer_max_quantity',
        'quantity',
    ]);

    $byOfferId = collect($group)->keyBy('offer_id');

    expect($byOfferId->has($offer1->id))->toBeTrue();
    expect($byOfferId->has($offer2->id))->toBeTrue();

    expect($byOfferId[$offer1->id]['quantity'])->toBe(2);
    expect($byOfferId[$offer1->id]['offer_price'])->toBe(1500);
    expect($byOfferId[$offer2->id]['quantity'])->toBe(1);
    expect($byOfferId[$offer2->id]['offer_price'])->toBe(800);
});

test('customer cart returns 404 when there is no active cart', function () {
    $customer = User::factory()->withRole('customer')->create([
        'state' => UserState::ACTIVE->value,
    ]);

    $response = $this->actingAs($customer)->getJson('/api/customer-cart');

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'No active cart found.',
        ]);
});

test('customer cart shows purchased offers with quantity 0 and state purchased', function () {
    $customer = User::factory()->withRole('customer')->create([
        'state' => UserState::ACTIVE->value,
    ]);

    $seller = User::factory()->withRole('seller')->create([
        'state' => UserState::ACTIVE->value,
    ]);

    $establishment = FoodEstablishment::factory()->create([
        'user_id' => $seller->id,
        'establishment_type_id' => $this->establishmentType->id,
    ]);

    $purchasedOffer = Offer::factory()->purchased()->create([
        'food_establishment_id' => $establishment->id,
        'quantity' => 0,
    ]);

    $cart = UserCart::create([
        'user_id' => $customer->id,
        'state' => CartState::ACTIVE->value,
    ]);

    OfferCart::create([
        'offer_id' => $purchasedOffer->id,
        'user_cart_id' => $cart->id,
        'quantity' => 1,
    ]);

    $response = $this->actingAs($customer)->getJson('/api/customer-cart');

    $response->assertStatus(200);

    $data = collect($response->json())->flatten(1);
    $item = $data->firstWhere('offer_id', $purchasedOffer->id);

    expect($item)->not->toBeNull();
    expect($item['offer_max_quantity'])->toBe(0);
    expect($item['offer_state'])->toBe('purchased');
});

test('customer cart shows expired offers with expiration datetime in the past', function () {
    $customer = User::factory()->withRole('customer')->create([
        'state' => UserState::ACTIVE->value,
    ]);

    $seller = User::factory()->withRole('seller')->create([
        'state' => UserState::ACTIVE->value,
    ]);

    $establishment = FoodEstablishment::factory()->create([
        'user_id' => $seller->id,
        'establishment_type_id' => $this->establishmentType->id,
    ]);

    $expiredAt = Carbon::now()->subHour();
    $expiredOffer = Offer::factory()->active()->create([
        'food_establishment_id' => $establishment->id,
        'expiration_datetime' => $expiredAt->format('Y-m-d H:i:s'),
    ]);

    $cart = UserCart::create([
        'user_id' => $customer->id,
        'state' => CartState::ACTIVE->value,
    ]);

    OfferCart::create([
        'offer_id' => $expiredOffer->id,
        'user_cart_id' => $cart->id,
        'quantity' => 1,
    ]);

    $response = $this->actingAs($customer)->getJson('/api/customer-cart');

    $response->assertStatus(200);

    $data = collect($response->json())->flatten(1);
    $item = $data->firstWhere('offer_id', $expiredOffer->id);

    expect($item)->not->toBeNull();
    $returnedExpiration = Carbon::parse($item['offer_expiration_datetime']);
    expect($returnedExpiration->lt(Carbon::now()))->toBeTrue();
});
