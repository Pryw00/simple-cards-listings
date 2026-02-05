# Sistema de Cupones Promocionales - Documentación

## Visión General

El sistema de cupones permite a los usuarios crear, gestionar y compartir cupones promocionales asociados a sus establecimientos. Incluye gestión completa desde el dashboard del usuario y visualización pública mediante shortcodes.

---

## Características Implementadas

### 1. Custom Post Type: `cupon`

**Ubicación**: `includes/class-scl-cupones.php`

- **CPT**: `cupon` con soporte para título, editor, thumbnail y permisos personalizados
- **Taxonomía**: `categoria_cupon` para clasificar cupones
- **Meta campos**:
  - `_scl_establecimiento_id`: ID del establecimiento asociado
  - `_scl_fecha_inicio`: Fecha de inicio de validez (DATETIME)
  - `_scl_fecha_fin`: Fecha de fin de validez (DATETIME)
  - `_scl_destacado`: Marcador para cupones destacados (1/0)

### 2. Sistema de Permisos

**Ubicación**: `includes/class-scl-cupones.php` → `map_cupon_meta_cap()`

- Los usuarios solo pueden crear cupones para sus propios establecimientos
- Solo pueden editar cupones dentro del período permitido (configurable)
- Solo pueden eliminar cupones que les pertenecen
- Administradores tienen acceso total

**Validación de edición**:

```php
can_edit_cupon($cupon_id, $user_id)
```

- Verifica propiedad del establecimiento asociado
- Valida días previos al inicio configurados en opciones

### 3. Shortcodes

#### `[scl_cupones]`

**Ubicación**: `includes/class-scl-shortcodes.php` → `render_cupones_grid()`

Muestra grid de cupones activos con búsqueda en tiempo real.

**Parámetros**:

- `columns`: Número de columnas (2, 3, 4) - Default: 3
- `per_page`: Cupones por página - Default: 12
- `search_placeholder`: Texto del campo de búsqueda - Default: "Buscar cupones..."

**Ejemplo de uso**:

```
[scl_cupones columns="3" per_page="12" search_placeholder="Buscar ofertas..."]
```

**Características**:

- Solo muestra cupones activos (fecha_inicio <= ahora <= fecha_fin)
- Ordena por fecha de expiración próxima y destacados primero
- Búsqueda AJAX en título, descripción y establecimiento
- Click en cupón abre modal con detalles completos

### 4. Modal de Cupón

**JavaScript**: `assets/js/cupones.js` → `openCuponModal()`
**Estilos**: `assets/css/frontend.css` → `.scl-cupon-modal-*`

**Características**:

- Carga datos via AJAX (`scl_get_cupon`)
- Muestra imagen, título, descripción, fechas y establecimiento
- **Compartir**: Copia URL con parámetro `?cupon_id=X` al portapapeles
- **Descargar**: Descarga imagen del cupón
- Auto-apertura si URL contiene `?cupon_id=X`

**Ejemplo de URL compartida**:

```
https://tusitio.com/?cupon_id=123
```

### 5. AJAX Handlers

**Ubicación**: `includes/class-scl-ajax-handlers.php`

#### `scl_get_cupon`

- Obtiene datos completos de un cupón para el modal
- Valida que el cupón esté publicado
- Retorna: título, descripción, imagen, fechas, establecimiento, URL compartir

#### `scl_search_cupones`

- Búsqueda en tiempo real de cupones activos
- Busca en: título, contenido del cupón, nombre del establecimiento
- Solo retorna cupones dentro del rango de fechas válido

#### `scl_submit_cupon`

- Crea o edita cupones desde el frontend
- Validaciones:
  - Usuario autenticado
  - Propiedad del establecimiento
  - Fechas válidas (fin > inicio)
  - Permisos de edición según fecha de inicio
- Maneja upload de imagen (thumbnail)

#### `scl_delete_cupon`

- Elimina cupón
- Verifica permisos `delete_cupon`
- Solo propietario o admin pueden eliminar

### 6. Dashboard de Usuario

**Ubicación**: `includes/class-scl-user-dashboard.php` → `get_user_cupones()`

**Endpoint AJAX**: `scl_get_user_cupones`

Retorna todos los cupones de los establecimientos del usuario con:

- Estado (activo, expirado, pendiente)
- Permisos de edición/eliminación
- Datos completos para gestión

**Estados posibles**:

- `activo`: Cupón publicado y dentro de fechas válidas
- `expirado`: Fecha fin pasada
- `pendiente`: No publicado o esperando aprobación

### 7. Interfaz de Administración

**Ubicación**: `includes/class-scl-cupones.php`

**Metaboxes**:

1. **Establecimiento**: Dropdown de establecimientos del usuario
2. **Fechas**: Campos datetime para inicio y fin
3. **Opciones**: Checkbox para marcar como destacado
4. **Imagen destacada**: Campo nativo de WordPress

**Columnas personalizadas en listado**:

- Establecimiento asociado
- Fecha de inicio
- Fecha de expiración
- Estado (activo/expirado)
- Badge de destacado

**Filtros rápidos**:

- Activos
- Expirados
- Destacados

---

## Flujo de Uso

### Usuario Final

1. **Descubrir cupones**:
   - Visita página con shortcode `[scl_cupones]`
   - Busca cupones por nombre o establecimiento
   - Ve grid de cupones activos ordenados por expiración

2. **Ver detalles**:
   - Click en "Ver cupón"
   - Modal muestra detalles completos
   - Puede compartir o descargar imagen

3. **Compartir**:
   - Click en "Compartir"
   - URL copiada al portapapeles
   - Al compartir URL, receptor ve modal auto-abierto

### Usuario con Establecimiento

1. **Crear cupón**:
   - Accede a WP Admin → Cupones → Añadir nuevo
   - Selecciona su establecimiento
   - Completa título, descripción, fechas
   - Sube imagen
   - Marca como destacado (opcional)
   - Publica

2. **Gestionar cupones**:
   - Dashboard de usuario (`[scl_user_dashboard]`)
   - Lista de sus cupones con estados
   - Puede editar antes de la fecha de inicio (según configuración)
   - Puede eliminar en cualquier momento

### Administrador

1. **Configuración**:
   - WP Admin → Ajustes → Simple Cards Listings
   - Configura días previos a inicio para permitir edición
   - Configura aprobación automática o manual
   - Define columnas de grid por defecto

2. **Moderación**:
   - Lista completa de todos los cupones
   - Filtros por estado, establecimiento, categoría
   - Aprobación/rechazo de cupones pendientes
   - Edición y eliminación sin restricciones

---

## Archivos Clave

```
simple-cards-listings/
├── includes/
│   ├── class-scl-cupones.php          # CPT, taxonomía, metaboxes, permisos
│   ├── class-scl-shortcodes.php       # Shortcode [scl_cupones]
│   ├── class-scl-ajax-handlers.php    # Endpoints AJAX
│   └── class-scl-user-dashboard.php   # Gestión de cupones del usuario
├── assets/
│   ├── js/
│   │   └── cupones.js                 # Búsqueda, modal, compartir, descargar
│   ├── css/
│   │   └── frontend.css               # Estilos de grid, modal, notificaciones
│   └── images/
│       └── cupon-placeholder.png      # Imagen por defecto
└── simple-cards-listings.php          # Carga de clases y scripts
```

---

## Configuración Recomendada

### Opciones del Plugin (a implementar en settings)

```php
// Días antes del inicio para permitir edición
'scl_cupon_dias_previos_edicion' => 7

// Aprobación automática
'scl_cupon_aprobacion_automatica' => true

// Columnas de grid por defecto
'scl_cupon_grid_columns' => 3

// Cupones por página
'scl_cupon_grid_per_page' => 12
```

### Permisos Recomendados

**Usuarios registrados con establecimientos**:

- `edit_cupon`: Para sus cupones, solo antes de X días del inicio
- `delete_cupon`: Para sus cupones en cualquier momento
- `publish_cupones`: Si aprobación automática está activada

**Administradores**:

- Acceso completo sin restricciones

---

## Ejemplos de Implementación

### Página de Cupones Públicos

```html
<h1>Ofertas y Cupones</h1>
<p>Descubre las mejores promociones de nuestros establecimientos</p>

[scl_cupones columns="3" per_page="12" search_placeholder="Buscar ofertas
especiales..."]
```

### Sección de Cupones Destacados

```html
<h2>🔥 Cupones Destacados</h2>
[scl_cupones columns="4" per_page="8" destacados="1"]
```

### Dashboard del Usuario

El dashboard ya incluye automáticamente la gestión de cupones cuando se usa:

```
[scl_user_dashboard]
```

---

## API AJAX

### Obtener Cupón

```javascript
jQuery.ajax({
  url: sclData.ajaxUrl,
  type: "POST",
  data: {
    action: "scl_get_cupon",
    nonce: sclData.nonce,
    post_id: 123,
  },
  success: function (response) {
    // response.data.cupon contiene todos los datos
  },
});
```

### Buscar Cupones

```javascript
jQuery.ajax({
  url: sclData.ajaxUrl,
  type: "POST",
  data: {
    action: "scl_search_cupones",
    nonce: sclData.nonce,
    search: "pizza",
  },
  success: function (response) {
    // response.data.html contiene las cards renderizadas
    // response.data.found contiene el número de resultados
  },
});
```

### Crear/Editar Cupón

```javascript
var formData = new FormData();
formData.append("action", "scl_submit_cupon");
formData.append("nonce", sclData.nonce);
formData.append("cupon_id", 123); // Omitir para crear nuevo
formData.append("establecimiento_id", 456);
formData.append("titulo", "Descuento 20%");
formData.append("descripcion", "Válido en todos los productos");
formData.append("fecha_inicio", "2025-01-01 00:00:00");
formData.append("fecha_fin", "2025-12-31 23:59:59");
formData.append("destacado", "1");
formData.append("imagen", imageFile); // File object

jQuery.ajax({
  url: sclData.ajaxUrl,
  type: "POST",
  data: formData,
  processData: false,
  contentType: false,
  success: function (response) {
    // response.data.cupon_id contiene el ID del cupón
  },
});
```

---

## Próximos Pasos Sugeridos

1. **Panel de configuración**: Crear página de opciones en WP Admin para:
   - Días previos a edición
   - Aprobación automática
   - Defaults de grid (columnas, per_page)

2. **Formulario frontend**: Agregar formulario en dashboard para crear/editar cupones sin acceder al admin

3. **Estadísticas**: Tracking de views, shares, descargas por cupón

4. **Email notifications**: Notificar al usuario cuando:
   - Su cupón es aprobado/rechazado
   - Su cupón está próximo a expirar
   - Su cupón ha expirado

5. **Integración con códigos QR**: Generar QR codes para compartir cupones físicamente

6. **Límites de uso**: Permitir definir un número máximo de canjes por cupón

---

## Notas Técnicas

### Queries de Performance

Los cupones activos se consultan con meta_query optimizado:

```php
'meta_query' => array(
    'relation' => 'AND',
    array(
        'key' => '_scl_fecha_inicio',
        'value' => $ahora,
        'compare' => '<=',
        'type' => 'DATETIME',
    ),
    array(
        'key' => '_scl_fecha_fin',
        'value' => $ahora,
        'compare' => '>=',
        'type' => 'DATETIME',
    ),
)
```

**Recomendación**: Agregar índices a las meta_keys para mejorar performance:

- `_scl_fecha_inicio`
- `_scl_fecha_fin`
- `_scl_establecimiento_id`
- `_scl_destacado`

### Seguridad

- Todas las peticiones AJAX validan nonce
- `check_ajax_referer('scl_nonce', 'nonce')`
- Validación de permisos con `current_user_can()`
- Sanitización de inputs: `sanitize_text_field()`, `wp_kses_post()`
- Validación de tipos de post antes de operaciones

### Compatibilidad

- WordPress 5.8+
- PHP 7.4+
- jQuery 3.x
- Compatible con temas que soportan thumbnails

---

## Soporte y Mantenimiento

**Versión actual**: 1.1.0
**Fecha**: Enero 2025
**Autor**: Simple Cards Listings Team

Para reportar bugs o sugerir mejoras, contacta al equipo de desarrollo.
