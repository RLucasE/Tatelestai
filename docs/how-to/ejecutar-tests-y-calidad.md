# Cómo ejecutar Tests y Control de Calidad con Pest y Pint

> **Tipo**: Guía How-To (Diátaxis)  
> **Objetivo**: Proporcionar recetas rápidas para ejecutar las pruebas automatizadas del proyecto y verificar los estándares de formato de código.

---

## Prerrequisitos

El entorno Docker debe estar levantado. Al utilizar el nombre del contenedor (`tatelestai-php-fpm`), puedes ejecutar todos estos comandos desde **cualquier carpeta de tu sistema**.

---

## 1. Ejecutar la suite de tests con Pest

Para correr toda la batería de tests dentro del contenedor `tatelestai-php-fpm`:

```bash
docker exec -it tatelestai-php-fpm php artisan test
```

> **Nota:** Laravel ejecuta Pest automáticamente a través de `php artisan test` gracias a `pestphp/pest-plugin-laravel`.

### Ejecutar un grupo o archivo específico

```bash
# Correr únicamente un archivo de tests
docker exec -it tatelestai-php-fpm php artisan test tests/Feature/Offers/CreateOfferTest.php

# Filtrar tests por nombre de prueba
docker exec -it tatelestai-php-fpm php artisan test --filter="debe impedir compra concurrente si stock es insuficiente"
```

### Ejecutar tests en paralelo

```bash
docker exec -it tatelestai-php-fpm php artisan test --parallel
```

### Generar reporte de cobertura de código (Coverage)

*(Requiere Xdebug configurado en modo `coverage` en el `.env` de Docker)*:

```bash
docker exec -it tatelestai-php-fpm php artisan test --coverage
```

---

## 2. Verificación de estilo de código con Laravel Pint

El proyecto utiliza **Laravel Pint** para mantener el estándar de código PSR-12 y convenciones del framework:

### Revisar inconsistencias sin modificar archivos:

```bash
docker exec -it tatelestai-php-fpm ./vendor/bin/pint --test
```

### Formatear y corregir automáticamente todo el código:

```bash
docker exec -it tatelestai-php-fpm ./vendor/bin/pint
```

---

## 3. Acceder al log interactivo con Laravel Pail

Para observar las excepciones y queries SQL en tiempo real mientras ejecutas pruebas o interactúas desde el frontend:

```bash
docker exec -it tatelestai-php-fpm php artisan pail
```
