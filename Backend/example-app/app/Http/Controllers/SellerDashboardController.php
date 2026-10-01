<?php

namespace App\Http\Controllers;

use App\Actions\Offers\GetUserEstablishmentAction;
use App\Actions\Seller\GetSellerDashboardStatsAction;
use App\Http\Requests\SellerDashboardRequest;
use App\Http\Resources\SellerDashboardResource;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class SellerDashboardController extends Controller
{
    public function __construct(
        private readonly GetUserEstablishmentAction $getUserEstablishmentAction,
        private readonly GetSellerDashboardStatsAction $getSellerDashboardStatsAction
    ) {}

    /**
     * Muestra las métricas y la operativa del dashboard para el vendedor autenticado.
     */
    public function index(SellerDashboardRequest $request): JsonResponse
    {
        $establishment = $this->getUserEstablishmentAction->execute();

        if (! $establishment) {
            return response()->json([
                'message' => 'No se encontró un establecimiento asociado al usuario',
            ], 404);
        }

        $targetDate = $request->validated('date')
            ? Carbon::createFromFormat('Y-m-d', $request->validated('date'))
            : null;

        $stats = $this->getSellerDashboardStatsAction->execute($establishment, $targetDate);

        return response()->json([
            'message' => 'Dashboard del vendedor obtenido exitosamente',
            'data' => new SellerDashboardResource($stats),
        ], 200);
    }
}
