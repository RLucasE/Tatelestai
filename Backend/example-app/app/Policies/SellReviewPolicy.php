<?php

namespace App\Policies;

use App\Enums\SellState;
use App\Exceptions\Reviews\ReviewNotAllowedException;
use App\Models\Sell;
use App\Models\User;

class SellReviewPolicy
{
    /**
     * Determina si una venta es calificable por un usuario según las reglas de negocio.
     */
    public function canReview(Sell $sell, ?User $user = null): bool
    {
        try {
            $this->assertCanReview($sell, $user);

            return true;
        } catch (ReviewNotAllowedException) {
            return false;
        }
    }

    /**
     * Evalúa las reglas de calificación y arroja ReviewNotAllowedException si no cumple la política.
     *
     * @throws ReviewNotAllowedException
     */
    public function assertCanReview(Sell $sell, ?User $user = null): void
    {
        // 1. Verificación de propiedad / autorización
        if ($user !== null && (int) $sell->bought_by !== (int) $user->id) {
            throw new ReviewNotAllowedException('No tienes autorización para calificar este pedido.', 403);
        }

        // 2. Whitelist de estado de venta: solo compras efectivamente retiradas
        if (! $sell->is_picked_up || $sell->state !== SellState::PICKED_UP) {
            throw new ReviewNotAllowedException('Solo puedes calificar compras que ya hayan sido retiradas.', 422);
        }

        // 3. Ventana temporal de 14 días desde el retiro
        if (! $sell->picked_up_at || $sell->picked_up_at->lt(now()->subDays(14))) {
            throw new ReviewNotAllowedException('El período de calificación para este pedido ha vencido (14 días desde el retiro).', 422);
        }
    }
}
