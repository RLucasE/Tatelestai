<?php

namespace App\Jobs\Reviews;

use App\Models\EstablishmentReview;
use App\Models\FoodEstablishment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateEstablishmentRatingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $establishmentId
    ) {}

    public function handle(): void
    {
        $establishment = FoodEstablishment::find($this->establishmentId);
        if (! $establishment) {
            return;
        }

        // 1. Cálculo Histórico
        $historical = EstablishmentReview::where('food_establishment_id', $this->establishmentId)
            ->selectRaw('COUNT(*) as total, AVG(rating) as avg_rating')
            ->first();

        $histCount = (int) ($historical->total ?? 0);
        $histAvg = $histCount > 0 ? round((float) $historical->avg_rating, 2) : null;

        // 2. Cálculo Reciente (Últimos 30 días con Fallback a últimas 10)
        $recent = EstablishmentReview::where('food_establishment_id', $this->establishmentId)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('COUNT(*) as total, AVG(rating) as avg_rating')
            ->first();

        $recentCount = (int) ($recent->total ?? 0);
        $recentAvg = null;

        if ($recentCount >= 3) {
            $recentAvg = round((float) $recent->avg_rating, 2);
        } elseif ($histCount >= 3) {
            $last10 = EstablishmentReview::where('food_establishment_id', $this->establishmentId)
                ->latest('created_at')
                ->latest('id')
                ->limit(10)
                ->pluck('rating');

            $recentCount = $last10->count();
            $recentAvg = $recentCount > 0 ? round((float) $last10->avg(), 2) : null;
        }

        // 3. Actualización en base de datos
        $establishment->update([
            'rating_average' => $histAvg,
            'reviews_count' => $histCount,
            'recent_rating_average' => $recentAvg,
            'recent_reviews_count' => $recentCount,
        ]);
    }
}
