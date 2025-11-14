<?php

use App\Models\User;
use App\Models\Sell;
use App\Models\SellDetail;
use App\Models\Offer;
use App\Models\Product;
use App\Models\FoodEstablishment;
use App\Models\EstablishmentType;
use App\Models\Category;
use App\Enums\UserState;
use Spatie\Permission\Models\Role;
use App\Actions\Sell\GeneratePickupCodeAction;
use App\Actions\Sell\CalculateMaxPickupDatetimeAction;
use App\DTOs\PreparePurchaseDTO;
use App\DTOs\PrepareOfferDTO;

beforeEach(function () {
    // Crear datos base que se reutilizarán en todos los tests
    $this->establishmentType = EstablishmentType::factory()->create();
    Role::create(['name' => 'customer']);
    Role::create(['name' => 'seller']);
});

test('customer can retrieve their purchases successfully and includes pickup deadline and state', function () {
    // Crear un customer autenticado usando factory
    $customer = User::factory()->withRole('customer')->create([
        'state' => UserState::ACTIVE->value
    ]);

    // Crear un seller y su estableciento usando factories
    $seller = User::factory()->withRole('seller')->create([
        'state' => UserState::ACTIVE->value
    ]);

    $establishment = FoodEstablishment::factory()->create([
        'user_id' => $seller->id,
        'establishment_type_id' => $this->establishmentType->id
    ]);

    // Crear productos usando factory
    $product1 = Product::factory()->create([
        'food_establishment_id' => $establishment->id,
    ]);

    $product2 = Product::factory()->create([
        'food_establishment_id' => $establishment->id,
    ]);

    // Crear ofertas usando factory con estado activo
    $offer1 = Offer::factory()->active()->create([
        'food_establishment_id' => $establishment->id,
    ]);

    $offer2 = Offer::factory()->active()->create([
        'food_establishment_id' => $establishment->id,
    ]);

    $offer1 = PrepareOfferDTO::createFromIdAndQuantity($offer1->id, $offer1->quantity);
    $offer2 = PrepareOfferDTO::createFromIdAndQuantity($offer2->id, $offer2->quantity);

    // Calcular max_pickup_datetime usando el Action
    $calculateMaxPickupAction = new CalculateMaxPickupDatetimeAction();
    $maxPickupDatetime = $calculateMaxPickupAction->execute([$offer1, $offer2]);

    $generatePickupCodeAction = new GeneratePickupCodeAction();
    $pickupCode = $generatePickupCodeAction->execute(
        $customer->id,
        $establishment->id,
        new PreparePurchaseDTO(
            food_establishment_id: $establishment->id,
            offers: []
        )
    );

    // Crear venta usando factory con max_pickup_datetime generado por el action
    $sell = Sell::factory()->create([
        'bought_by' => $customer->id,
        'sold_by' => $establishment->id,
        'is_picked_up' => false,
        'pickup_code' => $pickupCode,
        'max_pickup_datetime' => $maxPickupDatetime, // fecha generada por el action
    ]);

    // Crear detalles de venta usando factory
    $sellDetail1 = SellDetail::factory()->create([
        'offer_id' => $offer1->id,
        'sell_id' => $sell->id,
        'offer_quantity' => 2,
        'product_quantity' => 1,
        'product_price' => 1500,
        'product_name' => 'Pizza Margherita',
        'product_description' => 'Pizza con tomate y mozzarella'
    ]);

    $sellDetail2 = SellDetail::factory()->create([
        'offer_id' => $offer2->id,
        'sell_id' => $sell->id,
        'offer_quantity' => 1,
        'product_quantity' => 2,
        'product_price' => 800,
        'product_name' => 'Hamburguesa Clásica',
        'product_description' => 'Hamburguesa con carne y vegetales'
    ]);

    // Hacer la petición como customer autenticado
    $response = $this->actingAs($customer)->getJson('/api/customer/purchases');


    // Verificar respuesta exitosa
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
                        'product_quantity',
                        'product_price',
                        'product_name',
                        'product_description',
                    ]
                ]
            ]
        ]
    ]);

    // Verificar que los datos son correctos y que max_pickup_datetime coincide con el calculado por el action
    $responseData = $response->json('data');

    expect($responseData)->toHaveCount(1)
        ->and($responseData[0]['id'])->toBe($sell->id)
        ->and($responseData[0]['pickup_code'])->toBe($pickupCode)
        ->and($responseData[0]['sell_details'])->toHaveCount(2)
        ->and($responseData[0]['max_pickup_datetime'])->toBe($maxPickupDatetime->toISOString()) // Verificar fecha exacta del action en formato ISO
        ->and($responseData[0]['state'])->toBeString();

    // Verificar detalles específicos
    $details = $responseData[0]['sell_details'];
    expect($details[0]['product_name'])->toBe('Pizza Margherita')
        ->and($details[0]['offer_quantity'])->toBe(2)
        ->and($details[0]['product_quantity'])->toBe(1)
        ->and($details[0]['product_price'])->toBe(1500)
        ->and($details[1]['product_name'])->toBe('Hamburguesa Clásica')
        ->and($details[1]['offer_quantity'])->toBe(1)
        ->and($details[1]['product_quantity'])->toBe(2)
        ->and($details[1]['product_price'])->toBe(800);

});
