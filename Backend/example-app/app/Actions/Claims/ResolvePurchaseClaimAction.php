<?php

namespace App\Actions\Claims;

use App\Actions\Offers\ChangeOfferStatusAction;
use App\Contracts\Payment\PaymentServiceInterface;
use App\Enums\ClaimStatus;
use App\Enums\OfferState;
use App\Enums\PaymentStatus;
use App\Enums\TrustScoreCriterion;
use App\Exceptions\BusinessRuleException;
use App\Models\EstablishmentTrustLog;
use App\Models\PurchaseClaim;
use App\Models\Sell;
use App\Models\User;
use App\Notifications\RealtimeOrderNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResolvePurchaseClaimAction
{
    public function __construct(
        private readonly PaymentServiceInterface $paymentService
    ) {}

    /**
     * Resuelve un reclamo de compra como Administrador (aprobando reembolso total/parcial o rechazando).
     *
     * Reglas críticas:
     * - No restaura stock ni modifica Sell.state (no reingresa mercadería entregada o vencida).
     * - Propaga fallas de pasarela abortando la transacción para reintento seguro/idempotente.
     */
    public function execute(
        PurchaseClaim $claim,
        User $admin,
        string $decision,
        ?float $refundAmount = null,
        ?string $resolutionNotes = null,
        bool $isAbusive = false,
        bool $deactivateOffer = false,
        bool $applyTrustPenalty = false
    ): PurchaseClaim {
        if (! in_array($decision, ['approve', 'reject'], true)) {
            throw BusinessRuleException::unprocessable('La decisión de resolución debe ser approve o reject.', 'INVALID_CLAIM_DECISION');
        }

        $resolvedClaim = DB::transaction(function () use (
            $claim,
            $admin,
            $decision,
            $refundAmount,
            $resolutionNotes,
            $isAbusive,
            $deactivateOffer,
            $applyTrustPenalty
        ) {
            $lockedClaim = PurchaseClaim::whereKey($claim->id)->lockForUpdate()->firstOrFail();

            // Whitelist estricta de estados resolubles
            if (! in_array($lockedClaim->status, [ClaimStatus::PENDING, ClaimStatus::UNDER_REVIEW], true)) {
                throw BusinessRuleException::conflict('Este reclamo ya fue resuelto anteriormente.', 'CLAIM_ALREADY_RESOLVED');
            }

            $lockedSell = Sell::with(['sellDetails.offer', 'foodEstablishment.user', 'customer'])
                ->whereKey($lockedClaim->sell_id)
                ->lockForUpdate()
                ->firstOrFail();

            $now = now();

            if ($decision === 'reject') {
                $notes = trim((string) $resolutionNotes);
                if ($notes === '') {
                    throw BusinessRuleException::unprocessable('Debés ingresar una nota explicando el motivo del rechazo.', 'RESOLUTION_NOTES_REQUIRED');
                }

                $lockedClaim->update([
                    'status' => ClaimStatus::REJECTED->value,
                    'is_abusive' => $isAbusive,
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => $now,
                    'resolution_notes' => $notes,
                ]);

                return $lockedClaim->fresh(['sell.sellDetails', 'sell.foodEstablishment.user', 'customer', 'reviewer', 'evidences']);
            }

            // Aprobación de reembolso: exigir pago aprobado vigente
            if ($lockedSell->payment_status !== PaymentStatus::APPROVED) {
                throw BusinessRuleException::unprocessable('La compra no posee un pago aprobado vigente para reembolsar.', 'PAYMENT_NOT_REFUNDABLE');
            }

            $grossAmount = round($lockedSell->getGrossAmount(), 2);
            if ($grossAmount <= 0) {
                throw BusinessRuleException::unprocessable('El monto bruto de la compra no permite emitir un reembolso.', 'INVALID_SELL_GROSS_AMOUNT');
            }

            $requestedAmount = $refundAmount !== null ? round($refundAmount, 2) : $grossAmount;
            if ($requestedAmount <= 0 || $requestedAmount > $grossAmount) {
                throw BusinessRuleException::unprocessable(
                    "El monto de reembolso debe ser mayor a 0 y no superar el total de \${$grossAmount}.",
                    'INVALID_REFUND_AMOUNT'
                );
            }

            $isFullRefund = abs($requestedAmount - $grossAmount) < 0.01;
            $effectiveRefundAmount = $isFullRefund ? $grossAmount : $requestedAmount;
            $gatewayRefundId = null;

            if (! empty($lockedSell->payment_id)) {
                try {
                    $sharedPaymentCount = $lockedSell->preference_id
                        ? Sell::where('preference_id', $lockedSell->preference_id)->count()
                        : Sell::where('payment_id', $lockedSell->payment_id)->count();

                    $gatewayRefundAmount = ($isFullRefund && $sharedPaymentCount <= 1) ? null : $effectiveRefundAmount;

                    $refundResult = $this->paymentService->refundPayment(
                        (string) $lockedSell->payment_id,
                        $gatewayRefundAmount
                    );
                } catch (\Throwable $e) {
                    Log::error('Fallo en pasarela al emitir reembolso por reclamo.', [
                        'claim_id' => $lockedClaim->id,
                        'sell_id' => $lockedSell->id,
                        'payment_id' => $lockedSell->payment_id,
                        'requested_amount' => $effectiveRefundAmount,
                        'error' => $e->getMessage(),
                    ]);

                    throw new BusinessRuleException(
                        'No se pudo procesar el reembolso en la pasarela de pagos. Podés reintentar la aprobación.',
                        502,
                        'PAYMENT_GATEWAY_REFUND_FAILED',
                        ['claim_id' => $lockedClaim->id],
                        ['gateway_error' => $e->getMessage()],
                        $e
                    );
                }

                $gatewayStatus = strtolower((string) ($refundResult['status'] ?? 'approved'));
                if (in_array($gatewayStatus, ['rejected', 'cancelled', 'failed'], true)) {
                    throw new BusinessRuleException(
                        'La pasarela rechazó el reintegro. Podés revisar el pago y reintentar.',
                        502,
                        'PAYMENT_GATEWAY_REFUND_REJECTED',
                        ['claim_id' => $lockedClaim->id, 'gateway_status' => $gatewayStatus]
                    );
                }

                $gatewayRefundId = isset($refundResult['id']) ? (string) $refundResult['id'] : null;

                // En modo mock el adaptador devuelve amount = 0.0 cuando se pasa null; conservar $effectiveRefundAmount
                $reportedAmount = isset($refundResult['amount']) ? (float) $refundResult['amount'] : 0.0;
                if ($reportedAmount > 0 && ! $isFullRefund) {
                    $effectiveRefundAmount = min($grossAmount, round($reportedAmount, 2));
                }
            }

            // Actualizar pago de la venta SIN tocar Sell.state ni restaurar stock
            $lockedSell->update([
                'payment_status' => $isFullRefund ? PaymentStatus::REFUNDED->value : PaymentStatus::PARTIALLY_REFUNDED->value,
                'refunded_amount' => $effectiveRefundAmount,
                'refunded_at' => $now,
            ]);

            $lockedClaim->update([
                'status' => ClaimStatus::APPROVED->value,
                'refund_amount' => $effectiveRefundAmount,
                'gateway_refund_id' => $gatewayRefundId,
                'refunded_at' => $now,
                'reviewed_by' => $admin->id,
                'reviewed_at' => $now,
                'resolution_notes' => $resolutionNotes !== null ? trim($resolutionNotes) : null,
                'is_abusive' => false,
            ]);

            // Acciones moderadoras opcionales sobre el comercio/oferta
            if ($deactivateOffer) {
                $changeOfferStatusAction = new ChangeOfferStatusAction;
                $offersToDeactivate = $lockedSell->sellDetails
                    ->pluck('offer')
                    ->filter(fn ($offer) => $offer && $offer->state !== OfferState::INACTIVE->value)
                    ->unique('id');

                foreach ($offersToDeactivate as $offer) {
                    $changeOfferStatusAction->execute((int) $offer->id, OfferState::INACTIVE->value);
                }
            }

            if ($applyTrustPenalty && $lockedSell->sold_by) {
                EstablishmentTrustLog::create([
                    'food_establishment_id' => $lockedSell->sold_by,
                    'source_type' => TrustScoreCriterion::OTHER->value,
                    'points' => -15,
                    'verified_by_user_id' => $admin->id,
                    'metadata' => [
                        'claim_id' => $lockedClaim->id,
                        'sell_id' => $lockedSell->id,
                        'reason' => $lockedClaim->reason?->value,
                        'notes' => $resolutionNotes,
                    ],
                    'is_active' => true,
                ]);
            }

            return $lockedClaim->fresh(['sell.sellDetails', 'sell.foodEstablishment.user', 'customer', 'reviewer', 'evidences']);
        });

        $this->notifyParties($resolvedClaim);

        return $resolvedClaim;
    }

    private function notifyParties(PurchaseClaim $claim): void
    {
        try {
            $customer = $claim->customer;
            $sellerUser = $claim->sell?->foodEstablishment?->user;

            if ($claim->status === ClaimStatus::APPROVED) {
                $amountFormatted = number_format((float) $claim->refund_amount, 2, ',', '.');
                $isPartial = $claim->sell?->payment_status === PaymentStatus::PARTIALLY_REFUNDED;
                $refundLabel = $isPartial ? 'reembolso parcial' : 'reembolso total';

                if ($customer) {
                    $customer->notify(new RealtimeOrderNotification(
                        eventType: 'claim_approved',
                        title: 'Reclamo aprobado y reembolsado',
                        message: "Tu reclamo #{$claim->id} sobre la compra #{$claim->sell_id} fue aprobado. Emitimos un {$refundLabel} de \${$amountFormatted}.",
                        sellId: $claim->sell_id,
                        establishmentId: $claim->food_establishment_id,
                        actionUrl: $claim->was_picked_up_at_claim ? '/customer/history' : '/customer/purchases',
                        meta: [
                            'claim_id' => $claim->id,
                            'refund_amount' => (float) $claim->refund_amount,
                            'is_partial' => $isPartial,
                        ]
                    ));
                }

                if ($sellerUser) {
                    $sellerUser->notify(new RealtimeOrderNotification(
                        eventType: 'claim_refunded_seller',
                        title: 'Reembolso emitido por reclamo',
                        message: "Se aprobó un {$refundLabel} de \${$amountFormatted} por reclamo sobre la venta #{$claim->sell_id}.",
                        sellId: $claim->sell_id,
                        establishmentId: $claim->food_establishment_id,
                        actionUrl: '/seller/ventas',
                        meta: [
                            'claim_id' => $claim->id,
                            'refund_amount' => (float) $claim->refund_amount,
                            'reason' => $claim->reason?->value,
                        ]
                    ));
                }
            } elseif ($claim->status === ClaimStatus::REJECTED && $customer) {
                $customer->notify(new RealtimeOrderNotification(
                    eventType: 'claim_rejected',
                    title: 'Reclamo rechazado',
                    message: "Tu reclamo #{$claim->id} sobre la compra #{$claim->sell_id} fue rechazado: {$claim->resolution_notes}",
                    sellId: $claim->sell_id,
                    establishmentId: $claim->food_establishment_id,
                    actionUrl: $claim->was_picked_up_at_claim ? '/customer/history' : '/customer/purchases',
                    meta: [
                        'claim_id' => $claim->id,
                        'is_abusive' => (bool) $claim->is_abusive,
                    ]
                ));
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudieron enviar notificaciones tras resolver reclamo.', [
                'claim_id' => $claim->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
