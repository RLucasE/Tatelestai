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
}
