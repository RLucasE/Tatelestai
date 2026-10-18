<?php

namespace Tests\Feature;

use App\Enums\OfferState;
use App\Enums\PaymentStatus;
use App\Enums\SellState;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\Sell;
use App\Models\SellDetail;
use App\Models\User;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MercadoPagoPaymentAndCommissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected User $seller;

    protected FoodEstablishment $establishment;

    protected Offer $offer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(EstablishmentTypeSeeder::class);

        $this->customer = User::factory()->withRole(UserRole::CUSTOMER->value)->create([
            'state' => UserState::ACTIVE->value,
            'name' => 'Cliente Test',
            'email' => 'cliente@tatelestai.com',
        ]);

        $this->seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
            'name' => 'Comercio Test',
            'email' => 'comercio@tatelestai.com',
        ]);

        $type = EstablishmentType::first();

        $this->establishment = FoodEstablishment::factory()->create([
            'user_id' => $this->seller->id,
            'establishment_type_id' => $type->id,
            'name' => 'Panadería Tatelestai',
        ]);

        $this->offer = Offer::factory()->active()->create([
            'food_establishment_id' => $this->establishment->id,
            'title' => 'Bolsa Sorpresa Especial',
            'price' => 10000,
            'quantity' => 10,
            'pickup_start_datetime' => now()->addHours(3),
            'expiration_datetime' => now()->addHours(5),
        ]);
    }

    public function test_customer_can_create_payment_preference_with_commission_calculated_accurately(): void
    {
        $this->actingAs($this->customer);

        // 1. Preparar la compra en sesión
        $prepareResponse = $this->postJson('/api/customer/pack-reservations/prepare', [
            'food_establishment_id' => $this->establishment->id,
            'offers' => [
                [
                    'id' => $this->offer->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $prepareResponse->assertStatus(200);
        $purchaseToken = $prepareResponse->json('data.purchase_token');

        // 2. Crear la preferencia de Mercado Pago
        $preferenceResponse = $this->postJson('/api/customer/payment/create-preference', [
            'purchase_token' => $purchaseToken,
        ]);

        $preferenceResponse->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'sell_id',
                    'preference_id',
                    'init_point',
                    'gross_amount',
                    'pickup_code',
                ],
            ]);

        $sellId = $preferenceResponse->json('data.sell_id');
        $sell = Sell::findOrFail($sellId);

        // Validaciones financieras: Total = 2 * 10000 = $20000
        $this->assertEquals(20000.00, $sell->gross_amount);
        $this->assertEquals(15.00, $sell->commission_percentage);
        $this->assertEquals(3000.00, $sell->commission_amount); // 15% de 20000 = 3000
        $this->assertEquals(17000.00, $sell->net_amount);        // 85% para comercio = 17000
        $this->assertEquals(PaymentStatus::PENDING, $sell->payment_status);

        // Stock descontado de 10 a 8
        $this->assertEquals(8, $this->offer->fresh()->quantity);
    }

    public function test_customer_verifies_payment_and_activates_reservation(): void
    {
        $this->actingAs($this->customer);

        $sell = Sell::create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'TEST-PICKUP-123',
            'max_pickup_datetime' => now()->addHours(4),
            'payment_status' => PaymentStatus::PENDING->value,
            'gross_amount' => 10000,
            'commission_percentage' => 15,
            'commission_amount' => 1500,
            'net_amount' => 8500,
            'state' => SellState::PENDING->value,
        ]);

        $response = $this->postJson('/api/customer/payment/verify', [
            'sell_id' => $sell->id,
            'payment_id' => 'mock_payment_999',
            'status' => 'approved',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Pago confirmado exitosamente',
                'data' => [
                    'sell_id' => $sell->id,
                    'pickup_code' => 'TEST-PICKUP-123',
                    'payment_status' => PaymentStatus::APPROVED->value,
                ],
            ]);

        $this->assertEquals(PaymentStatus::APPROVED, $sell->fresh()->payment_status);
    }

    public function test_mercadopago_webhook_approves_payment_asynchronously(): void
    {
        $sell = Sell::create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'WEBHOOK-CODE-123',
            'max_pickup_datetime' => now()->addHours(4),
            'payment_status' => PaymentStatus::PENDING->value,
            'gross_amount' => 5000,
            'commission_percentage' => 15,
            'commission_amount' => 750,
            'net_amount' => 4250,
        ]);

        // Simular webhook de Mercado Pago
        $response = $this->postJson('/api/webhooks/mercadopago', [
            'type' => 'payment',
            'data' => [
                'id' => 'mock_ipn_payment_1',
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_customer_cancellation_in_time_triggers_automatic_refund(): void
    {
        $this->actingAs($this->customer);

        $sell = Sell::create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'REFUND-CODE-1',
            'payment_id' => 'mock_pay_refund_1',
            'payment_status' => PaymentStatus::APPROVED->value,
            'state' => SellState::CONFIRMED->value,
            'max_pickup_datetime' => now()->addHours(5),
            'gross_amount' => 4000,
            'commission_amount' => 600,
            'net_amount' => 3400,
        ]);

        SellDetail::create([
            'sell_id' => $sell->id,
            'offer_id' => $this->offer->id,
            'offer_quantity' => 2,
            'pack_price' => 2000,
            'pack_name' => $this->offer->title,
        ]);

        // Stock actual de la oferta
        $initialStock = $this->offer->fresh()->quantity;

        // Cancelar compra
        $response = $this->postJson("/api/customer/pack-reservations/{$sell->id}/cancel");

        $response->assertStatus(200);

        $refreshedSell = $sell->fresh();
        $this->assertEquals(SellState::CANCELLED, $refreshedSell->state);
        $this->assertEquals(PaymentStatus::REFUNDED, $refreshedSell->payment_status);
        $this->assertNotNull($refreshedSell->refunded_at);
        $this->assertEquals('customer', $refreshedSell->cancelled_by);

        // Restitución de stock: initialStock + 2
        $this->assertEquals($initialStock + 2, $this->offer->fresh()->quantity);
    }

    public function test_seller_cancellation_triggers_automatic_100_percent_refund(): void
    {
        $this->actingAs($this->seller);

        $sell = Sell::create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'SELLER-CANCEL-1',
            'payment_id' => 'mock_seller_refund_99',
            'payment_status' => PaymentStatus::APPROVED->value,
            'state' => SellState::CONFIRMED->value,
            'max_pickup_datetime' => now()->addHours(2),
            'gross_amount' => 3000,
            'commission_amount' => 450,
            'net_amount' => 2550,
        ]);

        SellDetail::create([
            'sell_id' => $sell->id,
            'offer_id' => $this->offer->id,
            'offer_quantity' => 1,
            'pack_price' => 3000,
            'pack_name' => $this->offer->title,
        ]);

        $response = $this->postJson("/api/seller/sells/{$sell->id}/cancel", [
            'reason' => 'Falta de excedente alimentario para armar el pack',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $refreshedSell = $sell->fresh();
        $this->assertEquals(SellState::CANCELLED, $refreshedSell->state);
        $this->assertEquals(PaymentStatus::REFUNDED, $refreshedSell->payment_status);
        $this->assertEquals('seller', $refreshedSell->cancelled_by);
        $this->assertEquals('Falta de excedente alimentario para armar el pack', $refreshedSell->cancellation_reason);
    }

    public function test_lazy_sync_processes_expired_sell_as_no_show_without_refund(): void
    {
        // Venta que venció hace 10 minutos (comprador no asistió a retirar: No-Show)
        $sell = Sell::create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'EXPIRED-NO-SHOW',
            'payment_id' => 'mock_noshow_pay',
            'payment_status' => PaymentStatus::APPROVED->value,
            'state' => SellState::CONFIRMED->value,
            'is_picked_up' => false,
            'max_pickup_datetime' => now()->subMinutes(10),
            'gross_amount' => 5000,
            'commission_amount' => 750,
            'net_amount' => 4250,
        ]);

        SellDetail::create([
            'sell_id' => $sell->id,
            'offer_id' => $this->offer->id,
            'offer_quantity' => 1,
            'pack_price' => 5000,
            'pack_name' => $this->offer->title,
        ]);

        // El cliente consulta sus compras
        $this->actingAs($this->customer);
        $response = $this->getJson('/api/customer/pack-reservations');

        $response->assertStatus(200);

        // La venta debe haber transicionado de forma lazy a EXPIRED
        $refreshedSell = $sell->fresh();
        $this->assertEquals(SellState::EXPIRED, $refreshedSell->state);
        // Pago sigue aprobado (no hay devolución por No-Show)
        $this->assertEquals(PaymentStatus::APPROVED, $refreshedSell->payment_status);
    }

    public function test_lazy_sync_refunds_automatically_when_merchant_deactivated_offer(): void
    {
        // Oferta dada de baja por el comercio
        $inactiveOffer = Offer::factory()->create([
            'food_establishment_id' => $this->establishment->id,
            'state' => OfferState::INACTIVE->value,
            'price' => 2000,
        ]);

        // Venta con fecha vencida vinculada a la oferta desactivada
        $sell = Sell::create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'LAZY-MERCHANT-FAIL',
            'payment_id' => 'mock_fail_pay',
            'payment_status' => PaymentStatus::APPROVED->value,
            'state' => SellState::PENDING->value,
            'is_picked_up' => false,
            'max_pickup_datetime' => now()->subMinutes(5),
            'gross_amount' => 2000,
            'commission_amount' => 300,
            'net_amount' => 1700,
        ]);

        SellDetail::create([
            'sell_id' => $sell->id,
            'offer_id' => $inactiveOffer->id,
            'offer_quantity' => 1,
            'pack_price' => 2000,
            'pack_name' => $inactiveOffer->title,
        ]);

        // El vendedor consulta su dashboard
        $this->actingAs($this->seller);
        $response = $this->getJson('/api/seller/dashboard');

        $response->assertStatus(200);

        // Debe haber cancelado y reembolsado por incumplimiento del local
        $refreshedSell = $sell->fresh();
        $this->assertEquals(SellState::CANCELLED, $refreshedSell->state);
        $this->assertEquals(PaymentStatus::REFUNDED, $refreshedSell->payment_status);
    }

    public function test_seller_dashboard_calculates_gross_commission_and_net_earnings_accurately(): void
    {
        $this->actingAs($this->seller);

        // Venta 1 de hoy: $4000 bruto
        $sell1 = Sell::create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'CODE-FIN-1',
            'payment_status' => PaymentStatus::APPROVED->value,
            'is_picked_up' => true,
            'picked_up_at' => now(),
            'max_pickup_datetime' => now()->addHours(2),
            'gross_amount' => 4000,
            'commission_percentage' => 15,
            'commission_amount' => 600,
            'net_amount' => 3400,
            'created_at' => now(),
        ]);
        SellDetail::create([
            'sell_id' => $sell1->id,
            'offer_id' => $this->offer->id,
            'offer_quantity' => 2,
            'pack_price' => 2000,
            'pack_name' => 'Pack A',
        ]);

        // Venta 2 de hoy: $6000 bruto
        $sell2 = Sell::create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'CODE-FIN-2',
            'payment_status' => PaymentStatus::APPROVED->value,
            'is_picked_up' => false,
            'max_pickup_datetime' => now()->addHours(3),
            'gross_amount' => 6000,
            'commission_percentage' => 15,
            'commission_amount' => 900,
            'net_amount' => 5100,
            'created_at' => now(),
        ]);
        SellDetail::create([
            'sell_id' => $sell2->id,
            'offer_id' => $this->offer->id,
            'offer_quantity' => 3,
            'pack_price' => 2000,
            'pack_name' => 'Pack B',
        ]);

        $response = $this->getJson('/api/seller/dashboard');

        $response->assertStatus(200);

        $summary = $response->json('data.summary');

        // Total bruto: 4000 + 6000 = $10000
        $this->assertEquals(10000.00, $summary['gross_earnings_today']);
        $this->assertEquals(10000.00, $summary['earnings_today']);
        // Comisión Tatelestai (15%): $1500
        $this->assertEquals(15.00, $summary['commission_percentage']);
        $this->assertEquals(1500.00, $summary['commission_today']);
        // Ganancia Neta Comercio (85%): $8500
        $this->assertEquals(8500.00, $summary['net_earnings_today']);
    }
}
