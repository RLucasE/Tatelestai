<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserState;
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
        $establishments = [
            [
                'seller_email' => 'seller@gmail.com',
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
            // Locales adicionales en Zona Norte de Salta Capital
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

        foreach ($establishments as $data) {
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

            FoodEstablishment::updateOrCreate(
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
                ]
            );
        }
    }
}
