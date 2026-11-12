<?php

namespace App\Policies;

use App\Enums\ClaimStatus;
use App\Enums\PaymentStatus;
use App\Enums\SellState;
use App\Exceptions\Claims\ClaimNotAllowedException;
use App\Models\PurchaseClaim;
use App\Models\Sell;
use App\Models\User;
use Carbon\Carbon;

class PurchaseClaimPolicy
{
    public function __construct(
        private ?PurchaseCancellationPolicy $cancellationPolicy = null
    ) {
        $this->cancellationPolicy ??= new PurchaseCancellationPolicy;
    }

    /**
     * Precalcula las cuotas y restricciones anti-abuso de un cliente para evitar N+1 en listados.
     *
     * @return array{
     *     has_abusive_cooldown: bool,
     *     abusive_cooldown_until: ?string,
     *     open_claims_count: int,
     *     claims_last_30_days_count: int,
     *     unpicked_claims_last_60_days_count: int
     * }
     */
    public function buildQuotaContext(User $user, ?Carbon $now = null): array
    {
        $now = $now ? Carbon::parse($now) : now();
        $cooldownDays = (int) config('claims.abuse_cooldown_days', 30);

        $latestAbusive = PurchaseClaim::query()
            ->where('customer_id', $user->id)
            ->where('is_abusive', true)
            ->get(['reviewed_at', 'created_at'])
            ->map(function (PurchaseClaim $claim) {
                $ref = $claim->reviewed_at ?? $claim->created_at;

                return $ref ? Carbon::parse($ref) : null;
            })
            ->filter()
            ->sortDesc()
            ->first();

        $cooldownUntil = $latestAbusive?->copy()->addDays($cooldownDays);
        $hasAbusiveCooldown = $cooldownUntil !== null && $now->lt($cooldownUntil);

        $openClaimsCount = PurchaseClaim::query()
            ->where('customer_id', $user->id)
            ->whereIn('status', [
                ClaimStatus::PENDING->value,
                ClaimStatus::UNDER_REVIEW->value,
            ])
            ->count();

        $claimsLast30DaysCount = PurchaseClaim::query()
            ->where('customer_id', $user->id)
            ->where('created_at', '>=', $now->copy()->subDays(30))
            ->count();

        $unpickedClaimsLast60DaysCount = PurchaseClaim::query()
            ->where('customer_id', $user->id)
            ->where('was_picked_up_at_claim', false)
            ->where('created_at', '>=', $now->copy()->subDays(60))
            ->count();

        return [
            'has_abusive_cooldown' => $hasAbusiveCooldown,
            'abusive_cooldown_until' => $hasAbusiveCooldown ? $cooldownUntil?->toIso8601String() : null,
            'open_claims_count' => $openClaimsCount,
            'claims_last_30_days_count' => $claimsLast30DaysCount,
            'unpicked_claims_last_60_days_count' => $unpickedClaimsLast60DaysCount,
        ];
    }

    /**
     * Evalúa elegibilidad de reclamo y retorna estructura detallada.
     *
     * @param  array<string, mixed>|null  $quotaContext
     * @return array{can_claim: bool, reason: ?string, http_status: int}
     */
    public function evaluateEligibility(Sell $sell, User $user, ?Carbon $now = null, ?array $quotaContext = null): array
    {
        $now = $now ? Carbon::parse($now) : now();

        // 1. Propiedad de la compra
        if ((int) $sell->bought_by !== (int) $user->id) {
            return [
                'can_claim' => false,
                'reason' => 'No tienes permiso para reclamar esta compra.',
                'http_status' => 403,
            ];
        }

        // 2. Estado de pago (whitelist estricta: solo APPROVED)
        $paymentStatus = $sell->payment_status instanceof PaymentStatus
            ? $sell->payment_status
            : PaymentStatus::tryFrom((string) ($sell->payment_status ?? ''));

        if ($paymentStatus !== PaymentStatus::APPROVED) {
            $reason = in_array($paymentStatus, [PaymentStatus::REFUNDED, PaymentStatus::PARTIALLY_REFUNDED], true)
                ? 'Esta compra ya cuenta con un reembolso registrado.'
                : 'Solo se pueden reclamar compras con pago aprobado.';

            return [
                'can_claim' => false,
                'reason' => $reason,
                'http_status' => 422,
            ];
        }

        // 3. Estado de venta (whitelist estricta según AGENTS.md §4.6)
        $sellState = $sell->state instanceof SellState
            ? $sell->state
            : SellState::tryFrom((string) ($sell->state ?? ''));

        $allowedStates = [
            SellState::PICKED_UP,
            SellState::PENDING,
            SellState::CONFIRMED,
            SellState::READY,
            SellState::EXPIRED,
        ];

        if (! in_array($sellState, $allowedStates, true)) {
            $reason = $sellState === SellState::CANCELLED
                ? 'Las compras canceladas no admiten reclamos.'
                : 'El estado actual de la compra no admite reclamos.';

            return [
                'can_claim' => false,
                'reason' => $reason,
                'http_status' => 422,
            ];
        }

        // 4. Unicidad: máximo un reclamo por compra
        $existingClaim = $sell->relationLoaded('claim') ? $sell->claim : $sell->claim()->first();
        if ($existingClaim !== null) {
            return [
                'can_claim' => false,
                'reason' => 'Ya existe un reclamo registrado para esta compra.',
                'http_status' => 422,
            ];
        }

        // 5. No superposición con cancelación voluntaria inmediata dentro de plazo
        if (! $sell->is_picked_up && $this->cancellationPolicy->canCancel($sell, $user, $now)) {
            return [
                'can_claim' => false,
                'reason' => 'Esta compra todavía está dentro del plazo de cancelación voluntaria inmediata. Podés cancelarla directamente desde tus compras.',
                'http_status' => 422,
            ];
        }

        // 6. Ventana máxima de 14 días (sin restricciones horarias previas de cancelación)
        $windowDays = (int) config('claims.claim_window_days', 14);
        $referenceDate = $sell->is_picked_up
            ? ($sell->picked_up_at ?? $sell->created_at)
            : ($sell->max_pickup_datetime ?? $sell->created_at);

        if ($referenceDate !== null) {
            $referenceCarbon = $referenceDate instanceof Carbon
                ? $referenceDate->copy()
                : Carbon::parse($referenceDate);

            if ($now->gt($referenceCarbon->copy()->addDays($windowDays))) {
                return [
                    'can_claim' => false,
                    'reason' => "El plazo máximo de {$windowDays} días para realizar un reclamo sobre esta compra ya expiró.",
                    'http_status' => 422,
                ];
            }
        }

        // 7. Controles anti-abuso y cuotas por usuario
        $quota = $quotaContext ?? $this->buildQuotaContext($user, $now);

        if (! empty($quota['has_abusive_cooldown'])) {
            return [
                'can_claim' => false,
                'reason' => 'Tu cuenta tiene una restricción temporal para iniciar nuevos reclamos por incumplimiento previo de la política.',
                'http_status' => 422,
            ];
        }

        $maxOpen = (int) config('claims.max_open_claims_per_user', 2);
        if ((int) ($quota['open_claims_count'] ?? 0) >= $maxOpen) {
            return [
                'can_claim' => false,
                'reason' => "Ya tenés {$maxOpen} reclamos abiertos en revisión. Esperá a que se resuelvan antes de iniciar otro.",
                'http_status' => 422,
            ];
        }

        $max30Days = (int) config('claims.max_claims_per_30_days', 3);
        if ((int) ($quota['claims_last_30_days_count'] ?? 0) >= $max30Days) {
            return [
                'can_claim' => false,
                'reason' => "Alcanzaste el límite máximo de {$max30Days} reclamos en los últimos 30 días.",
                'http_status' => 422,
            ];
        }

        $maxUnpicked60Days = (int) config('claims.max_unpicked_claims_per_60_days', 1);
        if (! $sell->is_picked_up && (int) ($quota['unpicked_claims_last_60_days_count'] ?? 0) >= $maxUnpicked60Days) {
            return [
                'can_claim' => false,
                'reason' => "Alcanzaste el límite de {$maxUnpicked60Days} reclamo sobre compras no retiradas en los últimos 60 días.",
                'http_status' => 422,
            ];
        }

        return [
            'can_claim' => true,
            'reason' => null,
            'http_status' => 200,
        ];
    }

    public function canClaim(Sell $sell, User $user, ?Carbon $now = null, ?array $quotaContext = null): bool
    {
        return $this->evaluateEligibility($sell, $user, $now, $quotaContext)['can_claim'];
    }

    public function ineligibilityReason(Sell $sell, User $user, ?Carbon $now = null, ?array $quotaContext = null): ?string
    {
        return $this->evaluateEligibility($sell, $user, $now, $quotaContext)['reason'];
    }

    /**
     * @param  array<string, mixed>|null  $quotaContext
     *
     * @throws ClaimNotAllowedException
     */
    public function assertCanClaim(Sell $sell, User $user, ?Carbon $now = null, ?array $quotaContext = null): void
    {
        $evaluation = $this->evaluateEligibility($sell, $user, $now, $quotaContext);

        if (! $evaluation['can_claim']) {
            throw new ClaimNotAllowedException(
                $evaluation['reason'] ?? 'No se puede iniciar un reclamo para esta compra.',
                $evaluation['http_status']
            );
        }
    }
}
