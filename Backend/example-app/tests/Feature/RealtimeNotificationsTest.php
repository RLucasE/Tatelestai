<?php

use App\Actions\Cart\NotifyCartStockDisruptionAction;
use App\Actions\Sell\CancelPurchaseAction;
use App\Actions\Sell\makeSellAction;
use App\Actions\Sell\SellerCancelSellAction;
use App\DTOs\PreparePurchaseDTO;
use App\Enums\CartState;
use App\Enums\OfferState;
use App\Enums\PaymentStatus;
use App\Enums\SellState;
use App\Enums\UserState;
use App\Events\PurchaseCompleted;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\OfferCart;
use App\Models\PackTemplate;
use App\Models\Sell;
use App\Models\SellDetail;
use App\Models\User;
use App\Models\UserCart;
use App\Notifications\RealtimeOrderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'customer']);
    Role::firstOrCreate(['name' => 'seller']);
    Role::firstOrCreate(['name' => 'admin']);
});

test('al completarse una compra se envia notificacion en tiempo real al vendedor', function () {
    Notification::fake();

    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $establishment = FoodEstablishment::factory()->create([
        'user_id' => $seller->id,
    ]);

    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $template = PackTemplate::factory()->create([
        'food_establishment_id' => $establishment->id,
    ]);

    $offer = Offer::factory()->create([
        'food_establishment_id' => $establishment->id,
        'pack_template_id' => $template->id,
        'quantity' => 5,
        'price' => 2500,
        'state' => OfferState::ACTIVE->value,
    ]);

    $sell = Sell::create([
        'bought_by' => $customer->id,
        'sold_by' => $establishment->id,
        'pickup_code' => 'ABC-1234',
        'max_pickup_datetime' => now()->addHours(2),
        'payment_status' => PaymentStatus::APPROVED->value,
        'gross_amount' => 5000.00,
        'commission_percentage' => 15.0,
        'commission_amount' => 750.00,
        'net_amount' => 4250.00,
    ]);

    SellDetail::create([
        'sell_id' => $sell->id,
        'offer_id' => $offer->id,
        'offer_quantity' => 2,
        'pack_name' => 'Bolsa Mediodía',
        'pack_description' => 'Excedente de panadería',
        'pack_price' => 2500.00,
    ]);

    PurchaseCompleted::dispatch($sell);

    Notification::assertSentTo(
        $seller,
        RealtimeOrderNotification::class,
        function ($notification) use ($sell) {
            return $notification->eventType === 'new_sale'
                && $notification->sellId === $sell->id;
        }
    );
});

test('makeSellAction notifica offer_sold_out al vendedor si el stock llega a 0', function () {
    Notification::fake();

    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $establishment = FoodEstablishment::factory()->create([
        'user_id' => $seller->id,
    ]);

    $template = PackTemplate::factory()->create([
        'food_establishment_id' => $establishment->id,
    ]);

    $offer = Offer::factory()->create([
        'food_establishment_id' => $establishment->id,
        'pack_template_id' => $template->id,
        'quantity' => 1,
        'price' => 1000,
        'state' => OfferState::ACTIVE->value,
    ]);

    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $prepareDTO = new PreparePurchaseDTO(
        food_establishment_id: $establishment->id,
        offers: [
            \App\DTOs\PrepareOfferDTO::createFromIdAndQuantity($offer->id, 1),
        ]
    );

    $makeSell = app(makeSellAction::class);
    $result = $makeSell->execute($prepareDTO, $customer->id, $establishment->id);

    expect($result['sell_id'])->toBeGreaterThan(0);

    Notification::assertSentTo(
        $seller,
        RealtimeOrderNotification::class,
        function ($notification) use ($offer) {
            return $notification->eventType === 'offer_sold_out'
                && $notification->offerId === $offer->id;
        }
    );
});

test('cancelacion del cliente notifica cancelled_by_customer al vendedor', function () {
    Notification::fake();

    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $establishment = FoodEstablishment::factory()->create([
        'user_id' => $seller->id,
    ]);

    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $template = PackTemplate::factory()->create([
        'food_establishment_id' => $establishment->id,
    ]);

    $offer = Offer::factory()->create([
        'food_establishment_id' => $establishment->id,
        'pack_template_id' => $template->id,
        'quantity' => 0,
        'price' => 1000,
        'state' => OfferState::PURCHASED->value,
    ]);

    $sell = Sell::create([
        'bought_by' => $customer->id,
        'sold_by' => $establishment->id,
        'pickup_code' => 'XYZ-9999',
        'max_pickup_datetime' => now()->addHours(3),
        'state' => SellState::PENDING->value,
        'payment_status' => PaymentStatus::APPROVED->value,
        'gross_amount' => 1000.00,
        'commission_percentage' => 15.0,
        'commission_amount' => 150.00,
        'net_amount' => 850.00,
    ]);

    SellDetail::create([
        'sell_id' => $sell->id,
        'offer_id' => $offer->id,
        'offer_quantity' => 1,
        'pack_name' => $offer->title,
        'pack_description' => $offer->description,
        'pack_price' => $offer->price,
    ]);

    $cancelAction = app(CancelPurchaseAction::class);
    $cancelAction->execute($sell, $customer);

    Notification::assertSentTo(
        $seller,
        RealtimeOrderNotification::class,
        function ($notification) use ($sell) {
            return $notification->eventType === 'cancelled_by_customer'
                && $notification->sellId === $sell->id;
        }
    );
});

test('cancelacion del vendedor despacha cancelled_by_seller al cliente', function () {
    Notification::fake();

    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $establishment = FoodEstablishment::factory()->create([
        'user_id' => $seller->id,
    ]);

    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $sell = Sell::create([
        'bought_by' => $customer->id,
        'sold_by' => $establishment->id,
        'pickup_code' => 'XYZ-8888',
        'max_pickup_datetime' => now()->addHours(3),
        'state' => SellState::PENDING->value,
        'payment_status' => PaymentStatus::APPROVED->value,
        'gross_amount' => 1000.00,
        'commission_percentage' => 15.0,
        'commission_amount' => 150.00,
        'net_amount' => 850.00,
    ]);

    $sellerCancelAction = app(SellerCancelSellAction::class);
    $sellerCancelAction->execute($sell, $seller, 'Sin excedentes para armar pack', false);

    Notification::assertSentTo(
        $customer,
        RealtimeOrderNotification::class,
        function ($notification) use ($sell) {
            return $notification->eventType === 'cancelled_by_seller'
                && $notification->sellId === $sell->id;
        }
    );
});

test('api de notificaciones permite listar y marcar como leidas con aislamiento de usuario', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $user1->notify(new RealtimeOrderNotification(
        eventType: 'new_sale',
        title: 'Venta de prueba 1',
        message: 'Mensaje para user 1'
    ));

    $user2->notify(new RealtimeOrderNotification(
        eventType: 'new_sale',
        title: 'Venta de prueba 2',
        message: 'Mensaje para user 2'
    ));

    $response = $this->actingAs($user1)->getJson('/api/notifications');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('unread_count', 1)
        ->assertJsonPath('data.0.title', 'Venta de prueba 1');

    $notificationId = $response->json('data.0.id');

    // Marcar como leída
    $markResponse = $this->actingAs($user1)->patchJson("/api/notifications/{$notificationId}/read");
    $markResponse->assertStatus(200)
        ->assertJsonPath('unread_count', 0);

    // Comprobar que en user2 sigue sin leer
    $responseUser2 = $this->actingAs($user2)->getJson('/api/notifications');
    $responseUser2->assertStatus(200)
        ->assertJsonPath('unread_count', 1);

    // Marcar todas leídas en user2
    $markAllResponse = $this->actingAs($user2)->postJson('/api/notifications/read-all');
    $markAllResponse->assertStatus(200)
        ->assertJsonPath('unread_count', 0);
});

test('al completar entrega de venta el vendedor despacha notificacion order_picked_up al cliente', function () {
    Notification::fake();

    $seller = User::factory()->create([
        'state' => UserState::ACTIVE->value,
    ]);
    $seller->assignRole('seller');

    $establishment = FoodEstablishment::factory()->create([
        'user_id' => $seller->id,
    ]);

    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $template = PackTemplate::factory()->create([
        'food_establishment_id' => $establishment->id,
    ]);

    $offer = Offer::factory()->create([
        'food_establishment_id' => $establishment->id,
        'pack_template_id' => $template->id,
        'quantity' => 5,
        'price' => 1200,
        'state' => OfferState::ACTIVE->value,
    ]);

    $pickupCode = 'PICK-1234-TEST';
    $sell = Sell::create([
        'bought_by' => $customer->id,
        'sold_by' => $establishment->id,
        'pickup_code' => $pickupCode,
        'max_pickup_datetime' => now()->addHours(2),
        'state' => SellState::PENDING->value,
        'is_picked_up' => false,
        'payment_status' => PaymentStatus::APPROVED->value,
        'gross_amount' => 1200.00,
        'commission_percentage' => 15.0,
        'commission_amount' => 180.00,
        'net_amount' => 1020.00,
    ]);

    SellDetail::create([
        'sell_id' => $sell->id,
        'offer_id' => $offer->id,
        'offer_quantity' => 1,
        'pack_name' => $offer->title,
        'pack_description' => $offer->description,
        'pack_price' => $offer->price,
    ]);

    $response = $this->actingAs($seller)->postJson("/api/complete-sell/{$sell->id}", [
        'pick_up_code' => $pickupCode,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Venta completada exitosamente');

    Notification::assertSentTo(
        $customer,
        RealtimeOrderNotification::class,
        function ($notification) use ($sell) {
            return $notification->eventType === 'order_picked_up'
                && $notification->sellId === $sell->id;
        }
    );
});

test('complete-sell con pickup_code invalido falla y no envia notificacion al cliente', function () {
    Notification::fake();

    $seller = User::factory()->create([
        'state' => UserState::ACTIVE->value,
    ]);
    $seller->assignRole('seller');

    $establishment = FoodEstablishment::factory()->create([
        'user_id' => $seller->id,
    ]);

    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $sell = Sell::create([
        'bought_by' => $customer->id,
        'sold_by' => $establishment->id,
        'pickup_code' => 'CODIGO-VALIDO',
        'max_pickup_datetime' => now()->addHours(2),
        'state' => SellState::PENDING->value,
        'is_picked_up' => false,
    ]);

    $response = $this->actingAs($seller)->postJson("/api/complete-sell/{$sell->id}", [
        'pick_up_code' => 'CODIGO-ERRONEO',
    ]);

    $response->assertStatus(400);

    Notification::assertNothingSent();
});

test('NotifyCartStockDisruptionAction notifica cart_item_stock_reduced si el stock remanente es menor a lo solicitado en el carrito', function () {
    Notification::fake();

    $establishment = FoodEstablishment::factory()->create();
    $template = PackTemplate::factory()->create(['food_establishment_id' => $establishment->id]);
    $offer = Offer::factory()->create([
        'food_establishment_id' => $establishment->id,
        'pack_template_id' => $template->id,
        'quantity' => 1,
        'state' => OfferState::ACTIVE->value,
    ]);

    $affectedCustomer = User::factory()->create();
    $affectedCustomer->assignRole('customer');

    $userCart = UserCart::create([
        'user_id' => $affectedCustomer->id,
        'food_establishment_id' => $establishment->id,
        'state' => CartState::ACTIVE->value,
    ]);

    OfferCart::create([
        'user_cart_id' => $userCart->id,
        'offer_id' => $offer->id,
        'quantity' => 3,
    ]);

    $action = app(NotifyCartStockDisruptionAction::class);
    $action->execute($offer);

    Notification::assertSentTo(
        $affectedCustomer,
        RealtimeOrderNotification::class,
        function ($notification) use ($offer) {
            return $notification->eventType === 'cart_item_stock_reduced'
                && $notification->offerId === $offer->id
                && $notification->actionUrl === '/customer/carrito';
        }
    );
});

test('NotifyCartStockDisruptionAction notifica cart_item_sold_out si la oferta se agota totalmente en el carrito', function () {
    Notification::fake();

    $establishment = FoodEstablishment::factory()->create();
    $template = PackTemplate::factory()->create(['food_establishment_id' => $establishment->id]);
    $offer = Offer::factory()->create([
        'food_establishment_id' => $establishment->id,
        'pack_template_id' => $template->id,
        'quantity' => 0,
        'state' => OfferState::PURCHASED->value,
    ]);

    $affectedCustomer = User::factory()->create();
    $affectedCustomer->assignRole('customer');

    $userCart = UserCart::create([
        'user_id' => $affectedCustomer->id,
        'food_establishment_id' => $establishment->id,
        'state' => CartState::ACTIVE->value,
    ]);

    OfferCart::create([
        'user_cart_id' => $userCart->id,
        'offer_id' => $offer->id,
        'quantity' => 2,
    ]);

    $action = app(NotifyCartStockDisruptionAction::class);
    $action->execute($offer);

    Notification::assertSentTo(
        $affectedCustomer,
        RealtimeOrderNotification::class,
        function ($notification) use ($offer) {
            return $notification->eventType === 'cart_item_sold_out'
                && $notification->offerId === $offer->id
                && $notification->actionUrl === '/customer/carrito';
        }
    );
});

test('NotifyCartStockDisruptionAction NO notifica al cliente si el stock disminuye pero sigue alcanzando para su carrito', function () {
    Notification::fake();

    $establishment = FoodEstablishment::factory()->create();
    $template = PackTemplate::factory()->create(['food_establishment_id' => $establishment->id]);
    $offer = Offer::factory()->create([
        'food_establishment_id' => $establishment->id,
        'pack_template_id' => $template->id,
        'quantity' => 5,
        'state' => OfferState::ACTIVE->value,
    ]);

    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $userCart = UserCart::create([
        'user_id' => $customer->id,
        'food_establishment_id' => $establishment->id,
        'state' => CartState::ACTIVE->value,
    ]);

    OfferCart::create([
        'user_cart_id' => $userCart->id,
        'offer_id' => $offer->id,
        'quantity' => 2,
    ]);

    $action = app(NotifyCartStockDisruptionAction::class);
    $action->execute($offer);

    Notification::assertNothingSent();
});

test('NotifyCartStockDisruptionAction excluye al comprador que realizo la compra de recibir alertas sobre su propio carrito', function () {
    Notification::fake();

    $establishment = FoodEstablishment::factory()->create();
    $template = PackTemplate::factory()->create(['food_establishment_id' => $establishment->id]);
    $offer = Offer::factory()->create([
        'food_establishment_id' => $establishment->id,
        'pack_template_id' => $template->id,
        'quantity' => 0,
        'state' => OfferState::PURCHASED->value,
    ]);

    $buyer = User::factory()->create();
    $buyer->assignRole('customer');

    $userCart = UserCart::create([
        'user_id' => $buyer->id,
        'food_establishment_id' => $establishment->id,
        'state' => CartState::ACTIVE->value,
    ]);

    OfferCart::create([
        'user_cart_id' => $userCart->id,
        'offer_id' => $offer->id,
        'quantity' => 1,
    ]);

    $action = app(NotifyCartStockDisruptionAction::class);
    $action->execute($offer, $buyer->id);

    Notification::assertNothingSent();
});

test('RealtimeOrderNotification genera tanto representacion toArray en BD como toBroadcast para WebSockets', function () {
    $notification = new RealtimeOrderNotification(
        eventType: 'new_sale',
        title: '¡Nueva venta!',
        message: 'Has recibido una venta por $5000',
        sellId: 42,
        actionUrl: '/seller/sells'
    );

    $user = User::factory()->create();

    expect($notification)->toBeInstanceOf(\Illuminate\Contracts\Broadcasting\ShouldBroadcastNow::class);
    expect($notification->via($user))->toContain('database', 'broadcast');

    $dbData = $notification->toArray($user);
    expect($dbData['event_type'])->toBe('new_sale');
    expect($dbData['sell_id'])->toBe(42);

    $broadcastMessage = $notification->toBroadcast($user);
    expect($broadcastMessage)->toBeInstanceOf(\Illuminate\Notifications\Messages\BroadcastMessage::class);
    expect($broadcastMessage->data['event_type'])->toBe('new_sale');
    expect($broadcastMessage->data['sell_id'])->toBe(42);
});

test('un usuario no puede marcar como leida una notificacion ajena', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $user2->notify(new RealtimeOrderNotification(
        eventType: 'new_sale',
        title: 'Venta ajena',
        message: 'Esta notificacion pertenece a user 2'
    ));

    $notificationId = $user2->notifications()->first()->id;

    // Intentar marcarla autenticado como user1
    $response = $this->actingAs($user1)->patchJson("/api/notifications/{$notificationId}/read");

    $response->assertStatus(404)
        ->assertJsonPath('message', 'Notificación no encontrada');

    // Verificar en BD que sigue intacta y sin leer
    expect($user2->unreadNotifications()->count())->toBe(1);
});

test('rutas de notificaciones requieren autenticacion estricta (401)', function () {
    $this->getJson('/api/notifications')->assertStatus(401);
    $this->patchJson('/api/notifications/un-id/read')->assertStatus(401);
    $this->postJson('/api/notifications/read-all')->assertStatus(401);
    $this->postJson('/api/broadcasting/auth', [
        'channel_name' => 'private-App.Models.User.1',
        'socket_id' => '1234.5678',
    ])->assertStatus(401);
});

test('autorizacion de canales de websockets protege canales privados de usuario y establecimiento', function () {
    $seller = User::factory()->create();
    $customer = User::factory()->create();

    $establishment = FoodEstablishment::factory()->create([
        'user_id' => $seller->id,
    ]);

    $channels = Broadcast::getChannels();
    expect($channels)->toHaveKey('App.Models.User.{id}');
    expect($channels)->toHaveKey('establishment.{establishmentId}');

    $userCallback = $channels['App.Models.User.{id}'];
    $establishmentCallback = $channels['establishment.{establishmentId}'];

    // 1. Canal App.Models.User.{id}
    // Usuario accediendo a su propio canal -> Permitido
    expect((bool) $userCallback($customer, $customer->id))->toBeTrue();

    // Usuario intentando espiar el canal de otro -> Rechazado
    expect((bool) $userCallback($customer, $seller->id))->toBeFalse();

    // 2. Canal establishment.{establishmentId}
    // Comerciante dueño del local -> Permitido
    expect((bool) $establishmentCallback($seller, $establishment->id))->toBeTrue();

    // Cliente no dueño del local -> Rechazado
    expect((bool) $establishmentCallback($customer, $establishment->id))->toBeFalse();

    // Establecimiento inexistente -> Rechazado
    expect((bool) $establishmentCallback($seller, 999999))->toBeFalse();
});
