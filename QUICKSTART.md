# Quickstart — Tatelestai

Esta guía documenta el paso a paso para levantar el entorno de desarrollo completo de **Tatelestai** utilizando Docker Compose.

---

## Requisitos Previos

* [Git](https://git-scm.com/)
* [Docker](https://docs.docker.com/get-docker/) y [Docker Compose v2](https://docs.docker.com/compose/)

---

## Servicios incluidos

| Servicio | Contenedor | Puerto Host | Descripción |
|---|---|---|---|
| **Nginx** | `tatelestai-nginx` | `8000` | Web server / Proxy inverso para Laravel |
| **PHP-FPM** | `tatelestai-php-fpm` | - | Backend PHP 8.3 con extensiones y Xdebug |
| **Queue** | `tatelestai-queue` | - | Worker de colas (`artisan queue:work`) |
| **Vue.js / Vite** | `tatelestai-vue` | `3000` | Frontend en Vue 3 con Vite (HMR) |
| **PostgreSQL** | `tatelestai-postgres` | `5432` | Base de datos principal |
| **Typesense** | `tatelestai-typesense` | `8108` | Motor de búsqueda instantánea |

---

## Paso a Paso

### 1. Clonar el repositorio

```bash
git clone https://github.com/RLucasE/Tatelestai.git
cd Tatelestai
```

---

### 2. Configurar variables de entorno

El proyecto cuenta con dos archivos `.env`. Para una explicación detallada de cada variable (Gmail SMTP, Google Maps, Postgres, Typesense), consulta el [Tutorial de Variables de Entorno](docs/tutorials/configuracion-variables-entorno.md).

#### A. Entorno de Docker Compose (Requerido)
Copiar la plantilla de Docker Compose:
```bash
cp docker-composes/tatelestai/.env.example docker-composes/tatelestai/.env
```

> **Nota para Linux:** Asegúrate de que `UID` y `GID` en `docker-composes/tatelestai/.env` coincidan con tu usuario host (`id -u` e `id -g`, usualmente `1000`) para evitar problemas de permisos en las carpetas montadas.

#### B. Entorno del Backend (Laravel) *(Opcional)*
Si deseas personalizar variables antes de arrancar, puedes copiar la plantilla de Laravel:
```bash
cp Backend/example-app/.env.example Backend/example-app/.env
```

*(Si omites este paso, el entrypoint de Docker lo creará de forma **100% automática** al arrancar a partir de `.env.example`).*

Los valores por defecto ya vienen configurados para conectarse a los servicios de Docker:
* `DB_HOST=postgres`
* `DB_PORT=5432`
* `DB_DATABASE=tatelestai_db`
* `DB_USERNAME=tatelestai`
* `DB_PASSWORD=secret`
* `TYPESENSE_HOST=typesense`
* `TYPESENSE_PORT=8108`
* `TYPESENSE_API_KEY=xyz`

---

### 3. Construir y levantar los contenedores

Ve a la carpeta del compose y levanta los servicios:

```bash
cd docker-composes/tatelestai
docker compose up -d --build
```

> **Automatizaciones incluidas al arrancar:**
> Al levantar los contenedores, Docker realiza automáticamente:
> 1. **Aprovisionamiento de entorno:** Crea `Backend/example-app/.env` si no existía.
> 2. **Dependencias de PHP / Laravel:** Ejecuta `composer install` si falta la carpeta `vendor` o si cambiaron las dependencias (`composer.json`/`composer.lock`).
> 3. **Clave de aplicación (`APP_KEY`):** Genera la clave con `php artisan key:generate --force` si aún no existe en `.env`.
> 4. **Dependencias de Vue / Frontend:** Ejecuta `npm install` dentro del contenedor si falta `node_modules` o si cambió el `package.json`, e inicia el servidor Vite con HMR (`npm run dev`).
> 5. **Permisos de almacenamiento:** Crea las carpetas y configura permisos `775`/`664` en `storage` y `bootstrap/cache`.
> 6. **Enlace de Storage (`storage:link`):** Configura automáticamente el enlace simbólico de archivos públicos (`public/storage`) si no existe o está roto.
> 7. **Sincronización de Worker:** El contenedor `queue` espera a que `php-fpm` termine el `composer install` y a que PostgreSQL esté listo antes de procesar tareas.

Verifica que todos los contenedores estén corriendo y saludables:
```bash
docker compose ps
```

---

### 4. Inicializar Base de Datos y Búsqueda (Migraciones y Seeders)

Con los contenedores activos, ejecuta las migraciones y datos de prueba desde cualquier carpeta utilizando el nombre del contenedor `tatelestai-php-fpm`:

```bash
docker exec -it tatelestai-php-fpm php artisan migrate --seed
```

> **Importante:** Este comando es **el único paso manual necesario en el primer inicio**. Crea todas las tablas en PostgreSQL (sesiones, usuarios, comercios, productos y ofertas) y **los seeders indexan automáticamente todas las ofertas en Typesense** (`Offer::makeAllSearchable()`). No requieres ejecutar `scout:import` manualmente.

---

### 5. Comprobación y URLs de Acceso

Una vez completados los pasos anteriores, puedes acceder a:

* **Frontend (Vue 3 / Vite)**: [http://localhost:3000](http://localhost:3000)
* **Backend API (Laravel)**: [http://localhost:8000/api](http://localhost:8000/api)
* **Typesense Health Check**: [http://localhost:8108/health](http://localhost:8108/health)
* **PostgreSQL**: `localhost:5432` (Usuario: `tatelestai`, Password: `secret`, BD: `tatelestai_db`)

---

## Comandos Útiles

> **Ventaja de usar el nombre del contenedor:** Los comandos con `docker exec -it` funcionan desde **cualquier directorio de tu terminal**, sin necesidad de ingresar a la carpeta `docker-composes/tatelestai`.

* **Ejecutar comandos artisan:**
  ```bash
  docker exec -it tatelestai-php-fpm php artisan <comando>
  ```
* **Ejecutar Composer:**
  ```bash
  docker exec -it tatelestai-php-fpm composer <comando>
  ```
* **Acceder a la terminal de los contenedores:**
  ```bash
  docker exec -it tatelestai-php-fpm bash
  docker exec -it tatelestai-vue sh
  ```
* **Refrescar la base de datos completa con datos de prueba:**
  ```bash
  docker exec -it tatelestai-php-fpm php artisan migrate:fresh --seed
  ```
* **Levantar o detener los servicios (requiere estar en el directorio de compose):**
  ```bash
  cd docker-composes/tatelestai
  docker compose up -d
  docker compose down
  ```
* **Ver logs en tiempo real:**
  ```bash
  docker logs -f tatelestai-php-fpm
  docker logs -f tatelestai-vue
  docker logs -f tatelestai-queue
  ```
