<?php

namespace App\Http\Controllers;

use App\Actions\Claims\CreatePurchaseClaimAction;
use App\Enums\ClaimReason;
use App\Http\Requests\Claims\StorePurchaseClaimRequest;
use App\Http\Resources\PurchaseClaimResource;
use App\Models\PurchaseClaim;
use App\Models\Sell;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerPurchaseClaimController extends Controller
{
    public function __construct(
        private readonly CreatePurchaseClaimAction $createClaimAction
    ) {}

    /**
     * Lista los reclamos realizados por el comprador autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $claims = PurchaseClaim::with(['sell.sellDetails', 'foodEstablishment', 'evidences'])
            ->where('customer_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'message' => 'Reclamos obtenidos exitosamente',
            'data' => PurchaseClaimResource::collection($claims)->resolve(),
        ], 200);
    }

    /**
     * Registra un reclamo de compra para su revisión administrativa.
     */
    public function store(StorePurchaseClaimRequest $request, Sell $sell): JsonResponse
    {
        $reason = ClaimReason::from((string) $request->validated('reason'));
        $description = (string) $request->validated('description');
        $evidences = $request->file('evidences', []);

        if (! is_array($evidences)) {
            $evidences = $evidences ? [$evidences] : [];
        }

        $claim = $this->createClaimAction->execute(
            $sell,
            $request->user(),
            $reason,
            $description,
            $evidences
        );

        return response()->json([
            'message' => 'Tu reclamo fue registrado y quedó pendiente de revisión por nuestro equipo.',
            'data' => (new PurchaseClaimResource($claim))->resolve(),
        ], 201);
    }
}
