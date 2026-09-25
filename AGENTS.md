# Instrucciones y Convenciones para Asistentes IA (Tatelestai)

Este archivo define el contexto operativo, la arquitectura del sistema y las reglas estrictas de desarrollo para cualquier asistente de IA que trabaje en el repositorio **Tatelestai**.

---

## 1. Visión y Dominio del Proyecto

**Tatelestai** es una plataforma web de economía circular y reducción del desperdicio alimentario que conecta establecimientos gastronómicos con consumidores para la venta de excedentes de comida (packs/bolsas sorpresa) a precios reducidos con ventanas horarias estrictas de retiro.

### Stack Tecnológico
- **Backend**: PHP 8.3+, Laravel 12, PostgreSQL 16, Typesense 30.1 (motor de búsqueda geolocalizada).
- **Frontend**: Vue.js 3.5+ (Composition API con `<script setup>`), Vite, Tailwind CSS, Pinia, Vue Router.
- **Testing**: Pest PHP (vía `pestphp/pest-plugin-laravel`), PHPUnit.
- **Calidad de Código**: Laravel Pint (PSR-12).
- **Infraestructura**: Docker & Docker Compose (`docker-composes/tatelestai/`).
- **Estándar Documental**: Framework Diátaxis en `docs/`.

---

## 2. REGLA FUNDAMENTAL DE ENTORNO: Docker First

> ⚠️ **CRÍTICO: NUNCA ejecutes PHP, Composer, Artisan o Pest directamente en la máquina host.**
> El entorno de desarrollo corre completamente encapsulado en contenedores Docker.
> 
> 🛑 **REGLA OBLIGATORIA PARA LA IA: NO EJECUTAR COMANDOS DE DOCKER.** La IA NO debe ejecutar comandos de Docker (`docker`, `docker exec`, `docker compose`, etc.); estos comandos los ejecuta el usuario manualmente. La IA debe únicamente proporcionar e indicar los comandos pertinentes para que el desarrollador los corra.

Todas las operaciones de backend deben ejecutarse dentro del contenedor `tatelestai-php-fpm`. Al usar el nombre del contenedor, estos comandos funcionan desde cualquier directorio:

### Comandos Frecuentes de Backend
```bash
# Migraciones y Seeders
docker exec tatelestai-php-fpm php artisan migrate
docker exec tatelestai-php-fpm php artisan migrate --seed
docker exec tatelestai-php-fpm php artisan migrate:rollback

# Tests automatizados con Pest
docker exec tatelestai-php-fpm php artisan test
docker exec tatelestai-php-fpm php artisan test tests/Feature/NombreDelTest.php
docker exec tatelestai-php-fpm php artisan test --filter="nombre de la prueba"

# Formato y linter con Laravel Pint
docker exec tatelestai-php-fpm ./vendor/bin/pint --test   # Solo verificar
docker exec tatelestai-php-fpm ./vendor/bin/pint          # Aplicar correcciones

# Indexación y sincronización de búsqueda con Typesense
docker exec tatelestai-php-fpm php artisan scout:import "App\Models\Offer"
docker exec tatelestai-php-fpm php artisan scout:flush "App\Models\Offer"

# Limpieza y refresco de caché
docker exec tatelestai-php-fpm php artisan optimize:clear
```

> **Nota para la IA**: No ejecutes comandos de Docker; indícaselos al desarrollador para su ejecución manual.

### Comandos de Frontend
La SPA reside en `Frontend/vue-project/`.
- El servidor Vite de desarrollo se ejecuta en el contenedor `tatelestai-vue` exponiendo el puerto `3000`.
- Si necesitas instalar paquetes o compilar: realizarlo dentro de `Frontend/vue-project/` (`npm run build`, `npm run lint`).

---

## 3. Mapa del Repositorio

```text
.
├── Backend/example-app/      # Código fuente del Backend Laravel 12
│   ├── app/                  # Modelos, Controladores, FormRequests, Resources, Servicios
│   ├── database/             # Migraciones, Seeders, Factories
│   ├── routes/               # api.php, web.php, console.php
│   └── tests/                # Tests unitarios y de integración (Pest PHP)
├── Frontend/vue-project/     # SPA en Vue 3 con Vite y Tailwind CSS
│   ├── src/                  # Componentes, vistas, stores de Pinia, router
│   └── package.json
├── docker-composes/
│   └── tatelestai/           # Orquestación Docker Compose y archivos .env
├── docs/                     # Documentación técnica bajo estándar Diátaxis
│   ├── tutorials/            # Guías paso a paso de aprendizaje
│   ├── how-to/               # Recetas prácticas orientadas a tareas
│   ├── reference/            # Catálogo técnico, puertos, variables de entorno
│   ├── explanation/          # Fundamentos de arquitectura, ADRs y lógica de negocio
│   └── project/              # Roadmap, alcance MoSCoW y gobierno
├── DESIGN.md                 # Especificación exhaustiva del Sistema de Diseño UI/UX
└── AGENTS.md                 # Este archivo de directrices para IA
```

---

## 4. Convenciones de Desarrollo de Backend (Laravel 12)

1. **Controladores Delgados**:
   - Los controladores solo coordinan: reciben el request validado, invocan servicios/acciones de negocio y devuelven un recurso.
   - La lógica compleja debe delegarse en clases de servicio dedicadas (`app/Services/` o `app/Actions/`).
2. **Validación Exhaustiva**:
   - No valides directamente en el controlador. Crea y utiliza siempre clases dedicadas `FormRequest` (`app/Http/Requests/`).
3. **Transformación de Respuestas**:
   - Todas las respuestas JSON de la API deben pasar por API Resources (`app/Http/Resources/`) para desacoplar la estructura del modelo Eloquent del contrato de la API.
4. **Control de Concurrencia y Stock (Pessimistic Locking)**:
   - Al reservar o vender stock de ofertas (`Offer`), es obligatorio utilizar transacciones de base de datos (`DB::transaction`) y bloqueo pesimista (`lockForUpdate()`) para prevenir condiciones de carrera y sobreventa.
   - Consulta `docs/explanation/concurrencia-y-stock.md` para ver el patrón de implementación establecido.
5. **Pruebas Obligatorias**:
   - Toda nueva funcionalidad o corrección de bugs debe acompañarse de sus pruebas en Pest PHP dentro de `tests/Feature/` o `tests/Unit/`.
   - Proporciona siempre al usuario el comando para verificar que pasen los tests (`docker exec tatelestai-php-fpm php artisan test`).

---

## 5. Convenciones de Desarrollo de Frontend (Vue 3)

1. **Componentes y Reactividad**:
   - Usa exclusivamente **Single File Components (SFC)** con Composition API y `<script setup>`.
   - Tipado y contratos claros en props y emits (`defineProps`, `defineEmits`).
2. **Estado Global**:
   - El estado compartido (autenticación, carrito, geolocalización del usuario) debe gestionarse mediante **Pinia stores** en `src/stores/`.
3. **Fidelidad al Sistema de Diseño (`DESIGN.md`)**:
   - Respeta estrictamente la identidad visual **Dark Violet & Plum**:
     - Fondo / Canvas principal: `#1A1625` (Deep Violet Dark).
     - Tarjetas y Contenedores: `#2D2438` (Dark Plum).
     - Superficies elevadas / Modales: `#3D3450`.
     - Acento principal (CTAs, activos): `#7C3AED` (Electric Violet).
     - Acento ecológico / Ahorro / Rescate: `#10B981` (Emerald Green).
     - Tipografía de lectura: `#E8EAF6` (Soft Lavender White).
     - Precios y títulos destacados: `#FFFFFF`.

---

## 6. Documentación Técnica (Framework Diátaxis)

Si creas o actualizas documentación en la carpeta `docs/`, mantén la categorización Diátaxis:
- **Tutoriales** (`docs/tutorials/`): Orientados al aprendizaje paso a paso para recién llegados.
- **Guías How-To** (`docs/how-to/`): Pasos concisos para resolver una tarea o problema concreto.
- **Referencia** (`docs/reference/`): Fichas técnicas, tablas de configuración, puertos, contratos de API.
- **Explicación** (`docs/explanation/`): Por qué se diseñó de cierta forma, arquitectura, ADRs (Architecture Decision Records).

---

## 7. Buenas Prácticas de Git y Comunicación

- **Idioma**: Español para documentación, comentarios de código y comunicación técnica.
- **Commits**: Seguir el estándar [Conventional Commits](https://www.conventionalcommits.org/):
  - `feat: ...` para nuevas funcionalidades.
  - `fix: ...` para corrección de errores.
  - `refactor: ...` para cambios de código sin alterar comportamiento externo.
  - `test: ...` para agregado o modificación de pruebas.
  - `docs: ...` para cambios en la documentación.
