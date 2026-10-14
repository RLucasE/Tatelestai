<?php

namespace App\Actions\Verification;

use App\Enums\TrustScoreCriterion;
use App\Models\EstablishmentTrustLog;
use App\Models\EstablishmentVerificationFile;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class ReviewVerificationFileAction
{
    /**
     * Revisa un documento individual de verificación (aceptar o rechazar) y actualiza el score.
     *
     * @param  EstablishmentVerificationFile  $file
     * @param  User  $admin
     * @param  string  $decision  'accept' o 'reject'
     * @param  string|null  $rejectionReason
     * @param  TrustScoreCriterion|null  $overrideCategory
     * @param  int|null  $customPoints
     * @return EstablishmentVerificationFile
     *
     * @throws Exception
     */
    public function execute(
        EstablishmentVerificationFile $file,
        User $admin,
        string $decision,
        ?string $rejectionReason = null,
        ?TrustScoreCriterion $overrideCategory = null,
        ?int $customPoints = null
    ): EstablishmentVerificationFile {
        if (! in_array($decision, ['accept', 'reject'])) {
            throw new Exception("Decisión no válida. Debe ser 'accept' o 'reject'.", 400);
        }

        return DB::transaction(function () use ($file, $admin, $decision, $rejectionReason, $overrideCategory, $customPoints) {
            $establishment = $file->foodEstablishment;

            if ($overrideCategory) {
                $file->document_category = $overrideCategory;
            }

            $category = $file->document_category;

            if ($decision === 'accept') {
                $file->status = 'accepted';
                $file->rejection_reason = null;
                $file->reviewed_at = now();
                $file->reviewed_by = $admin->id;
                $file->save();

                // Si la categoría cambió respecto a un log previo de este archivo, desactivar el viejo
                EstablishmentTrustLog::where('food_establishment_id', $establishment->id)
                    ->whereJsonContains('metadata->file_id', $file->id)
                    ->where('source_type', '!=', $category?->value)
                    ->update(['is_active' => false]);

                // Si es "Otro documento adicional", asignar los puntos manuales definidos por el admin
                if ($category === TrustScoreCriterion::OTHER) {
                    $points = max(0, $customPoints ?? 0);

                    $existingLog = EstablishmentTrustLog::where('food_establishment_id', $establishment->id)
                        ->where('source_type', TrustScoreCriterion::OTHER->value)
                        ->whereJsonContains('metadata->file_id', $file->id)
                        ->first();

                    if ($existingLog) {
                        $existingLog->update([
                            'points' => $points,
                            'verified_by_user_id' => $admin->id,
                            'metadata' => [
                                'file_id' => $file->id,
                                'reviewed_by_name' => $admin->name.' '.$admin->last_name,
                                'reviewed_at' => now()->toIso8601String(),
                                'notes' => 'Puntos asignados manualmente',
                            ],
                            'is_active' => $points > 0,
                        ]);
                    } elseif ($points > 0) {
                        EstablishmentTrustLog::create([
                            'food_establishment_id' => $establishment->id,
                            'source_type' => TrustScoreCriterion::OTHER->value,
                            'points' => $points,
                            'verified_by_user_id' => $admin->id,
                            'metadata' => [
                                'file_id' => $file->id,
                                'reviewed_by_name' => $admin->name.' '.$admin->last_name,
                                'reviewed_at' => now()->toIso8601String(),
                                'notes' => 'Puntos asignados manualmente',
                            ],
                            'is_active' => true,
                        ]);
                    }
                } elseif ($category) {
                    // Criterios fijos estándar
                    EstablishmentTrustLog::updateOrCreate(
                        [
                            'food_establishment_id' => $establishment->id,
                            'source_type' => $category->value,
                        ],
                        [
                            'points' => $category->defaultPoints(),
                            'verified_by_user_id' => $admin->id,
                            'metadata' => [
                                'file_id' => $file->id,
                                'reviewed_by_name' => $admin->name.' '.$admin->last_name,
                                'reviewed_at' => now()->toIso8601String(),
                            ],
                            'is_active' => true,
                        ]
                    );
                }
            } else {
                $file->status = 'rejected';
                $file->rejection_reason = $rejectionReason ?? 'Documento no válido o ilegible';
                $file->reviewed_at = now();
                $file->reviewed_by = $admin->id;
                $file->save();

                // Desactivar cualquier log activo vinculado a este archivo
                EstablishmentTrustLog::where('food_establishment_id', $establishment->id)
                    ->whereJsonContains('metadata->file_id', $file->id)
                    ->update(['is_active' => false]);

                // Si era de categoría fija única, desactivar por source_type
                if ($category && $category !== TrustScoreCriterion::OTHER) {
                    EstablishmentTrustLog::where('food_establishment_id', $establishment->id)
                        ->where('source_type', $category->value)
                        ->update(['is_active' => false]);
                }
            }

            return $file->fresh(['reviewer', 'foodEstablishment']);
        });
    }
}
