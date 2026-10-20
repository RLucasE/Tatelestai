# Cómo Conectar y Depurar WebSockets con Laravel Reverb

> **Tipo**: Guía How-To (Diátaxis)  
> **Objetivo**: Pasos prácticos para verificar la salud del servidor WebSocket Reverb, inspeccionar conexiones en el navegador, autorizar canales privados y diagnosticar problemas comunes de conectividad en Tatelestai.

---

## Paso 1: Verificar el Estado del Contenedor Reverb

Asegúrate de que el contenedor `tatelestai-reverb` esté activo y ejecutando el demonio en el puerto `8080`:

```bash
docker ps --filter "name=tatelestai-reverb"
```

Para ver la salida en vivo del servidor WebSocket:

```bash
docker logs -f tatelestai-reverb
```

Deberías observar el banner de inicio de Laravel Reverb:
```text
INFO  Starting Reverb server on 0.0.0.0:8080 (0.0.0.0)
INFO  Pruning stale connections...
```

---

## Paso 2: Inspeccionar la Conexión WebSocket en el Navegador

1. Abre el frontend de Tatelestai en tu navegador (`http://localhost:3000`).
2. Abre las **Herramientas de Desarrollador** (`F12` o `Ctrl + Shift + I`).
3. Dirígete a la pestaña **Network** (Red) y filtra por la categoría **WS** (WebSockets).
4. Recarga la página: verás una solicitud dirigida a:
   $$\texttt{ws://localhost:8080/app/tatelestai-key?protocol=7&client=js...}$$
5. Haz clic en la conexión y abre la pestaña **Messages** o **Frames**:
   * Flecha verde hacia abajo: `{"event":"pusher:connection_established","data":"..."}`.
   * Flecha roja hacia arriba: `{"event":"pusher:subscribe","data":{"channel":"private-App.Models.User.1"}}`.
   * Confirmación del servidor: `{"event":"pusher_internal:subscription_succeeded","channel":"..."}`.

---

## Paso 3: Verificar la Autorización del Canal Privado

Los canales privados de Laravel requieren autorización a través de la API HTTP:
1. En la pestaña **Network**, filtra por `Fetch/XHR`.
2. Busca la petición `POST /api/broadcasting/auth` o `/broadcasting/auth`.
3. Debe retornar código de estado `HTTP 200 OK` con un cuerpo JSON de firma:
   ```json
   {
     "auth": "tatelestai-key:5f8e91c7a1..."
   }
   ```
4. Si esta petición devuelve `401 Unauthorized` o `403 Forbidden`, verifica que tu sesión en el frontend esté activa y que las cookies de Sanctum se estén transmitiendo (`withCredentials: true`).

---

## Paso 4: Disparar una Notificación Manual de Prueba desde Tinker

Para verificar la reactividad visual del frontend sin tener que realizar una compra completa:

1. Ingresa a la consola interactiva de Laravel:
   ```bash
   docker exec -it tatelestai-php-fpm php artisan tinker
   ```
2. Identifica el usuario con el que tienes la sesión iniciada en el navegador:
   ```php
   $user = App\Models\User::where('email', 'cliente@tatelestai.test')->first();
   ```
3. Emite la notificación en tiempo real:
   ```php
   $user->notify(new App\Notifications\RealtimeOrderNotification(
       eventType: 'cart_item_stock_reduced',
       title: 'Prueba de WebSocket',
       message: '¡El mensaje llegó instantáneamente desde Tinker!',
       offerId: 1,
       establishmentId: 1,
       actionUrl: '/customer/carrito'
   ));
   ```
4. Observa tu navegador: verás aparecer de inmediato un **Toast de notificación** de advertencia y el contador numérico de la campana en el navbar se incrementará a `1`.

---

## Paso 5: Resolución de Problemas Frecuentes

### 1. Error `WebSocket connection to 'ws://localhost:8080/...' failed`
* **Causa**: El puerto `8080` no está expuesto o hay un conflicto con otra aplicación en tu máquina host.
* **Solución**: Revisa con `netstat -tlpn | grep 8080` o ajusta `REVERB_PORT=8085` en `docker-composes/tatelestai/.env` y reconstruye con `docker compose up -d reverb`.

### 2. Error `Broadcast connection [reverb] is not defined`
* **Causa**: La variable `BROADCAST_CONNECTION` en `Backend/example-app/.env` no está configurada como `reverb`.
* **Solución**: Declara `BROADCAST_CONNECTION=reverb` y ejecuta `docker exec tatelestai-php-fpm php artisan config:clear`.

### 3. El frontend no recibe los eventos aunque Tinker no arroja errores
* **Causa**: `REVERB_HOST` en el backend está configurado como `127.0.0.1` o `localhost` en lugar del nombre DNS del contenedor en la red Docker (`reverb`).
* **Solución**: En `Backend/example-app/.env`, verifica:
  ```dotenv
  REVERB_HOST="reverb"
  REVERB_PORT=8080
  ```
  *(Dentro de la red interna de Docker, PHP-FPM debe conectarse a `http://reverb:8080`, mientras que el navegador en el host se conecta a `ws://localhost:8080`).*
