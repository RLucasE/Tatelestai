<?php

namespace App\Policies;

use App\Enums\SellState;
use App\Exceptions\CancellationNotAllowedException;
use App\Models\Sell;
use App\Models\User;
use Carbon\Carbon;

class PurchaseCancellationPolicy
{
    /**
     * Determina si una venta es cancelable según las políticas de Tatelestai.
     */
    public function canCancel(Sell $sell, ?User $user = null, ?Carbon $now = null): bool
    {
        try {
            $this->assertCanCancel($sell, $user, $now);

            return true;
        } catch (CancellationNotAllowedException) {
            return false;
        }
    }

    /**
     * Evalúa las reglas de cancelación de la compra y arroja una excepción si no cumple la política.
     *
     * @throws CancellationNotAllowedException
     */
    public function assertCanCancel(Sell $sell, ?User $user = null, ?Carbon $now = null): void
    {
        $now = $now ? Carbon::parse($now) : now();

        // 1. Verificación de autorización de usuario (si se especifica)
        if ($user !== null && $sell->bought_by !== $user->id) {
            throw new CancellationNotAllowedException('No tienes permiso para cancelar esta compra.', 403);
        }

        // 2. Verificación de retiro en mostrador
        if ($sell->is_picked_up || $sell->state === SellState::PICKED_UP) {
            throw new CancellationNotAllowedException('El pedido ya fue retirado en el local y no puede ser cancelado.', 422);
        }

        // 3. Verificación de estado ya cancelado
        if ($sell->state === SellState::CANCELLED) {
            throw new CancellationNotAllowedException('La compra ya se encuentra cancelada.', 422);
        }

        // 4. Franjas horarias
        $pickupStart = $sell->getPickupStartDatetime();
        $pickupEnd = $sell->max_pickup_datetime
            ? Carbon::parse($sell->max_pickup_datetime)
            : ($pickupStart ? $pickupStart->copy()->addHours(1) : null);

        // Si la franja de retiro ya finalizó completamente (No-Show)
        if ($pickupEnd && $now->gte($pickupEnd)) {
            throw new CancellationNotAllowedException('La franja de retiro ya finalizó (No-Show). No se admiten cancelaciones.', 422);
        }

        // Regla 1: Anticipación >= 2 horas antes del inicio del retiro
        if ($pickupStart && $now->lt($pickupStart)) {
            $minutesToStart = $now->diffInMinutes($pickupStart, true);
            if ($minutesToStart >= 120) {
                return;
            }
        }

        // Regla 2: Período de gracia post-compra
        $createdAt = $sell->created_at ? Carbon::parse($sell->created_at) : $now;
        $minutesSincePurchase = $now->diffInMinutes($createdAt, true);

        // Caso A: Compra realizada ANTES de que comience la franja (con menos de 2 horas restantes)
        if ($pickupStart && $createdAt->lt($pickupStart)) {
            if ($minutesSincePurchase <= 15) {
                return;
            }

            throw new CancellationNotAllowedException('Han transcurrido más de 15 minutos desde la compra y faltan menos de 2 horas para el inicio del retiro.', 422);
        }

        // Caso B: Compra realizada DENTRO de la franja horaria en curso
        if ($minutesSincePurchase <= 5) {
            return;
        }

        throw new CancellationNotAllowedException('Han transcurrido más de 5 minutos desde la compra realizada dentro de la franja horaria.', 422);
    }
}
