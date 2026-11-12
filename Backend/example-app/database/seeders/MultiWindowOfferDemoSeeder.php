<?php

namespace Database\Seeders;

use App\Enums\ClaimReason;
use App\Enums\ClaimStatus;
use App\Enums\OfferState;
use App\Enums\PaymentStatus;
use App\Enums\SellState;
use App\Enums\TrustScoreCriterion;
use App\Enums\UserRole;
use App\Enums\UserState;
use App\Models\EstablishmentTrustLog;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\PackTemplate;
use App\Models\PurchaseClaim;
use App\Models\Sell;
use App\Models\SellDetail;
use App\Models\User;
use App\Models\UserCart;
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
        $seededEstablishments = [];
        $seededOffersByEst = [];

        foreach ($establishmentsData as $estIndex => $data) {
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

            $seededEstablishments[$estIndex] = $establishment;
            $seededOffersByEst[$estIndex] = [];

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
                    $seededOffersByEst[$estIndex][] = $offer;
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

        $this->seedDemoPurchasesAndClaims($seededEstablishments, $seededOffersByEst, $adminUser, $baseTime);

        try {
            Offer::makeAllSearchable();
        } catch (\Throwable $e) {
            $this->command?->warn('No se pudo sincronizar con Typesense: '.$e->getMessage());
        }

        $this->command?->info("MultiWindowOfferDemoSeeder: 2 establecimientos y {$totalOffers} ofertas en múltiples horarios creados exitosamente.");
        $this->command?->info('Credenciales Vendedor 1 (3 turnos): multiturno1@gmail.com / 12345678');
        $this->command?->info('Credenciales Vendedor 2 (2 turnos): multiturno2@gmail.com / 12345678');
    }

    /**
     * Siembra compras activas, retiradas elegibles para reclamo y reclamos de ejemplo de forma idempotente.
     *
     * @param  array<int, FoodEstablishment>  $establishments
     * @param  array<int, array<int, Offer>>  $offersByEst
     */
    private function seedDemoPurchasesAndClaims(
        array $establishments,
        array $offersByEst,
        ?User $adminUser,
        \Carbon\Carbon $baseTime
    ): void {
        $primaryCustomer = User::firstOrCreate(
            ['email' => 'lucascabjnmro2@gmail.com'],
            [
                'name' => 'Lucas',
                'last_name' => 'Ricalde',
                'state' => UserState::ACTIVE->value,
                'password' => '12345678',
                'email_verified_at' => $baseTime,
            ]
        );
        if (! $primaryCustomer->hasRole(UserRole::CUSTOMER->value)) {
            $primaryCustomer->assignRole(UserRole::CUSTOMER->value);
        }
        UserCart::firstOrCreate(['user_id' => $primaryCustomer->id]);

        $secondaryCustomer = User::firstOrCreate(
            ['email' => 'cliente.demo2@gmail.com'],
            [
                'name' => 'Sofía',
                'last_name' => 'Méndez',
                'state' => UserState::ACTIVE->value,
                'password' => '12345678',
                'email_verified_at' => $baseTime,
            ]
        );
        if (! $secondaryCustomer->hasRole(UserRole::CUSTOMER->value)) {
            $secondaryCustomer->assignRole(UserRole::CUSTOMER->value);
        }
        UserCart::firstOrCreate(['user_id' => $secondaryCustomer->id]);

        $est1 = $establishments[0];
        $est2 = $establishments[1];
        $est1Offers = $offersByEst[0];
        $est2Offers = $offersByEst[1];

        $commissionPct = (float) config('services.mercadopago.commission_percentage', 15.0);

        $createDemoSell = function (array $config) use ($commissionPct): Sell {
            $grossAmount = 0.0;
            foreach ($config['items'] as $item) {
                $grossAmount += (int) $item['quantity'] * (float) $item['offer']->price;
            }
            $grossAmount = round($grossAmount, 2);
            $commissionAmount = round($grossAmount * ($commissionPct / 100), 2);
            $netAmount = round($grossAmount - $commissionAmount, 2);

            $sell = Sell::updateOrCreate(
                ['pickup_code' => $config['pickup_code']],
                [
                    'bought_by' => $config['customer']->id,
                    'sold_by' => $config['establishment']->id,
                    'state' => $config['state']->value,
                    'payment_status' => $config['payment_status']->value,
                    'payment_method' => 'mercadopago',
                    'payment_id' => 'MP-'.$config['pickup_code'],
                    'preference_id' => 'PREF-'.$config['pickup_code'],
                    'gross_amount' => $grossAmount,
                    'commission_percentage' => $commissionPct,
                    'commission_amount' => $commissionAmount,
                    'net_amount' => $netAmount,
                    'refunded_amount' => $config['refunded_amount'] ?? 0,
                    'refunded_at' => $config['refunded_at'] ?? null,
                    'is_picked_up' => $config['is_picked_up'],
                    'picked_up_at' => $config['picked_up_at'] ?? null,
                    'max_pickup_datetime' => $config['max_pickup_datetime'],
                    'created_at' => $config['created_at'],
                    'updated_at' => $config['picked_up_at'] ?? $config['created_at'],
                ]
            );

            $sell->sellDetails()->delete();
            foreach ($config['items'] as $item) {
                /** @var Offer $offer */
                $offer = $item['offer'];
                SellDetail::create([
                    'sell_id' => $sell->id,
                    'offer_id' => $offer->id,
                    'offer_quantity' => $item['quantity'],
                    'pack_price' => $offer->price,
                    'pack_name' => $offer->title,
                    'pack_description' => $offer->description,
                    'allergens' => $offer->allergens,
                    'estimated_weight_kg' => $offer->estimated_weight_kg,
                    'minimum_value' => $offer->minimum_value,
                ]);
            }

            return $sell;
        };

        // 1. Compra activa READY para retirar hoy
        $createDemoSell([
            'pickup_code' => 'DEMO-MW-001',
            'customer' => $primaryCustomer,
            'establishment' => $est1,
            'state' => SellState::READY,
            'payment_status' => PaymentStatus::APPROVED,
            'is_picked_up' => false,
            'max_pickup_datetime' => $est1Offers[0]->expiration_datetime,
            'created_at' => $baseTime->copy()->subMinutes(20),
            'items' => [
                ['offer' => $est1Offers[0], 'quantity' => 1],
            ],
        ]);

        // 2. Compra activa CONFIRMED para turno posterior
        $createDemoSell([
            'pickup_code' => 'DEMO-MW-002',
            'customer' => $primaryCustomer,
            'establishment' => $est2,
            'state' => SellState::CONFIRMED,
            'payment_status' => PaymentStatus::APPROVED,
            'is_picked_up' => false,
            'max_pickup_datetime' => $est2Offers[1]->expiration_datetime,
            'created_at' => $baseTime->copy()->subMinutes(10),
            'items' => [
                ['offer' => $est2Offers[1], 'quantity' => 1],
            ],
        ]);

        // 3. Compra retirada hace 1 día (elegible para reclamo: can_claim = true)
        $pickedUpAt1 = $baseTime->copy()->subDay();
        $sellPicked1 = $createDemoSell([
            'pickup_code' => 'DEMO-MW-003',
            'customer' => $primaryCustomer,
            'establishment' => $est1,
            'state' => SellState::PICKED_UP,
            'payment_status' => PaymentStatus::APPROVED,
            'is_picked_up' => true,
            'picked_up_at' => $pickedUpAt1,
            'max_pickup_datetime' => $pickedUpAt1->copy()->addHour(),
            'created_at' => $pickedUpAt1->copy()->subHours(2),
            'items' => [
                ['offer' => $est1Offers[0], 'quantity' => 1],
                ['offer' => $est1Offers[2], 'quantity' => 1],
            ],
        ]);
        PurchaseClaim::where('sell_id', $sellPicked1->id)->delete();

        // 4. Compra retirada hace 3 días en comercio 2 (elegible para reclamo: can_claim = true)
        $pickedUpAt2 = $baseTime->copy()->subDays(3);
        $sellPicked2 = $createDemoSell([
            'pickup_code' => 'DEMO-MW-004',
            'customer' => $primaryCustomer,
            'establishment' => $est2,
            'state' => SellState::PICKED_UP,
            'payment_status' => PaymentStatus::APPROVED,
            'is_picked_up' => true,
            'picked_up_at' => $pickedUpAt2,
            'max_pickup_datetime' => $pickedUpAt2->copy()->addHour(),
            'created_at' => $pickedUpAt2->copy()->subHours(2),
            'items' => [
                ['offer' => $est2Offers[0], 'quantity' => 1],
            ],
        ]);
        PurchaseClaim::where('sell_id', $sellPicked2->id)->delete();

        // 5. Compra retirada hace 5 días con un reclamo PENDING (visible para cliente y para evaluar en admin)
        $pickedUpAt3 = $baseTime->copy()->subDays(5);
        $sellWithPendingClaim = $createDemoSell([
            'pickup_code' => 'DEMO-MW-005',
            'customer' => $primaryCustomer,
            'establishment' => $est1,
            'state' => SellState::PICKED_UP,
            'payment_status' => PaymentStatus::APPROVED,
            'is_picked_up' => true,
            'picked_up_at' => $pickedUpAt3,
            'max_pickup_datetime' => $pickedUpAt3->copy()->addHour(),
            'created_at' => $pickedUpAt3->copy()->subHours(2),
            'items' => [
                ['offer' => $est1Offers[3], 'quantity' => 1],
            ],
        ]);
        PurchaseClaim::updateOrCreate(
            ['sell_id' => $sellWithPendingClaim->id],
            [
                'customer_id' => $primaryCustomer->id,
                'food_establishment_id' => $est1->id,
                'reason' => ClaimReason::MISSING_ITEMS->value,
                'description' => 'Faltaban varios panificados respecto al peso estimado informado en el pack sorpresa.',
                'status' => ClaimStatus::PENDING->value,
                'was_picked_up_at_claim' => true,
                'refund_amount' => null,
                'gateway_refund_id' => null,
                'refunded_at' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'resolution_notes' => null,
                'is_abusive' => false,
            ]
        );

        // 6. Compra del cliente secundario con reclamo APPROVED (reembolso parcial) para historial de admin
        $pickedUpAt4 = $baseTime->copy()->subDays(6);
        $partialRefundAmount = round((float) $est2Offers[2]->price * 0.5, 2);
        $sellWithApprovedClaim = $createDemoSell([
            'pickup_code' => 'DEMO-MW-006',
            'customer' => $secondaryCustomer,
            'establishment' => $est2,
            'state' => SellState::PICKED_UP,
            'payment_status' => PaymentStatus::PARTIALLY_REFUNDED,
            'refunded_amount' => $partialRefundAmount,
            'refunded_at' => $baseTime->copy()->subDays(2),
            'is_picked_up' => true,
            'picked_up_at' => $pickedUpAt4,
            'max_pickup_datetime' => $pickedUpAt4->copy()->addHour(),
            'created_at' => $pickedUpAt4->copy()->subHours(2),
            'items' => [
                ['offer' => $est2Offers[2], 'quantity' => 1],
            ],
        ]);
        PurchaseClaim::updateOrCreate(
            ['sell_id' => $sellWithApprovedClaim->id],
            [
                'customer_id' => $secondaryCustomer->id,
                'food_establishment_id' => $est2->id,
                'reason' => ClaimReason::NOT_AS_DESCRIBED->value,
                'description' => 'El box incluía solo guarniciones y no el producto principal publicado.',
                'status' => ClaimStatus::APPROVED->value,
                'was_picked_up_at_claim' => true,
                'refund_amount' => $partialRefundAmount,
                'gateway_refund_id' => 'REF-DEMO-MW-006',
                'refunded_at' => $baseTime->copy()->subDays(2),
                'reviewed_by' => $adminUser?->id,
                'reviewed_at' => $baseTime->copy()->subDays(2),
                'resolution_notes' => 'Reembolso parcial del 50% aprobado por diferencia significativa en el contenido del pack.',
                'is_abusive' => false,
            ]
        );
    }
}
