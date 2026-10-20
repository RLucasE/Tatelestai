# Referencia: Variables de Entorno (.env)

> **Tipo**: Referencia (Diátaxis)  
> **Objetivo**: Catálogo de parámetros de configuración y variables de entorno utilizadas por Docker y Laravel.

---

## 1. Docker Compose (`docker-composes/tatelestai/.env`)

Variables utilizadas para orquestar la infraestructura de contenedores:

| Variable | Valor por defecto | Descripción |
|---|---|---|
| `UID` / `GID` | `1000` | Identificador de usuario y grupo para evitar conflictos de permisos con archivos generados en el host. |
| `NGINX_PORT` | `8000` | Puerto público del proxy Nginx en la máquina host (fallback a 80 si no existe `.env`). |
| `FRONTEND_TARGET`| `development` | Stage del Dockerfile de Node: `development` (servidor Vite con HMR) o `production` (build estático con Nginx SPA). |
| `VITE_PORT` | `3000` | Puerto público del servidor de desarrollo Vite (Frontend). |
| `NODE_ENV` | `development` | Modo de ejecución del entorno Node.js. |
| `POSTGRES_PORT` | `5432` | Puerto público de la base de datos PostgreSQL. |
| `POSTGRES_DB` | `tatelestai_db` | Nombre de la base de datos PostgreSQL. |
| `POSTGRES_USER` | `tatelestai` | Usuario maestro de PostgreSQL. |
| `POSTGRES_PASSWORD` | `secret` | Contraseña del usuario maestro de PostgreSQL. |
| `TYPESENSE_PORT` | `8108` | Puerto público de la API de Typesense. |
| `TYPESENSE_API_KEY` | `xyz` | Llave de autenticación para consultar o indexar en Typesense. |
| `REVERB_PORT` | `8080` | Puerto público del servidor WebSocket Reverb en la máquina host. |
| `XDEBUG_ENABLED` | `true` | Activa o desactiva la extensión Xdebug en PHP-FPM (desactivada en el worker de colas). |
| `XDEBUG_MODE` | `develop,coverage,debug,profile` | Modos de operación configurados para Xdebug. |

---

## 2. Backend Laravel (`Backend/example-app/.env`)

Parámetros clave de conexión hacia los servicios de la red Docker:

| Variable | Valor recomendado (Docker) | Propósito |
|---|---|---|
| `APP_URL` | `http://localhost:8000` | URL base accesible del backend vía Nginx. |
| `APP_KEY` | *(autogenerada)* | Si se deja vacía, el entrypoint la genera automáticamente con `php artisan key:generate --force`. |
| `DB_CONNECTION` | `pgsql` | Driver de base de datos PostgreSQL. |
| `DB_HOST` | `postgres` | Nombre DNS del contenedor de Postgres en la red interna de Docker. |
| `DB_PORT` | `5432` | Puerto interno de conexión a Postgres. |
| `DB_DATABASE` | `tatelestai_db` | Nombre de la base de datos. |
| `DB_USERNAME` | `tatelestai` | Usuario de conexión. |
| `DB_PASSWORD` | `secret` | Contraseña de conexión. |
| `SCOUT_DRIVER` | `typesense` | Driver del motor de búsqueda Laravel Scout. |
| `TYPESENSE_HOST` | `typesense` | Nombre DNS del contenedor de Typesense en la red interna. |
| `TYPESENSE_PORT` | `8108` | Puerto interno de conexión a Typesense. |
| `TYPESENSE_API_KEY`| `xyz` | Clave que coincide con la configurada en Docker Compose. |
| `QUEUE_CONNECTION` | `database` | Conexión del procesador de colas (`queue`). |
| `FRONTEND_URL` | `http://localhost:3000` | URL del frontend para resolución de CORS y Sanctum. |
| `SANCTUM_STATEFUL_DOMAINS` | `localhost:3000,localhost:8000` | Dominios habilitados para autenticación SPA basada en cookies de sesión. |

### Configuración de WebSockets y Tiempo Real (Laravel Reverb)

| Variable | Valor recomendado (Docker) | Propósito |
|---|---|---|
| `BROADCAST_CONNECTION` | `reverb` | Driver de broadcasting activo de Laravel. |
| `REVERB_APP_ID` | `tatelestai-app` | Identificador de aplicación para el servidor Reverb. |
| `REVERB_APP_KEY` | `tatelestai-key` | Clave pública de autenticación del socket. |
| `REVERB_APP_SECRET` | `tatelestai-secret` | Clave secreta para autorizar emisiones desde PHP-FPM hacia Reverb. |
| `REVERB_HOST` | `reverb` | Nombre DNS del contenedor de Reverb en la red interna de Docker. |
| `REVERB_PORT` | `8080` | Puerto de comunicación interna con el servidor Reverb. |
| `REVERB_SCHEME` | `http` | Protocolo de transporte interno (`http` en desarrollo local). |
| `VITE_REVERB_APP_KEY`| `${REVERB_APP_KEY}` | Clave pública inyectada al frontend para instanciar Laravel Echo. |
| `VITE_REVERB_HOST` | `localhost` | Host accesible desde el navegador del usuario. |
| `VITE_REVERB_PORT` | `8080` | Puerto accesible desde el navegador del usuario (`${REVERB_PORT}`). |
| `VITE_REVERB_SCHEME`| `http` | Esquema de conexión WebSocket para el cliente (`http` / `ws`). |

### Pasarela de Pagos (Mercado Pago Checkout Pro)

| Variable | Valor recomendado (Docker) | Propósito |
|---|---|---|
| `MERCADOPAGO_ACCESS_TOKEN` | *(Access Token de prueba/producción)* | Token privado para autenticar llamadas a la API de Mercado Pago. |
| `MERCADOPAGO_PUBLIC_KEY` | *(Public Key)* | Clave pública para inicializar componentes de frontend o SDKs de cliente. |
| `MERCADOPAGO_WEBHOOK_SECRET` | *(Clave secreta del webhook)* | Firma criptográfica para validar la autenticidad de notificaciones entrantes. |
| `MERCADOPAGO_SANDBOX` | `true` | `true` para operar contra el sandbox de prueba; `false` en producción. |
| `TATELESTAI_COMMISSION_PERCENTAGE` | `15.0` | Porcentaje de comisión retenido por la plataforma sobre el importe bruto de la venta. |
