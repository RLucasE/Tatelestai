<?php

namespace App\Http\Controllers;

use App\Actions\Offers\ChangeOfferStatusAction;
use App\Exceptions\Offer\OfferStatusChangeException;
use App\Models\Offer;
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
}
