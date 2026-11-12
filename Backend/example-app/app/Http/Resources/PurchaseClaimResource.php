<?php

namespace App\Http\Resources;

use App\Enums\ClaimReason;
use App\Enums\ClaimStatus;
use App\Enums\PaymentStatus;
use App\Enums\SellState;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseClaimResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $reason = $this->reason instanceof ClaimReason
            ? $this->reason
            : ClaimReason::tryFrom((string) $this->reason);

        $status = $this->status instanceof ClaimStatus
            ? $this->status
            : ClaimStatus::tryFrom((string) $this->status);

        $establishment = $this->relationLoaded('foodEstablishment')
            ? $this->foodEstablishment
            : ($this->relationLoaded('sell') && $this->sell?->relationLoaded('foodEstablishment')
                ? $this->sell->foodEstablishment
                : null);

        return [
            'id' => $this->id,
            'sell_id' => $this->sell_id,
            'customer_id' => $this->customer_id,
            'food_establishment_id' => $this->food_establishment_id,
            'reason' => $reason?->value ?? $this->reason,
            'reason_label' => $reason?->label() ?? $this->reason,
            'description' => $this->description,
            'status' => $status?->value ?? $this->status,
            'status_label' => $status?->label() ?? $this->status,
            'was_picked_up_at_claim' => (bool) $this->was_picked_up_at_claim,
            'refund_amount' => $this->refund_amount !== null ? (float) $this->refund_amount : null,
            'gateway_refund_id' => $this->gateway_refund_id,
            'refunded_at' => $this->refunded_at,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at,
            'resolution_notes' => $this->resolution_notes,
            'is_abusive' => (bool) $this->is_abusive,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'last_name' => $this->customer->last_name,
                'email' => $this->customer->email,
            ] : null),
            'reviewer' => $this->whenLoaded('reviewer', fn () => $this->reviewer ? [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
                'last_name' => $this->reviewer->last_name,
            ] : null),
            'establishment' => $establishment ? [
                'id' => $establishment->id,
                'name' => $establishment->name,
                'address' => $establishment->address,
            ] : null,
            'sell' => $this->whenLoaded('sell', function () {
                if (! $this->sell) {
                    return null;
                }

                $sellState = $this->sell->state instanceof SellState
                    ? $this->sell->state->value
                    : $this->sell->state;

                $paymentStatus = $this->sell->payment_status instanceof PaymentStatus
                    ? $this->sell->payment_status->value
                    : ($this->sell->payment_status ?? PaymentStatus::APPROVED->value);

                return [
                    'id' => $this->sell->id,
                    'state' => $sellState,
                    'payment_status' => $paymentStatus,
                    'is_picked_up' => (bool) $this->sell->is_picked_up,
                    'picked_up_at' => $this->sell->picked_up_at,
                    'max_pickup_datetime' => $this->sell->max_pickup_datetime,
                    'created_at' => $this->sell->created_at,
                    'gross_amount' => $this->sell->getGrossAmount(),
                    'refunded_amount' => (float) ($this->sell->refunded_amount ?? 0),
                    'refunded_at' => $this->sell->refunded_at,
                    'sell_details' => $this->sell->relationLoaded('sellDetails')
                        ? $this->sell->sellDetails->map(fn ($detail) => [
                            'id' => $detail->id,
                            'offer_id' => $detail->offer_id,
                            'offer_quantity' => $detail->offer_quantity,
                            'pack_name' => $detail->pack_name,
                            'pack_description' => $detail->pack_description,
                            'pack_price' => $detail->pack_price,
                            'allergens' => $detail->allergens,
                            'estimated_weight_kg' => $detail->estimated_weight_kg !== null ? (float) $detail->estimated_weight_kg : null,
                            'minimum_value' => $detail->minimum_value !== null ? (float) $detail->minimum_value : null,
                        ])->values()->toArray()
                        : [],
                ];
            }),
            'evidences' => $this->whenLoaded('evidences', function () {
                return $this->evidences->map(fn ($evidence) => [
                    'id' => $evidence->id,
                    'original_name' => $evidence->original_name,
                    'mime_type' => $evidence->mime_type,
                    'file_size' => $evidence->file_size,
                    'url' => "/claims/{$this->id}/evidences/{$evidence->id}",
                ])->values()->toArray();
            }),
            'customer_risk_summary' => $this->when(
                isset($this->customer_risk_summary),
                fn () => $this->customer_risk_summary
            ),
        ];
    }
}
