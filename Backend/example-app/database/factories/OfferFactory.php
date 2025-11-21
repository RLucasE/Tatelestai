<?php

namespace Database\Factories;

use App\Models\Product;
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

        $titles = [
            '¡Combo especial 2x1!', 'Descuento del 30% en menú completo', 'Promo familiar - 4 personas',
            'Happy Hour - 50% OFF en bebidas', 'Menú ejecutivo especial', 'Combo estudiante con descuento',
            'Oferta de fin de semana', 'Promoción almuerzo completo', 'Descuento para delivery',
            '¡Última hora! 40% OFF', 'Combo pareja romántica', 'Promo cumpleañero gratis',
            'Descuento grupos +6 personas', 'Oferta especial del chef', 'Menú vegano con descuento',
            'Combo desayuno continental', 'Promo cena italiana', 'Descuento comida mexicana',
            'Oferta sushi all you can eat', 'Combo BBQ para compartir', 'Promo pizza grande + bebida',
            'Descuento en pastas caseras', 'Oferta hamburguesas gourmet', 'Combo empanadas x12',
            'Promo café + medialunas', 'Descuento en postres', 'Oferta wrap saludable',
            'Combo sandwich + papas', 'Promo ensaladas frescas', 'Descuento en sopas calientes',
            'Mega combo familiar x6', 'Promo milanesas napolitanas', 'Oferta parrilla libre',
            'Descuento 25% menú completo', 'Combo brunch dominical', 'Promo choripán + bebida',
            'Oferta tacos x3 + guacamole', 'Descuento en mariscos', 'Combo asado criollo',
            'Promo ramen + gyozas', 'Oferta curry tailandés', 'Descuento en comida china',
            'Combo falafel + hummus', 'Promo ceviche peruano', 'Oferta paella valenciana',
            'Descuento en carnes premium', 'Combo pescado + ensalada', 'Promo smoothies x2',
            'Oferta helados artesanales', 'Descuento café gourmet', 'Combo croissants rellenos',
            'Promo bagels salmón', 'Oferta quinoa bowl', 'Descuento en wraps veganos',
            'Combo tapas españolas', 'Promo antipasto italiano', 'Oferta risotto hongos',
            'Descuento en lasagna', 'Combo gnocchi 4 quesos', 'Promo cannelloni caseros',
            'Oferta tiramisu + café', 'Descuento panna cotta', 'Combo gelato italiano',
            'Promo bruschetta x4', 'Oferta carpaccio', 'Descuento en focaccia',
            'Combo dim sum x8', 'Promo pato laqueado', 'Oferta wontons fritos',
            'Descuento en sopa agripicante', 'Combo arroz cantones', 'Promo chow mein',
            'Oferta spring rolls', 'Descuento en té bubble', 'Combo bao buns x4',
            'Promo bibimbap coreano', 'Oferta kimchi casero', 'Descuento en bulgogi',
            'Combo samosas x6', 'Promo chicken tikka', 'Oferta biryani especial',
            'Descuento en naan bread', 'Combo dal + arroz', 'Promo mango lassi',
            'Oferta churros + chocolate', 'Descuento en tres leches', 'Combo flan casero',
            'Promo alfajores x6', 'Oferta torta húmeda', 'Descuento en cheesecake',
            'Combo macarons x12', 'Promo profiteroles', 'Oferta crème brûlée',
            'Descuento en tartas finas', 'Combo cupcakes x6', 'Promo donuts artesanales',
            'Oferta muffins integrales', 'Descuento en cookies', 'Combo brownies x4',
            'Promo crepes dulces', 'Oferta waffles belgas', 'Descuento en pancakes',
            'Combo French toast', 'Promo granola bowl', 'Oferta acai bowl',
            'Descuento en smoothie bowl', 'Combo avocado toast', 'Promo eggs benedict',
            'Oferta omelete de hierbas', 'Descuento en shakshuka', 'Combo huevos rancheros',
            'Promo English breakfast', 'Oferta continental plus', 'Descuento en yogurt parfait',
            'Combo fruit salad', 'Promo detox juice', 'Oferta green smoothie',
            'Descuento en kombucha', 'Combo cold brew coffee', 'Promo matcha latte',
            'Oferta chai tea latte', 'Descuento en cappuccino', 'Combo espresso doble'
        ];

        $descriptions = [
            'Aprovecha esta increíble oferta por tiempo limitado. Ideal para compartir en familia o con amigos.',
            'Una promoción especial que no puedes perderte. Calidad premium a precio increíble.',
            'Disfruta de nuestros platos más populares con un descuento exclusivo solo por hoy.',
            'Perfecto para una comida deliciosa sin gastar de más. Ingredientes frescos y de primera calidad.',
            'Oferta especial para nuestros clientes más fieles. Sabores únicos que te conquistarán.',
            'Una oportunidad única de probar nuestro menú estrella con descuentos increíbles.',
            'Promoción válida solo por tiempo limitado. No te quedes sin probar esta delicia.',
            'Ideal para una salida especial o una comida casual. Calidad garantizada.',
            'Descuento exclusivo para pedidos online. Entrega rápida y comida caliente.',
            'Una experiencia gastronómica única a precio especial. Reserva ya tu mesa.',
            'Perfecto para una cita romántica o cena especial. Ambiente acogedor incluido.',
            'Celebra tu día especial con nosotros. Descuentos y sorpresas te esperan.',
            'Ideal para grupos grandes. Atención personalizada y menú variado.',
            'Creación especial de nuestro chef. Ingredientes de temporada y sabores únicos.',
            'Opciones saludables y deliciosas. Ideal para cuidar tu alimentación sin sacrificar sabor.',
            'Comienza tu día con energía. Desayuno completo y nutritivo.',
            'Auténticos sabores italianos en cada bocado. Recetas tradicionales de la nonna.',
            'Picante y sabroso. Los mejores ingredientes mexicanos en tu mesa.',
            'Todo lo que puedas comer. Sushi fresco preparado al momento.',
            'Parrillada completa para compartir. Cortes premium y acompañamientos.',
            'Pizza artesanal con masa madre. Ingredientes frescos y sabores únicos.',
            'Pastas caseras como en Italia. Salsas tradicionales y quesos importados.',
            'Hamburguesas gourmet con carne premium. Papas artesanales incluidas.',
            'Empanadas caseras horneadas. Masa crocante y rellenos abundantes.',
            'El desayuno perfecto para empezar el día. Café recién molido.',
            'Dulces tentaciones para cerrar tu comida. Postres caseros irresistibles.',
            'Opción saludable y sabrosa. Ingredientes frescos y salsas caseras.',
            'Sandwich gourmet con ingredientes premium. Perfecto para el almuerzo.',
            'Ensaladas frescas y nutritivas. Aderezos caseros y vegetales orgánicos.',
            'Sopas reconfortantes para días fríos. Recetas caseras llenas de sabor.',
            'Mariscos frescos del día. Preparados con técnicas mediterráneas tradicionales.',
            'Carnes seleccionadas y maduradas. Cocción perfecta garantizada por nuestros maestros parrilleros.',
            'Vegetales de huerta propia. Cultivados sin pesticidas para tu bienestar.',
            'Postres artesanales elaborados diariamente. Endulza tu día con nuestras creaciones.',
            'Bebidas refrescantes y naturales. Preparadas con frutas de estación.',
            'Platos fusion que combinan tradición e innovación. Una experiencia culinaria única.',
            'Ingredientes importados de la mejor calidad. Sabores auténticos que te transportarán.',
            'Opciones sin gluten disponibles. Cuidamos cada detalle para todos nuestros comensales.',
            'Menú diseñado por nutricionistas. Equilibrio perfecto entre sabor y salud.',
            'Técnicas de cocción al vapor. Conservamos todos los nutrientes y sabores naturales.',
            'Especias aromáticas de todo el mundo. Cada plato es un viaje sensorial.',
            'Productos de temporada garantizados. Frescura y calidad en cada preparación.',
            'Recetas familiares de cinco generaciones. Tradición culinaria transmitida con amor.',
            'Preparaciones al momento. No utilizamos congelados ni conservantes artificiales.',
            'Ambiente familiar y acogedor. Perfecto para reuniones íntimas y celebraciones.',
            'Servicio de entrega express. Tu comida favorita en la comodidad de tu hogar.',
            'Porciones generosas y abundantes. Calidad que satisface hasta el apetito más exigente.',
            'Cocina abierta para que veas. Transparencia total en nuestros procesos culinarios.',
            'Chef con 20 años de experiencia. Cada plato es una obra maestra gastronómica.',
            'Menú cambiante según estación. Siempre ingredientes frescos y de temporada.',
            'Técnicas de cocción francesa. Elegancia y refinamiento en cada preparación.',
            'Fusión asiática contemporánea. Sabores exóticos con presentación moderna.',
            'BBQ estilo americano auténtico. Ahumado lento para máximo sabor.',
            'Comida casera como la de la abuela. Nostalgia y sabor en cada bocado.',
            'Ingredientes orgánicos certificados. Compromiso con tu salud y el medio ambiente.',
            'Maridaje perfecto incluido. Cada plato acompañado de la bebida ideal.',
            'Servicio de sommelier disponible. Asesoramiento experto para tu experiencia completa.',
            'Cocina molecular innovadora. Texturas y sabores que desafían tus sentidos.',
            'Platos bajos en sodio. Cuida tu presión sin sacrificar el sabor.',
            'Opciones keto y paleo. Respetamos tu estilo de vida y preferencias alimentarias.',
            'Preparaciones al wok tradicional. Cocción rápida que preserva nutrientes.',
            'Fermentados caseros incluidos. Beneficios probióticos para tu digestión.',
            'Cocción sous vide de precisión. Temperatura exacta para texturas perfectas.',
            'Menú infantil especialmente diseñado. Diversión y nutrición para los más pequeños.',
            'Opciones para diabéticos disponibles. Endulzantes naturales y carbohidratos complejos.',
            'Preparaciones crudas seguras. Pescados y mariscos de la más alta calidad.',
            'Técnicas de ahumado artesanal. Sabores profundos y aromáticos únicos.',
            'Fermentación natural en masa madre. Panes y pizzas con digestión mejorada.',
            'Cocina de autor personalizada. Creaciones exclusivas de nuestro chef principal.',
            'Ingredientes hidropónicos frescos. Cultivados sin tierra para mayor pureza.',
            'Técnicas ancestrales preservadas. Métodos de cocción milenarios recuperados.',
            'Superalimentos incorporados. Quinoa, chía y otros nutrientes excepcionales.',
            'Preparaciones anti-inflamatorias. Cúrcuma, jengibre y especias medicinales.',
            'Cocina energética balanceada. Combinaciones que armonizan tu organismo.',
            'Texturas contrastantes estudiadas. Cada bocado es una experiencia sensorial.',
            'Presentación Instagram-worthy. Tan hermoso como delicioso.',
            'Sabores umami potenciados. El quinto sabor perfectamente equilibrado.',
            'Técnicas de deshidratación. Concentración de sabores naturales intensos.',
            'Aceites esenciales comestibles. Aromas que despiertan todos tus sentidos.',
            'Cocción a baja temperatura. Preservación máxima de nutrientes y texturas.',
            'Combinaciones inusuales exitosas. Ingredientes que sorprenden positivamente.',
            'Preparaciones sin lactosa disponibles. Inclusión para intolerancias alimentarias.',
            'Técnicas de esferificación. Ciencia culinaria que explota en tu boca.',
            'Ingredientes de comercio justo. Ética y sabor van de la mano.',
            'Preparaciones detox naturales. Limpia tu organismo mientras disfrutas.',
            'Cocina plant-based innovadora. Proteínas vegetales con sabores carnosos.',
            'Fermentación controlada artesanal. Probióticos vivos para tu salud intestinal.',
            'Técnicas de confitado perfecto. Texturas sedosas y sabores concentrados.',
            'Preparaciones alcalinas balanceadas. Equilibra el pH de tu organismo.',
            'Cocina terapéutica funcional. Alimentos que nutren y sanan naturalmente.',
            'Técnicas de marinado profundo. Sabores que penetran hasta el centro.',
            'Preparaciones ricas en omega-3. Grasas saludables para tu cerebro.',
            'Cocina ayurvédica adaptada. Equilibrio de doshas en cada preparación.',
            'Técnicas de reducción intensa. Salsas concentradas llenas de sabor.',
            'Preparaciones ricas en antioxidantes. Combate el envejecimiento celular.',
            'Cocina macrobiótica equilibrada. Yin y yang en perfecta armonía.',
            'Técnicas de caramelización perfecta. Azúcares naturales realzados.',
            'Preparaciones ricas en fibra. Digestión saludable garantizada.',
            'Cocina cruda segura y sabrosa. Enzimas vivas para máxima nutrición.',
            'Técnicas de infusión aromática. Sabores que se desarrollan lentamente.',
            'Preparaciones ricas en proteína. Construcción muscular natural.',
            'Cocina mediterránea auténtica. Longevidad y sabor del viejo mundo.',
            'Técnicas de glaseado natural. Brillos apetitosos sin químicos.',
            'Preparaciones ricas en vitaminas. Fortalece tu sistema inmunológico.',
            'Cocina nórdica minimalista. Pureza y simplicidad en cada plato.',
            'Técnicas de encapsulado natural. Explosiones de sabor controladas.',
            'Preparaciones ricas en minerales. Nutrición completa en cada bocado.',
            'Cocina latina tradicional. Sabores que abrazan el alma.',
            'Técnicas de cristalización. Texturas crujientes sorprendentes.'
        ];

        return [
            'food_establishment_id' => \App\Models\FoodEstablishment::factory(),
            'title' => $this->faker->randomElement($titles),
            'description' => $this->faker->randomElement($descriptions),
            'expiration_date' => $expirationDate->format('Y-m-d'),
            'expiration_datetime' => $expirationDate->format('Y-m-d') . ' ' . $expirationTime,
            'quantity' => $this->faker->numberBetween(1, 50),
            'state' => $this->faker->randomElement([
                OfferState::ACTIVE->value,
                OfferState::VERIFIYING->value,
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

    /**
     * Configura la oferta con productos aleatorios
     */
    public function withProducts(int $productCount = null): static
    {
        return $this->afterCreating(function ($offer) use ($productCount) {
            $count = $productCount ?? $this->faker->numberBetween(1, 5);

            $products = Product::inRandomOrder()
                ->limit($count)
                ->get();

            foreach ($products as $product) {
                $offer->products()->attach($product->id, [
                    'price' => $this->faker->randomDigit(1000,5000),
                    'quantity' => $this->faker->numberBetween(1, 10),
                    'expiration_date' => $this->faker->dateTimeBetween('+1 day', '+1 week')->format('Y-m-d'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

}
