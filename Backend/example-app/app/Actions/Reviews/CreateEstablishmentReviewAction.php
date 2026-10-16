<?php

namespace App\Actions\Reviews;

use App\Exceptions\Reviews\ReviewNotAllowedException;
use App\Jobs\Reviews\RecalculateEstablishmentRatingsJob;
use App\Models\EstablishmentReview;
use App\Models\Sell;
use App\Models\User;
use App\Policies\SellReviewPolicy;
use Illuminate\Support\Facades\DB;

class CreateEstablishmentReviewAction
{
    public function __construct(
        private readonly SellReviewPolicy $policy
    ) {}

    /**
     * Valida la elegibilidad y registra la calificación por estrellas de una compra retirada,
     * delegando la autorización en SellReviewPolicy bajo transacción y bloqueo pesimista.
     * Despacha asíncronamente el recálculo de los promedios en segundo plano.
     *
     * @throws ReviewNotAllowedException
     */
    public function execute(Sell $sell, User $customer, int $rating): EstablishmentReview
    {
        $review = DB::transaction(function () use ($sell, $customer, $rating) {
            $lockedSell = Sell::whereKey($sell->id)->lockForUpdate()->firstOrFail();

            // 1. Delegar control de propiedad, estado y ventana temporal en la Policy
            $this->policy->assertCanReview($lockedSell, $customer);

            // 2. Unicidad estricta protegida contra race conditions
            if ($lockedSell->review()->exists()) {
                throw new ReviewNotAllowedException('Esta compra ya ha sido calificada.', 422);
            }

            // 3. Inserción de la calificación
            return EstablishmentReview::create([
                'sell_id' => $lockedSell->id,
                'customer_id' => $customer->id,
                'food_establishment_id' => $lockedSell->sold_by,
                'rating' => $rating,
            ]);
        });

        // 4. Despacho asíncrono para recalcular promedios en segundo plano tras confirmar la transacción
        RecalculateEstablishmentRatingsJob::dispatch((int) $sell->sold_by);

        return $review;
    }
}
