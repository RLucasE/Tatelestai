<?php

namespace Tests\Feature\Verification;

use App\Enums\SellState;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Jobs\Reviews\RecalculateEstablishmentRatingsJob;
use App\Models\EstablishmentReview;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Sell;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EstablishmentReviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected User $otherCustomer;

    protected User $seller;

    protected FoodEstablishment $establishment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);

        $this->customer = User::factory()->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $this->customer->assignRole(UserRole::CUSTOMER->value);

        $this->otherCustomer = User::factory()->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $this->otherCustomer->assignRole(UserRole::CUSTOMER->value);

        $this->seller = User::factory()->create([
            'state' => UserState::ACTIVE->value,
        ]);
        $this->seller->assignRole(UserRole::SELLER->value);

        $type = EstablishmentType::factory()->create(['state' => 'active']);

        $this->establishment = FoodEstablishment::create([
            'user_id' => $this->seller->id,
            'establishment_type_id' => $type->id,
            'name' => 'Panadería San Martín',
            'address' => 'Av. San Martín 1234',
            'latitude' => -34.6037,
            'longitude' => -58.3816,
            'description' => 'Especialistas en panificados artesanales',
        ]);
    }

    #[Test]
    public function permite_a_un_comprador_calificar_un_pedido_retirado_con_1_a_5_estrellas(): void
    {
        Queue::fake();

        $sell = Sell::create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'TEST-1234-5678',
            'state' => SellState::PICKED_UP->value,
            'is_picked_up' => true,
            'picked_up_at' => now()->subHours(2),
        ]);

        $response = $this->actingAs($this->customer)->postJson("/api/customer/pack-reservations/{$sell->id}/review", [
            'rating' => 5,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => '¡Gracias por calificar tu compra!',
                'data' => [
                    'sell_id' => $sell->id,
                    'rating' => 5,
                ],
            ]);

        $this->assertDatabaseHas('establishment_reviews', [
            'sell_id' => $sell->id,
            'customer_id' => $this->customer->id,
            'food_establishment_id' => $this->establishment->id,
            'rating' => 5,
        ]);

        Queue::assertPushed(RecalculateEstablishmentRatingsJob::class, function ($job) {
            return $job->establishmentId === $this->establishment->id;
        });
    }

    #[Test]
    public function rechaza_calificar_pedidos_no_retirados_o_cancelados_whitelist(): void
    {
        Queue::fake();

        $statesToTest = [
            SellState::PENDING->value,
            SellState::CONFIRMED->value,
            SellState::READY->value,
            SellState::CANCELLED->value,
        ];

        foreach ($statesToTest as $state) {
            $sell = Sell::create([
                'bought_by' => $this->customer->id,
                'sold_by' => $this->establishment->id,
                'pickup_code' => 'CODE-'.uniqid(),
                'state' => $state,
                'is_picked_up' => false,
                'picked_up_at' => null,
            ]);

            $response = $this->actingAs($this->customer)->postJson("/api/customer/pack-reservations/{$sell->id}/review", [
                'rating' => 4,
            ]);

            $response->assertStatus(422)
                ->assertJson([
                    'message' => 'Solo puedes calificar compras que ya hayan sido retiradas.',
                ]);
        }

        Queue::assertNothingPushed();
    }

    #[Test]
    public function impide_que_un_usuario_califique_compras_de_otro_cliente(): void
    {
        Queue::fake();

        $sell = Sell::create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'TEST-OTHER-USER',
            'state' => SellState::PICKED_UP->value,
            'is_picked_up' => true,
            'picked_up_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->otherCustomer)->postJson("/api/customer/pack-reservations/{$sell->id}/review", [
            'rating' => 5,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'No tienes autorización para calificar este pedido.',
            ]);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function impide_calificar_dos_veces_la_misma_venta(): void
    {
        $sell = Sell::create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'TEST-DUPLICATE',
            'state' => SellState::PICKED_UP->value,
            'is_picked_up' => true,
            'picked_up_at' => now()->subHours(1),
        ]);

        EstablishmentReview::create([
            'sell_id' => $sell->id,
            'customer_id' => $this->customer->id,
            'food_establishment_id' => $this->establishment->id,
            'rating' => 5,
        ]);

        $response = $this->actingAs($this->customer)->postJson("/api/customer/pack-reservations/{$sell->id}/review", [
            'rating' => 4,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Esta compra ya ha sido calificada.',
            ]);
    }

    #[Test]
    public function rechaza_calificaciones_de_pedidos_retirados_hace_mas_de_14_dias(): void
    {
        Queue::fake();

        $sell = Sell::create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'TEST-EXPIRED-WINDOW',
            'state' => SellState::PICKED_UP->value,
            'is_picked_up' => true,
            'picked_up_at' => now()->subDays(15),
        ]);

        $response = $this->actingAs($this->customer)->postJson("/api/customer/pack-reservations/{$sell->id}/review", [
            'rating' => 5,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'El período de calificación para este pedido ha vencido (14 días desde el retiro).',
            ]);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function valida_que_el_rating_este_estrictamente_entre_1_y_5(): void
    {
        $sell = Sell::create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'TEST-RATING-LIMITS',
            'state' => SellState::PICKED_UP->value,
            'is_picked_up' => true,
            'picked_up_at' => now()->subDay(),
        ]);

        $invalidRatings = [0, 6, -1, 'cinco', null];

        foreach ($invalidRatings as $invalid) {
            $response = $this->actingAs($this->customer)->postJson("/api/customer/pack-reservations/{$sell->id}/review", [
                'rating' => $invalid,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['rating']);
        }
    }

    #[Test]
    public function job_recalcula_correctamente_la_media_historica_y_reciente(): void
    {
        // Creamos 4 ventas retiradas en el pasado reciente (< 30 días)
        for ($i = 1; $i <= 4; $i++) {
            $sell = Sell::create([
                'bought_by' => $this->customer->id,
                'sold_by' => $this->establishment->id,
                'pickup_code' => 'RECENT-'.$i,
                'state' => SellState::PICKED_UP->value,
                'is_picked_up' => true,
                'picked_up_at' => now()->subDays(2),
            ]);

            EstablishmentReview::create([
                'sell_id' => $sell->id,
                'customer_id' => $this->customer->id,
                'food_establishment_id' => $this->establishment->id,
                'rating' => 5,
                'created_at' => now()->subDays(2),
            ]);
        }

        // Creamos 1 venta retirada antigua (> 30 días) con 1 estrella
        $oldSell = Sell::create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'OLD-1',
            'state' => SellState::PICKED_UP->value,
            'is_picked_up' => true,
            'picked_up_at' => now()->subDays(45),
        ]);

        EstablishmentReview::create([
            'sell_id' => $oldSell->id,
            'customer_id' => $this->customer->id,
            'food_establishment_id' => $this->establishment->id,
            'rating' => 1,
            'created_at' => now()->subDays(45),
        ]);

        // Ejecutamos el Job
        (new RecalculateEstablishmentRatingsJob($this->establishment->id))->handle();

        $this->establishment->refresh();

        // Total 5 reviews: (5+5+5+5+1) / 5 = 4.20 histórico
        $this->assertEquals(5, $this->establishment->reviews_count);
        $this->assertEquals(4.20, (float) $this->establishment->rating_average);

        // Recientes (últimos 30 días, 4 reviews de 5 estrellas): 5.00
        $this->assertEquals(4, $this->establishment->recent_reviews_count);
        $this->assertEquals(5.00, (float) $this->establishment->recent_rating_average);
    }

    #[Test]
    public function job_calcula_directamente_reciente_e_historica_sin_umbral_minimo_de_resenas(): void
    {
        // 1 review en los últimos 30 días (4 estrellas)
        $sellRecent = Sell::create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'LOW-RECENT-1',
            'state' => SellState::PICKED_UP->value,
            'is_picked_up' => true,
            'picked_up_at' => now()->subDays(5),
        ]);
        EstablishmentReview::create([
            'sell_id' => $sellRecent->id,
            'customer_id' => $this->customer->id,
            'food_establishment_id' => $this->establishment->id,
            'rating' => 4,
            'created_at' => now()->subDays(5),
        ]);

        // 2 reviews viejas (> 30 días) con 5 estrellas
        for ($i = 1; $i <= 2; $i++) {
            $sellOld = Sell::create([
                'bought_by' => $this->customer->id,
                'sold_by' => $this->establishment->id,
                'pickup_code' => 'LOW-OLD-'.$i,
                'state' => SellState::PICKED_UP->value,
                'is_picked_up' => true,
                'picked_up_at' => now()->subDays(40),
            ]);
            EstablishmentReview::create([
                'sell_id' => $sellOld->id,
                'customer_id' => $this->customer->id,
                'food_establishment_id' => $this->establishment->id,
                'rating' => 5,
                'created_at' => now()->subDays(40),
            ]);
        }

        (new RecalculateEstablishmentRatingsJob($this->establishment->id))->handle();
        $this->establishment->refresh();

        // Directo: total 3 calificaciones (4 + 5 + 5) / 3 = 4.67
        $this->assertEquals(3, $this->establishment->reviews_count);
        $this->assertEquals(4.67, (float) $this->establishment->rating_average);

        // Reciente directo: solo la de los últimos 30 días (1 calificación de 4)
        $this->assertEquals(1, $this->establishment->recent_reviews_count);
        $this->assertEquals(4.00, (float) $this->establishment->recent_rating_average);
    }

    #[Test]
    public function job_establece_reciente_en_null_si_no_hay_calificaciones_en_ultimos_30_dias(): void
    {
        // 2 reviews viejas (> 30 días) con 5 estrellas
        for ($i = 1; $i <= 2; $i++) {
            $sellOld = Sell::create([
                'bought_by' => $this->customer->id,
                'sold_by' => $this->establishment->id,
                'pickup_code' => 'ONLY-OLD-'.$i,
                'state' => SellState::PICKED_UP->value,
                'is_picked_up' => true,
                'picked_up_at' => now()->subDays(45),
            ]);
            EstablishmentReview::create([
                'sell_id' => $sellOld->id,
                'customer_id' => $this->customer->id,
                'food_establishment_id' => $this->establishment->id,
                'rating' => 5,
                'created_at' => now()->subDays(45),
            ]);
        }

        (new RecalculateEstablishmentRatingsJob($this->establishment->id))->handle();
        $this->establishment->refresh();

        // Histórico presente: 2 calificaciones de 5.0
        $this->assertEquals(2, $this->establishment->reviews_count);
        $this->assertEquals(5.00, (float) $this->establishment->rating_average);

        // Reciente nulo: 0 compras en los últimos 30 días
        $this->assertEquals(0, $this->establishment->recent_reviews_count);
        $this->assertNull($this->establishment->recent_rating_average);
    }

    #[Test]
    public function expone_can_review_y_is_reviewed_correctamente_en_sell_resource(): void
    {
        $eligibleSell = Sell::create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'RESOURCE-ELIGIBLE',
            'state' => SellState::PICKED_UP->value,
            'is_picked_up' => true,
            'picked_up_at' => now()->subDays(3),
        ]);

        $response = $this->actingAs($this->customer)->getJson('/api/customer/pack-reservations/history');

        $response->assertStatus(200);
        $item = collect($response->json('data'))->firstWhere('sell_id', $eligibleSell->id);
        $this->assertTrue($item['can_review']);
        $this->assertFalse($item['is_reviewed']);
        $this->assertNull($item['user_rating']);

        // Ahora calificamos
        EstablishmentReview::create([
            'sell_id' => $eligibleSell->id,
            'customer_id' => $this->customer->id,
            'food_establishment_id' => $this->establishment->id,
            'rating' => 4,
        ]);

        $responseAfter = $this->actingAs($this->customer)->getJson('/api/customer/pack-reservations/history');
        $itemAfter = collect($responseAfter->json('data'))->firstWhere('sell_id', $eligibleSell->id);
        $this->assertFalse($itemAfter['can_review']);
        $this->assertTrue($itemAfter['is_reviewed']);
        $this->assertEquals(4, $itemAfter['user_rating']);
    }

    #[Test]
    public function endpoint_rating_summary_retorna_resumen_y_distribucion_de_estrellas(): void
    {
        $this->establishment->update([
            'rating_average' => 4.50,
            'reviews_count' => 10,
            'recent_rating_average' => 4.80,
            'recent_reviews_count' => 4,
        ]);

        // Sin autenticación debe responder 401
        $unauthResponse = $this->getJson("/api/establishments/{$this->establishment->id}/rating-summary");
        $unauthResponse->assertStatus(401);

        // Con autenticación retorna el resumen correctamente
        $authResponse = $this->actingAs($this->customer)->getJson("/api/establishments/{$this->establishment->id}/rating-summary");
        $authResponse->assertStatus(200)
            ->assertJson([
                'data' => [
                    'establishment_id' => $this->establishment->id,
                    'rating_average' => 4.50,
                    'reviews_count' => 10,
                    'recent_rating_average' => 4.80,
                    'recent_reviews_count' => 4,
                    'distribution' => [
                        '5' => 0,
                        '4' => 0,
                        '3' => 0,
                        '2' => 0,
                        '1' => 0,
                    ],
                ],
            ]);
    }

    #[Test]
    public function retorna_404_limpio_en_json_cuando_la_venta_a_calificar_no_existe(): void
    {
        $response = $this->actingAs($this->customer)->postJson('/api/customer/pack-reservations/999999/review', [
            'rating' => 5,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'La compra especificada no existe o no fue encontrada.',
            ]);
    }

    #[Test]
    public function sell_resource_y_history_exigen_estrictamente_sell_state_picked_up(): void
    {
        // Venta con is_picked_up true pero estado CONFIRMED (inconsistencia)
        $invalidStateSell = Sell::create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'TEST-INVALID-STATE',
            'state' => SellState::CONFIRMED->value,
            'is_picked_up' => true,
            'picked_up_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->customer)->getJson('/api/customer/pack-reservations/history');
        $response->assertStatus(200);

        // No debe aparecer en el historial porque su estado no es PICKED_UP
        $historySells = collect($response->json('data'));
        $this->assertNull($historySells->firstWhere('sell_id', $invalidStateSell->id));

        // Directamente en el recurso, can_review debe ser false
        $resourceData = (new \App\Http\Resources\SellResource($invalidStateSell))->resolve();
        $this->assertFalse($resourceData['can_review']);
    }

    #[Test]
    public function food_establishment_show_retorna_resource_con_campos_de_reputacion(): void
    {
        $this->establishment->update([
            'rating_average' => 4.60,
            'reviews_count' => 12,
            'recent_rating_average' => 4.80,
            'recent_reviews_count' => 5,
        ]);

        $response = $this->actingAs($this->customer)->getJson("/api/establishments/{$this->establishment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $this->establishment->id,
                    'name' => $this->establishment->name,
                    'rating_average' => 4.60,
                    'reviews_count' => 12,
                    'recent_rating_average' => 4.80,
                    'recent_reviews_count' => 5,
                ],
            ]);
    }
}
