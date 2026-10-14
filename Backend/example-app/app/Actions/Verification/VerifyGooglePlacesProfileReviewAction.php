<?php

namespace App\Actions\Verification;

use App\Enums\TrustScoreCriterion;
use App\Models\EstablishmentTrustLog;
use App\Models\FoodEstablishment;
use App\Models\User;
use Exception;

class VerifyGooglePlacesProfileReviewAction
{
    /**
     * Valida la ficha comercial de Google Places por parte del administrador y otorga o retira 20 puntos.
     *
     * @param  FoodEstablishment  $establishment
     * @param  User  $admin
     * @param  bool  $isVerified
     * @param  string|null  $notes
     * @return FoodEstablishment
     *
     * @throws Exception
     */
    public function execute(
        FoodEstablishment $establishment,
        User $admin,
        bool $isVerified = true,
        ?string $notes = null
    ): FoodEstablishment {
        if (empty($establishment->google_place_id)) {
            throw new Exception('El establecimiento no cuenta con una ficha de Google Places vinculada.', 400);
        }

        if ($isVerified) {
            EstablishmentTrustLog::updateOrCreate(
                [
                    'food_establishment_id' => $establishment->id,
                    'source_type' => TrustScoreCriterion::GOOGLE_PLACES_PROFILE->value,
                ],
                [
                    'points' => TrustScoreCriterion::GOOGLE_PLACES_PROFILE->defaultPoints(),
                    'verified_by_user_id' => $admin->id,
                    'metadata' => [
                        'google_place_id' => $establishment->google_place_id,
                        'name_in_google' => $establishment->google_place_data['name'] ?? $establishment->name,
                        'phone_in_google' => $establishment->google_place_data['formatted_phone_number'] ?? null,
                        'notes' => $notes,
                        'reviewed_at' => now()->toIso8601String(),
                    ],
                    'is_active' => true,
                ]
            );
        } else {
            EstablishmentTrustLog::where('food_establishment_id', $establishment->id)
                ->where('source_type', TrustScoreCriterion::GOOGLE_PLACES_PROFILE->value)
                ->update(['is_active' => false]);
        }

        return $establishment->fresh(['trustLogs']);
    }
}
