# Cómo indexar y sincronizar datos en Typesense

> **Tipo**: Guía How-To (Diátaxis)  
> **Objetivo**: Pasos para crear, sincronizar o reiniciar la colección de búsqueda y geolocalización en Typesense utilizando Laravel Scout.

---

## Prerrequisitos

* Tener los contenedores `tatelestai-typesense` y `tatelestai-php-fpm` activos.
* Al utilizar el nombre del contenedor (`tatelestai-php-fpm`), puedes ejecutar estos comandos desde **cualquier directorio**.

---

## 1. Importar todos los registros existentes

> **Nota de automatización:** Al ejecutar `php artisan migrate --seed`, el seeder principal (`DatabaseSeeder`) llama internamente a `\App\Models\Offer::makeAllSearchable()`, por lo que **todas las ofertas quedan indexadas automáticamente en el despliegue inicial**.
>
> Utiliza el siguiente comando únicamente si insertaste ofertas directamente por SQL fuera de Eloquent, si restauraste un dump de PostgreSQL o si reiniciaste el volumen de Typesense:

```bash
docker exec -it tatelestai-php-fpm php artisan scout:import "App\Models\Offer"
```

> **Resultado esperado**: La terminal mostrará el progreso importando las ofertas por lotes (*batches*).

---

## 2. Vaciar el índice de Typesense

Para eliminar todos los documentos indexados de una colección sin alterar los registros en la base de datos PostgreSQL:

```bash
docker exec -it tatelestai-php-fpm php artisan scout:flush "App\Models\Offer"
```

---

## 3. Reindexación completa desde cero

Cuando se modifique la estructura de campos indexables en el método `toSearchableArray()` de `Offer.php`:

```bash
# Paso 1: Vaciar colección anterior
docker exec -it tatelestai-php-fpm php artisan scout:flush "App\Models\Offer"

# Paso 2: Reimportar con el nuevo schema
docker exec -it tatelestai-php-fpm php artisan scout:import "App\Models\Offer"
```

---

## 4. Verificar colecciones directamente en Typesense

Puedes comprobar las colecciones e ítems indexados haciendo una petición cURL a la API de Typesense:

```bash
# Consultar estado de la colección 'offers'
curl -H "X-TYPESENSE-API-KEY: xyz" http://localhost:8108/collections/offers
```
