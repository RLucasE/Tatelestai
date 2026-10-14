<?php

namespace App\Http\Controllers;

use App\Enums\TrustScoreCriterion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstablishmentVerificationController extends Controller
{
    /**
     * Resumen del estado de verificación y score de confianza para el vendedor autenticado
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        $establishment = $user->foodEstablishment;

        if (! $establishment) {
            return response()->json([
                'message' => 'No tienes un establecimiento registrado.',
            ], 404);
        }

        $trustScore = $establishment->trust_score;
        $trustLevel = $establishment->trust_level;

        $activeLogs = $establishment->trustLogs()
            ->where('is_active', true)
            ->get();

        $activeCriteria = $activeLogs->pluck('source_type')->map(fn ($criterion) => $criterion->value)->toArray();

        // Calcular recomendaciones faltantes
        $possibleCriteria = [
            TrustScoreCriterion::TAX_CERTIFICATE,
            TrustScoreCriterion::MUNICIPAL_PERMIT,
            TrustScoreCriterion::MUNICIPAL_TAX_RECEIPT,
            TrustScoreCriterion::PEST_CONTROL_CERTIFICATE,
            TrustScoreCriterion::STORE_COUNTER_PHOTO,
            TrustScoreCriterion::FACADE_PHOTO,
        ];

        $recommendations = [];
        foreach ($possibleCriteria as $criterion) {
            if (! in_array($criterion->value, $activeCriteria)) {
                $recommendations[] = [
                    'criterion' => $criterion->value,
                    'label' => $criterion->label(),
                    'points' => $criterion->defaultPoints(),
                ];
            }
        }

        return response()->json([
            'establishment_id' => $establishment->id,
            'name' => $establishment->name,
            'verification_status' => $establishment->verification_status,
            'verification_notes' => $establishment->verification_notes,
            'trust_score' => $trustScore,
            'trust_level' => $trustLevel,
            'is_eligible' => $trustScore >= 70,
            'is_manual_location' => empty($establishment->google_place_id),
            'files' => $establishment->verificationFiles->map(function ($file) {
                return [
                    'id' => $file->id,
                    'file_type' => $file->file_type,
                    'document_category' => $file->document_category?->value,
                    'status' => $file->status,
                    'rejection_reason' => $file->rejection_reason,
                    'reviewed_at' => $file->reviewed_at,
                ];
            }),
            'active_logs' => $activeLogs->map(function ($log) {
                return [
                    'source_type' => $log->source_type->value,
                    'label' => $log->source_type->label(),
                    'points' => $log->points,
                    'verified_at' => $log->created_at,
                ];
            }),
            'missing_recommendations' => $recommendations,
        ], 200);
    }
}
