<?php

namespace App\Actions\Claims;

use App\Enums\ClaimStatus;
use App\Enums\PaymentStatus;
use App\Models\PurchaseClaim;
use App\Models\Sell;
use Carbon\Carbon;

class BuildCustomerClaimRiskSummaryAction
{
    /**
     * Calcula métricas objetivas y nivel de riesgo anti-abuso del comprador.
     *
     * @return array{
     *     total_purchases: int,
     *     total_claims: int,
     *     pending_claims: int,
     *     approved_claims: int,
     *     rejected_claims: int,
     *     abusive_claims: int,
     *     unpicked_claims_last_60d: int,
     *     claims_last_30d: int,
     *     claim_rate_percentage: float,
     *     has_active_cooldown: bool,
     *     risk_level: string
     * }
     */
    public function execute(int $customerId, ?Carbon $now = null): array
    {
        $now = $now ? Carbon::parse($now) : now();
        $cooldownDays = (int) config('claims.abuse_cooldown_days', 30);

        $totalPurchases = Sell::query()
            ->where('bought_by', $customerId)
            ->whereIn('payment_status', [
                PaymentStatus::APPROVED->value,
                PaymentStatus::REFUNDED->value,
                PaymentStatus::PARTIALLY_REFUNDED->value,
            ])
            ->count();

        $claims = PurchaseClaim::query()
            ->where('customer_id', $customerId)
            ->get(['status', 'is_abusive', 'was_picked_up_at_claim', 'reviewed_at', 'created_at']);

        $totalClaims = $claims->count();

        $pendingClaims = $claims->filter(function (PurchaseClaim $claim) {
            return in_array($claim->status, [ClaimStatus::PENDING, ClaimStatus::UNDER_REVIEW], true);
        })->count();

        $approvedClaims = $claims->filter(fn (PurchaseClaim $claim) => $claim->status === ClaimStatus::APPROVED)->count();
        $rejectedClaims = $claims->filter(fn (PurchaseClaim $claim) => $claim->status === ClaimStatus::REJECTED)->count();
        $abusiveClaims = $claims->filter(fn (PurchaseClaim $claim) => (bool) $claim->is_abusive)->count();

        $claimsLast30d = $claims->filter(function (PurchaseClaim $claim) use ($now) {
            return $claim->created_at && Carbon::parse($claim->created_at)->gte($now->copy()->subDays(30));
        })->count();

        $unpickedClaimsLast60d = $claims->filter(function (PurchaseClaim $claim) use ($now) {
            return ! $claim->was_picked_up_at_claim
                && $claim->created_at
                && Carbon::parse($claim->created_at)->gte($now->copy()->subDays(60));
        })->count();

        $hasActiveCooldown = $claims->contains(function (PurchaseClaim $claim) use ($now, $cooldownDays) {
            if (! $claim->is_abusive) {
                return false;
            }
            $ref = $claim->reviewed_at ?? $claim->created_at;

            return $ref && $now->lt(Carbon::parse($ref)->addDays($cooldownDays));
        });

        $claimRate = $totalPurchases > 0
            ? round(($totalClaims / $totalPurchases) * 100, 1)
            : ($totalClaims > 0 ? 100.0 : 0.0);

        $riskLevel = 'low';
        if (
            $abusiveClaims > 0
            || $rejectedClaims >= 2
            || $unpickedClaimsLast60d >= 2
            || ($totalPurchases >= 3 && $claimRate >= 50.0)
            || $claimsLast30d >= 3
        ) {
            $riskLevel = 'high';
        } elseif (
            $rejectedClaims >= 1
            || $unpickedClaimsLast60d >= 1
            || $claimsLast30d >= 2
            || ($totalPurchases >= 2 && $claimRate >= 25.0)
        ) {
            $riskLevel = 'medium';
        }

        return [
            'total_purchases' => $totalPurchases,
            'total_claims' => $totalClaims,
            'pending_claims' => $pendingClaims,
            'approved_claims' => $approvedClaims,
            'rejected_claims' => $rejectedClaims,
            'abusive_claims' => $abusiveClaims,
            'unpicked_claims_last_60d' => $unpickedClaimsLast60d,
            'claims_last_30d' => $claimsLast30d,
            'claim_rate_percentage' => $claimRate,
            'has_active_cooldown' => $hasActiveCooldown,
            'risk_level' => $riskLevel,
        ];
    }
}
