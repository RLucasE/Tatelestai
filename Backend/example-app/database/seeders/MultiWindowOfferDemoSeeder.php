<?php

namespace Database\Seeders;

use App\Enums\OfferState;
use App\Enums\TrustScoreCriterion;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentTrustLog;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\PackTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class MultiWindowOfferDemoSeeder extends Seeder
{
    /**
     * Sembrador dedicado para probar ofertas activas con múltiples franjas horarias
     * de retiro en un máximo de 2 establecimientos aprobados.
     */
    public function run(): void
    {
        // Asegurar que existan los roles básicos si se corre en limpio
        foreach ([UserRole::DEFAULT, UserRole::CUSTOMER, UserRole::SELLER, UserRole::ADMIN] as $role) {
            Role::firstOrCreate(['name' => $role->value]);
        }

        // Asegurar que existan los tipos de establecimiento necesarios
        $bakeryType = EstablishmentType::firstOrCreate(
            ['name' => 'Cafetería'],
            [
                'slug' => 'cafetería',
                'description' => 'Un establecimiento que sirve café y otros alimentos ligeros.',
            ]
        );

        $restaurantType = EstablishmentType::firstOrCreate(
            ['name' => 'Restaurante'],
            [
                'slug' => 'restaurante',
                'description' => 'Un lugar donde se sirven comidas y bebidas.',
            ]
        );

        $adminUser = User::role(UserRole::ADMIN->value)->first();

        $establishmentsData = [
            [
                'seller' => [
                    'name' => 'Martina',
                    'last_name' => 'Peralta',
                    'email' => 'multiturno1@gmail.com',
                    'password' => '12345678',
                ],
                'establishment' => [
                    'name' => 'La Boulangerie Multiturno (Demo Horarios)',
                    'address' => 'Av. Belgrano 620, Salta Capital',
                    'latitude' => -24.78720000,
                    'longitude' => -65.41180000,
                    'establishment_type_id' => $bakeryType->id,
                    'phone' => '3874551101',
                    'description' => 'Panadería y pastelería artesanal de prueba con 3 franjas horarias de retiro distintas para validar agrupación en carrito y selector de horarios del vendedor.',
                ],
                'templates' => [
                    [
                        'title' => 'Pack Desayuno & Medialunas (Turno Mañana)',
                        'description' => 'Croissants de manteca, medialunas recién horneadas y panes de masa madre del primer horneado.',
                        'allergens' => ['gluten', 'lácteos', 'huevo'],
                        'estimated_weight_kg' => 0.90,
                        'offers' => [
                            [
                                'title' => 'Pack Desayuno & Medialunas (Turno 1)',
                                'price' => 2200,
                                'minimum_value' => 5500,
                                'quantity' => 5,
                                'start_offset_minutes' => 30,
                                'end_offset_minutes' => 120,
                            ],
                            [
                                'title' => 'Selección Panes de Masa Madre (Turno 1)',
                                'price' => 2500,
                                'minimum_value' => 6000,
                                'quantity' => 4,
                                'start_offset_minutes' => 30,
                                'end_offset_minutes' => 120,
                            ],
                        ],
                    ],
                    [
                        'title' => 'Sweet Box Merienda & Pastelería (Turno Tarde)',
                        'description' => 'Porciones de torta, muffins artesanales, alfajores de maicena y budines para la merienda.',
                        'allergens' => ['gluten', 'lácteos', 'huevo', 'frutos secos'],
                        'estimated_weight_kg' => 1.10,
                        'offers' => [
                            [
                                'title' => 'Sweet Box Merienda & Pastelería (Turno 2)',
                                'price' => 2900,
                                'minimum_value' => 7200,
                                'quantity' => 6,
                                'start_offset_minutes' => 180,
                                'end_offset_minutes' => 300,
                            ],
                        ],
                    ],
                    [
                        'title' => 'Bolsa Sorpresa Cierre de Panadería (Turno Noche)',
                        'description' => 'Surtido abundante de panificados salados, focaccias, chipás y facturas del cierre del día.',
                        'allergens' => ['gluten', 'lácteos'],
                        'estimated_weight_kg' => 1.50,
                        'offers' => [
                            [
                                'title' => 'Bolsa Sorpresa Cierre de Panadería (Turno 3)',
                                'price' => 3200,
                                'minimum_value' => 8000,
                                'quantity' => 5,
                                'start_offset_minutes' => 360,
                                'end_offset_minutes' => 480,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'seller' => [
                    'name' => 'Santiago',
                    'last_name' => 'Alvarado',
                    'email' => 'multiturno2@gmail.com',
                    'password' => '12345678',
                ],
                'establishment' => [
                    'name' => 'Bistró del Paseo (Demo 2 Turnos)',
                    'address' => 'Paseo Güemes 315, Salta Capital',
                    'latitude' => -24.78590000,
                    'longitude' => -65.40580000,
                    'establishment_type_id' => $restaurantType->id,
                    'phone' => '3874552202',
                    'description' => 'Cocina de autor y rotisería gourmet con dos ventanas de retiro diferenciadas (almuerzo/tarde y cena).',
                ],
                'templates' => [
                    [
                        'title' => 'Vianda Ejecutiva Mediodía & Tarde',
                        'description' => 'Plato principal del día con guarnición saludable: carnes braseadas, pastas caseras o tartas gourmet.',
                        'allergens' => ['gluten', 'lácteos'],
                        'estimated_weight_kg' => 1.25,
                        'offers' => [
                            [
                                'title' => 'Vianda Ejecutiva Gourmet (Turno A)',
                                'price' => 3500,
                                'minimum_value' => 8500,
                                'quantity' => 5,
                                'start_offset_minutes' => 45,
                                'end_offset_minutes' => 150,
                            ],
                        ],
                    ],
                    [
                        'title' => 'Pack Cena & Pizzas a la Piedra',
                        'description' => 'Selección nocturna de pizzas artesanales al horno de barro, empanadas cortadas a cuchillo y focaccia.',
                        'allergens' => ['gluten', 'lácteos'],
                        'estimated_weight_kg' => 1.60,
                        'offers' => [
                            [
                                'title' => 'Pack Cena & Pizzas a la Piedra (Turno B)',
                                'price' => 4200,
                                'minimum_value' => 10500,
                                'quantity' => 4,
                                'start_offset_minutes' => 240,
                                'end_offset_minutes' => 360,
                            ],
                            [
                                'title' => 'Box Empanadas & Picada Caliente (Turno B)',
                                'price' => 3800,
                                'minimum_value' => 9500,
                                'quantity' => 3,
                                'start_offset_minutes' => 240,
                                'end_offset_minutes' => 360,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $baseTime = now()->startOfMinute();
        $totalOffers = 0;

        foreach ($establishmentsData as $data) {
            $seller = User::updateOrCreate(
                ['email' => $data['seller']['email']],
                [
                    'name' => $data['seller']['name'],
                    'last_name' => $data['seller']['last_name'],
                    'state' => UserState::ACTIVE->value,
                    'password' => $data['seller']['password'],
                    'email_verified_at' => now(),
                ]
            );

            if (! $seller->hasRole(UserRole::SELLER->value)) {
                $seller->assignRole(UserRole::SELLER->value);
            }

            $establishment = FoodEstablishment::updateOrCreate(
                ['user_id' => $seller->id],
                [
                    'name' => $data['establishment']['name'],
                    'address' => $data['establishment']['address'],
                    'latitude' => $data['establishment']['latitude'],
                    'longitude' => $data['establishment']['longitude'],
                    'establishment_type_id' => $data['establishment']['establishment_type_id'],
                    'phone' => $data['establishment']['phone'],
                    'description' => $data['establishment']['description'],
                    'verification_status' => 'approved',
                    'google_place_id' => 'ChIJ_multiturno_'.$seller->id,
                ]
            );

            // Asegurar nivel de confianza verde (80 pts)
            foreach ([
                [TrustScoreCriterion::TAX_CERTIFICATE, 30],
                [TrustScoreCriterion::MUNICIPAL_PERMIT, 30],
                [TrustScoreCriterion::GOOGLE_PLACES_PROFILE, 20],
            ] as [$criterion, $points]) {
                EstablishmentTrustLog::firstOrCreate(
                    [
                        'food_establishment_id' => $establishment->id,
                        'source_type' => $criterion->value,
                    ],
                    [
                        'points' => $points,
                        'verified_by_user_id' => $adminUser?->id,
                        'is_active' => true,
                        'metadata' => ['notes' => 'Verificado por MultiWindowOfferDemoSeeder'],
                    ]
                );
            }

            $seededOfferIds = [];

            foreach ($data['templates'] as $templateData) {
                $template = PackTemplate::updateOrCreate(
                    [
                        'food_establishment_id' => $establishment->id,
                        'title' => $templateData['title'],
                    ],
                    [
                        'description' => $templateData['description'],
                        'allergens' => $templateData['allergens'],
                        'estimated_weight_kg' => $templateData['estimated_weight_kg'],
                    ]
                );

                foreach ($templateData['offers'] as $offerData) {
                    $pickupStart = (clone $baseTime)->addMinutes($offerData['start_offset_minutes']);
                    $pickupEnd = (clone $baseTime)->addMinutes($offerData['end_offset_minutes']);

                    $offer = Offer::withTrashed()->updateOrCreate(
                        [
                            'food_establishment_id' => $establishment->id,
                            'title' => $offerData['title'],
                        ],
                        [
                            'pack_template_id' => $template->id,
                            'description' => $template->description,
                            'allergens' => $template->allergens,
                            'estimated_weight_kg' => $template->estimated_weight_kg,
                            'price' => $offerData['price'],
                            'minimum_value' => $offerData['minimum_value'],
                            'quantity' => $offerData['quantity'],
                            'pickup_start_datetime' => $pickupStart,
                            'visible_from' => $baseTime->copy()->subMinutes(5),
                            'expiration_datetime' => $pickupEnd,
                            'state' => OfferState::ACTIVE->value,
                            'deleted_at' => null,
                        ]
                    );

                    $seededOfferIds[] = $offer->id;
                    $totalOffers++;
                }
            }

            // Desactivar y archivar (soft delete) ofertas previas de este local demo que ya no formen parte del set
            Offer::where('food_establishment_id', $establishment->id)
                ->whereNotIn('id', $seededOfferIds)
                ->update(['state' => OfferState::INACTIVE->value]);

            Offer::where('food_establishment_id', $establishment->id)
                ->whereNotIn('id', $seededOfferIds)
                ->delete();
        }

        try {
            Offer::makeAllSearchable();
        } catch (\Throwable $e) {
            $this->command?->warn('No se pudo sincronizar con Typesense: '.$e->getMessage());
        }

        $this->command?->info("MultiWindowOfferDemoSeeder: 2 establecimientos y {$totalOffers} ofertas en múltiples horarios creados exitosamente.");
        $this->command?->info('Credenciales Vendedor 1 (3 turnos): multiturno1@gmail.com / 12345678');
        $this->command?->info('Credenciales Vendedor 2 (2 turnos): multiturno2@gmail.com / 12345678');
    }
}
