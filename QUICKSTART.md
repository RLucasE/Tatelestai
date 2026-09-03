# 🚀 Quickstart — Tatelestai

Esta guía documenta el paso a paso para levantar el entorno de desarrollo completo de **Tatelestai** utilizando Docker Compose.

---

## 📋 Requisitos Previos

* [Git](https://git-scm.com/)
* [Docker](https://docs.docker.com/get-docker/) y [Docker Compose v2](https://docs.docker.com/compose/)

---

## 🛠️ Servicios incluidos

| Servicio | Contenedor | Puerto Host | Descripción |
|---|---|---|---|
| **Nginx** | `tatelestai-nginx` | `8000` | Web server / Proxy inverso para Laravel |
| **PHP-FPM** | `tatelestai-php-fpm` | - | Backend PHP 8.3 con extensiones y Xdebug |
| **Queue** | `tatelestai-queue` | - | Worker de colas (`artisan queue:work`) |
| **Vue.js / Vite** | `tatelestai-vue` | `3000` | Frontend en Vue 3 con Vite (HMR) |
| **PostgreSQL** | `tatelestai-postgres` | `5432` | Base de datos principal |
| **Typesense** | `tatelestai-typesense` | `8108` | Motor de búsqueda instantánea |

---

## 👣 Paso a Paso

### 1. Clonar el repositorio

```bash
git clone https://github.com/RLucasE/Tatelestai.git
cd Tatelestai
```

---

### 2. Configurar variables de entorno

El proyecto cuenta con dos archivos `.env` principales. Para una explicación detallada de cada variable (Gmail SMTP, Google Maps, Postgres, Typesense), consulta el [Tutorial de Variables de Entorno](docs/tutorials/configuracion-variables-entorno.md).

#### A. Entorno de Docker Compose
Copiar la plantilla de Docker Compose:
```bash
cp docker-composes/tatelestai/.env.example docker-composes/tatelestai/.env
```

> **Nota para Linux:** Asegúrate de que `UID` y `GID` en `docker-composes/tatelestai/.env` coincidan con tu usuario host (`id -u` e `id -g`, usualmente `1000`) para evitar problemas de permisos en las carpetas montadas.

#### B. Entorno del Backend (Laravel)
Copiar la plantilla de Laravel:
```bash
cp Backend/example-app/.env.example Backend/example-app/.env
```

*(Si no copias este archivo manualmente, Docker lo creará de forma automática al iniciar a partir de `.env.example`).*

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

> ⚡ **Automatizaciones incluidas al arrancar:**
> Al levantar los contenedores, Docker realiza automáticamente:
> 1. **Dependencias de PHP / Laravel:** Ejecuta `composer install` si falta la carpeta `vendor` o si cambiaron las dependencias.
> 2. **Clave de aplicación (`APP_KEY`):** Genera la clave con `php artisan key:generate` si aún no existe en `.env`.
> 3. **Dependencias de Vue / Frontend:** Ejecuta `npm install` dentro del contenedor si falta `node_modules` o si cambió el `package.json`, e inicia el servidor Vite (`npm run dev`).
> 4. **Permisos de almacenamiento:** Configura permisos en las carpetas `storage` y `bootstrap/cache`.
> 5. **Enlace de Storage (`storage:link`):** Configura automáticamente el enlace simbólico de archivos públicos (`public/storage`) si no existe o está roto.

Verifica que todos los contenedores estén corriendo y saludables:
```bash
docker compose ps
```

---

### 4. Inicializar Base de Datos (Migraciones y Seeders)

Con los contenedores activos, ejecuta las migraciones y datos de prueba dentro del contenedor `php-fpm`:

```bash
docker compose exec php-fpm php artisan migrate --seed
```

> ⚠️ **Importante:** Este comando es **obligatorio en el primer inicio**. Crea todas las tablas necesarias en PostgreSQL (incluyendo sesiones, usuarios, productos y ofertas) y las indexa en Typesense. Si intentas acceder a la aplicación antes de ejecutar las migraciones, verás un error del tipo `relation "sessions" does not exist`.

---

### 5. Comprobación y URLs de Acceso

Una vez completados los pasos anteriores, puedes acceder a:

* 🌐 **Frontend (Vue 3 / Vite)**: [http://localhost:3000](http://localhost:3000)
* ⚙️ **Backend API (Laravel)**: [http://localhost:8000/api](http://localhost:8000/api)
* 🔍 **Typesense Health Check**: [http://localhost:8108/health](http://localhost:8108/health)
* 🗄️ **PostgreSQL**: `localhost:5432` (Usuario: `tatelestai`, Password: `secret`, BD: `tatelestai_db`)

---

## 🛠️ Comandos Útiles

* **Levantar los servicios (en el día a día):**
  ```bash
  cd docker-composes/tatelestai
  docker compose up -d
  ```
* **Detener los servicios:**
  ```bash
  docker compose down
  ```
* **Ver logs en tiempo real:**
  ```bash
  docker compose logs -f
  # o de un servicio específico:
  docker compose logs -f php-fpm
  docker compose logs -f vue
  docker compose logs -f queue
  ```
* **Ejecutar comandos artisan:**
  ```bash
  docker compose exec php-fpm php artisan <comando>
  ```
* **Acceder a la terminal de un contenedor:**
  ```bash
  docker compose exec php-fpm bash
  docker compose exec vue sh
  ```
* **Refrescar la base de datos completa con datos de prueba:**
  ```bash
  docker compose exec php-fpm php artisan migrate:fresh --seed
  ```
