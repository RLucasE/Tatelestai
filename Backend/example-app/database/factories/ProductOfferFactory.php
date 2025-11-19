<?php

namespace Database\Factories;

use App\Models\Offer;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductOffer>
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
        // Intentar obtener el establishment específico, si no existe usar uno aleatorio
        $user = \App\Models\User::where('email', 'seller@gmail.com')->first();

        if ($user && $user->foodEstablishment) {
            $establishment = $user->foodEstablishment;
        } else {
            // En tests, usar un establishment aleatorio
            $establishment = \App\Models\FoodEstablishment::inRandomOrder()->first();
        }

        // Si no hay establishment, crear datos básicos
        if (!$establishment) {
            return [
                'product_id' => Product::factory(),
                'offer_id' => Offer::factory(),
            ];
        }

        return [
            'product_id' => Product::inRandomOrder()->where('food_establishment_id', $establishment->id)->first()->id,
            'offer_id' => Offer::inRandomOrder()->where('food_establishment_id', $establishment->id)->first()->id,
        ];
    }
}
