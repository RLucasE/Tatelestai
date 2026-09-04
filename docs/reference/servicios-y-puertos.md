# Referencia: Servicios, Contenedores y Puertos

> **Tipo**: Referencia (Diátaxis)  
> **Objetivo**: Catálogo técnico de la infraestructura de contenedores orquestada por Docker Compose en Tatelestai.

---

## Tabla de Servicios

| Contenedor | Imagen / Base | Puerto Host | Puerto Interno | Función |
|---|---|---|---|---|
| `tatelestai-vue` | `node:22-alpine` (Vite) | `3000` | `3000` | Servidor de desarrollo frontend con HMR. |
| `tatelestai-nginx` | `nginx:alpine` | `8000` *(o `${NGINX_PORT}`)* | `80` | Proxy inverso web para Laravel API. |
| `tatelestai-php-fpm` | `php:8.3-fpm-alpine` | N/A | `9000` | Backend API Laravel 12 (FastCGI). Incluye Composer 2. |
| `tatelestai-queue` | `php:8.3-fpm-alpine` | N/A | N/A | Worker para colas en segundo plano (`queue:work`). |
| `tatelestai-postgres` | `postgres:16-alpine` | `5432` | `5432` | Base de datos relacional principal. |
| `tatelestai-typesense`| `typesense/typesense:30.1`| `8108` | `8108` | Motor de búsqueda instantánea y geocodificación. |

---

## Automatizaciones en el Arranque (Entrypoints)

Los contenedores de Tatelestai están diseñados para inicializarse de forma completamente desatendida mediante scripts de entrada (*entrypoints*):

### 1. Backend (`tatelestai-php-fpm`)
* **Archivo de entorno**: Si `/var/www/.env` no existe, se crea automáticamente a partir de `.env.example`.
* **Dependencias de Composer**: Si falta `vendor/autoload.php` o si los manifiestos (`composer.json`/`composer.lock`) son más recientes que la carpeta `vendor`, ejecuta `composer install --no-interaction --prefer-dist --optimize-autoloader`. Durante este proceso, crea un archivo de bloqueo temporal (`storage/.composer_installing`).
* **Clave de cifrado (`APP_KEY`)**: Si la clave está ausente o vacía en `.env`, ejecuta `php artisan key:generate --force`.
* **Enlace de almacenamiento (`storage:link`)**: Si no existe el symlink en `public/storage`, ejecuta `php artisan storage:link --relative`.
* **Espera activa de base de datos**: Verifica la disponibilidad del puerto de PostgreSQL antes de iniciar PHP-FPM.
* **Permisos y estructura**: Asegura la existencia de `storage/framework/{cache,sessions,views}`, `storage/logs` y `bootstrap/cache` asignando permisos `775`/`664` bajo el usuario no-root `www` (`${UID}:${GID}`).

### 2. Worker de Colas (`tatelestai-queue`)
* **Sincronización con `php-fpm`**: Espera de forma activa (hasta 180s) a que el contenedor `php-fpm` finalice la instalación de Composer y elimine el archivo `.composer_installing`.
* **Espera activa de base de datos**: Confirma la conectividad con PostgreSQL antes de ejecutar el comando `php artisan queue:work --sleep=3 --tries=3 --timeout=90`.

### 3. Frontend (`tatelestai-vue`)
* **Dependencias de Node**: Si falta `node_modules` o si cambiaron `package.json`/`package-lock.json`, ejecuta `npm install` automáticamente.
* **Permisos**: Ajusta los permisos de `package-lock.json` al usuario host `${UID}:${GID}` para evitar conflictos en Git.
* **Servidor Vite**: Lanza `npm run dev -- --host 0.0.0.0` para permitir acceso externo con recarga en caliente (HMR).

---

## Herramientas de Desarrollo y CLI

Composer 2 está integrado directamente en la imagen de `tatelestai-php-fpm`, permitiendo ejecutar comandos sin herramientas auxiliares y desde cualquier carpeta:

```bash
# Ejecutar Composer directamente en el backend (desde cualquier carpeta)
docker exec -it tatelestai-php-fpm composer require <paquete>
docker exec -it tatelestai-php-fpm composer update
```

### Perfiles de Desarrollo (`--profile tools`)
Contenedores auxiliares opcionales para entornos aislados o pipelines de CI:

| Contenedor | Comando base | Uso habitual |
|---|---|---|
| `tatelestai-composer` | `composer:latest` | `docker compose --profile tools run --rm composer install` |
| `tatelestai-npm` | `node:22-alpine` | `docker compose --profile tools run --rm npm install` |

---

## Redes Docker

* **Nombre de red**: `tatelestai-network`
* **Driver**: `bridge`
* **Resolución interna**: Todos los contenedores resuelven los nombres de servicio DNS internamente (ej. `postgres:5432`, `typesense:8108`, `php-fpm:9000`).

---

## Volúmenes y Persistencia

| Servicio | Ruta en Host | Ruta en Contenedor | Persistencia |
|---|---|---|---|
| PostgreSQL | `docker-composes/tatelestai/data/postgres/` | `/var/lib/postgresql/data` | Persistente (física en disco). |
| Typesense | `docker-composes/tatelestai/data/typesense/` | `/data` | Persistente (física en disco). |
| Backend | `Backend/example-app/` | `/var/www` | Bind mount (hot-reload en dev). |
| Frontend | `Frontend/vue-project/` | `/app` | Bind mount (excluyendo `/app/node_modules` vía volumen anónimo). |
