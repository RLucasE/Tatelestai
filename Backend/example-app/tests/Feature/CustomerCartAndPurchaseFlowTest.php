<?php

use App\Enums\CartState;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Events\PurchaseCompleted;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\OfferCart;
use App\Models\Sell;
use App\Models\SellDetail;
use App\Models\User;
use App\Models\UserCart;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(EstablishmentTypeSeeder::class);

    $this->customer = User::factory()->withRole(UserRole::CUSTOMER->value)->create([
        'state' => UserState::ACTIVE->value,
    ]);

    $this->sellerA = User::factory()->withRole(UserRole::SELLER->value)->create([
        'state' => UserState::ACTIVE->value,
    ]);

    $this->sellerB = User::factory()->withRole(UserRole::SELLER->value)->create([
        'state' => UserState::ACTIVE->value,
    ]);

    $establishmentType = EstablishmentType::first();

    $this->establishmentA = FoodEstablishment::factory()->create([
        'user_id' => $this->sellerA->id,
        'establishment_type_id' => $establishmentType->id,
        'name' => 'Panadería San José',
        'address' => 'Av. Corrientes 1234',
    ]);

    $this->establishmentB = FoodEstablishment::factory()->create([
        'user_id' => $this->sellerB->id,
        'establishment_type_id' => $establishmentType->id,
        'name' => 'Pizzería Napolitana',
        'address' => 'Av. Santa Fe 4321',
    ]);

    $this->offerA1 = Offer::factory()->active()->create([
        'food_establishment_id' => $this->establishmentA->id,
        'title' => 'Bolsa Sorpresa Medialunas',
        'description' => 'Medialunas y facturas surtidas del día',
        'quantity' => 5,
        'price' => 1500,
        'minimum_value' => 4500,
        'expiration_datetime' => now()->addDays(2),
    ]);

    $this->offerA2 = Offer::factory()->active()->create([
        'food_establishment_id' => $this->establishmentA->id,
        'title' => 'Bolsa Sorpresa Pan Artesanal',
        'description' => 'Panes de masa madre y focaccia',
        'quantity' => 3,
        'price' => 2000,
        'minimum_value' => 6000,
        'expiration_datetime' => now()->addDays(2),
    ]);

    $this->offerB1 = Offer::factory()->active()->create([
        'food_establishment_id' => $this->establishmentB->id,
        'title' => 'Bolsa Sorpresa Porciones Pizza',
        'description' => 'Porciones variadas al corte',
        'quantity' => 4,
        'price' => 2500,
        'minimum_value' => 7000,
        'expiration_datetime' => now()->addDays(2),
    ]);
});

test('customer can store offers from multiple different establishments in separate active carts', function () {
    $this->actingAs($this->customer);

    // 1. Agregar oferta del Comercio A
    $responseA = $this->postJson('/api/add-to-cart', [
        'offer_id' => $this->offerA1->id,
        'quantity' => 2,
    ]);
    $responseA->assertStatus(200);

    // 2. Agregar oferta del Comercio B
    $responseB = $this->postJson('/api/add-to-cart', [
        'offer_id' => $this->offerB1->id,
        'quantity' => 1,
    ]);
    $responseB->assertStatus(200);

    // Verificar en BD que existen 2 UserCarts activos, uno por comercio
    $activeCarts = UserCart::where('user_id', $this->customer->id)
        ->where('state', CartState::ACTIVE->value)
        ->get();

    expect($activeCarts)->toHaveCount(2);

    $cartA = $activeCarts->firstWhere('food_establishment_id', $this->establishmentA->id);
    $cartB = $activeCarts->firstWhere('food_establishment_id', $this->establishmentB->id);

    expect($cartA)->not->toBeNull();
    expect($cartB)->not->toBeNull();

    // Consultar el endpoint del carrito y verificar los dos grupos
    $cartResponse = $this->getJson('/api/customer-cart');
    $cartResponse->assertStatus(200);

    $data = $cartResponse->json();
    expect($data)->toBeArray()->toHaveCount(2);

    $establishmentIds = collect($data)->map(fn ($group) => $group[0]['establishment_id'])->toArray();
    expect($establishmentIds)->toContain($this->establishmentA->id);
    expect($establishmentIds)->toContain($this->establishmentB->id);
});

test('adding more offers from the same establishment reuses the existing active cart', function () {
    $this->actingAs($this->customer);

    // 1. Agregar primera oferta de Panadería San José
    $this->postJson('/api/add-to-cart', [
        'offer_id' => $this->offerA1->id,
        'quantity' => 1,
    ])->assertStatus(200);

    // 2. Agregar segunda oferta de Panadería San José
    $this->postJson('/api/add-to-cart', [
        'offer_id' => $this->offerA2->id,
        'quantity' => 2,
    ])->assertStatus(200);

    // Verificar que solo existe 1 UserCart activo para Panadería San José
    $activeCarts = UserCart::where('user_id', $this->customer->id)
        ->where('food_establishment_id', $this->establishmentA->id)
        ->where('state', CartState::ACTIVE->value)
        ->get();

    expect($activeCarts)->toHaveCount(1);

    // Verificar que ese carrito contiene las dos ofertas
    $cartItems = OfferCart::where('user_cart_id', $activeCarts->first()->id)->get();
    expect($cartItems)->toHaveCount(2);
    expect($cartItems->pluck('offer_id')->toArray())->toContain($this->offerA1->id, $this->offerA2->id);
});

test('customer can prepare and buy a single offer from an establishment', function () {
    Event::fake([PurchaseCompleted::class]);
    $this->actingAs($this->customer);

    // Agregar oferta al carrito
    $this->postJson('/api/add-to-cart', [
        'offer_id' => $this->offerA1->id,
        'quantity' => 2,
    ])->assertStatus(200);

    // Preparar compra
    $prepareResponse = $this->postJson('/api/prepare-purchase', [
        'food_establishment_id' => $this->establishmentA->id,
        'offers' => [
            ['id' => $this->offerA1->id, 'quantity' => 2],
        ],
    ]);

    $prepareResponse->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => [
                'purchase_token',
                'offers',
                'food_establishment_id',
                'establishment' => ['id', 'name', 'address'],
                'expires_at',
            ],
        ]);

    expect($prepareResponse->json('data.establishment.name'))->toBe('Panadería San José');
    $purchaseToken = $prepareResponse->json('data.purchase_token');

    // Confirmar compra
    $buyResponse = $this->postJson('/api/buy-offers', [
        'purchase_token' => $purchaseToken,
    ]);

    $buyResponse->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => [
                'sell_id',
                'pickup_code',
                'max_pickup_datetime',
                'food_establishment_id',
                'establishment',
                'offers',
            ],
        ]);

    // Verificar transición del carrito a PURCHASED
    $userCart = UserCart::where('user_id', $this->customer->id)
        ->where('food_establishment_id', $this->establishmentA->id)
        ->first();

    expect($userCart->state)->toBe(CartState::PURCHASED->value);

    // Verificar decremento de stock: 5 - 2 = 3
    $this->offerA1->refresh();
    expect($this->offerA1->quantity)->toBe(3);

    // Verificar que se creó la venta y el detalle
    $this->assertDatabaseHas('sells', [
        'bought_by' => $this->customer->id,
        'sold_by' => $this->establishmentA->id,
    ]);

    $this->assertDatabaseHas('sell_details', [
        'offer_id' => $this->offerA1->id,
        'offer_quantity' => 2,
        'pack_name' => $this->offerA1->title,
    ]);

    Event::assertDispatched(PurchaseCompleted::class);
});

test('customer can prepare and buy multiple offers from the same establishment in one atomic order', function () {
    Event::fake([PurchaseCompleted::class]);
    $this->actingAs($this->customer);

    // Agregar dos ofertas del Comercio A
    $this->postJson('/api/add-to-cart', ['offer_id' => $this->offerA1->id, 'quantity' => 1])->assertStatus(200);
    $this->postJson('/api/add-to-cart', ['offer_id' => $this->offerA2->id, 'quantity' => 2])->assertStatus(200);

    // Preparar compra atómica
    $prepareResponse = $this->postJson('/api/prepare-purchase', [
        'food_establishment_id' => $this->establishmentA->id,
        'offers' => [
            ['id' => $this->offerA1->id, 'quantity' => 1],
            ['id' => $this->offerA2->id, 'quantity' => 2],
        ],
    ]);
    $prepareResponse->assertStatus(200);
    $purchaseToken = $prepareResponse->json('data.purchase_token');

    // Confirmar compra
    $buyResponse = $this->postJson('/api/buy-offers', [
        'purchase_token' => $purchaseToken,
    ]);
    $buyResponse->assertStatus(200);

    $sellId = $buyResponse->json('data.sell_id');
    expect($sellId)->not->toBeNull();

    // Debe existir 1 venta con 2 detalles
    $sellDetails = SellDetail::where('sell_id', $sellId)->get();
    expect($sellDetails)->toHaveCount(2);

    // Ambos stocks decrementados
    expect($this->offerA1->fresh()->quantity)->toBe(4); // 5 - 1
    expect($this->offerA2->fresh()->quantity)->toBe(1); // 3 - 2

    // El carrito del Comercio A pasó a PURCHASED
    $cartA = UserCart::where('user_id', $this->customer->id)
        ->where('food_establishment_id', $this->establishmentA->id)
        ->first();
    expect($cartA->state)->toBe(CartState::PURCHASED->value);
});

test('buying a cart from one establishment leaves carts from other establishments active and intact', function () {
    $this->actingAs($this->customer);

    // 1. Agregar ofertas de Panadería San José y Pizzería Napolitana
    $this->postJson('/api/add-to-cart', ['offer_id' => $this->offerA1->id, 'quantity' => 2])->assertStatus(200);
    $this->postJson('/api/add-to-cart', ['offer_id' => $this->offerB1->id, 'quantity' => 1])->assertStatus(200);

    // 2. Preparar y comprar ÚNICAMENTE el carrito de Panadería San José
    $prep = $this->postJson('/api/prepare-purchase', [
        'food_establishment_id' => $this->establishmentA->id,
        'offers' => [
            ['id' => $this->offerA1->id, 'quantity' => 2],
        ],
    ]);
    $token = $prep->json('data.purchase_token');

    $buy = $this->postJson('/api/buy-offers', ['purchase_token' => $token]);
    $buy->assertStatus(200);

    // 3. Verificar estados de ambos carritos en BD
    $cartA = UserCart::where('user_id', $this->customer->id)
        ->where('food_establishment_id', $this->establishmentA->id)
        ->first();
    expect($cartA->state)->toBe(CartState::PURCHASED->value);

    $cartB = UserCart::where('user_id', $this->customer->id)
        ->where('food_establishment_id', $this->establishmentB->id)
        ->first();
    expect($cartB->state)->toBe(CartState::ACTIVE->value);

    // 4. Consultar el carrito del usuario: solo debe retornar Pizzería Napolitana
    $cartResponse = $this->getJson('/api/customer-cart');
    $cartResponse->assertStatus(200);

    $data = $cartResponse->json();
    expect($data)->toHaveCount(1);
    expect($data[0][0]['establishment_id'])->toBe($this->establishmentB->id);
    expect($data[0][0]['offer_id'])->toBe($this->offerB1->id);
});

test('cannot prepare purchase mixing offers from different establishments', function () {
    $this->actingAs($this->customer);

    // Intentar preparar una compra mezclando ofertas de Comercios A y B
    $response = $this->postJson('/api/prepare-purchase', [
        'food_establishment_id' => $this->establishmentA->id,
        'offers' => [
            ['id' => $this->offerA1->id, 'quantity' => 1],
            ['id' => $this->offerB1->id, 'quantity' => 1], // Pertenece al Comercio B!
        ],
    ]);

    // Debe ser rechazado
    $response->assertStatus(500)
        ->assertJson([
            'error' => 'Failed to prepare purchase',
            'message' => 'Esos packs no pertenecen a ese establecimiento',
        ]);

    // Verificar que no se creó ninguna venta
    expect(Sell::count())->toBe(0);
});

test('cannot buy offer when requested quantity exceeds available stock', function () {
    $this->actingAs($this->customer);

    $prep = $this->postJson('/api/prepare-purchase', [
        'food_establishment_id' => $this->establishmentA->id,
        'offers' => [
            ['id' => $this->offerA2->id, 'quantity' => 3], // Pide los 3 disponibles
        ],
    ]);
    $prep->assertStatus(200);
    $token = $prep->json('data.purchase_token');

    // Simular que otra compra paralela redujo el stock a 1
    $this->offerA2->update(['quantity' => 1]);

    // Intentar comprar los 3
    $buy = $this->postJson('/api/buy-offers', ['purchase_token' => $token]);

    $buy->assertStatus(400);
    expect($buy->json('error'))->toContain('No hay suficiente stock');

    // No se debe haber creado ninguna venta
    expect(Sell::count())->toBe(0);
});

test('customer cannot remove an offer from another customer cart when having no active cart', function () {
    $otherCustomer = User::factory()->withRole(UserRole::CUSTOMER->value)->create([
        'state' => UserState::ACTIVE->value,
    ]);

    // 1. Otro cliente agrega una oferta a su carrito
    $this->actingAs($otherCustomer);
    $this->postJson('/api/add-to-cart', [
        'offer_id' => $this->offerA1->id,
        'quantity' => 2,
    ])->assertStatus(200);

    $otherCartItem = OfferCart::where('offer_id', $this->offerA1->id)->first();
    expect($otherCartItem)->not->toBeNull();

    // 2. El cliente actual (sin carrito activo) intenta borrar esa oferta
    $this->actingAs($this->customer);
    $response = $this->deleteJson("/api/customer-cart/{$this->offerA1->id}");

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'No active cart found.',
        ]);

    // 3. Verificar que la oferta del otro cliente sigue existiendo intacta
    $this->assertDatabaseHas('offer_carts', [
        'id' => $otherCartItem->id,
        'offer_id' => $this->offerA1->id,
        'quantity' => 2,
    ]);
});

test('customer cannot remove an offer from another customer cart when it is not in their own cart', function () {
    $otherCustomer = User::factory()->withRole(UserRole::CUSTOMER->value)->create([
        'state' => UserState::ACTIVE->value,
    ]);

    // 1. El otro cliente tiene la oferta A1
    $this->actingAs($otherCustomer);
    $this->postJson('/api/add-to-cart', [
        'offer_id' => $this->offerA1->id,
        'quantity' => 1,
    ])->assertStatus(200);

    $otherCartItem = OfferCart::where('offer_id', $this->offerA1->id)->first();

    // 2. El cliente actual tiene una oferta diferente (B1)
    $this->actingAs($this->customer);
    $this->postJson('/api/add-to-cart', [
        'offer_id' => $this->offerB1->id,
        'quantity' => 1,
    ])->assertStatus(200);

    // 3. El cliente actual intenta borrar la oferta A1 (que pertenece al otro cliente)
    $response = $this->deleteJson("/api/customer-cart/{$this->offerA1->id}");

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Offer not found in cart.',
        ]);

    // 4. La oferta del otro cliente no fue borrada
    $this->assertDatabaseHas('offer_carts', [
        'id' => $otherCartItem->id,
        'offer_id' => $this->offerA1->id,
        'quantity' => 1,
    ]);

    // La oferta propia sigue existiendo
    $this->assertDatabaseHas('offer_carts', [
        'offer_id' => $this->offerB1->id,
    ]);
});

test('deleting an offer only removes it from the authenticated customer cart and preserves it in other customer carts', function () {
    $otherCustomer = User::factory()->withRole(UserRole::CUSTOMER->value)->create([
        'state' => UserState::ACTIVE->value,
    ]);

    // 1. Ambos clientes agregan la misma oferta A1 a sus respectivos carritos
    $this->actingAs($otherCustomer);
    $this->postJson('/api/add-to-cart', [
        'offer_id' => $this->offerA1->id,
        'quantity' => 2,
    ])->assertStatus(200);

    $this->actingAs($this->customer);
    $this->postJson('/api/add-to-cart', [
        'offer_id' => $this->offerA1->id,
        'quantity' => 1,
    ])->assertStatus(200);

    $customerCart = UserCart::where('user_id', $this->customer->id)->first();
    $otherCustomerCart = UserCart::where('user_id', $otherCustomer->id)->first();

    $customerItem = OfferCart::where('user_cart_id', $customerCart->id)->first();
    $otherItem = OfferCart::where('user_cart_id', $otherCustomerCart->id)->first();

    // 2. El cliente actual borra la oferta de su carrito
    $response = $this->deleteJson("/api/customer-cart/{$this->offerA1->id}");
    $response->assertStatus(200);

    // 3. El item del cliente autenticado fue eliminado (soft delete)
    $this->assertSoftDeleted('offer_carts', [
        'id' => $customerItem->id,
    ]);

    // 4. El item del otro cliente permanece intacto en su carrito
    $this->assertNotSoftDeleted('offer_carts', [
        'id' => $otherItem->id,
    ]);
    $this->assertDatabaseHas('offer_carts', [
        'id' => $otherItem->id,
        'quantity' => 2,
    ]);

    // 5. El otro cliente aún puede ver su carrito con la oferta
    $this->actingAs($otherCustomer);
    $cartResponse = $this->getJson('/api/customer-cart');
    $cartResponse->assertStatus(200);
    $items = collect($cartResponse->json())->flatten(1);
    expect($items->firstWhere('offer_id', $this->offerA1->id))->not->toBeNull();
});

test('customer cannot clear another customer cart by establishment', function () {
    $otherCustomer = User::factory()->withRole(UserRole::CUSTOMER->value)->create([
        'state' => UserState::ACTIVE->value,
    ]);

    // 1. El otro cliente tiene items en el establecimiento A
    $this->actingAs($otherCustomer);
    $this->postJson('/api/add-to-cart', [
        'offer_id' => $this->offerA1->id,
        'quantity' => 2,
    ])->assertStatus(200);

    $otherCart = UserCart::where('user_id', $otherCustomer->id)
        ->where('food_establishment_id', $this->establishmentA->id)
        ->first();
    expect($otherCart)->not->toBeNull();

    // 2. El cliente actual intenta vaciar el carrito del establecimiento A (sin tener items)
    $this->actingAs($this->customer);
    $response = $this->deleteJson("/api/customer-cart/establishment/{$this->establishmentA->id}");
    $response->assertStatus(200)
        ->assertJson([
            'deleted' => 0,
        ]);

    // 3. El carrito y la oferta del otro cliente siguen intactos
    $this->assertDatabaseHas('user_carts', [
        'id' => $otherCart->id,
        'user_id' => $otherCustomer->id,
        'state' => CartState::ACTIVE->value,
    ]);

    $this->assertDatabaseHas('offer_carts', [
        'user_cart_id' => $otherCart->id,
        'offer_id' => $this->offerA1->id,
        'quantity' => 2,
    ]);
});
