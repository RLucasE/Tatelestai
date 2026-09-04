# Tutorial: Onboarding y Primer Despliegue Local

> **Tipo**: Tutorial (Diátaxis)  
> **Objetivo**: Llevarte desde un repositorio recién clonado hasta ver la aplicación funcionando en tu navegador, sin desviarnos en teoría ni configuraciones avanzadas.

---

## Requisitos previos

Antes de comenzar, asegúrate de tener instalado en tu sistema:
* [Docker](https://docs.docker.com/get-docker/) (v24.0+)
* [Docker Compose v2](https://docs.docker.com/compose/)
* [Git](https://git-scm.com/)

---

## Paso 1: Clonar el repositorio

Abre tu terminal y clona el proyecto en tu carpeta de trabajo:

```bash
git clone https://github.com/RLucasE/Tatelestai.git
cd Tatelestai
```

---

## Paso 2: Crear el archivo de entorno

Copia la plantilla de variables de Docker Compose:

```bash
cp docker-composes/tatelestai/.env.example docker-composes/tatelestai/.env
```

> **Nota:** No es estrictamente necesario copiar manualmente `Backend/example-app/.env.example`, ya que Docker lo aprovisionará automáticamente al iniciar si detecta que no existe. Si deseas personalizar credenciales de base de datos o claves de Google por adelantado, puedes copiarlo con `cp Backend/example-app/.env.example Backend/example-app/.env`.

---

## Paso 3: Construir e iniciar los contenedores

Navega a la carpeta de infraestructura e inicia los servicios en segundo plano:

```bash
cd docker-composes/tatelestai
docker compose up -d --build
```

> **¿Qué ocurre automáticamente en segundo plano?**
> Al arrancar, los scripts de entrada (*entrypoints*) de los contenedores realizan solos:
> 1. **Backend (`php-fpm`)**: Crea `.env` si falta, ejecuta `composer install` (si falta la carpeta `vendor` o se actualizaron dependencias), genera la clave `APP_KEY`, crea el enlace simbólico `storage:link` y ajusta los permisos de `storage` y `bootstrap/cache`.
> 2. **Worker (`queue`)**: Espera a que `php-fpm` complete la instalación de Composer y a que PostgreSQL esté saludable antes de iniciar `queue:work`.
> 3. **Frontend (`vue`)**: Instala las dependencias de NPM si falta `node_modules` o cambiaron los paquetes, y levanta el servidor de desarrollo Vite con recarga en caliente (HMR).

Verifica que todos los contenedores estén corriendo y saludables:

```bash
docker compose ps
```

Deberías ver los siguientes 6 contenedores con estado `Up` o `healthy`:
* `tatelestai-nginx`
* `tatelestai-php-fpm`
* `tatelestai-postgres`
* `tatelestai-typesense`
* `tatelestai-vue`
* `tatelestai-queue`

---

## Paso 4: Inicializar la Base de Datos y Catálogo de Búsqueda

Con los contenedores listos, ejecuta las migraciones y seeders de prueba desde cualquier carpeta utilizando el nombre del contenedor:

```bash
docker exec -it tatelestai-php-fpm php artisan migrate --seed
```

> **Indexación automática incluida:**  
> Este comando crea todas las tablas en PostgreSQL y carga los registros de prueba (categorías, comercios, productos y ofertas). Al finalizar el sembrado, `DatabaseSeeder` ejecuta internamente `Offer::makeAllSearchable()`, por lo que **todo el catálogo queda indexado automáticamente en Typesense** sin requerir comandos de importación adicionales. Puedes correrlo desde cualquier directorio sin necesidad de estar en la carpeta de Compose.

---

## Paso 5: Comprobar el funcionamiento

Abre tu navegador y comprueba las siguientes URLs:

1. **Frontend Web**: Visita [http://localhost:3000](http://localhost:3000). Verás la interfaz de bienvenida de Tatelestai con Vite activo.
2. **API Backend**: Visita [http://localhost:8000/api](http://localhost:8000/api). Comprobarás que Nginx y PHP-FPM responden correctamente.
3. **Typesense**: Visita [http://localhost:8108/health](http://localhost:8108/health). Debería responder `{"ok":true}`.

---

## ¡Felicitaciones!

Tienes el entorno de desarrollo de Tatelestai 100% operativo.
* Para resolver problemas o tareas comunes, consulta las [Guías How-To](../how-to/).
* Para entender cómo está estructurado el código y por qué se tomaron estas decisiones técnicas, consulta la sección de [Explicación](../explanation/).
