<?php

namespace Tests\Feature;

use App\Actions\Sell\makeSellAction;
use App\DTOs\PrepareOfferDTO;
use App\DTOs\PreparePurchaseDTO;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\Sell;
use App\Models\User;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchaseConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer1;

    protected User $customer2;

    protected User $seller;

    protected FoodEstablishment $establishment;

    protected Offer $offer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(EstablishmentTypeSeeder::class);

        $this->customer1 = User::factory()->withRole(UserRole::CUSTOMER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $this->customer2 = User::factory()->withRole(UserRole::CUSTOMER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $this->seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $establishmentType = EstablishmentType::inRandomOrder()->first();

        $this->establishment = FoodEstablishment::factory()->create([
            'user_id' => $this->seller->id,
            'establishment_type_id' => $establishmentType->id,
        ]);

        $this->offer = Offer::factory()->active()->create([
            'food_establishment_id' => $this->establishment->id,
            'quantity' => 1,
            'price' => 2000,
            'expiration_datetime' => now()->addDays(5),
        ]);
    }

    /**
     * TEST 1 (Nivel Action / Base de Datos):
     * Dos procesos concurrentes reales intentan comprar la última unidad disponible (stock = 1)
     * llamando a makeSellAction::execute en paralelo mediante Concurrency::run().
     *
     * Con el código actual (sin decremento condicional atómico en base de datos):
     * Ambos procesos leen quantity = 1, ambos decrementan, se crean 2 ventas y el stock queda en -1.
     *
     * ESTE TEST DEBE FALLAR EN LA FASE 1 (demostrando sobreventa real en paralelo).
     */
    public function test_action_prevents_overselling_under_real_concurrency(): void
    {
        // Persistir datos para que sean visibles por los procesos hijos
        DB::commit();

        $offerId = $this->offer->id;
        $establishmentId = $this->establishment->id;
        $customer1Id = $this->customer1->id;
        $customer2Id = $this->customer2->id;

        $results = Concurrency::run([
            function () use ($offerId, $establishmentId, $customer1Id) {
                $app = require base_path('bootstrap/app.php');
                $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

                $action = $app->make(makeSellAction::class);
                $dto = new PreparePurchaseDTO(
                    food_establishment_id: $establishmentId,
                    offers: [
                        PrepareOfferDTO::createFromIdAndQuantity($offerId, 1),
                    ]
                );

                try {
                    $res = $action->execute($dto, $customer1Id, $establishmentId);

                    return ['success' => true, 'data' => $res];
                } catch (\Throwable $e) {
                    return ['success' => false, 'error' => $e->getMessage()];
                }
            },
            function () use ($offerId, $establishmentId, $customer2Id) {
                $app = require base_path('bootstrap/app.php');
                $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

                $action = $app->make(makeSellAction::class);
                $dto = new PreparePurchaseDTO(
                    food_establishment_id: $establishmentId,
                    offers: [
                        PrepareOfferDTO::createFromIdAndQuantity($offerId, 1),
                    ]
                );

                try {
                    $res = $action->execute($dto, $customer2Id, $establishmentId);

                    return ['success' => true, 'data' => $res];
                } catch (\Throwable $e) {
                    return ['success' => false, 'error' => $e->getMessage()];
                }
            },
        ]);

        $successCount = collect($results)->where('success', true)->count();
        $failureCount = collect($results)->where('success', false)->count();

        // Solo UNA compra debe tener éxito; la otra debe fallar por stock insuficiente
        $this->assertEquals(
            1,
            $successCount,
            'FAIL: Ambas compras concurrentes tuvieron éxito. Se produjo sobreventa (overselling) en base de datos.'
        );

        $this->assertEquals(
            1,
            $failureCount,
            'FAIL: Ninguna compra falló. Una compra debió ser rechazada por falta de stock.'
        );

        // En la base de datos solo debe haber 1 venta
        $this->assertEquals(
            1,
            Sell::where('sold_by', $this->establishment->id)->count(),
            'FAIL: Se crearon 2 ventas en la base de datos para una oferta con stock = 1.'
        );

        // El stock final debe ser exactamente 0, no negativo
        $this->offer->refresh();
        $this->assertEquals(
            0,
            $this->offer->quantity,
            'FAIL: El stock final es negativo ('.$this->offer->quantity.'). Sobrevendido en concurrencia.'
        );
    }

    /**
     * TEST 2 (Nivel Endpoint HTTP):
     * Dos peticiones HTTP concurrentes reales envían el mismo purchase_token a /api/buy-offers
     * en paralelo mediante Concurrency::run().
     *
     * Con el código actual:
     * El token solo se elimina al final del método con session()->forget(), por lo que
     * dos peticiones paralelas leen el token simultáneamente y ambas procesan la compra.
     *
     * ESTE TEST DEBE FALLAR EN LA FASE 1 (demostrando doble consumo del token bajo concurrencia).
     */
    public function test_endpoint_prevents_duplicate_purchase_with_same_token_under_real_concurrency(): void
    {
        // Usar driver database para que la sesión y caché sean compartidos entre procesos paralelos
        config(['session.driver' => 'database']);
        config(['cache.default' => 'database']);

        // Stock suficiente para aislar el test a la concurrencia del token
        $this->offer->update(['quantity' => 10]);

        $this->actingAs($this->customer1);

        $prepareResponse = $this->postJson('/api/prepare-purchase', [
            'food_establishment_id' => $this->establishment->id,
            'offers' => [
                ['id' => $this->offer->id, 'quantity' => 1],
            ],
        ]);

        $prepareResponse->assertStatus(200);
        $purchaseToken = $prepareResponse->json('data.purchase_token');
        $this->assertNotNull($purchaseToken);

        // Guardar la sesión explícitamente en la base de datos y hacer commit
        session()->save();
        $sessionId = session()->getId();
        DB::commit();

        $customerId = $this->customer1->id;

        // Ejecutar 2 peticiones concurrentes reales en procesos paralelos con el mismo token y sesión
        $responses = Concurrency::run([
            function () use ($purchaseToken, $customerId, $sessionId) {
                $app = require base_path('bootstrap/app.php');
                $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

                // Asegurar que el proceso hijo use el driver database (phpunit.xml tiene array por defecto)
                config(['session.driver' => 'database']);
                config(['cache.default' => 'database']);
                config(['sanctum.stateful' => ['localhost:8000', 'localhost:3000', 'localhost']]);

                $user = User::find($customerId);
                \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);
                $app['auth']->guard('web')->login($user);

                $session = $app['session']->driver('database');
                $session->setId($sessionId);
                $session->start();

                $request = Request::create('/api/buy-offers', 'POST', [
                    'purchase_token' => $purchaseToken,
                ]);
                $request->headers->set('Accept', 'application/json');
                $request->headers->set('referer', 'http://localhost:8000');
                $request->cookies->set(config('session.cookie'), $sessionId);
                $request->setLaravelSession($session);

                $response = $app->handle($request);

                return [
                    'status' => $response->getStatusCode(),
                    'content' => json_decode($response->getContent(), true),
                ];
            },
            function () use ($purchaseToken, $customerId, $sessionId) {
                $app = require base_path('bootstrap/app.php');
                $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

                // Asegurar que el proceso hijo use el driver database (phpunit.xml tiene array por defecto)
                config(['session.driver' => 'database']);
                config(['cache.default' => 'database']);
                config(['sanctum.stateful' => ['localhost:8000', 'localhost:3000', 'localhost']]);

                $user = User::find($customerId);
                \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);
                $app['auth']->guard('web')->login($user);

                $session = $app['session']->driver('database');
                $session->setId($sessionId);
                $session->start();

                $request = Request::create('/api/buy-offers', 'POST', [
                    'purchase_token' => $purchaseToken,
                ]);
                $request->headers->set('Accept', 'application/json');
                $request->headers->set('referer', 'http://localhost:8000');
                $request->cookies->set(config('session.cookie'), $sessionId);
                $request->setLaravelSession($session);

                $response = $app->handle($request);

                return [
                    'status' => $response->getStatusCode(),
                    'content' => json_decode($response->getContent(), true),
                ];
            },
        ]);

        $statuses = collect($responses)->pluck('status')->all();

        // Una petición debe tener éxito (200) y la otra debe ser rechazada (400)
        $this->assertContains(
            200,
            $statuses,
            'Al menos una de las peticiones debió procesarse exitosamente. Respuestas recibidas: '.json_encode($responses)
        );

        $this->assertContains(
            400,
            $statuses,
            'FAIL: Ambas peticiones concurrentes respondieron 200. El token fue consumido dos veces en paralelo. Respuestas: '.json_encode($responses)
        );

        // En la base de datos solo debe haber 1 venta
        $this->assertEquals(
            1,
            Sell::where('bought_by', $this->customer1->id)->count(),
            'FAIL: Se crearon 2 ventas con el mismo token debido a la falta de consumo atómico (session()->pull).'
        );
    }
}
