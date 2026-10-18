<?php

namespace App\Payment\Adapters;

use App\Contracts\Payment\PaymentServiceInterface;
use App\Models\Sell;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Payment\PaymentRefundClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoPaymentAdapter implements PaymentServiceInterface
{
    private ?string $accessToken;

    private bool $isSandbox;

    public function __construct()
    {
        $this->accessToken = config('services.mercadopago.access_token');
        $this->isSandbox = (bool) config('services.mercadopago.sandbox', true);

        if (! empty($this->accessToken)) {
            MercadoPagoConfig::setAccessToken($this->accessToken);
        }
    }

    /**
     * Crea una preferencia de pago en Mercado Pago Checkout Pro utilizando el SDK oficial.
     *
     * @throws Exception
     */
    public function createPreference(Sell $sell, User $customer): array
    {
        $sell->loadMissing('sellDetails');

        $frontendUrl = rtrim(config('app.frontend_url', 'http://localhost:3000'), '/');

        // Modo offline / mock para tests y entornos sin token configurado
        if (empty($this->accessToken) || (app()->environment('testing') && ! config('services.mercadopago.test_with_real_api', false))) {
            $mockPrefId = 'pref_mock_'.$sell->id.'_'.uniqid();
            $mockUrl = "{$frontendUrl}/customer/payment/return?status=approved&collection_id=mock_pay_{$sell->id}&collection_status=approved&external_reference={$sell->id}&preference_id={$mockPrefId}";

            return [
                'preference_id' => $mockPrefId,
                'init_point' => $mockUrl,
                'sandbox_init_point' => $mockUrl,
            ];
        }

        $items = [];
        foreach ($sell->sellDetails as $detail) {
            $items[] = [
                'id' => (string) ($detail->offer_id ?? $detail->id),
                'title' => (string) ($detail->pack_name ?: 'Pack Sorpresa Tatelestai'),
                'description' => (string) ($detail->pack_description ?: 'Rescate de comida excedente en Tatelestai'),
                'quantity' => (int) $detail->offer_quantity,
                'unit_price' => (float) $detail->pack_price,
                'currency_id' => 'ARS',
            ];
        }

        $payload = [
            'items' => $items,
            'payer' => [
                'name' => $customer->name,
                'email' => $customer->email,
            ],
            'external_reference' => (string) $sell->id,
            'back_urls' => [
                'success' => "{$frontendUrl}/customer/payment/return?status=approved",
                'pending' => "{$frontendUrl}/customer/payment/return?status=pending",
                'failure' => "{$frontendUrl}/customer/payment/return?status=failure",
            ],
            'auto_return' => 'approved',
            'notification_url' => url('/api/webhooks/mercadopago'),
            'statement_descriptor' => 'TATELESTAI',
        ];

        try {
            MercadoPagoConfig::setAccessToken($this->accessToken);
            $client = new PreferenceClient;
            $preference = $client->create($payload);

            return [
                'preference_id' => (string) $preference->id,
                'init_point' => (string) ($preference->init_point ?? ''),
                'sandbox_init_point' => (string) ($preference->sandbox_init_point ?? ($preference->init_point ?? '')),
            ];
        } catch (MPApiException $e) {
            Log::error('Error MPApiException al crear preferencia en Mercado Pago SDK', [
                'status' => $e->getStatusCode(),
                'api_response' => $e->getApiResponse()?->getContent(),
                'message' => $e->getMessage(),
            ]);
            throw new Exception('No se pudo generar la preferencia de pago en Mercado Pago: '.$e->getMessage(), (int) $e->getCode(), $e);
        } catch (Exception $e) {
            Log::error('Excepción al conectar con Mercado Pago SDK', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Consulta los detalles de un pago en Mercado Pago utilizando el SDK oficial.
     *
     * @throws Exception
     */
    public function getPaymentDetails(string $paymentId): array
    {
        if (str_starts_with($paymentId, 'mock_') || empty($this->accessToken) || ! is_numeric($paymentId)) {
            return [
                'id' => $paymentId,
                'status' => 'approved',
                'external_reference' => null,
                'payment_method_id' => 'account_money',
                'transaction_amount' => 0.0,
            ];
        }

        try {
            MercadoPagoConfig::setAccessToken($this->accessToken);
            $client = new PaymentClient;
            $payment = $client->get((int) $paymentId);

            return [
                'id' => (string) ($payment->id ?? $paymentId),
                'status' => (string) ($payment->status ?? 'pending'),
                'external_reference' => (string) ($payment->external_reference ?? ''),
                'payment_method_id' => (string) ($payment->payment_method_id ?? ''),
                'transaction_amount' => (float) ($payment->transaction_amount ?? 0.0),
            ];
        } catch (MPApiException $e) {
            Log::error('Error MPApiException al consultar pago en Mercado Pago SDK', [
                'payment_id' => $paymentId,
                'status' => $e->getStatusCode(),
                'api_response' => $e->getApiResponse()?->getContent(),
                'message' => $e->getMessage(),
            ]);
            throw new Exception('No se pudo verificar el pago en Mercado Pago: '.$e->getMessage(), (int) $e->getCode(), $e);
        } catch (Exception $e) {
            Log::error('Excepción al consultar pago en Mercado Pago SDK', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Emite un reembolso en Mercado Pago utilizando el SDK oficial.
     *
     * @throws Exception
     */
    public function refundPayment(string $paymentId, ?float $amount = null): array
    {
        if (str_starts_with($paymentId, 'mock_') || empty($this->accessToken) || ! is_numeric($paymentId)) {
            return [
                'id' => 'ref_mock_'.uniqid(),
                'status' => 'approved',
                'amount' => $amount ?? 0.0,
            ];
        }

        try {
            MercadoPagoConfig::setAccessToken($this->accessToken);
            $client = new PaymentRefundClient;
            $pId = (int) $paymentId;

            $options = new RequestOptions;
            $options->setCustomHeaders([
                'X-Idempotency-Key' => uniqid('refund_', true),
            ]);

            if ($amount !== null && $amount > 0) {
                $refund = $client->refund($pId, round($amount, 2), $options);
            } else {
                $refund = $client->refundTotal($pId, $options);
            }

            return [
                'id' => (string) ($refund->id ?? uniqid()),
                'status' => (string) ($refund->status ?? 'approved'),
                'amount' => (float) ($refund->amount ?? ($amount ?? 0.0)),
            ];
        } catch (MPApiException $e) {
            Log::error('Error MPApiException al emitir reembolso en Mercado Pago SDK', [
                'payment_id' => $paymentId,
                'status' => $e->getStatusCode(),
                'api_response' => $e->getApiResponse()?->getContent(),
                'message' => $e->getMessage(),
            ]);
            throw new Exception('Error al procesar el reembolso en Mercado Pago: '.$e->getMessage(), (int) $e->getCode(), $e);
        } catch (Exception $e) {
            Log::error('Excepción al emitir reembolso en Mercado Pago SDK', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
