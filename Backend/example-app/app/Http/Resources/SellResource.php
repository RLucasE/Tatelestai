<?php

namespace App\Http\Resources;

use App\Enums\ClaimReason;
use App\Enums\ClaimStatus;
use App\Enums\PaymentStatus;
use App\Enums\SellState;
use App\Models\User;
use App\Policies\PurchaseClaimPolicy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $review = $this->relationLoaded('review') ? $this->review : $this->review()->first();
        $isReviewed = $review !== null;
        $userRating = $review?->rating;
        $isPickedUp = (bool) $this->is_picked_up && ($this->state === SellState::PICKED_UP || $this->state === SellState::PICKED_UP->value);
        $canReview = (bool) ($isPickedUp && ! $isReviewed && $this->picked_up_at?->gte(now()->subDays(14)));

        $sellState = $this->state instanceof SellState ? $this->state->value : $this->state;
        $paymentStatus = $this->payment_status instanceof PaymentStatus
            ? $this->payment_status->value
            : ($this->payment_status ?? PaymentStatus::APPROVED->value);

        $claim = $this->relationLoaded('claim') ? $this->claim : $this->claim()->first();

        $claimEligibility = ['can_claim' => false, 'reason' => null];
        $evaluatorUser = $request->user()
            ?? ($this->relationLoaded('customer') ? $this->customer : User::find($this->bought_by));

        if ($evaluatorUser) {
            $claimPolicy = app(PurchaseClaimPolicy::class);
            $attrKey = '_claim_quota_ctx_'.$evaluatorUser->id;
            $quotaContext = $request->attributes->get($attrKey);
            if ($quotaContext === null) {
                $quotaContext = $claimPolicy->buildQuotaContext($evaluatorUser);
                $request->attributes->set($attrKey, $quotaContext);
            }
            $claimEligibility = $claimPolicy->evaluateEligibility($this->resource, $evaluatorUser, null, $quotaContext);
        }

        $claimSummary = null;
        if ($claim) {
            $claimReason = $claim->reason instanceof ClaimReason
                ? $claim->reason
                : ClaimReason::tryFrom((string) $claim->reason);
            $claimStatus = $claim->status instanceof ClaimStatus
                ? $claim->status
                : ClaimStatus::tryFrom((string) $claim->status);

            $claimSummary = [
                'id' => $claim->id,
                'status' => $claimStatus?->value ?? $claim->status,
                'status_label' => $claimStatus?->label() ?? $claim->status,
                'reason' => $claimReason?->value ?? $claim->reason,
                'reason_label' => $claimReason?->label() ?? $claim->reason,
                'refund_amount' => $claim->refund_amount !== null ? (float) $claim->refund_amount : null,
                'resolution_notes' => $claim->resolution_notes,
                'created_at' => $claim->created_at,
            ];
        }

        return [
            'sell_id' => $this->id,
            'state' => $sellState,
            'payment_status' => $paymentStatus,
            'refunded_amount' => (float) ($this->refunded_amount ?? 0),
            'refunded_at' => $this->refunded_at,
            'is_picked_up' => (bool) $this->is_picked_up,
            'picked_up_at' => $this->picked_up_at,
            'pickup_start_datetime' => $this->getPickupStartDatetime(),
            'max_pickup_datetime' => $this->max_pickup_datetime,
            'is_reviewed' => $isReviewed,
            'user_rating' => $userRating,
            'can_review' => $canReview,
            'can_claim' => (bool) $claimEligibility['can_claim'],
            'claim_ineligibility_reason' => $claimEligibility['reason'],
            'claim' => $claimSummary,
            'created_at' => $this->created_at,
            'establishment' => [
                'id' => $this->foodEstablishment?->id,
                'name' => $this->foodEstablishment?->name,
                'address' => $this->foodEstablishment?->address,
            ],
            'total_price' => $this->sellDetails?->reduce(function ($total, $detail) {
                return $total + ((int) $detail->offer_quantity * (int) $detail->pack_price);
            }, 0) ?? 0,
            'offers' => $this->sellDetails?->map(function ($detail) {
                return [
                    'offer_id' => $detail->offer_id,
                    'offer_quantity' => $detail->offer_quantity,
                    'pack_name' => $detail->pack_name,
                    'pack_description' => $detail->pack_description,
                    'pack_price' => $detail->pack_price,
                    'allergens' => $detail->allergens,
                ];
            })->values()->toArray() ?? [],
        ];
    }
}
