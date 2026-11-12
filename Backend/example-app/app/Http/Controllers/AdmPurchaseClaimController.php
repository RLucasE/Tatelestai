<?php

namespace App\Http\Controllers;

use App\Actions\Claims\BuildCustomerClaimRiskSummaryAction;
use App\Actions\Claims\ResolvePurchaseClaimAction;
use App\Enums\ClaimStatus;
use App\Exceptions\BusinessRuleException;
use App\Http\Requests\Claims\ResolvePurchaseClaimRequest;
use App\Http\Resources\PurchaseClaimResource;
use App\Models\PurchaseClaim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmPurchaseClaimController extends Controller
{
    /**
     * Lista los reclamos para moderación administrativa con filtros opcionales.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PurchaseClaim::with([
            'customer:id,name,last_name,email',
            'foodEstablishment:id,name,address',
            'sell.sellDetails',
            'evidences',
            'reviewer:id,name,last_name',
        ]);

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        if ($request->filled('reason')) {
            $query->where('reason', (string) $request->string('reason'));
        }

        if ($request->filled('establishment_id')) {
            $query->where('food_establishment_id', $request->integer('establishment_id'));
        }

        $claims = $query->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        $countsRaw = PurchaseClaim::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return PurchaseClaimResource::collection($claims)
            ->additional([
                'counts' => [
                    'pending' => (int) ($countsRaw[ClaimStatus::PENDING->value] ?? 0),
                    'under_review' => (int) ($countsRaw[ClaimStatus::UNDER_REVIEW->value] ?? 0),
                    'approved' => (int) ($countsRaw[ClaimStatus::APPROVED->value] ?? 0),
                    'rejected' => (int) ($countsRaw[ClaimStatus::REJECTED->value] ?? 0),
                ],
            ])
            ->response();
    }

    /**
     * Muestra el detalle completo de un reclamo incluyendo snapshot histórico y señales anti-abuso.
     */
    public function show(PurchaseClaim $claim, BuildCustomerClaimRiskSummaryAction $riskSummaryAction): JsonResponse
    {
        $claim->load([
            'customer:id,name,last_name,email',
            'foodEstablishment:id,name,address',
            'sell.sellDetails.offer',
            'evidences',
            'reviewer:id,name,last_name',
        ]);

        $riskSummary = $riskSummaryAction->execute((int) $claim->customer_id);
        $claim->setAttribute('customer_risk_summary', $riskSummary);

        return response()->json([
            'data' => (new PurchaseClaimResource($claim))->resolve(),
        ], 200);
    }

    /**
     * Marca un reclamo pendiente como en análisis por un administrador.
     */
    public function markUnderReview(Request $request, PurchaseClaim $claim): JsonResponse
    {
        $updated = DB::transaction(function () use ($request, $claim) {
            $locked = PurchaseClaim::whereKey($claim->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, [ClaimStatus::APPROVED, ClaimStatus::REJECTED], true)) {
                throw BusinessRuleException::conflict('Este reclamo ya fue resuelto y no puede pasar a revisión.', 'CLAIM_ALREADY_RESOLVED');
            }

            if ($locked->status === ClaimStatus::PENDING) {
                $locked->update([
                    'status' => ClaimStatus::UNDER_REVIEW->value,
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                ]);
            }

            return $locked->fresh([
                'customer:id,name,last_name,email',
                'foodEstablishment:id,name,address',
                'sell.sellDetails',
                'evidences',
                'reviewer:id,name,last_name',
            ]);
        });

        return response()->json([
            'message' => 'El reclamo pasó a estado en revisión.',
            'data' => (new PurchaseClaimResource($updated))->resolve(),
        ], 200);
    }

    /**
     * Resuelve el reclamo aprobando reembolso total/parcial o rechazándolo.
     */
    public function resolve(
        ResolvePurchaseClaimRequest $request,
        PurchaseClaim $claim,
        ResolvePurchaseClaimAction $resolveAction
    ): JsonResponse {
        $validated = $request->validated();

        $resolved = $resolveAction->execute(
            claim: $claim,
            admin: $request->user(),
            decision: (string) $validated['decision'],
            refundAmount: isset($validated['refund_amount']) ? (float) $validated['refund_amount'] : null,
            resolutionNotes: $validated['resolution_notes'] ?? null,
            isAbusive: (bool) ($validated['is_abusive'] ?? false),
            deactivateOffer: (bool) ($validated['deactivate_offer'] ?? false),
            applyTrustPenalty: (bool) ($validated['apply_trust_penalty'] ?? false)
        );

        $message = $resolved->status === ClaimStatus::APPROVED
            ? 'Reclamo aprobado y reembolso emitido exitosamente.'
            : 'Reclamo rechazado exitosamente.';

        return response()->json([
            'message' => $message,
            'data' => (new PurchaseClaimResource($resolved))->resolve(),
        ], 200);
    }
}
