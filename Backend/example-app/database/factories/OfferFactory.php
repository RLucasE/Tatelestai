<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\OfferState;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Offer>
 */
class OfferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $expirationDate = $this->faker->dateTimeBetween('+1 day', '+1 week');
        $expirationTime = $this->faker->time('H:i:s');

        return [
            'title' => $this->faker->randomElement([
                '2x1 en Pizzas',
                'Combo Hamburguesa + Papas',
                'Descuento 30% en Pastas',
                'Menú Ejecutivo Completo',
                'Happy Hour Bebidas',
                'Promoción Empanadas x12',
                'Desayuno Continental',
                'Parrilla para 2 Personas',
                'Sushi Libre Almuerzo',
                'Helado Artesanal 2kg',
                'Café + Medialunas',
                'Promo Milanesas',
                'Combo Familiar',
                'Oferta del Chef',
                'Brunch de Fin de Semana'
            ]),
            'description' => $this->faker->randomElement([
                'Oferta especial válida por tiempo limitado. No te la pierdas!',
                'La mejor calidad al mejor precio. Ideal para compartir en familia.',
                'Promoción exclusiva para nuestros clientes. Ingredientes frescos del día.',
                'Aprovechá esta increíble oportunidad. Preparado con amor y dedicación.',
                'Oferta imperdible con descuentos únicos. Reservá ya tu mesa.',
                'Combo especial con todo incluido. Perfecto para cualquier ocasión.',
                'Promoción limitada mientras dure el stock disponible.',
                'La tradición de siempre con sabores auténticos.',
                'Experiencia gastronómica única a precio especial.',
                'No te pierdas esta oportunidad única de disfrutar lo mejor.'
            ]),
            'expiration_date' => $expirationDate->format('Y-m-d'),
            'expiration_datetime' => $expirationDate->format('Y-m-d') . ' ' . $expirationTime,
            'quantity' => $this->faker->numberBetween(1, 50),
            'state' => $this->faker->randomElement([
                OfferState::ACTIVE->value,
                OfferState::ACTIVE->value, // Más probabilidad de que esté activa
                OfferState::ACTIVE->value,
                OfferState::PURCHASED->value
            ]),
        ];
    }

    /**
     * State para ofertas activas
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'state' => OfferState::ACTIVE->value,
        ]);
    }

    /**
     * State para ofertas compradas/agotadas
     */
    public function purchased(): static
    {
        return $this->state(fn(array $attributes) => [
            'state' => OfferState::PURCHASED->value,
            'quantity' => 0,
        ]);
    }

    /**
     * State para ofertas que expiran pronto
     */
    public function expiringSoon(): static
    {
        $expirationDateTime = $this->faker->dateTimeBetween('+1 hour', '+6 hours');

        return $this->state(fn(array $attributes) => [
            'expiration_date' => $expirationDateTime->format('Y-m-d'),
            'expiration_datetime' => $expirationDateTime->format('Y-m-d H:i:s'),
            'state' => OfferState::ACTIVE->value,
        ]);
    }

}
