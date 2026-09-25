# Cómo funciona el Pipeline de CI con GitHub Actions

> **Tipo**: Guía How-To (Diátaxis)  
> **Objetivo**: Explicar el funcionamiento de los flujos de Integración Continua (CI) en GitHub Actions, qué valida cada pipeline y cómo replicar las mismas comprobaciones en el entorno local antes de hacer `git push`.

---

## 1. Visión General de los Workflows

Para optimizar recursos y tiempos en el monorepo, **Tatelestai** cuenta con dos flujos independientes en `.github/workflows/`:

| Workflow | Archivo | Disparadores (`paths`) | Entorno | Qué valida |
|---|---|---|---|---|
| **Backend CI** | [`.github/workflows/backend-ci.yml`](file:///.github/workflows/backend-ci.yml) | `Backend/**`, `docker-composes/tatelestai/**` | Docker Compose (`postgres`, `typesense`, `php-fpm`) | Formato de código con **Pint** y batería completa de pruebas con **Pest** sobre PostgreSQL real y Typesense. |
| **Frontend CI** | [`.github/workflows/frontend-ci.yml`](file:///.github/workflows/frontend-ci.yml) | `Frontend/**` | Node.js 22 | Instalación limpia (`npm ci`) y compilación de producción con **Vite** (`npm run build`). |

Ambos workflows se ejecutan automáticamente en:
- Cualquier `git push` a la rama `main`.
- Cualquier *Pull Request* que apunte a `main`.

---

## 2. Detalle del Pipeline de Backend

El pipeline de Backend aplica el principio **Docker First** del proyecto para garantizar paridad del 100% con el entorno de desarrollo y producción:

1. **Configuración rápida de variables**: Copia automáticamente las plantillas `.env.example` para Docker y Laravel.
2. **Optimización de Build**: Establece `XDEBUG_ENABLED=false` en el `.env` de CI, omitiendo la compilación de Xdebug para reducir drásticamente el tiempo de inicio.
3. **Arranque con sincronización (`--wait`)**:
   ```bash
   docker compose up -d --build --wait postgres typesense php-fpm
   ```
   El flag `--wait` asegura que PostgreSQL complete su inicialización interna y pase su *healthcheck* (`pg_isready`) antes de continuar.
4. **Instalación y Migraciones**: Instala dependencias con Composer y corre `php artisan migrate --force`.
5. **Verificación de Estilo (Laravel Pint)**:
   ```bash
   docker compose exec -T php-fpm ./vendor/bin/pint --test
   ```
   Si algún archivo PHP no cumple con PSR-12, el pipeline fallará indicando qué líneas deben corregirse.
6. **Ejecución de Pruebas (Pest PHP)**:
   ```bash
   docker compose exec -T php-fpm php artisan test
   ```
   Valida toda la suite de pruebas unitarias y de integración (control de concurrencia de compras, adaptadores de Typesense, transiciones de estado, etc.).
7. **Limpieza garantizada**: Ejecuta `docker compose down -v` al terminar (incluso si la prueba falla).

---

## 3. Detalle del Pipeline de Frontend

1. **Caché de Dependencias**: Emplea `actions/setup-node@v4` con caché vinculada al archivo `package-lock.json`. Si las dependencias no cambiaron, se restauran en segundos.
2. **Instalación determinista**:
   ```bash
   npm ci
   ```
3. **Compilación de producción**:
   ```bash
   npm run build
   ```
   Garantiza que no existan errores tipográficos en plantillas Vue, importaciones rotas o fallos en el empaquetado de Tailwind CSS v4.

---

## 4. Cómo verificar localmente antes de hacer `git push`

Para asegurarte de que tu código pasará el CI sin errores antes de subirlo a GitHub:

### Para cambios de Backend
Ejecuta desde tu terminal:
```bash
# 1. Verificar y corregir formato de código
docker exec tatelestai-php-fpm ./vendor/bin/pint

# 2. Correr la batería de pruebas
docker exec tatelestai-php-fpm php artisan test
```

### Para cambios de Frontend
Dentro de la carpeta `Frontend/vue-project`:
```bash
npm run build
```

---

## 5. Monitoreo y Solución de Fallos Comunes

### "Laravel Pint failed" en CI
- **Causa**: Uno o más archivos tienen espacios, sangrías o saltos de línea fuera del estándar PSR-12.
- **Solución en local**:
  ```bash
  docker exec tatelestai-php-fpm ./vendor/bin/pint
  git add . && git commit -m "style: corregir formato de código con Pint"
  ```

### "Vite build failed" en CI
- **Causa**: Alguna importación apunta a un archivo que no existe en el repositorio (por ejemplo, omitido por `.gitignore`) o hay un error de sintaxis en un componente `<script setup>`.
- **Solución**: Corre `npm run build` en `Frontend/vue-project` para ver el error exacto en tu consola.
