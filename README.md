# Simple Cards Listings

Plugin de directorio de cartas de contacto de negocios para WordPress, desarrollado conforme a la especificación IEEE 830-1998.

## Descripción

"Simple Cards Listings" es un plugin para WordPress que gestiona cartas de información de contacto de negocios y cupones promocionales, permitiendo a los usuarios buscar negocios según criterios específicos y descubrir ofertas especiales. Soporta tanto la administración desde el backend como la interacción avanzada con usuarios en el frontend.

## Características

### Para Administradores

- **Custom Post Type "Establecimiento"**: Gestión completa de negocios con campos personalizados
- **Custom Post Type "Cupón"**: Sistema de cupones promocionales con validación de fechas
- **Taxonomías personalizadas**:
  - Categorías de establecimiento y Tags de búsqueda
  - Categorías de cupones
- **Campos personalizados de establecimientos**:
  - Logo del establecimiento
  - Imagen del local
  - Archivo PDF (Menú/Carta)
  - Redes sociales (Instagram, TikTok, Facebook)
  - WhatsApp
  - Sitio web
  - Dirección física
  - URL de Google Maps
- **Campos personalizados de cupones**:
  - Establecimiento asociado
  - Fecha de inicio y fin de validez
  - Imagen promocional
- **Sistema de logs**: Registro de todas las acciones importantes
- **Panel de configuración**: Opciones personalizables

### Para Usuarios Registrados

- **Panel de usuario frontend**: Gestión de establecimientos propios y sus cupones
- **Formulario de solicitud**: Envío de nuevos establecimientos para aprobación
- **Edición de datos**: Actualización de información de establecimientos asignados
- **Gestión de cupones**: Crear, editar y eliminar cupones de sus establecimientos

### Para Visitantes

- **Grid de establecimientos**: Visualización de logos en cuadrícula
- **Grid de cupones**: Visualización de ofertas activas con búsqueda
- **Buscador en tiempo real**: Filtrado por nombre, descripción, categoría y tags
- **Modal de información**: Vista detallada con enlaces a redes, WhatsApp, ubicación y menú
- **Modal de cupones**: Vista detallada con opción de compartir y descargar
- **Sistema de compartir**: URLs con auto-apertura de cupones

## Instalación

1. Sube la carpeta `simple-cards-listings` al directorio `/wp-content/plugins/`
2. Activa el plugin desde el menú "Plugins" en WordPress
3. Configura las opciones en "Establecimientos > Configuración"
4. Ve a Ajustes → Enlaces permanentes y haz click en "Guardar cambios" para registrar los nuevos tipos de contenido

## Shortcodes

### Grid de Establecimientos

```
[scl_grid]
```

Parámetros opcionales:

- `categoria=""` - Filtrar por slug de categoría
- `limit="-1"` - Límite de resultados (-1 para todos)
- `columns="3"` - Número de columnas (2-6)
- `per_page="12"` - Elementos por página (para paginación)
- `pagination_type="default"` - Tipo de paginación: default, lazy, load_more
- `search_placeholder=""` - Texto personalizado del campo de búsqueda

Ejemplo:

```
[scl_grid categoria="restaurantes" columns="4" per_page="12" pagination_type="lazy"]
```

### Grid de Cupones **[NUEVO]**

```
[scl_cupones]
```

Parámetros opcionales:

- `columns="3"` - Número de columnas (2, 3, 4)
- `per_page="12"` - Cupones por página
- `search_placeholder=""` - Texto personalizado del campo de búsqueda

Ejemplo:

```
[scl_cupones columns="3" per_page="12" search_placeholder="Buscar ofertas..."]
```

**Características del grid de cupones**:

- Solo muestra cupones activos (dentro del rango de fechas válido)
- Búsqueda en tiempo real (título, descripción, establecimiento)
- Indicador visual de días restantes para expirar
- Click en cupón abre modal con detalles completos
- Opción de compartir cupón (copia URL al portapapeles)
- Opción de descargar imagen del cupón
- URLs compartidas abren automáticamente el modal (`?cupon_id=123`)

### Formulario de Solicitud

```
[scl_solicitud]
```

Solo visible para usuarios registrados. Permite solicitar el registro de un nuevo establecimiento.

### Panel de Usuario

```
[scl_user_dashboard]
```

Dashboard para que los usuarios gestionen sus establecimientos y cupones asociados.

## Requisitos

- WordPress 5.0 o superior
- PHP 7.4 o superior
- MySQL 5.6 o superior

## Estructura del Plugin

```
simple-cards-listings/
├── simple-cards-listings.php    # Archivo principal
├── assets/
│   ├── css/
│   │   ├── frontend.css         # Estilos del frontend
│   │   └── admin.css            # Estilos del admin
│   ├── js/
│   │   ├── frontend.js          # JavaScript del frontend
│   │   ├── cupones.js           # JavaScript de cupones [NUEVO]
│   │   └── admin.js             # JavaScript del admin
│   └── images/
│       ├── placeholder.png      # Imagen placeholder
│       └── cupon-placeholder.png # Imagen placeholder de cupones [NUEVO]
├── includes/
│   ├── class-scl-post-types.php # Registro de CPT
│   ├── class-scl-taxonomies.php # Registro de taxonomías
│   ├── class-scl-cupones.php    # CPT y gestión de cupones [NUEVO]
│   ├── class-scl-metaboxes.php  # Metaboxes personalizados
│   ├── class-scl-shortcodes.php # Shortcodes
│   ├── class-scl-ajax-handlers.php # Manejadores AJAX
│   ├── class-scl-notifications.php # Sistema de notificaciones
│   ├── class-scl-logger.php     # Sistema de logs
│   ├── class-scl-user-dashboard.php # Dashboard de usuario
│   ├── class-scl-permissions.php # Sistema de permisos
│   └── admin/
│       └── class-scl-admin.php  # Funcionalidades de admin
├── languages/                    # Archivos de traducción
├── README.md                     # Este archivo
├── CUPONES_DOCUMENTACION.md      # Documentación completa de cupones [NUEVO]
└── RESUMEN_CUPONES.md            # Resumen de implementación [NUEVO]
```

## Internacionalización

El plugin está preparado para traducción. Los archivos de traducción deben colocarse en la carpeta `languages/` con el formato:

- `simple-cards-listings-es_ES.po`
- `simple-cards-listings-es_ES.mo`

## Hooks y Filtros

El plugin dispara varios hooks para personalización:

### Acciones

- `scl_before_grid` - Antes del grid de establecimientos
- `scl_after_grid` - Después del grid de establecimientos
- `scl_establecimiento_created` - Cuando se crea un establecimiento
- `scl_establecimiento_updated` - Cuando se actualiza un establecimiento

## Seguridad

- Verificación de nonces en todas las operaciones AJAX
- Validación de permisos basada en roles
- Sanitización de todos los datos de entrada
- Escape de todos los datos de salida

## Changelog

### 1.1.0 **[NUEVO]**

- **Sistema de Cupones Promocionales completo**:
  - CPT "Cupón" con taxonomía de categorías
  - Metaboxes para establecimiento, fechas
  - Sistema de permisos (usuarios solo gestionan cupones de sus establecimientos)
  - Validación de fechas (inicio/fin de validez)
  - Shortcode `[scl_cupones]` con grid responsive
  - Modal de cupones con compartir y descargar imagen
  - Búsqueda en tiempo real de cupones
  - Auto-apertura de modal vía URL compartida
  - Notificaciones toast
  - Integración con dashboard de usuario
  - Endpoints AJAX: get_cupon, search_cupones, submit_cupon, delete_cupon
  - Estilos CSS completos para grid, modal, badges
  - JavaScript para interactividad (cupones.js)
  - Documentación completa en CUPONES_DOCUMENTACION.md

- **Mejoras en sistema de paginación**:
  - 3 tipos: default (números), lazy (scroll infinito), load_more (botón)
  - Parámetro `per_page` en shortcode de establecimientos
  - Búsqueda global AJAX (no limitada a página cargada)

- **Mejoras en búsqueda**:
  - Búsqueda en taxonomías (categorías y tags)
  - Placeholder personalizable en shortcode
  - Anti-duplicación en resultados

### 1.0.0

- Versión inicial
- CPT Establecimiento con taxonomías
- Grid con buscador en tiempo real
- Modal de información de contacto
- Formulario de solicitud para usuarios
- Panel de usuario frontend
- Sistema de notificaciones por email
- Sistema de logs

## Licencia

GPL v2 o posterior

## Autor

Tu Nombre o Empresa

## Soporte

Para reportar bugs o solicitar características, por favor abre un issue en el repositorio.
