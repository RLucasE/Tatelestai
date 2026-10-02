<?php

namespace Tests\Feature;

use App\Enums\OfferState;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\PackTemplate;
use App\Models\User;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OfferVisibilityTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $seller;

    protected User $customer;

    protected FoodEstablishment $establishment;

    protected PackTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(EstablishmentTypeSeeder::class);

        $this->seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $this->customer = User::factory()->withRole(UserRole::CUSTOMER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $this->establishment = FoodEstablishment::factory()->create([
            'user_id' => $this->seller->id,
            'establishment_type_id' => EstablishmentType::inRandomOrder()->first()->id,
        ]);

        $this->template = PackTemplate::factory()->create([
            'food_establishment_id' => $this->establishment->id,
        ]);
    }

    #[Test]
    public function seller_can_create_a_pack_with_immediate_visibility(): void
    {
        $this->actingAs($this->seller);

        $payload = [
            'pack_template_id' => $this->template->id,
            'price' => 2500,
            'minimum_value' => 7500,
            'quantity' => 4,
            'pickup_start_datetime' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'pickup_end_datetime' => now()->addHours(4)->format('Y-m-d H:i:s'),
            'visible_from' => null,
        ];

        $response = $this->postJson('/api/packs', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.visible_from', null);

        $this->assertDatabaseHas('offers', [
            'food_establishment_id' => $this->establishment->id,
            'price' => 2500,
            'visible_from' => null,
        ]);
    }

    #[Test]
    public function seller_can_create_a_pack_with_scheduled_visibility(): void
    {
        $this->actingAs($this->seller);

        $scheduledTime = now()->addHour()->format('Y-m-d H:i:s');

        $payload = [
            'pack_template_id' => $this->template->id,
            'price' => 3000,
            'minimum_value' => 9000,
            'quantity' => 3,
            'pickup_start_datetime' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'pickup_end_datetime' => now()->addHours(5)->format('Y-m-d H:i:s'),
            'visible_from' => $scheduledTime,
        ];

        $response = $this->postJson('/api/packs', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('offers', [
            'food_establishment_id' => $this->establishment->id,
            'price' => 3000,
        ]);

        $offer = Offer::where('food_establishment_id', $this->establishment->id)->first();
        $this->assertNotNull($offer->visible_from);
        $this->assertEquals($scheduledTime, $offer->visible_from->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_rejects_pack_when_visible_from_is_after_pickup_end_datetime(): void
    {
        $this->actingAs($this->seller);

        $payload = [
            'pack_template_id' => $this->template->id,
            'price' => 3000,
            'minimum_value' => 9000,
            'quantity' => 3,
            'pickup_start_datetime' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'pickup_end_datetime' => now()->addHours(4)->format('Y-m-d H:i:s'),
            'visible_from' => now()->addHours(5)->format('Y-m-d H:i:s'), // Posterior al fin de retiro
        ];

        $response = $this->postJson('/api/packs', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['visible_from']);
    }

    #[Test]
    public function customer_cannot_see_future_scheduled_packs_in_catalog(): void
    {
        // Oferta con visibilidad futura (aún no comenzó)
        Offer::factory()->create([
            'food_establishment_id' => $this->establishment->id,
            'pack_template_id' => $this->template->id,
            'state' => OfferState::ACTIVE->value,
            'title' => 'Pack Futuro Oculto',
            'visible_from' => now()->addHours(3),
            'expiration_datetime' => now()->addHours(6),
        ]);

        // Oferta visible inmediatamente
        Offer::factory()->create([
            'food_establishment_id' => $this->establishment->id,
            'pack_template_id' => $this->template->id,
            'state' => OfferState::ACTIVE->value,
            'title' => 'Pack Inmediato Visible',
            'visible_from' => null,
            'expiration_datetime' => now()->addHours(6),
        ]);

        // Oferta cuya visibilidad ya inició en el pasado
        Offer::factory()->create([
            'food_establishment_id' => $this->establishment->id,
            'pack_template_id' => $this->template->id,
            'state' => OfferState::ACTIVE->value,
            'title' => 'Pack Programado Ya Activo',
            'visible_from' => now()->subHour(),
            'expiration_datetime' => now()->addHours(6),
        ]);

        $this->actingAs($this->customer);

        $response = $this->getJson('/api/packs');

        $response->assertStatus(200);

        $titles = collect($response->json('data'))->pluck('title')->toArray();

        $this->assertContains('Pack Inmediato Visible', $titles);
        $this->assertContains('Pack Programado Ya Activo', $titles);
        $this->assertNotContains('Pack Futuro Oculto', $titles);
    }

    #[Test]
    public function customer_receives_404_when_accessing_future_scheduled_pack_directly(): void
    {
        $futurePack = Offer::factory()->create([
            'food_establishment_id' => $this->establishment->id,
            'pack_template_id' => $this->template->id,
            'state' => OfferState::ACTIVE->value,
            'title' => 'Pack Futuro Directo',
            'visible_from' => now()->addHours(2),
            'expiration_datetime' => now()->addHours(5),
        ]);

        $this->actingAs($this->customer);

        $response = $this->getJson("/api/packs/{$futurePack->id}");

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Pack no encontrado');
    }

    #[Test]
    public function seller_can_update_pack_visibility_schedule(): void
    {
        $this->actingAs($this->seller);

        $pack = Offer::factory()->create([
            'food_establishment_id' => $this->establishment->id,
            'pack_template_id' => $this->template->id,
            'state' => OfferState::ACTIVE->value,
            'visible_from' => null,
            'expiration_datetime' => now()->addHours(8),
        ]);

        $newSchedule = now()->addHour()->format('Y-m-d H:i:s');

        $response = $this->patchJson("/api/packs/{$pack->id}", [
            'visible_from' => $newSchedule,
        ]);

        $response->assertStatus(200);

        $pack->refresh();
        $this->assertEquals($newSchedule, $pack->visible_from->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function seller_cannot_update_visible_from_to_be_after_expiration(): void
    {
        $this->actingAs($this->seller);

        $pack = Offer::factory()->create([
            'food_establishment_id' => $this->establishment->id,
            'pack_template_id' => $this->template->id,
            'state' => OfferState::ACTIVE->value,
            'expiration_datetime' => now()->addHours(3),
        ]);

        $response = $this->patchJson("/api/packs/{$pack->id}", [
            'visible_from' => now()->addHours(5)->format('Y-m-d H:i:s'),
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function customer_cannot_add_future_scheduled_offer_to_cart(): void
    {
        $futurePack = Offer::factory()->create([
            'food_establishment_id' => $this->establishment->id,
            'pack_template_id' => $this->template->id,
            'state' => OfferState::ACTIVE->value,
            'title' => 'Pack Futuro No Visible',
            'quantity' => 5,
            'visible_from' => now()->addHours(2),
            'expiration_datetime' => now()->addHours(6),
        ]);

        $this->actingAs($this->customer);

        $response = $this->postJson('/api/add-to-cart', [
            'offer_id' => $futurePack->id,
            'quantity' => 1,
        ]);

        // La petición debe ser rechazada (no debe retornar 200 OK)
        $this->assertNotEquals(200, $response->getStatusCode(), 'No se debe permitir agregar al carrito una oferta no visible aún');

        // Verificar que no se creó el registro en el carrito
        $this->assertDatabaseMissing('offer_carts', [
            'offer_id' => $futurePack->id,
        ]);
    }

    #[Test]
    public function customer_cannot_prepare_purchase_for_future_scheduled_offer(): void
    {
        $futurePack = Offer::factory()->create([
            'food_establishment_id' => $this->establishment->id,
            'pack_template_id' => $this->template->id,
            'state' => OfferState::ACTIVE->value,
            'title' => 'Pack Futuro Bloqueado para Compra',
            'quantity' => 5,
            'visible_from' => now()->addHours(3),
            'expiration_datetime' => now()->addHours(6),
        ]);

        $this->actingAs($this->customer);

        $response = $this->postJson('/api/customer/pack-reservations/prepare', [
            'food_establishment_id' => $this->establishment->id,
            'offers' => [
                ['id' => $futurePack->id, 'quantity' => 1],
            ],
        ]);

        // La preparación de la compra debe ser rechazada
        $this->assertNotEquals(200, $response->getStatusCode(), 'No se debe permitir preparar la compra de una oferta no visible aún');

        // Verificar que no se generó ninguna venta
        $this->assertDatabaseMissing('sells', [
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
        ]);
    }

    #[Test]
    public function customer_can_add_to_cart_and_prepare_purchase_for_currently_visible_offer(): void
    {
        $visiblePack = Offer::factory()->create([
            'food_establishment_id' => $this->establishment->id,
            'pack_template_id' => $this->template->id,
            'state' => OfferState::ACTIVE->value,
            'title' => 'Pack Visible Inmediato',
            'quantity' => 5,
            'visible_from' => now()->subMinute(),
            'expiration_datetime' => now()->addHours(6),
        ]);

        $this->actingAs($this->customer);

        // Debe poder agregarse al carrito
        $cartResponse = $this->postJson('/api/add-to-cart', [
            'offer_id' => $visiblePack->id,
            'quantity' => 1,
        ]);
        $cartResponse->assertStatus(200);

        // Debe poder prepararse para compra
        $prepareResponse = $this->postJson('/api/customer/pack-reservations/prepare', [
            'food_establishment_id' => $this->establishment->id,
            'offers' => [
                ['id' => $visiblePack->id, 'quantity' => 1],
            ],
        ]);
        $prepareResponse->assertStatus(200);
    }

    #[Test]
    public function customer_cannot_confirm_purchase_for_future_scheduled_offer(): void
    {
        $futurePack = Offer::factory()->create([
            'food_establishment_id' => $this->establishment->id,
            'pack_template_id' => $this->template->id,
            'state' => OfferState::ACTIVE->value,
            'title' => 'Pack Futuro Bloqueado para Compra Confirmada',
            'quantity' => 5,
            'visible_from' => now()->addHours(3),
            'expiration_datetime' => now()->addHours(6),
        ]);

        $this->actingAs($this->customer);

        $preparePurchaseDTO = new \App\DTOs\PreparePurchaseDTO(
            food_establishment_id: $this->establishment->id,
            offers: [
                \App\DTOs\PrepareOfferDTO::createFromIdAndQuantity($futurePack->id, 1),
            ]
        );

        $fakeToken = 'test_token_future_pack_'.uniqid();
        session()->put('purchase_'.$fakeToken, [
            'preparePurchaseDTO' => $preparePurchaseDTO,
            'expires_at' => now()->addMinutes(5),
        ]);
        session()->save();

        $response = $this->postJson('/api/customer/pack-reservations/confirm', [
            'purchase_token' => $fakeToken,
        ]);

        $this->assertNotEquals(200, $response->getStatusCode(), 'No se debe permitir confirmar la compra de una oferta no visible aún');
        $this->assertDatabaseMissing('sells', [
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
        ]);
    }
}
