# Cómo Probar Pagos y Webhooks de Mercado Pago Localmente

> **Tipo**: Guía How-To (Diátaxis)  
> **Objetivo**: Proporcionar los pasos exactos para configurar credenciales sandbox, simular pagos con Checkout Pro, disparar webhooks de prueba y verificar reembolsos automáticos en el entorno de desarrollo.

---

## Prerrequisitos

* Contenedores de Tatelestai en ejecución (`docker compose up -d`).
* Una cuenta de desarrollador en [Mercado Pago Developers](https://www.mercadopago.com.ar/developers).
* Credenciales de prueba (*Test Access Token* y *Test Public Key*).

---

## Paso 1: Configurar Variables de Entorno en el Backend

Edita `Backend/example-app/.env` y asegúrate de declarar el modo sandbox con tus claves de prueba:

```dotenv
MERCADOPAGO_ACCESS_TOKEN="TEST-1234567890-abcdef..."
MERCADOPAGO_PUBLIC_KEY="TEST-abcdef-1234..."
MERCADOPAGO_WEBHOOK_SECRET="test_webhook_secret_key"
MERCADOPAGO_SANDBOX=true
TATELESTAI_COMMISSION_PERCENTAGE=15.0
```

Tras modificar el archivo, limpia la caché de configuración en el contenedor:

```bash
docker exec tatelestai-php-fpm php artisan config:clear
```

---

## Paso 2: Generar una Orden de Prueba desde la Interfaz

1. Inicia sesión en el Frontend (`http://localhost:3000`) como un usuario con rol `customer`.
2. Explora el catálogo y agrega una bolsa sorpresa a tu carrito.
3. Dirígete a `/customer/carrito` y presiona **"Reservar y Pagar"**.
4. La aplicación enviará la petición `POST /api/customer/payment/create-preference` y abrirá la pasarela de Checkout Pro en su modalidad Sandbox.
5. Utiliza las [Tarjetas de Prueba de Mercado Pago](https://www.mercadopago.com.ar/developers/es/docs/checkout-pro/additional-content/test-cards):
   * **Tarjeta Aprobada**: `APRO` (Nombre: `APRO`, Vencimiento: fecha futura, CVV: `123`).

---

## Paso 3: Simular la Notificación Asíncrona (Webhook) con `curl`

En desarrollo local, los servidores de Mercado Pago no pueden alcanzar directamente tu `localhost` a menos que utilices un túnel (como ngrok o cloudflared).

Para probar el procesamiento inmediato del webhook de forma manual, ejecuta el siguiente comando `curl` desde tu terminal:

```bash
curl -X POST http://localhost:8000/api/webhooks/mercadopago \
  -H "Content-Type: application/json" \
  -d '{
    "action": "payment.updated",
    "api_version": "v1",
    "data": {
      "id": "123456789"
    },
    "type": "payment"
  }'
```

> [!NOTE]
> En entorno Sandbox con credenciales válidas, el backend llamará a `https://api.mercadopago.com/v1/payments/123456789` para cotejar los datos del pago y transicionará la orden `Sell` a `SellState::READY` y `PaymentStatus::APPROVED`.

---

## Paso 4: Probar Cancelación y Reembolso Automático

1. Con la orden en estado `READY` (Confirmada y lista para retiro), ingresa a la vista de pedidos del cliente (`/customer/pedidos`).
2. Haz clic en **"Cancelar Reserva"**.
3. El backend invocará `CancelPurchaseAction`, que a su vez ejecutará:
   * La llamada a la API de Mercado Pago para reembolsar el importe (`refundPayment`).
   * La restitución inmediata del stock en la oferta (`RestoreOfferStockAction`).
   * El envío de una notificación en tiempo real al vendedor avisando que la reserva fue cancelada.
4. Confirma el estado en base de datos mediante Tinker:

```bash
docker exec -it tatelestai-php-fpm php artisan tinker
```
```php
$sell = App\Models\Sell::latest()->first();
$sell->state;          // Debe retornar: "cancelled"
$sell->payment_status; // Debe retornar: "refunded"
$sell->refunded_at;    // Debe mostrar la marca de tiempo actual
```

---

## Paso 5: Ejecución de la Suite de Pruebas Automatizadas

Para validar que la integración no presente regresiones de código, ejecuta la suite de tests unitarios y de integración con Pest:

```bash
docker exec tatelestai-php-fpm php artisan test tests/Feature/MercadoPagoPaymentAndCommissionTest.php
```
