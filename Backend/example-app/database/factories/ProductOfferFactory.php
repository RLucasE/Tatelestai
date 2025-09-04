<?php

namespace Database\Factories;

use App\Models\Offer;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\product_offer>
 */
class ProductOfferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $establishment = \App\Models\User::where('email', 'seller@gmail.com')->first()->foodEstablishment;
        return [
            'product_id' => Product::inRandomOrder()->where('food_establishment_id',$establishment->id)->first()->id,
            'offer_id' => Offer::inRandomOrder()->where('food_establishment_id',$establishment->id)->first()->id,
        ];
    }
}
