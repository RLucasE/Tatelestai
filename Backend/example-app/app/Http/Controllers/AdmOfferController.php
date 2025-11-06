<?php

namespace App\Http\Controllers;

use App\Actions\Offers\ChangeOfferStatusAction;
use App\Enums\UserRole;
use App\Exceptions\Offer\OfferStatusChangeException;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdmOfferController extends Controller
{
    public function __construct()
    {
    }
    public function index(): JsonResponse
    {
        try {
            // Obtener todas las ofertas con sus relaciones
            $offers = Offer::with([
                'foodEstablishment:id,name,user_id',
                'products:id,name,description',
            ])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

            return response()->json([
                'data' => $offers,
                'message' => 'Ofertas obtenidas exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las ofertas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request,int $id, ChangeOfferStatusAction $changeOfferStatusAction): JsonResponse
    {
        if (!is_numeric($id)) {
            return response()->json(['message' => 'ID inválido'], 422);
        }
        try {
            $offer = $changeOfferStatusAction->execute($id, $request->input('state'));
        }catch (OfferStatusChangeException $e){
            return response()->json([
                'message' => $e->getMessage(),
                'error' => $e->context()
            ], $e->getCode());
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cambiar el estado de la oferta',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'message' => 'Estado de la oferta actualizado correctamente',
            'data' => [
                'id' => $offer->id,
                'state' => $offer->state
            ]
        ]);
    }
    public function indexByUser(string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        if(!$user || !$user->hasRole(UserRole::SELLER->value)){
            return response()->json(['message' => 'Usuario no valido para esta operación'], 404);
        }
        try {
            $offers = Offer::with('products:id,name,description')
                ->whereHas('foodEstablishment', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();
            return response()->json([
                'data' => $offers,
                'message' => 'Ofertas del usuario obtenidas exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las ofertas del usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function offerStats(): JsonResponse
    {
        try {
            $stats = Offer::where('state', \App\Enums\OfferState::ACTIVE->value)
                ->where('created_at', '>=', now()->subDays(7))
                ->with('foodEstablishment.establishmentType')
                ->get()
                ->groupBy(function ($offer) {
                    return $offer->foodEstablishment->establishmentType->name;
                })
                ->map(function ($group, $establishmentType) {
                    return [
                        'establishment_type' => $establishmentType,
                        'count' => $group->count()
                    ];
                })
                ->values();

            return response()->json([
                'message' => 'Estadísticas de ofertas obtenidas exitosamente',
                'data' => $stats
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las estadísticas de ofertas', 'error' => $e->getMessage()
            ], 500);
        }
    }

    public function activeOffersCount(): JsonResponse
    {
        try {
            $stats = Offer::where('state', \App\Enums\OfferState::ACTIVE->value)
                ->where('created_at', '>=', now()->subDays(7))
                ->with('foodEstablishment.establishmentType')
                ->get()
                ->groupBy(function ($offer) {
                    return $offer->foodEstablishment->establishmentType->name;
                })
                ->map(function ($group, $establishmentType) {
                    return [
                        'establishment_type' => $establishmentType,
                        'count' => $group->count()
                    ];
                })
                ->values();

            return response()->json([
                'message' => 'Cantidad de ofertas activas obtenidas exitosamente',
                'data' => $stats
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener la cantidad de ofertas activas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function expiringOffersCount(): JsonResponse
    {
        try {
            $today = now()->startOfDay();
            $nextWeek = now()->addDays(7)->endOfDay();

            // Obtener todas las ofertas activas con sus productos
            $offers = Offer::where('state', \App\Enums\OfferState::ACTIVE->value)
                ->with(['productOffer' => function($query) use ($today, $nextWeek) {
                    $query->whereBetween('expiration_date', [$today, $nextWeek]);
                }])
                ->get();

            // Agrupar por día de la semana
            $daysOfWeek = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
            $stats = [];

            foreach ($daysOfWeek as $index => $day) {
                $stats[] = [
                    'day' => $day,
                    'count' => 0
                ];
            }

            // Contar ofertas por día
            foreach ($offers as $offer) {
                foreach ($offer->productOffer as $productOffer) {
                    if ($productOffer->expiration_date) {
                        $expirationDate = \Carbon\Carbon::parse($productOffer->expiration_date);
                        if ($expirationDate->between($today, $nextWeek)) {
                            // Carbon usa 1=Lunes, 7=Domingo, ajustamos para nuestro array (0-6)
                            $dayIndex = $expirationDate->dayOfWeekIso - 1;
                            $stats[$dayIndex]['count']++;
                        }
                    }
                }
            }

            return response()->json([
                'message' => 'Ofertas que expiran esta semana obtenidas exitosamente',
                'data' => $stats
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las ofertas que expiran',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function pendingOffers(): JsonResponse
    {
        try {
            $offers = Offer::where('state', \App\Enums\OfferState::VERIFIYING->value)
                ->with([
                    'foodEstablishment:id,name,address,user_id,establishment_type_id',
                    'foodEstablishment.establishmentType:id,name',
                    'foodEstablishment.user:id,name,email',
                    'products:id,name,description',
                ])
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($offer) {
                    return [
                        'id' => $offer->id,
                        'title' => $offer->title,
                        'description' => $offer->description,
                        'state' => $offer->state,
                        'quantity' => $offer->quantity,
                        'expiration_datetime' => $offer->expiration_datetime,
                        'created_at' => $offer->created_at,
                        'establishment' => [
                            'id' => $offer->foodEstablishment->id,
                            'name' => $offer->foodEstablishment->name,
                            'address' => $offer->foodEstablishment->address,
                            'type' => $offer->foodEstablishment->establishmentType->name,
                        ],
                        'seller' => [
                            'id' => $offer->foodEstablishment->user->id,
                            'name' => $offer->foodEstablishment->user->name,
                            'email' => $offer->foodEstablishment->user->email,
                        ],
                        'products' => $offer->products->map(function ($product) {
                            return [
                                'id' => $product->id,
                                'name' => $product->name,
                                'description' => $product->description,
                                'price' => $product->pivot->price ?? 0,
                                'quantity' => $product->pivot->quantity ?? 0,
                                'expiration_date' => $product->pivot->expiration_date ?? null,
                            ];
                        }),
                    ];
                });

            return response()->json([
                'data' => $offers,
                'message' => 'Ofertas pendientes obtenidas exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las ofertas pendientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function approveOffer(int $id, ChangeOfferStatusAction $changeOfferStatusAction): JsonResponse
    {
        if (!is_numeric($id)) {
            return response()->json(['message' => 'ID inválido'], 422);
        }

        try {
            $offer = Offer::findOrFail($id);

            if ($offer->state !== \App\Enums\OfferState::VERIFIYING->value) {
                return response()->json([
                    'message' => 'La oferta no está en estado de verificación',
                ], 400);
            }

            $offer = $changeOfferStatusAction->execute($id, \App\Enums\OfferState::ACTIVE->value);

            return response()->json([
                'message' => 'Oferta aprobada exitosamente',
                'data' => [
                    'id' => $offer->id,
                    'state' => $offer->state
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Oferta no encontrada'
            ], 404);
        } catch (OfferStatusChangeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => $e->context()
            ], $e->getCode());
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al aprobar la oferta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function rejectOffer(int $id, ChangeOfferStatusAction $changeOfferStatusAction): JsonResponse
    {
        if (!is_numeric($id)) {
            return response()->json(['message' => 'ID inválido'], 422);
        }

        try {
            $offer = Offer::findOrFail($id);

            if ($offer->state !== \App\Enums\OfferState::VERIFIYING->value) {
                return response()->json([
                    'message' => 'La oferta no está en estado de verificación',
                ], 400);
            }

            $offer = $changeOfferStatusAction->execute($id, \App\Enums\OfferState::INACTIVE->value);

            return response()->json([
                'message' => 'Oferta rechazada exitosamente',
                'data' => [
                    'id' => $offer->id,
                    'state' => $offer->state
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Oferta no encontrada'
            ], 404);
        } catch (OfferStatusChangeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => $e->context()
            ], $e->getCode());
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al rechazar la oferta',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
