<?php

namespace App\Actions\Sell;

use App\Enums\ClaimReason;
use App\Enums\ClaimStatus;
use App\Models\Sell;
use App\Models\User;
use App\Policies\PurchaseClaimPolicy;

class getCustomerSellsAction
{
    public function __construct(
        private readonly ProcessExpiredSellsAction $processExpiredSellsAction,
        private readonly ?PurchaseClaimPolicy $claimPolicy = null,
    ) {}

    /**
     * Obtiene todas las compras (sells) de un customer específico con sus detalles
     *
     * @param  int  $userId  ID del usuario customer
     * @return array Array con las ventas y sus detalles
     */
    public function execute(int $userId): array
    {
        // Sincronización lazy de compras vencidas antes de retornar
        $this->processExpiredSellsAction->execute(boughtBy: $userId);

        $sells = Sell::with([
            'sellDetails.offer',
            'foodEstablishment',
            'claim',
        ])
            ->where('bought_by', $userId)
            ->where('is_picked_up', false)
            ->orderBy('created_at', 'desc')
            ->get();

        $claimPolicy = $this->claimPolicy ?? app(PurchaseClaimPolicy::class);
        $customer = User::find($userId);
        $quotaContext = $customer ? $claimPolicy->buildQuotaContext($customer) : null;

        // Mapear para asegurar estructura y casting del enum state
        return $sells->map(function (Sell $sell) use ($customer, $claimPolicy, $quotaContext) {
            $claimEligibility = $customer
                ? $claimPolicy->evaluateEligibility($sell, $customer, null, $quotaContext)
                : ['can_claim' => false, 'reason' => null];

            $claimSummary = null;
            if ($sell->claim) {
                $claimReason = $sell->claim->reason instanceof ClaimReason
                    ? $sell->claim->reason
                    : ClaimReason::tryFrom((string) $sell->claim->reason);
                $claimStatus = $sell->claim->status instanceof ClaimStatus
                    ? $sell->claim->status
                    : ClaimStatus::tryFrom((string) $sell->claim->status);

                $claimSummary = [
                    'id' => $sell->claim->id,
                    'status' => $claimStatus?->value ?? $sell->claim->status,
                    'status_label' => $claimStatus?->label() ?? $sell->claim->status,
                    'reason' => $claimReason?->value ?? $sell->claim->reason,
                    'reason_label' => $claimReason?->label() ?? $sell->claim->reason,
                    'refund_amount' => $sell->claim->refund_amount !== null ? (float) $sell->claim->refund_amount : null,
                    'resolution_notes' => $sell->claim->resolution_notes,
                    'created_at' => $sell->claim->created_at,
                ];
            }

            return [
                'id' => $sell->id,
                'bought_by' => $sell->bought_by,
                'sold_by' => $sell->sold_by,
                'pickup_code' => $sell->pickup_code,
                'is_picked_up' => $sell->is_picked_up,
                'picked_up_at' => $sell->picked_up_at,
                'created_at' => $sell->created_at,
                'updated_at' => $sell->updated_at,
                'pickup_start_datetime' => $sell->getPickupStartDatetime(),
                'max_pickup_datetime' => $sell->max_pickup_datetime,
                'state' => $sell->state?->value ?? $sell->state,
                'payment_status' => $sell->payment_status?->value ?? $sell->payment_status ?? 'approved',
                'refunded_amount' => (float) ($sell->refunded_amount ?? 0),
                'refunded_at' => $sell->refunded_at,
                'can_claim' => (bool) $claimEligibility['can_claim'],
                'claim_ineligibility_reason' => $claimEligibility['reason'],
                'claim' => $claimSummary,
                'establishment' => [
                    'id' => $sell->foodEstablishment->id,
                    'name' => $sell->foodEstablishment->name,
                    'address' => $sell->foodEstablishment->address,
                    'phone_number' => $sell->foodEstablishment->phone_number,
                ],
                'sell_details' => $sell->sellDetails->map(function ($detail) {
                    return [
                        'id' => $detail->id,
                        'offer_id' => $detail->offer_id,
                        'offer_quantity' => $detail->offer_quantity,
                        'pack_price' => $detail->pack_price,
                        'pack_name' => $detail->pack_name,
                        'pack_description' => $detail->pack_description,
                        'allergens' => $detail->allergens,
                    ];
                })->toArray(),
            ];
        })->toArray();
    }
}
