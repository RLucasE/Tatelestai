<?php

namespace App\Contracts\Payment;

use App\Models\Sell;
use App\Models\User;

interface PaymentServiceInterface
{
    /**
     * Crea una preferencia de pago en Mercado Pago Checkout Pro para una venta.
     *
     * @return array{preference_id: string, init_point: string, sandbox_init_point: string}
     */
    public function createPreference(Sell $sell, User $customer): array;

    /**
     * Consulta los detalles de un pago en Mercado Pago.
     *
     * @return array{id: string, status: string, external_reference: ?string, payment_method_id: ?string, transaction_amount: float}
     */
    public function getPaymentDetails(string $paymentId): array;

    /**
     * Emite un reembolso total o parcial de un pago en Mercado Pago.
     *
     * @return array{id: string|int, status: string, amount: float}
     */
    public function refundPayment(string $paymentId, ?float $amount = null): array;
}
