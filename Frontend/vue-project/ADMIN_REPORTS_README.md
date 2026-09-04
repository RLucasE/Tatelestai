# Vista de Gestión de Reportes - Admin Panel

## Descripción
Vista completa para que los administradores gestionen los reportes realizados por los usuarios sobre ofertas, establecimientos y otros usuarios.

## Características Implementadas

### Vista Principal (AdminReports.vue)
- Lista paginada de todos los reportes
- Filtros por estado (pendiente, en revisión, resuelto, descartado)
- Filtros por tipo (ofertas, establecimientos, usuarios)
- Información de resultados en tiempo real
- Botón para limpiar filtros
- Paginación completa con navegación
- Estados de carga y error
- Diseño responsive

### Tarjeta de Reporte (ReportCard.vue)
- Visualización compacta con información clave
- Badges de color para estados y tipos
- Expandir/colapsar detalles completos
- Modal para actualizar estado del reporte
- Campo para notas del administrador (max 1000 caracteres)
- Visualización de información del reportador
- Visualización de información del revisor
- Visualización del objeto reportado (offer/establishment/user)

### Actualización de Estado
- Modal intuitivo para cambiar estado
- Opciones: Pendiente, En revisión, Resuelto, Descartado
- Campo de notas del administrador (opcional)
- Contador de caracteres
- Validación de errores
- Feedback visual durante la actualización

## Rutas
- `/adm/reports` - Vista principal de reportes

## API Endpoints Utilizados
- `GET /api/adm/reports` - Listar reportes con filtros
  - Query params: `status`, `reportable_type`, `page`, `per_page`
- `PATCH /api/adm/reports/:id/status` - Actualizar estado de un reporte
  - Body: `status`, `admin_notes` (opcional)

## Navegación
El enlace "Reportes" está disponible en el menú lateral del panel de administración.

## Estilos
- Diseño minimalista y moderno
- Badges de colores para estados:
  - Pendiente: Amarillo
  - En revisión: Azul
  - Resuelto: Verde
  - Descartado: Rojo
- Responsive design para móviles y tablets
- Animaciones suaves de transición

## Permisos
Solo accesible para usuarios con rol de administrador.

## Cosas a mejorar
Que las report card se muestre una opción para ir al perfil del usuario reportado, oferta o establecimiento dependiendo del tipo de reporte.





