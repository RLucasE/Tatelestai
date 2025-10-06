<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentType;
use App\Models\User;
use App\Models\Product;
use App\Models\FoodEstablishment;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OfferSellerControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $seller;
    protected FoodEstablishment $establishment;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(EstablishmentTypeSeeder::class);

        $this->seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $establishmentType = EstablishmentType::inRandomOrder()->first();

        $this->establishment = FoodEstablishment::factory()->create([
            'user_id' => $this->seller->id,
            'establishment_type_id' => $establishmentType->id,
        ]);

        $this->product = Product::factory()->create([
            'food_establishment_id' => $this->establishment->id,
        ]);

        $this->actingAs($this->seller);
    }

    #[Test]
    public function it_can_store_offer_with_equal_dates(): void
    {
        $validData = [
            'title' => 'Nueva Oferta Test',
            'description' => 'Descripción de la oferta de prueba',
            'total_price' => 500,
            'expiration_date' => now()->addDays(5)->format('Y-m-d\TH:i'),
            'quantity' => 10,
            'products' => [
                [
                    'id' => $this->product->id,
                    'quantity' => 2,
                    'price' => 250,
                    'expirationDate' => now()->addDays(5)->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->postJson('/api/offer', $validData);


        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Oferta creada exitosamente'
            ])
            ->assertJsonStructure([
                'message',
                'offer' => [
                    'id',
                    'title',
                    'description',
                    'quantity',
                    'expiration_datetime',
                    'food_establishment_id'
                ]
            ]);

        $this->assertDatabaseHas('offers', [
            'title' => 'Nueva Oferta Test',
            'description' => 'Descripción de la oferta de prueba',
            'quantity' => 10,
            'food_establishment_id' => $this->establishment->id,
        ]);

        $this->assertDatabaseHas('product_offers', [
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => 250,
        ]);
    }


    #[Test]
    public function it_can_not_store_offer_with_product_date_before_offer_date(): void
    {
        $invalidData = [
            'title' => 'Oferta inválida por fecha de producto anterior',
            'description' => 'Producto vence antes que la oferta',
            'total_price' => 400,
            'expiration_date' => now()->addDays(5)->format('Y-m-d\TH:i'),
            'quantity' => 8,
            'products' => [
                [
                    'id' => $this->product->id,
                    'quantity' => 3,
                    'price' => 133,
                    'expirationDate' => now()->addDays(2)->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->postJson('/api/offer', $invalidData);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'La fecha de expiración del producto no puede ser posterior a la fecha de expiración de la oferta'
            ])
            ->assertJsonStructure([
                'message',
                'context' => [
                    'offer_expiration_date',
                    'product_expiration_date',
                    'product_id',
                    'error'
                ]
            ]);
    }

    #[Test]
    public function it_fails_to_store_offer_with_product_date_after_offer_date(): void
    {
        // Caso exitoso: fecha del producto es posterior a la fecha de la oferta (más futura)
        $validData = [
            'title' => 'Oferta con producto que vence después',
            'description' => 'Descripción de prueba - producto vence después que la oferta',
            'total_price' => 200,
            'expiration_date' => now()->addDays(3)->format('Y-m-d\TH:i'), // Oferta vence en 3 días
            'quantity' => 4,
            'products' => [
                [
                    'id' => $this->product->id,
                    'quantity' => 2,
                    'price' => 100,
                    'expirationDate' => now()->addDays(7)->format('Y-m-d'), // Producto vence en 7 días (después que la oferta)
                ]
            ]
        ];

        $response = $this->postJson('/api/offer', $validData);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Oferta creada exitosamente'
            ]);

        // Verificar que se creó la oferta en la base de datos
        $this->assertDatabaseHas('offers', [
            'title' => 'Oferta con producto que vence después',
            'food_establishment_id' => $this->establishment->id,
        ]);
    }

    #[Test]
    public function it_fails_to_store_offer_with_incorrect_dates(): void
    {
        // Caso 1: Fecha de oferta en el pasado
        $pastDateData = [
            'title' => 'Oferta con fecha pasada',
            'description' => 'Descripción de prueba',
            'total_price' => 300,
            'expiration_date' => now()->subDays(1)->format('Y-m-d\TH:i'),
            'quantity' => 5,
            'products' => [
                [
                    'id' => $this->product->id,
                    'quantity' => 1,
                    'price' => 300,
                    'expirationDate' => now()->addDays(1)->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->postJson('/api/offer', $pastDateData);


        $response->assertStatus(422)
            ->assertJson([
                'message' => 'La fecha de expiración de la oferta debe ser mayor a la fecha actual'
            ])
            ->assertJsonStructure([
                'message',
                'context' => [
                    'offer_expiration_date',
                    'error'
                ]
            ]);

        // Caso 2: Fecha de producto menor a la fecha de oferta (producto vence antes)
        $productDateBeforeOfferData = [
            'title' => 'Oferta con producto que vence antes',
            'description' => 'Descripción de prueba',
            'total_price' => 300,
            'expiration_date' => now()->addDays(5)->format('Y-m-d\TH:i'),
            'quantity' => 5,
            'products' => [
                [
                    'id' => $this->product->id,
                    'quantity' => 1,
                    'price' => 300,
                    'expirationDate' => now()->addDays(2)->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->postJson('/api/offer', $productDateBeforeOfferData);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'La fecha de expiración del producto no puede ser posterior a la fecha de expiración de la oferta'
            ])
            ->assertJsonStructure([
                'message',
                'context' => [
                    'offer_expiration_date',
                    'product_expiration_date',
                    'product_id',
                    'error'
                ]
            ]);
    }

    #[Test]
    public function it_fails_to_store_offer_without_required_dates(): void
    {
        // Caso 1: Sin fecha de expiración de oferta
        $noOfferDateData = [
            'title' => 'Oferta sin fecha',
            'description' => 'Descripción de prueba',
            'total_price' => 300,
            'quantity' => 5,
            'products' => [
                [
                    'id' => $this->product->id,
                    'quantity' => 1,
                    'price' => 300,
                    'expirationDate' => now()->addDays(1)->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->postJson('/api/offer', $noOfferDateData);



        $response->assertStatus(422)
            ->assertJsonValidationErrors(['expiration_date']);

        // Caso 2: Sin fecha de expiración de producto
        $noProductDateData = [
            'title' => 'Oferta sin fecha de producto',
            'description' => 'Descripción de prueba',
            'total_price' => 300,
            'expiration_date' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'quantity' => 5,
            'products' => [
                [
                    'id' => $this->product->id,
                    'quantity' => 1,
                    'price' => 300,
                ]
            ]
        ];

        $response = $this->postJson('/api/offer', $noProductDateData);


        $response->assertStatus(422);
    }

    #[Test]
    public function it_fails_to_store_offer_with_invalid_validation_data(): void
    {
        $invalidData = [
            // Sin title requerido
            'description' => 'Descripción de prueba',
            'expiration_date' => 'invalid_date', // Fecha inválida
            'quantity' => -1, // Cantidad negativa
            // Sin products requerido
        ];

        $response = $this->postJson('/api/offer', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'title',
                'expiration_date',
                'quantity',
                'products'
            ]);
    }

    #[Test]
    public function it_fails_to_store_offer_with_product_not_owned(): void
    {
        // Crear otro seller y producto que no pertenece al seller actual
        $otherSeller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $otherEstablishment = FoodEstablishment::factory()->create([
            'user_id' => $otherSeller->id,
            'establishment_type_id' => EstablishmentType::inRandomOrder()->first()->id,
        ]);

        $otherProduct = Product::factory()->create([
            'food_establishment_id' => $otherEstablishment->id,
        ]);

        $invalidData = [
            'title' => 'Oferta con producto ajeno',
            'description' => 'Descripción de prueba',
            'total_price' => 300,
            'expiration_date' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'quantity' => 5,
            'products' => [
                [
                    'id' => $otherProduct->id, // Producto que no pertenece al seller
                    'quantity' => 1,
                    'price' => 300,
                    'expirationDate' => now()->addDays(1)->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->postJson('/api/offer', $invalidData);

        $response->assertStatus(422);
    }

}
