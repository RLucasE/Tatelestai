<?php

namespace App\Actions\Reviews;

use App\Models\EstablishmentReview;
use App\Models\FoodEstablishment;

class GetEstablishmentRatingSummaryAction
{
    /**
     * Obtiene el resumen de reputación y distribución de calificaciones por estrella de un comercio.
     *
     * @return array<string, mixed>
     */
    public function execute(FoodEstablishment $establishment): array
    {
        $distributionRaw = EstablishmentReview::where('food_establishment_id', $establishment->id)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        $distribution = [
            '5' => (int) ($distributionRaw[5] ?? 0),
            '4' => (int) ($distributionRaw[4] ?? 0),
            '3' => (int) ($distributionRaw[3] ?? 0),
            '2' => (int) ($distributionRaw[2] ?? 0),
            '1' => (int) ($distributionRaw[1] ?? 0),
        ];

        return [
            'establishment_id' => $establishment->id,
            'rating_average' => $establishment->rating_average !== null ? (float) $establishment->rating_average : null,
            'reviews_count' => (int) $establishment->reviews_count,
            'recent_rating_average' => $establishment->recent_rating_average !== null ? (float) $establishment->recent_rating_average : null,
            'recent_reviews_count' => (int) $establishment->recent_reviews_count,
            'distribution' => $distribution,
        ];
    }
}
