<?php

use App\Actions\Sell\CalculateMaxPickupDatetimeAction;
use App\Actions\Sell\GeneratePickupCodeAction;
use App\DTOs\PrepareOfferDTO;
use App\DTOs\PreparePurchaseDTO;
use App\Enums\UserState;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\Sell;
use App\Models\SellDetail;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->establishmentType = EstablishmentType::factory()->create();
    Role::create(['name' => 'customer']);
    Role::create(['name' => 'seller']);
});

test('customer can retrieve their purchases successfully and includes pickup deadline and state', function () {
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
    ]);

    $offer2 = Offer::factory()->active()->create([
        'food_establishment_id' => $establishment->id,
        'price' => 800,
    ]);

    $dto1 = PrepareOfferDTO::createFromIdAndQuantity($offer1->id, $offer1->quantity);
    $dto2 = PrepareOfferDTO::createFromIdAndQuantity($offer2->id, $offer2->quantity);

    $calculateMaxPickupAction = new CalculateMaxPickupDatetimeAction;
    $maxPickupDatetime = $calculateMaxPickupAction->execute([$dto1, $dto2]);

    $generatePickupCodeAction = new GeneratePickupCodeAction;
    $pickupCode = $generatePickupCodeAction->execute(
        $customer->id,
        $establishment->id,
        new PreparePurchaseDTO(
            food_establishment_id: $establishment->id,
            offers: []
        )
    );

    $sell = Sell::factory()->create([
        'bought_by' => $customer->id,
        'sold_by' => $establishment->id,
        'is_picked_up' => false,
        'pickup_code' => $pickupCode,
        'max_pickup_datetime' => $maxPickupDatetime,
    ]);

    SellDetail::factory()->create([
        'offer_id' => $offer1->id,
        'sell_id' => $sell->id,
        'offer_quantity' => 2,
        'pack_price' => 1500,
        'pack_name' => 'Pack Margherita',
        'pack_description' => 'Pack sorpresa con opciones de pizza',
    ]);

    SellDetail::factory()->create([
        'offer_id' => $offer2->id,
        'sell_id' => $sell->id,
        'offer_quantity' => 1,
        'pack_price' => 800,
        'pack_name' => 'Pack Hamburguesa',
        'pack_description' => 'Pack sorpresa con opciones de hamburguesa',
    ]);

    $response = $this->actingAs($customer)->getJson('/api/customer/purchases');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            '*' => [
                'id',
                'created_at',
                'sold_by',
                'pickup_code',
                'is_picked_up',
                'picked_up_at',
                'max_pickup_datetime',
                'state',
                'sell_details' => [
                    '*' => [
                        'id',
                        'offer_id',
                        'offer_quantity',
                        'pack_price',
                        'pack_name',
                        'pack_description',
                    ],
                ],
            ],
        ],
    ]);

    $responseData = $response->json('data');

    expect($responseData)->toHaveCount(1)
        ->and($responseData[0]['id'])->toBe($sell->id)
        ->and($responseData[0]['pickup_code'])->toBe($pickupCode)
        ->and($responseData[0]['sell_details'])->toHaveCount(2)
        ->and($responseData[0]['max_pickup_datetime'])->toBe($maxPickupDatetime->toISOString())
        ->and($responseData[0]['state'])->toBeString();

    $details = $responseData[0]['sell_details'];
    expect($details[0]['pack_name'])->toBe('Pack Margherita')
        ->and($details[0]['offer_quantity'])->toBe(2)
        ->and($details[0]['pack_price'])->toBe(1500)
        ->and($details[1]['pack_name'])->toBe('Pack Hamburguesa')
        ->and($details[1]['offer_quantity'])->toBe(1)
        ->and($details[1]['pack_price'])->toBe(800);
});
