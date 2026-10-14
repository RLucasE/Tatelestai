<?php

namespace App\Actions\Verification;

use App\Enums\TrustScoreCriterion;
use App\Models\EstablishmentTrustLog;
use App\Models\FoodEstablishment;
use App\Models\User;

class VerifyPhoneCallAction
{
    /**
     * Registra la llamada telefónica realizada por el administrador y otorga 30 puntos de confianza.
     */
    public function execute(
        FoodEstablishment $establishment,
        User $admin,
        bool $callSuccessful = true,
        ?string $notes = null
    ): FoodEstablishment {
        $phoneCalled = $establishment->google_place_data['formatted_phone_number']
            ?? $establishment->phone
            ?? 'No especificado';

        if ($callSuccessful) {
            EstablishmentTrustLog::updateOrCreate(
                [
                    'food_establishment_id' => $establishment->id,
                    'source_type' => TrustScoreCriterion::PHONE_CALL_VERIFICATION->value,
                ],
                [
                    'points' => TrustScoreCriterion::PHONE_CALL_VERIFICATION->defaultPoints(),
                    'verified_by_user_id' => $admin->id,
                    'metadata' => [
                        'phone_called' => $phoneCalled,
                        'admin_name' => $admin->name.' '.$admin->last_name,
                        'notes' => $notes,
                        'called_at' => now()->toIso8601String(),
                    ],
                    'is_active' => true,
                ]
            );
        } else {
            EstablishmentTrustLog::where('food_establishment_id', $establishment->id)
                ->where('source_type', TrustScoreCriterion::PHONE_CALL_VERIFICATION->value)
                ->update(['is_active' => false]);
        }

        return $establishment->fresh(['trustLogs']);
    }
}
