<?php

namespace Database\Seeders;

use App\Enums\TrustScoreCriterion;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentTrustLog;
use App\Models\EstablishmentVerificationFile;
use App\Models\FoodEstablishment;
use App\Models\User;
use Illuminate\Database\Seeder;

class FoodEstablishmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. ESTABLECIMIENTOS APROBADOS (APPROVED) - 20 locales oficiales para catálogo y mapa
        $approvedEstablishments = [
            [
                'seller_email' => 'yatarasu@gmail.com',
                'seller_name' => 'Lucas',
                'name' => 'Café del Convento',
                'address' => 'Caseros 540, Salta Capital',
                'latitude' => -24.78850000,
                'longitude' => -65.41050000,
                'establishment_type_id' => 2, // Cafetería
                'phone' => '3874211550',
                'description' => 'Cafetería histórica frente a la Plaza 9 de Julio, pastelería artesanal y café de especialidad.',
            ],
            [
                'seller_email' => 'seller2@gmail.com',
                'seller_name' => 'Pedro',
                'name' => 'Peña & Restó La Vieja Estación',
                'address' => 'Balcarce 875, Salta Capital',
                'latitude' => -24.78080000,
                'longitude' => -65.41580000,
                'establishment_type_id' => 1, // Restaurante
                'phone' => '3874229988',
                'description' => 'Comida típica salteña, empanadas de lomo cortadas a cuchillo, tamales, humitas y vinos calchaquíes.',
            ],
            [
                'seller_email' => 'seller3@gmail.com',
                'seller_name' => 'Martín',
                'name' => 'La Posta de Güemes Bar & Grill',
                'address' => 'Paseo Güemes 420, Salta Capital',
                'latitude' => -24.78520000,
                'longitude' => -65.40450000,
                'establishment_type_id' => 1, // Restaurante
                'phone' => '3874312233',
                'description' => 'Gastrobar con carnes a las brasas, hamburguesas gourmet y cervezas artesanales en el corazón de Güemes.',
            ],
            [
                'seller_email' => 'seller4@gmail.com',
                'seller_name' => 'Florencia',
                'name' => 'Cerro & Sabor Restó',
                'address' => 'Av. Uruguay 650, Salta Capital',
                'latitude' => -24.78360000,
                'longitude' => -65.39950000,
                'establishment_type_id' => 1, // Restaurante
                'phone' => '3874241199',
                'description' => 'Cocina regional y platos de autor al pie del cerro San Bernardo y el monumento histórico.',
            ],
            [
                'seller_email' => 'seller5@gmail.com',
                'seller_name' => 'Sofía',
                'name' => 'Panadería y Confitería Los Cerritos',
                'address' => 'Av. Reyes Católicos 1480, Salta Capital',
                'latitude' => -24.76750000,
                'longitude' => -65.40520000,
                'establishment_type_id' => 2, // Cafetería
                'phone' => '3874395566',
                'description' => 'Panificación tradicional, medialunas de manteca, tartas dulces y sándwiches de miga especiales.',
            ],
            [
                'seller_email' => 'seller6@gmail.com',
                'seller_name' => 'Carlos',
                'name' => 'Parrilla y Rotisería Parque San Martín',
                'address' => 'Av. San Martín 820, Salta Capital',
                'latitude' => -24.79250000,
                'longitude' => -65.40720000,
                'establishment_type_id' => 1, // Restaurante
                'phone' => '3874223344',
                'description' => 'Parrillada al paso, pollos al spiedo, minutas salteñas y platos del día listos para llevar.',
            ],
            [
                'seller_email' => 'seller7@gmail.com',
                'seller_name' => 'Mariana',
                'name' => 'Pastas Frescas La Salteñita',
                'address' => 'Mendoza 1120, Salta Capital',
                'latitude' => -24.79850000,
                'longitude' => -65.41650000,
                'establishment_type_id' => 1, // Restaurante
                'phone' => '3874238877',
                'description' => 'Fábrica de pastas caseras, ravioles de verdura y pavita, sorrentinos de jamón y queso con salsas tradicionales.',
            ],
            [
                'seller_email' => 'seller8@gmail.com',
                'seller_name' => 'Rodrigo',
                'name' => 'Pizzería & Lomitería San Cayetano',
                'address' => 'Av. Entre Ríos 1820, Salta Capital',
                'latitude' => -24.77150000,
                'longitude' => -65.42600000,
                'establishment_type_id' => 1, // Restaurante
                'phone' => '3874360011',
                'description' => 'Pizzas a la piedra con mozzarella de primera, lomos completos y empanadas salteñas al horno.',
            ],
            [
                'seller_email' => 'seller9@gmail.com',
                'seller_name' => 'Valeria',
                'name' => 'Mercado Natural Grand Bourg',
                'address' => 'Av. San Martín 2350, Grand Bourg, Salta Capital',
                'latitude' => -24.77450000,
                'longitude' => -65.44350000,
                'establishment_type_id' => 3, // Supermercado
                'phone' => '3874384455',
                'description' => 'Alimentos orgánicos, frutos secos, productos frescos de huerta y rescate de canastas saludables.',
            ],
            [
                'seller_email' => 'seller10@gmail.com',
                'seller_name' => 'Gonzalo',
                'name' => 'Comedor Universitario & Minutas Norte',
                'address' => 'Av. Bolivia 4600, Salta Capital',
                'latitude' => -24.73900000,
                'longitude' => -65.41200000,
                'establishment_type_id' => 2, // Cafetería
                'phone' => '3874257788',
                'description' => 'Menús ejecutivos económicos para estudiantes y docentes, tartas, wraps saludables y café al paso.',
            ],
            [
                'seller_email' => 'seller11@gmail.com',
                'seller_name' => 'Esteban',
                'name' => 'Supermercado El Tribuno Express',
                'address' => 'Av. Ex Combatientes de Malvinas 3850, Salta Capital',
                'latitude' => -24.82100000,
                'longitude' => -65.42700000,
                'establishment_type_id' => 3, // Supermercado
                'phone' => '3874246677',
                'description' => 'Abarrotes, panadería del día, lácteos y productos de almacén con promociones diarias de liquidación.',
            ],
            [
                'seller_email' => 'seller12@gmail.com',
                'seller_name' => 'Beatriz',
                'name' => 'Empanadas & Pizzas Don Yeyo',
                'address' => 'Av. Roberto Romero 3100, Salta Capital',
                'latitude' => -24.83200000,
                'longitude' => -65.43100000,
                'establishment_type_id' => 1, // Restaurante
                'phone' => '3874248899',
                'description' => 'Empanadas salteñas tradicionales de charqui y carne suave, pizzas gigantes y calzones al horno de barro.',
            ],
            [
                'seller_email' => 'seller13@gmail.com',
                'seller_name' => 'Joaquín',
                'name' => 'Rotisería y Pizzería Castañares',
                'address' => 'Av. Jaime Durán 450, Barrio Castañares, Salta Capital',
                'latitude' => -24.74350000,
                'longitude' => -65.40800000,
                'establishment_type_id' => 1, // Restaurante
                'phone' => '3874259911',
                'description' => 'Comidas caseras, empanadas al horno, sándwiches de milanesa gigantes y pizzas populares para el barrio y estudiantes.',
            ],
            [
                'seller_email' => 'seller14@gmail.com',
                'seller_name' => 'Gisela',
                'name' => 'Panadería y Pastelería El Milagro',
                'address' => 'Av. Héroes de la Patria 890, Ciudad del Milagro, Salta Capital',
                'latitude' => -24.73500000,
                'longitude' => -65.41450000,
                'establishment_type_id' => 2, // Cafetería
                'phone' => '3874253344',
                'description' => 'Facturas recién horneadas, pan con grasa, medialunas de manteca y tortas artesanales.',
            ],
            [
                'seller_email' => 'seller15@gmail.com',
                'seller_name' => 'Nicolás',
                'name' => 'Bistró Judicial El Huaico',
                'address' => 'Av. Democracia y Av. Lucrecia Barquet, Barrio El Huaico, Salta Capital',
                'latitude' => -24.72950000,
                'longitude' => -65.41900000,
                'establishment_type_id' => 1, // Restaurante
                'phone' => '3874952200',
                'description' => 'Platos ejecutivos, ensaladas completas, pastas y café frente a la Ciudad Judicial y Parque Belgrano.',
            ],
            [
                'seller_email' => 'seller16@gmail.com',
                'seller_name' => 'Camila',
                'name' => 'Almacén & Fiambrería Pereyra Rozas',
                'address' => 'Av. Robustiano Patrón Costas 1200, Barrio Pereyra Rozas, Salta Capital',
                'latitude' => -24.74900000,
                'longitude' => -65.40300000,
                'establishment_type_id' => 3, // Supermercado
                'phone' => '3874391122',
                'description' => 'Quesos y fiambres seleccionados, panadería artesanal, lácteos y productos frescos de almacén.',
            ],
            [
                'seller_email' => 'seller17@gmail.com',
                'seller_name' => 'Federico',
                'name' => 'Café Universitario Chachapoyas',
                'address' => 'Campus Castañares (UCASAL), Salta Capital',
                'latitude' => -24.75500000,
                'longitude' => -65.39400000,
                'establishment_type_id' => 2, // Cafetería
                'phone' => '3874268800',
                'description' => 'Punto de encuentro universitario con café tostado, submarinos, tostados triples y medialunas.',
            ],
            [
                'seller_email' => 'seller18@gmail.com',
                'seller_name' => 'Luciana',
                'name' => 'La Pérgola de Tres Cerritos Restó',
                'address' => 'Los Molles 230, Tres Cerritos, Salta Capital',
                'latitude' => -24.76400000,
                'longitude' => -65.40200000,
                'establishment_type_id' => 1, // Restaurante
                'phone' => '3874398877',
                'description' => 'Cocina gourmet, risottos, carnes a punto y tablas de picadas en la parte alta de Tres Cerritos.',
            ],
            [
                'seller_email' => 'seller19@gmail.com',
                'seller_name' => 'Matías',
                'name' => 'Parrilla & Minutas Don Vicente',
                'address' => 'Mitre 1680, Barrio Vicente Solá, Salta Capital',
                'latitude' => -24.77350000,
                'longitude' => -65.40850000,
                'establishment_type_id' => 1, // Restaurante
                'phone' => '3874316655',
                'description' => 'Tradicional asado salteño, achuras, matambre a la pizza y empanadas jugosas al corte.',
            ],
            [
                'seller_email' => 'seller20@gmail.com',
                'seller_name' => 'Raúl',
                'name' => 'Almacén Criollo El Quirquincho',
                'address' => 'Av. Bolivia 5400 (Rotonda El Quirquincho), Salta Capital',
                'latitude' => -24.72300000,
                'longitude' => -65.41600000,
                'establishment_type_id' => 3, // Supermercado
                'phone' => '3874251188',
                'description' => 'Quesillos de cabra, dulces regionales, pan casero al horno de barro y productos de granja.',
            ],
        ];

        // 2. ESTABLECIMIENTOS PENDIENTES (PENDING) - Solicitudes para revisión de admin con paginación
        $pendingEstablishments = [
            [
                'seller_email' => 'pending_seller1@gmail.com',
                'seller_name' => 'Gonzalo',
                'seller_last_name' => 'Navarro',
                'name' => 'Parrilla La Querencia Salteña',
                'address' => 'Av. Belgrano 1350, Salta Capital',
                'latitude' => -24.78200000,
                'longitude' => -65.41700000,
                'establishment_type_id' => 1,
                'phone' => '3874219900',
                'description' => 'Especialidad en asado a la cruz, vacío y achuras tradicionales.',
                'google_place_id' => 'ChIJ_laquerencia_salta',
                'files' => [
                    ['category' => TrustScoreCriterion::FACADE_PHOTO, 'file_type' => 'jpg', 'status' => 'pending'],
                    ['category' => TrustScoreCriterion::STORE_COUNTER_PHOTO, 'file_type' => 'jpg', 'status' => 'pending'],
                    ['category' => TrustScoreCriterion::MUNICIPAL_PERMIT, 'file_type' => 'pdf', 'status' => 'pending'],
                ],
            ],
            [
                'seller_email' => 'pending_seller2@gmail.com',
                'seller_name' => 'Valentina',
                'seller_last_name' => 'Correa',
                'name' => 'Heladería Polar Salta Norte',
                'address' => 'Av. Constitución 890, Salta Capital',
                'latitude' => -24.76200000,
                'longitude' => -65.40900000,
                'establishment_type_id' => 2,
                'phone' => '3874391234',
                'description' => 'Cremas heladas artesanales, paletas y tortas heladas de elaboración diaria.',
                'google_place_id' => 'ChIJ_polar_salta_norte',
                'files' => [
                    ['category' => TrustScoreCriterion::FACADE_PHOTO, 'file_type' => 'jpg', 'status' => 'accepted', 'points' => 10],
                    ['category' => TrustScoreCriterion::STORE_COUNTER_PHOTO, 'file_type' => 'jpg', 'status' => 'accepted', 'points' => 10],
                    ['category' => TrustScoreCriterion::TAX_CERTIFICATE, 'file_type' => 'pdf', 'status' => 'pending'],
                ],
            ],
            [
                'seller_email' => 'pending_seller3@gmail.com',
                'seller_name' => 'Agustín',
                'seller_last_name' => 'Paredes',
                'name' => 'Sushi & Wok Mitre',
                'address' => 'Mitre 620, Salta Capital',
                'latitude' => -24.78650000,
                'longitude' => -65.41350000,
                'establishment_type_id' => 1,
                'phone' => '3874225566',
                'description' => 'Sushi roll fusión, woks de vegetales y carnes, gyozas y ramen.',
                'google_place_id' => 'ChIJ_sushi_wok_mitre',
                'files' => [
                    ['category' => TrustScoreCriterion::FACADE_PHOTO, 'file_type' => 'jpg', 'status' => 'pending'],
                    ['category' => TrustScoreCriterion::MUNICIPAL_TAX_RECEIPT, 'file_type' => 'pdf', 'status' => 'pending'],
                    ['category' => TrustScoreCriterion::OTHER, 'file_type' => 'pdf', 'status' => 'pending'],
                ],
            ],
            [
                'seller_email' => 'pending_seller4@gmail.com',
                'seller_name' => 'Julieta',
                'seller_last_name' => 'Gutiérrez',
                'name' => 'Trattoria Bella Italia',
                'address' => 'España 1105, Salta Capital',
                'latitude' => -24.78150000,
                'longitude' => -65.41900000,
                'establishment_type_id' => 1,
                'phone' => '3874318899',
                'description' => 'Auténtica pasta italiana rellena, lasagnas y salsas caseras.',
                'google_place_id' => null,
                'files' => [
                    ['category' => TrustScoreCriterion::FACADE_PHOTO, 'file_type' => 'jpg', 'status' => 'pending'],
                    ['category' => TrustScoreCriterion::STORE_COUNTER_PHOTO, 'file_type' => 'jpg', 'status' => 'pending'],
                ],
            ],
            [
                'seller_email' => 'pending_seller5@gmail.com',
                'seller_name' => 'Lucas',
                'seller_last_name' => 'Ferreyra',
                'name' => 'Cervecería Artesanal Güemes',
                'address' => 'Paseo Güemes 780, Salta Capital',
                'latitude' => -24.78400000,
                'longitude' => -65.40200000,
                'establishment_type_id' => 1,
                'phone' => '3874231122',
                'description' => 'Cervezas tiradas de origen local y tapeo variado: papas, nachos y burgers.',
                'google_place_id' => 'ChIJ_guemes_beer',
                'files' => [
                    ['category' => TrustScoreCriterion::FACADE_PHOTO, 'file_type' => 'jpg', 'status' => 'accepted', 'points' => 10],
                    ['category' => TrustScoreCriterion::MUNICIPAL_PERMIT, 'file_type' => 'pdf', 'status' => 'accepted', 'points' => 30],
                    ['category' => TrustScoreCriterion::PEST_CONTROL_CERTIFICATE, 'file_type' => 'pdf', 'status' => 'pending'],
                ],
            ],
            [
                'seller_email' => 'pending_seller6@gmail.com',
                'seller_name' => 'Carmen',
                'seller_last_name' => 'Flores',
                'name' => 'Empanadas Doña Carmen',
                'address' => 'San Juan 420, Salta Capital',
                'latitude' => -24.79300000,
                'longitude' => -65.41400000,
                'establishment_type_id' => 1,
                'phone' => '3874214433',
                'description' => 'Empanadas salteñas al horno de barro con masa casera y carne cortada a cuchillo.',
                'google_place_id' => null,
                'files' => [
                    ['category' => TrustScoreCriterion::FACADE_PHOTO, 'file_type' => 'jpg', 'status' => 'pending'],
                    ['category' => TrustScoreCriterion::TAX_CERTIFICATE, 'file_type' => 'pdf', 'status' => 'pending'],
                ],
            ],
            [
                'seller_email' => 'pending_seller7@gmail.com',
                'seller_name' => 'Marcos',
                'seller_last_name' => 'Benítez',
                'name' => 'Café y Medialunas San Bernardo',
                'address' => 'Av. San Martín 1500, Salta Capital',
                'latitude' => -24.79500000,
                'longitude' => -65.40200000,
                'establishment_type_id' => 2,
                'phone' => '3874226677',
                'description' => 'Cafetería al paso con medialunas recién horneadas y tostados de jamón cocido.',
                'google_place_id' => 'ChIJ_cafe_san_bernardo',
                'files' => [
                    ['category' => TrustScoreCriterion::FACADE_PHOTO, 'file_type' => 'jpg', 'status' => 'pending'],
                    ['category' => TrustScoreCriterion::STORE_COUNTER_PHOTO, 'file_type' => 'jpg', 'status' => 'pending'],
                    ['category' => TrustScoreCriterion::MUNICIPAL_PERMIT, 'file_type' => 'pdf', 'status' => 'pending'],
                ],
            ],
            [
                'seller_email' => 'pending_seller8@gmail.com',
                'seller_name' => 'Joaquín',
                'seller_last_name' => 'Salinas',
                'name' => 'Hamburguesería The Burger Club',
                'address' => 'Alvear 560, Salta Capital',
                'latitude' => -24.78000000,
                'longitude' => -65.41100000,
                'establishment_type_id' => 1,
                'phone' => '3874315544',
                'description' => 'Smash burgers dobles con queso cheddar americano y papas con panceta.',
                'google_place_id' => 'ChIJ_burger_club_salta',
                'files' => [
                    ['category' => TrustScoreCriterion::FACADE_PHOTO, 'file_type' => 'jpg', 'status' => 'accepted', 'points' => 10],
                    ['category' => TrustScoreCriterion::STORE_COUNTER_PHOTO, 'file_type' => 'jpg', 'status' => 'accepted', 'points' => 10],
                    ['category' => TrustScoreCriterion::TAX_CERTIFICATE, 'file_type' => 'pdf', 'status' => 'accepted', 'points' => 30],
                ],
            ],
            [
                'seller_email' => 'pending_seller9@gmail.com',
                'seller_name' => 'Patricia',
                'seller_last_name' => 'Arias',
                'name' => 'Panificadora San Martín',
                'address' => 'Pellegrini 310, Salta Capital',
                'latitude' => -24.79100000,
                'longitude' => -65.41800000,
                'establishment_type_id' => 2,
                'phone' => '3874228899',
                'description' => 'Pan francés, miñones, bizcochos de grasa y facturas variadas.',
                'google_place_id' => null,
                'files' => [
                    ['category' => TrustScoreCriterion::FACADE_PHOTO, 'file_type' => 'jpg', 'status' => 'pending'],
                    ['category' => TrustScoreCriterion::OTHER, 'file_type' => 'pdf', 'status' => 'pending'],
                ],
            ],
            [
                'seller_email' => 'pending_seller10@gmail.com',
                'seller_name' => 'Nahuel',
                'seller_last_name' => 'Quinteros',
                'name' => 'Restó Vegetariano Vida Sana',
                'address' => 'Urquiza 890, Salta Capital',
                'latitude' => -24.78700000,
                'longitude' => -65.41600000,
                'establishment_type_id' => 1,
                'phone' => '3874332211',
                'description' => 'Cocina vegetariana y vegana, tartas integrales, ensaladas completas y jugos naturales.',
                'google_place_id' => 'ChIJ_vida_sana_salta',
                'files' => [
                    ['category' => TrustScoreCriterion::FACADE_PHOTO, 'file_type' => 'jpg', 'status' => 'pending'],
                    ['category' => TrustScoreCriterion::MUNICIPAL_PERMIT, 'file_type' => 'pdf', 'status' => 'pending'],
                ],
            ],
            [
                'seller_email' => 'pending_seller11@gmail.com',
                'seller_name' => 'Tomás',
                'seller_last_name' => 'Cabrera',
                'name' => 'Asador Criollo Don Tomás',
                'address' => 'Av. Tavella 1800, Salta Capital',
                'latitude' => -24.80500000,
                'longitude' => -65.41200000,
                'establishment_type_id' => 1,
                'phone' => '3874261100',
                'description' => 'Parrillada abundante de campo con ensaladas caseras y pan casero.',
                'google_place_id' => null,
                'files' => [
                    ['category' => TrustScoreCriterion::FACADE_PHOTO, 'file_type' => 'jpg', 'status' => 'pending'],
                    ['category' => TrustScoreCriterion::STORE_COUNTER_PHOTO, 'file_type' => 'jpg', 'status' => 'pending'],
                    ['category' => TrustScoreCriterion::MUNICIPAL_PERMIT, 'file_type' => 'pdf', 'status' => 'pending'],
                ],
            ],
            [
                'seller_email' => 'pending_seller12@gmail.com',
                'seller_name' => 'Andrea',
                'seller_last_name' => 'Villalba',
                'name' => 'Fábrica de Sándwiches El Sol',
                'address' => 'Vicente López 540, Salta Capital',
                'latitude' => -24.78300000,
                'longitude' => -65.40800000,
                'establishment_type_id' => 2,
                'phone' => '3874229911',
                'description' => 'Sándwiches de miga triples especiales de jamón y queso, palmitos y roquefort.',
                'google_place_id' => 'ChIJ_sandwiches_el_sol',
                'files' => [
                    ['category' => TrustScoreCriterion::FACADE_PHOTO, 'file_type' => 'jpg', 'status' => 'pending'],
                    ['category' => TrustScoreCriterion::TAX_CERTIFICATE, 'file_type' => 'pdf', 'status' => 'pending'],
                ],
            ],
        ];

        // 3. ESTABLECIMIENTOS RECHAZADOS (REJECTED) - Solicitudes denegadas con notas de motivo
        $rejectedEstablishments = [
            [
                'seller_email' => 'rejected_seller1@gmail.com',
                'seller_name' => 'Bruno',
                'seller_last_name' => 'Giménez',
                'name' => 'Pizzería Fantasma Express',
                'address' => 'Calle Falsa 123, Salta Capital',
                'latitude' => -24.78000000,
                'longitude' => -65.41000000,
                'establishment_type_id' => 1,
                'phone' => '3874998877',
                'description' => 'Pizzas al molde.',
                'verification_notes' => 'Dirección inexistente constatada en verificación física. Local comercial ficticio.',
            ],
            [
                'seller_email' => 'rejected_seller2@gmail.com',
                'seller_name' => 'Silvia',
                'seller_last_name' => 'Montenegro',
                'name' => 'Rotisería El Rincón Salteño',
                'address' => 'Lerma 890, Salta Capital',
                'latitude' => -24.79400000,
                'longitude' => -65.41100000,
                'establishment_type_id' => 1,
                'phone' => '3874213322',
                'description' => 'Comidas rápidas y viandas diarias.',
                'verification_notes' => 'Habilitación municipal vencida hace más de 2 años y no renovada. Incumple requisitos bromatológicos.',
            ],
            [
                'seller_email' => 'rejected_seller3@gmail.com',
                'seller_name' => 'Daniel',
                'seller_last_name' => 'Figueroa',
                'name' => 'Cafetería Express Salta Centro',
                'address' => 'Mitre 210, Salta Capital',
                'latitude' => -24.78800000,
                'longitude' => -65.41200000,
                'establishment_type_id' => 2,
                'phone' => '3874312299',
                'description' => 'Café al paso y golosinas.',
                'verification_notes' => 'El solicitante no presentó documentación identificatoria tras múltiples intimaciones formales.',
            ],
            [
                'seller_email' => 'rejected_seller4@gmail.com',
                'seller_name' => 'Lorena',
                'seller_last_name' => 'Acosta',
                'name' => 'Granja y Distribuidora Los Andes',
                'address' => 'Av. Paraguay 2500, Salta Capital',
                'latitude' => -24.81000000,
                'longitude' => -65.42000000,
                'establishment_type_id' => 3,
                'phone' => '3874278899',
                'description' => 'Venta mayorista de carnes congeladas y derivados.',
                'verification_notes' => 'El rubro de faena mayorista no aplica al programa de rescate de excedentes minoristas de la plataforma.',
            ],
            [
                'seller_email' => 'rejected_seller5@gmail.com',
                'seller_name' => 'Enzo',
                'seller_last_name' => 'Romero',
                'name' => 'Bistró San Lorenzo Gourmet',
                'address' => 'Av. San Martín 320, Villa San Lorenzo',
                'latitude' => -24.73500000,
                'longitude' => -65.48500000,
                'establishment_type_id' => 1,
                'phone' => '3874921100',
                'description' => 'Cocina de montaña y picadas de campo.',
                'verification_notes' => 'Constancia de CUIT apócrifa rechazada en verificación con AFIP/ARCA.',
            ],
            [
                'seller_email' => 'rejected_seller6@gmail.com',
                'seller_name' => 'Clara',
                'seller_last_name' => 'Zárate',
                'name' => 'Pastas Caseras Don Juan',
                'address' => 'Ituzaingó 760, Salta Capital',
                'latitude' => -24.79200000,
                'longitude' => -65.41900000,
                'establishment_type_id' => 1,
                'phone' => '3874220011',
                'description' => 'Fábrica de pastas artesanales.',
                'verification_notes' => 'Solicitud cancelada y denegada formalmente a petición expresa del propio comerciante.',
            ],
        ];

        $adminUser = User::role('admin')->first() ?? User::first();

        // 1. SEMBRAR APROBADAS
        foreach ($approvedEstablishments as $data) {
            $user = User::where('email', $data['seller_email'])->first();
            if (! $user) {
                $user = User::factory()->withRole(UserRole::SELLER->value)->create([
                    'name' => $data['seller_name'],
                    'last_name' => 'Vendedor',
                    'email' => $data['seller_email'],
                    'state' => UserState::ACTIVE->value,
                    'password' => 12345678,
                ]);
            }

            $establishment = FoodEstablishment::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $data['name'],
                    'address' => $data['address'],
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'establishment_type_id' => $data['establishment_type_id'],
                    'phone' => $data['phone'],
                    'description' => $data['description'],
                    'verification_status' => 'approved',
                    'google_place_id' => 'ChIJ_approved_'.$user->id,
                ]
            );

            // Documentos verificados y trust log para que figure score verde
            EstablishmentTrustLog::firstOrCreate(
                [
                    'food_establishment_id' => $establishment->id,
                    'source_type' => TrustScoreCriterion::TAX_CERTIFICATE->value,
                ],
                [
                    'points' => 30,
                    'verified_by_user_id' => $adminUser?->id,
                    'is_active' => true,
                    'metadata' => ['notes' => 'CUIT verificado automáticamente por seeder'],
                ]
            );

            EstablishmentTrustLog::firstOrCreate(
                [
                    'food_establishment_id' => $establishment->id,
                    'source_type' => TrustScoreCriterion::MUNICIPAL_PERMIT->value,
                ],
                [
                    'points' => 30,
                    'verified_by_user_id' => $adminUser?->id,
                    'is_active' => true,
                    'metadata' => ['notes' => 'Habilitación municipal validada'],
                ]
            );

            EstablishmentTrustLog::firstOrCreate(
                [
                    'food_establishment_id' => $establishment->id,
                    'source_type' => TrustScoreCriterion::GOOGLE_PLACES_PROFILE->value,
                ],
                [
                    'points' => 20,
                    'verified_by_user_id' => $adminUser?->id,
                    'is_active' => true,
                    'metadata' => ['notes' => 'Local en Google Maps verificado'],
                ]
            );
        }

        // 2. SEMBRAR PENDIENTES
        foreach ($pendingEstablishments as $data) {
            $user = User::where('email', $data['seller_email'])->first();
            if (! $user) {
                $user = User::factory()->withRole(UserRole::SELLER->value)->create([
                    'name' => $data['seller_name'],
                    'last_name' => $data['seller_last_name'],
                    'email' => $data['seller_email'],
                    'state' => UserState::WAITING_FOR_CONFIRMATION->value,
                    'password' => 12345678,
                ]);
            }

            $establishment = FoodEstablishment::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $data['name'],
                    'address' => $data['address'],
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'establishment_type_id' => $data['establishment_type_id'],
                    'phone' => $data['phone'],
                    'description' => $data['description'],
                    'verification_status' => 'pending',
                    'google_place_id' => $data['google_place_id'] ?? null,
                ]
            );

            // Crear archivos de verificación y archivos físicos placeholder
            if (! empty($data['files'])) {
                foreach ($data['files'] as $fileData) {
                    $category = $fileData['category'];
                    $criterion = $category instanceof TrustScoreCriterion ? $category : TrustScoreCriterion::from($category);
                    $fileType = $fileData['file_type'] ?? 'jpg';
                    $relativePath = 'establishment_verification/'.$establishment->id.'/seed_'.$criterion->value.'.'.$fileType;

                    $file = EstablishmentVerificationFile::firstOrCreate(
                        [
                            'food_establishment_id' => $establishment->id,
                            'document_category' => $criterion,
                        ],
                        [
                            'file_type' => $fileType,
                            'file_path' => $relativePath,
                            'status' => $fileData['status'] ?? 'pending',
                            'reviewed_at' => ($fileData['status'] ?? '') === 'accepted' ? now() : null,
                            'reviewed_by' => ($fileData['status'] ?? '') === 'accepted' ? $adminUser?->id : null,
                        ]
                    );

                    $this->ensurePlaceholderFileExists($relativePath, $fileType);

                    // Si ya fue aceptado, registrar log de puntos
                    if (($fileData['status'] ?? '') === 'accepted') {
                        $pts = $fileData['points'] ?? $criterion->defaultPoints();
                        EstablishmentTrustLog::firstOrCreate(
                            [
                                'food_establishment_id' => $establishment->id,
                                'source_type' => $criterion->value,
                            ],
                            [
                                'points' => $pts,
                                'verified_by_user_id' => $adminUser?->id,
                                'is_active' => true,
                                'metadata' => ['file_id' => $file->id],
                            ]
                        );
                    }
                }
            }
        }

        // 3. SEMBRAR RECHAZADAS
        foreach ($rejectedEstablishments as $data) {
            $user = User::where('email', $data['seller_email'])->first();
            if (! $user) {
                $user = User::factory()->withRole(UserRole::SELLER->value)->create([
                    'name' => $data['seller_name'],
                    'last_name' => $data['seller_last_name'],
                    'email' => $data['seller_email'],
                    'state' => UserState::REGISTERING->value,
                    'password' => 12345678,
                ]);
            }

            $establishment = FoodEstablishment::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $data['name'],
                    'address' => $data['address'],
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'establishment_type_id' => $data['establishment_type_id'],
                    'phone' => $data['phone'],
                    'description' => $data['description'],
                    'verification_status' => 'rejected',
                    'verification_notes' => $data['verification_notes'],
                ]
            );

            $relativePath = 'establishment_verification/'.$establishment->id.'/seed_rejected_facade.jpg';

            EstablishmentVerificationFile::firstOrCreate(
                [
                    'food_establishment_id' => $establishment->id,
                    'document_category' => TrustScoreCriterion::FACADE_PHOTO,
                ],
                [
                    'file_type' => 'jpg',
                    'file_path' => $relativePath,
                    'status' => 'rejected',
                    'rejection_reason' => $data['verification_notes'],
                    'reviewed_at' => now(),
                    'reviewed_by' => $adminUser?->id,
                ]
            );

            $this->ensurePlaceholderFileExists($relativePath, 'jpg');
        }
    }

    /**
     * Asegura la existencia de un archivo físico placeholder en private storage para servir sin 404
     */
    private function ensurePlaceholderFileExists(string $relativePath, string $fileType): void
    {
        $fullPath = storage_path('app/private/'.$relativePath);
        $directory = dirname($fullPath);

        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        if (! file_exists($fullPath)) {
            if ($fileType === 'pdf') {
                file_put_contents(
                    $fullPath,
                    "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 300 144]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f \n0000000010 00000 n \n0000000060 00000 n \n0000000118 00000 n \ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n212\n%%EOF\n"
                );
            } else {
                file_put_contents(
                    $fullPath,
                    base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=')
                );
            }
        }
    }
}
