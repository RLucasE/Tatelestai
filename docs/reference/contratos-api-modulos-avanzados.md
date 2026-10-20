# Referencia: Contratos de API para Módulos Avanzados

> **Tipo**: Referencia (Diátaxis)  
> **Objetivo**: Ficha técnica exhaustiva de los endpoints REST, parámetros, cabeceras, cuerpos JSON y respuestas para los módulos de Pagos, Notificaciones, Calificaciones, Verificación/Trust Score y Plantillas de Packs.

---

## 1. Pasarela de Pagos (Mercado Pago Checkout Pro)

### `POST /api/customer/payment/create-preference`
Genera una preferencia de pago en Mercado Pago para una orden de venta en estado `SellState::PENDING`.

* **Autenticación**: Obligatoria (`Bearer <token>` o cookie Sanctum). Rol: `customer`. Estado: `active`.
* **Cuerpo de la Petición (`application/json`)**:
  ```json
  {
    "sell_id": 42
  }
  ```
* **Respuesta Exitosa (`200 OK`)**:
  ```json
  {
    "preference_id": "123456789-abcdef-4321",
    "init_point": "https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=123456789-abcdef-4321",
    "sandbox_init_point": "https://sandbox.mercadopago.com.ar/checkout/v1/redirect?pref_id=123456789-abcdef-4321"
  }
  ```
* **Errores Posibles**:
  - `403 Forbidden`: La orden no pertenece al cliente autenticado.
  - `422 Unprocessable Content`: La orden no se encuentra en estado `pending` o ya fue abonada.

---

### `POST /api/customer/payment/verify`
Consulta el estado de un pago en Mercado Pago tras el retorno del checkout en frontend.

* **Autenticación**: Obligatoria (`customer`, `active`).
* **Cuerpo de la Petición (`application/json`)**:
  ```json
  {
    "payment_id": "987654321"
  }
  ```
* **Respuesta Exitosa (`200 OK`)**:
  ```json
  {
    "status": "approved",
    "payment_status": "approved",
    "sell_id": 42,
    "sell_state": "ready",
    "message": "Pago verificado y orden lista para retiro."
  }
  ```

---

### `POST /api/webhooks/mercadopago`
Endpoint público e idempotente que recibe las notificaciones asíncronas enviadas por los servidores de Mercado Pago.

* **Autenticación**: Pública (validada contra la API oficial de Mercado Pago).
* **Parámetros de Query / Body (`application/json`)**:
  ```json
  {
    "action": "payment.updated",
    "api_version": "v1",
    "data": {
      "id": "987654321"
    },
    "type": "payment"
  }
  ```
* **Respuesta Exitosa (`200 OK`)**:
  ```json
  {
    "status": "success",
    "message": "Webhook processed"
  }
  ```

---

## 2. Notificaciones en Tiempo Real

### `GET /api/notifications`
Obtiene la lista paginada de notificaciones del usuario autenticado y el conteo de no leídas.

* **Autenticación**: Obligatoria (cualquier rol autenticado).
* **Respuesta Exitosa (`200 OK`)**:
  ```json
  {
    "unread_count": 2,
    "data": [
      {
        "id": "c1a9f1a0-2f9b-4b2a-8c5e-8b6b3e9b1a01",
        "type": "App\\Notifications\\RealtimeOrderNotification",
        "data": {
          "event_type": "cart_item_stock_reduced",
          "title": "Stock insuficiente en tu carrito",
          "message": "Solo queda(n) 1 unidad(es) de «Pack Medialunas». Tenías 2 en tu carrito.",
          "offer_id": 15,
          "establishment_id": 3,
          "action_url": "/customer/carrito",
          "meta": {
            "offer_id": 15,
            "cart_quantity": 2,
            "available_quantity": 1
          }
        },
        "read_at": null,
        "created_at": "2026-10-19T18:45:00.000000Z"
      }
    ]
  }
  ```

---

### `PATCH /api/notifications/{id}/read`
Marca una notificación específica como leída.

* **Autenticación**: Obligatoria.
* **Respuesta Exitosa (`200 OK`)**:
  ```json
  {
    "message": "Notificación marcada como leída.",
    "unread_count": 1
  }
  ```

---

### `POST /api/notifications/read-all`
Marca todas las notificaciones pendientes del usuario como leídas.

* **Autenticación**: Obligatoria.
* **Respuesta Exitosa (`200 OK`)**:
  ```json
  {
    "message": "Todas las notificaciones han sido marcadas como leídas.",
    "unread_count": 0
  }
  ```

---

## 3. Calificaciones y Reseñas de Establecimientos

### `POST /api/customer/pack-reservations/{sell}/review`
Registra la calificación por estrellas de una compra retirada (`SellState::PICKED_UP`).

* **Autenticación**: Obligatoria (`customer`, `active`).
* **Cuerpo de la Petición (`application/json`)**:
  ```json
  {
    "rating": 5
  }
  ```
* **Reglas de Validación**: `rating` entero obligatorio entre `1` y `5`.
* **Respuesta Exitosa (`201 Created`)**:
  ```json
  {
    "message": "Calificación registrada exitosamente.",
    "data": {
      "id": 12,
      "sell_id": 42,
      "rating": 5,
      "food_establishment_id": 3,
      "created_at": "2026-10-19T19:00:00.000000Z"
    }
  }
  ```
* **Errores de Dominio**:
  - `403 Forbidden`: El usuario no es el comprador de la venta.
  - `422 Unprocessable Content`: La orden no está en `picked_up`, ya fue calificada previamente, o han pasado más de 14 días desde la compra.

---

### `GET /api/establishments/{id}/rating-summary`
Obtiene el desglose estadístico y la doble media de reputación de un establecimiento.

* **Autenticación**: Obligatoria.
* **Respuesta Exitosa (`200 OK`)**:
  ```json
  {
    "data": {
      "rating_average": 4.65,
      "reviews_count": 84,
      "recent_rating_average": 4.90,
      "recent_reviews_count": 18,
      "stars_distribution": {
        "5": 60,
        "4": 18,
        "3": 4,
        "2": 1,
        "1": 1
      }
    }
  }
  ```

---

## 4. Verificación de Comercios y Trust Score (Admin / Seller)

### `GET /api/seller/verification-summary`
Consulta el estado actual de auditoría y puntaje de confianza acumulado del comercio del vendedor autenticado.

* **Autenticación**: Obligatoria (`seller`).
* **Respuesta Exitosa (`200 OK`)**:
  ```json
  {
    "status": "waiting_for_confirmation",
    "trust_score": 80,
    "required_score": 70,
    "criteria_met": [
      {
        "criterion": "tax_certificate",
        "points": 30,
        "verified_at": "2026-10-18T14:00:00Z"
      },
      {
        "criterion": "municipal_permit",
        "points": 30,
        "verified_at": "2026-10-18T14:15:00Z"
      },
      {
        "criterion": "pest_control_certificate",
        "points": 20,
        "verified_at": "2026-10-18T14:30:00Z"
      }
    ]
  }
  ```

---

### `POST /api/adm/establishments/{id}/files/{fileId}/review`
Audita y dictamina un archivo probatorio presentado por el establecimiento.

* **Autenticación**: Obligatoria (`admin`, `active`).
* **Cuerpo de la Petición (`application/json`)**:
  ```json
  {
    "status": "approved",
    "source_type": "tax_certificate",
    "rejection_reason": null
  }
  ```
* **Respuesta Exitosa (`200 OK`)**:
  ```json
  {
    "message": "Archivo auditado con éxito.",
    "new_trust_score": 80
  }
  ```

---

### `POST /api/adm/establishments/{id}/verify-phone-call`
Registra la comprobación por llamada telefónica efectuada al establecimiento.

* **Autenticación**: Obligatoria (`admin`, `active`).
* **Cuerpo de la Petición (`application/json`)**:
  ```json
  {
    "phone_number": "+543875551234",
    "contact_person": "Martín (Encargado)",
    "notes": "Confirmó horario de atención y venta de excedentes."
  }
  ```
* **Respuesta Exitosa (`200 OK`)**:
  ```json
  {
    "message": "Llamada registrada y puntaje adjudicado (+30 pts).",
    "new_trust_score": 110
  }
  ```

---

### `PATCH /api/adm/establishments/{id}/verify`
Dictamina la aprobación formal de un comercio, transicionándolo a `ACTIVE`.

* **Autenticación**: Obligatoria (`admin`, `active`).
* **Respuesta Exitosa (`200 OK`)**:
  ```json
  {
    "message": "Establecimiento aprobado y activado exitosamente."
  }
  ```

---

## 5. Plantillas de Packs y Dashboard Operativo (Seller)

### `GET /api/pack-templates`
Lista las plantillas de bolsas sorpresa creadas por el comercio del vendedor autenticado.

* **Autenticación**: Obligatoria (`seller`, `active`).
* **Respuesta Exitosa (`200 OK`)**:
  ```json
  {
    "data": [
      {
        "id": 5,
        "name": "Pack Panadería Tradicional",
        "description": "Variedad de facturas, medialunas y chipás del turno mañana.",
        "original_price": 5000.00,
        "selling_price": 2000.00,
        "allergens": ["gluten", "lactosa"],
        "estimated_weight_kg": 0.85
      }
    ]
  }
  ```

---

### `POST /api/pack-templates`
Crea una nueva plantilla reutilizable.

* **Autenticación**: Obligatoria (`seller`, `active`).
* **Cuerpo de la Petición (`application/json`)**:
  ```json
  {
    "name": "Pack Rotisería Mediodía",
    "description": "Porciones de tartas, empanadas o guarniciones cocinadas en el día.",
    "original_price": 6000.00,
    "selling_price": 2500.00,
    "allergens": ["gluten"],
    "estimated_weight_kg": 1.20
  }
  ```
* **Respuesta Exitosa (`201 Created`)**: Devuelve el recurso de la plantilla creada.

---

### `GET /api/seller/dashboard`
Retorna las métricas consolidadas en tiempo real para la consola de mostrador.

* **Autenticación**: Obligatoria (`seller`, `active`).
* **Respuesta Exitosa (`200 OK`)**:
  ```json
  {
    "active_packs_count": 4,
    "today_sales_count": 7,
    "pending_pickups_count": 3,
    "expired_packs_count": 0,
    "today_gross_revenue": 17500.00,
    "today_net_revenue": 14875.00,
    "platform_fee_total": 2625.00
  }
  ```
