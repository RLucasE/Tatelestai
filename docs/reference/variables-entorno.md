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
