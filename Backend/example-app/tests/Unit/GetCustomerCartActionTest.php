<?php

namespace Tests\Unit;

use App\Actions\Cart\GetCustomerCartAction;
use App\Enums\CartState;
use App\Enums\OfferState;
use App\Enums\UserState;
use App\Http\Controllers\CartController;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\OfferCart;
use App\Models\Product;
use App\Models\User;
use App\Models\UserCart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GetCustomerCartActionTest extends TestCase
{
    use RefreshDatabase;

    protected GetCustomerCartAction $action;
    protected EstablishmentType $establishmentType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new GetCustomerCartAction(app(CartController::class));

        // Create necessary roles
        Role::create(['name' => 'customer']);
        Role::create(['name' => 'seller']);

        // Create establishment type
        $this->establishmentType = EstablishmentType::factory()->create();
    }

    #[Test]
    public function it_returns_null_when_user_has_no_active_cart()
    {
        $customer = User::factory()->withRole('customer')->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $result = $this->action->handle($customer);

        expect($result)->toBeNull();
    }

    #[Test]
    public function it_returns_grouped_offers_by_establishment_when_user_has_active_cart()
    {
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

        $product = Product::factory()->create([
            'food_establishment_id' => $establishment->id,
        ]);

        $offer = Offer::factory()->active()->create([
            'food_establishment_id' => $establishment->id,
        ]);

        $offer->products()->attach($product->id, [
            'quantity' => 2,
            'price' => 1500,
            'expiration_date' => now()->addDays(3)->toDateString()
        ]);

        $cart = UserCart::create([
            'user_id' => $customer->id,
            'state' => CartState::ACTIVE->value,
        ]);

        OfferCart::create([
            'offer_id' => $offer->id,
            'user_cart_id' => $cart->id,
            'quantity' => 2,
        ]);

        $result = $this->action->handle($customer);

        expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class)
            ->and($result)->toHaveCount(1);

        $establishmentGroup = $result->first();
        expect($establishmentGroup)->toHaveCount(1);

        $offerData = $establishmentGroup->first();
        expect($offerData)->toHaveKeys([
            'offer_id',
            'establishment_id',
            'establishment_name',
            'establishment_address',
            'offer_title',
            'offer_description',
            'offer_max_quantity',
            'offer_state',
            'offer_expiration_datetime',
            'quantity',
            'products',
        ])
            ->and($offerData['offer_id'])->toBe($offer->id)
            ->and($offerData['establishment_id'])->toBe($establishment->id)
            ->and($offerData['establishment_name'])->toBe($establishment->name)
            ->and($offerData['establishment_address'])->toBe($establishment->address)
            ->and($offerData['offer_title'])->toBe($offer->title)
            ->and($offerData['offer_description'])->toBe($offer->description)
            ->and($offerData['offer_max_quantity'])->toBe($offer->quantity)
            ->and($offerData['offer_state'])->toBe($offer->state)
            ->and($offerData['quantity'])->toBe(2)
            ->and($offerData['products'])->toHaveCount(1)
            ->and($offerData['products'][0])->toHaveKeys([
                'product_name',
                'product_description',
                'product_price',
                'product_quantity',
                'product_expiration_date',
            ])
            ->and($offerData['products'][0]['product_name'])->toBe($product->name)
            ->and($offerData['products'][0]['product_description'])->toBe($product->description)
            ->and($offerData['products'][0]['product_price'])->toBe(1500)
            ->and($offerData['products'][0]['product_quantity'])->toBe(2);

    }

    #[Test]
    public function it_groups_offers_by_establishment_correctly()
    {
        $customer = User::factory()->withRole('customer')->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $seller1 = User::factory()->withRole('seller')->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $seller2 = User::factory()->withRole('seller')->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $establishment1 = FoodEstablishment::factory()->create([
            'user_id' => $seller1->id,
            'establishment_type_id' => $this->establishmentType->id,
        ]);

        $establishment2 = FoodEstablishment::factory()->create([
            'user_id' => $seller2->id,
            'establishment_type_id' => $this->establishmentType->id,
        ]);

        $product1 = Product::factory()->create([
            'food_establishment_id' => $establishment1->id,
        ]);

        $product2 = Product::factory()->create([
            'food_establishment_id' => $establishment2->id,
        ]);

        $offer1 = Offer::factory()->active()->create([
            'food_establishment_id' => $establishment1->id,
        ]);

        $offer2 = Offer::factory()->active()->create([
            'food_establishment_id' => $establishment2->id,
        ]);

        $offer1->products()->attach($product1->id, ['quantity' => 1, 'price' => 1000]);
        $offer2->products()->attach($product2->id, ['quantity' => 1, 'price' => 1200]);

        $cart = UserCart::create([
            'user_id' => $customer->id,
            'state' => CartState::ACTIVE->value,
        ]);

        OfferCart::create([
            'offer_id' => $offer1->id,
            'user_cart_id' => $cart->id,
            'quantity' => 1,
        ]);

        OfferCart::create([
            'offer_id' => $offer2->id,
            'user_cart_id' => $cart->id,
            'quantity' => 1,
        ]);

        $result = $this->action->handle($customer);

        expect($result)->toHaveCount(2);

        $establishmentIds = $result->map(function ($group) {
            return $group->first()['establishment_id'];
        });

        expect($establishmentIds)->toContain($establishment1->id);
        expect($establishmentIds)->toContain($establishment2->id);
    }

    #[Test]
    public function it_includes_purchased_offers_with_correct_state()
    {
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

        $product = Product::factory()->create([
            'food_establishment_id' => $establishment->id,
        ]);

        $purchasedOffer = Offer::factory()->purchased()->create([
            'food_establishment_id' => $establishment->id,
        ]);

        $purchasedOffer->products()->attach($product->id, ['quantity' => 1, 'price' => 1000]);

        $cart = UserCart::create([
            'user_id' => $customer->id,
            'state' => CartState::ACTIVE->value,
        ]);

        OfferCart::create([
            'offer_id' => $purchasedOffer->id,
            'user_cart_id' => $cart->id,
            'quantity' => 1,
        ]);

        $result = $this->action->handle($customer);

        expect($result)->toHaveCount(1);

        $offerData = $result->first()->first();
        expect($offerData['offer_state'])->toBe(OfferState::PURCHASED->value);
        expect($offerData['offer_max_quantity'])->toBe(0);
    }

    #[Test]
    public function it_orders_offers_by_created_at_descending()
    {
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

        $product = Product::factory()->create([
            'food_establishment_id' => $establishment->id,
        ]);

        $offer1 = Offer::factory()->active()->create([
            'food_establishment_id' => $establishment->id,
            'title' => 'First Offer',
        ]);

        $offer2 = Offer::factory()->active()->create([
            'food_establishment_id' => $establishment->id,
            'title' => 'Second Offer',
        ]);

        $offer1->products()->attach($product->id, ['quantity' => 1, 'price' => 1000]);
        $offer2->products()->attach($product->id, ['quantity' => 1, 'price' => 1200]);

        $cart = UserCart::create([
            'user_id' => $customer->id,
            'state' => CartState::ACTIVE->value,
        ]);

        // Create first offer cart entry
        $offerCart1 = OfferCart::create([
            'offer_id' => $offer1->id,
            'user_cart_id' => $cart->id,
            'quantity' => 1,
        ]);

        // Wait a moment and create second offer cart entry
        sleep(1);
        $offerCart2 = OfferCart::create([
            'offer_id' => $offer2->id,
            'user_cart_id' => $cart->id,
            'quantity' => 1,
        ]);

        $result = $this->action->handle($customer);

        $offers = $result->first();
        expect($offers)->toHaveCount(2);

        // The most recent (offer2) should be first due to orderByDesc('created_at')
        expect($offers[0]['offer_title'])->toBe('Second Offer');
        expect($offers[1]['offer_title'])->toBe('First Offer');
    }
}
