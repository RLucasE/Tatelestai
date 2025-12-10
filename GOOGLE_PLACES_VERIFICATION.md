# Sistema de Verificación de Establecimientos con Google Places

Este documento explica cómo funciona el sistema de verificación de establecimientos utilizando Google Places API.

## Características Implementadas

### Backend

1. **Migración de Base de Datos**
   - Nuevos campos en `food_establishments`:
     - `google_place_id`: ID único del lugar en Google Places
     - `google_place_data`: Datos completos del lugar (JSON)
     - `establishment_photo`: Foto del establecimiento
     - `owner_selfie`: Selfie del propietario
     - `phone`: Teléfono del establecimiento
     - `description`: Descripción
     - `latitude` y `longitude`: Coordenadas GPS
     - `verification_status`: Estado de verificación (pending, approved, rejected)
     - `verification_notes`: Notas del administrador

2. **Servicio de Google Places** (`GooglePlacesService`)
   - `searchPlaces()`: Buscar lugares por texto
   - `getPlaceDetails()`: Obtener detalles de un lugar específico
   - `autocomplete()`: Autocompletar búsqueda
   - `getPhotoUrl()`: Obtener URL de fotos

3. **Controladores**
   - `GooglePlacesController`: Endpoints para búsqueda de lugares
   - `UserManagement::registerEstablishment()`: Actualizado para usar Google Places
   - `AdmUserController`: Nuevos métodos para verificación
     - `pendingEstablishments()`: Listar establecimientos pendientes
     - `verifyEstablishment()`: Aprobar establecimiento
     - `rejectEstablishment()`: Rechazar establecimiento

4. **Request de Validación** (`StoreEstablishmentWithVerificationRequest`)
   - Valida `google_place_id`
   - Valida fotos (máx 5MB, formatos: jpeg, png, jpg)
   - Valida tipo de establecimiento

### Frontend

1. **Componente Actualizado** (`register-establishment.vue`)
   - Búsqueda en tiempo real de Google Places
   - Selección de lugar desde resultados
   - Carga de foto del establecimiento
   - Carga de selfie del propietario
   - Vista previa de imágenes
   - Información adicional opcional

## Configuración

### 1. Obtener API Key de Google Places

1. Ve a [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un nuevo proyecto o selecciona uno existente
3. Habilita las siguientes APIs:
   - Places API
   - Maps JavaScript API
   - Geocoding API

4. Ve a "Credenciales" y crea una API Key
5. Restringe la API Key (opcional pero recomendado):
   - Restricciones de aplicación: HTTP referrers o direcciones IP
   - Restricciones de API: Selecciona solo las APIs que necesitas

### 2. Configurar Backend

1. Agrega las API Keys a tu archivo `.env`:

```env
GOOGLE_PLACES_API_KEY=tu_api_key_aqui
GOOGLE_MAPS_API_KEY=tu_api_key_aqui
```

2. Ejecuta las migraciones:

```bash
php artisan migrate
```

3. Configura el almacenamiento de archivos:

```bash
php artisan storage:link
```

### 3. Rutas API

**Rutas para Sellers (autenticadas)**:
- `GET /api/places/search?query={texto}` - Buscar lugares
- `GET /api/places/autocomplete?input={texto}` - Autocompletar
- `GET /api/places/{placeId}` - Detalles de un lugar
- `POST /api/food-establishment` - Registrar establecimiento (multipart/form-data)

**Rutas para Administradores**:
- `GET /api/adm/pending-establishments` - Listar establecimientos pendientes
- `PATCH /api/adm/establishments/{id}/verify` - Aprobar establecimiento
- `PATCH /api/adm/establishments/{id}/reject` - Rechazar (requiere `reason`)

## Flujo de Verificación

### Para el Vendedor (Seller)

1. El vendedor accede a la página de registro de establecimiento
2. Busca su negocio en Google Places
3. Selecciona el lugar correcto de los resultados
4. Sube una foto del establecimiento
5. Sube una selfie para verificar identidad
6. Selecciona el tipo de establecimiento
7. Envía el formulario
8. El estado del usuario cambia a `WAITING_FOR_CONFIRMATION`
9. Espera la aprobación del administrador

### Para el Administrador

1. Accede al panel de administración
2. Ve la lista de establecimientos pendientes (`/api/adm/pending-establishments`)
3. Revisa cada solicitud:
   - Datos de Google Places (nombre, dirección, coordenadas)
   - Foto del establecimiento
   - Selfie del propietario
   - Tipo de establecimiento
4. Toma una decisión:
   - **Aprobar**: `PATCH /api/adm/establishments/{id}/verify`
     - El establecimiento se marca como `approved`
     - El usuario pasa a estado `ACTIVE`
     - Se envía email de confirmación
   - **Rechazar**: `PATCH /api/adm/establishments/{id}/reject`
     - El establecimiento se marca como `rejected`
     - El usuario pasa a estado `DENIED_CONFIRMATION`
     - Se envía email con la razón del rechazo

## Estructura de Datos

### Registro de Establecimiento (Request)

```javascript
FormData {
  google_place_id: "ChIJ...", // Required
  establishment_type_id: 1,    // Required
  establishment_photo: File,   // Required (image, max 5MB)
  owner_selfie: File,          // Required (image, max 5MB)
  additional_info: "..."       // Optional
}
```

### Respuesta de Google Places

```json
{
  "status": "OK",
  "result": {
    "place_id": "ChIJ...",
    "name": "Panadería San Juan",
    "formatted_address": "Av. Principal 123, Centro",
    "geometry": {
      "location": {
        "lat": -34.397,
        "lng": 150.644
      }
    },
    "formatted_phone_number": "+1234567890",
    "photos": [...],
    "rating": 4.5,
    "types": ["bakery", "food", "store"]
  }
}
```

## Seguridad y Validaciones

### Backend
- Validación de tipos de archivo (solo imágenes)
- Límite de tamaño de archivo (5MB)
- Verificación de existencia del lugar en Google Places
- Verificación de que el usuario no tenga ya un establecimiento
- Verificación de que el tipo de establecimiento esté activo
- Autenticación requerida para todas las operaciones

### Frontend
- Vista previa de imágenes antes de enviar
- Debounce en búsqueda (500ms)
- Validación de campos requeridos
- Manejo de errores con mensajes descriptivos
- Loading states para mejor UX

## Costos de Google Places API

Ten en cuenta los costos de uso de la API:
- **Text Search**: $32 por 1000 solicitudes
- **Place Details**: $17 por 1000 solicitudes
- **Autocomplete**: $2.83 por 1000 solicitudes

**Recomendaciones**:
- Implementa caché para búsquedas frecuentes
- Limita el número de resultados
- Usa autocomplete en lugar de text search cuando sea posible
- Monitorea el uso en Google Cloud Console

## Próximos Pasos

Para mejorar el sistema puedes:

1. **Verificación adicional**:
   - Comparación facial entre selfie y foto de perfil
   - Verificación de documentos de identidad
   - Verificación telefónica

2. **Mejoras en la UI**:
   - Mapa interactivo para ver ubicación
   - Galería de fotos del lugar desde Google
   - Rating y reviews del lugar

3. **Panel de administración**:
   - Vista de establecimientos en mapa
   - Filtros y búsqueda
   - Historial de cambios
   - Dashboard con estadísticas

4. **Notificaciones**:
   - Email con estado de verificación
   - Notificaciones push
   - SMS de confirmación

## Troubleshooting

### Error: "No se pudo obtener información del lugar"
- Verifica que la API Key sea válida
- Verifica que la Places API esté habilitada
- Revisa los logs en `storage/logs/laravel.log`

### Error: "The file must be an image"
- Verifica que el archivo sea jpeg, png o jpg
- Verifica el tamaño (máx 5MB)

### Error: "OVER_QUERY_LIMIT"
- Has excedido el límite de consultas
- Verifica tu facturación en Google Cloud Console
- Implementa rate limiting o caché

## Soporte

Para reportar problemas o sugerencias, contacta al equipo de desarrollo.

