# Simple Cards Listings

Plugin de directorio de cartas de contacto de negocios para WordPress, desarrollado conforme a la especificación IEEE 830-1998.

## Descripción

"Simple Cards Listings" es un plugin para WordPress que gestiona cartas de información de contacto de negocios, permitiendo a los usuarios buscar negocios según criterios específicos. Soporta tanto la administración desde el backend como la interacción avanzada con usuarios en el frontend.

## Características

### Para Administradores

- **Custom Post Type "Establecimiento"**: Gestión completa de negocios con campos personalizados
- **Taxonomías personalizadas**: Categorías de establecimiento y Tags de búsqueda
- **Campos personalizados**:
  - Logo del establecimiento
  - Imagen del local
  - Archivo PDF (Menú/Carta)
  - Redes sociales (Instagram, TikTok, Facebook)
  - WhatsApp
  - Sitio web
  - Dirección física
  - URL de Google Maps
- **Sistema de logs**: Registro de todas las acciones importantes
- **Panel de configuración**: Opciones personalizables

### Para Usuarios Registrados

- **Panel de usuario frontend**: Gestión de establecimientos propios
- **Formulario de solicitud**: Envío de nuevos establecimientos para aprobación
- **Edición de datos**: Actualización de información de establecimientos asignados

### Para Visitantes

- **Grid de establecimientos**: Visualización de logos en cuadrícula
- **Buscador en tiempo real**: Filtrado por nombre, descripción, categoría y tags
- **Modal de información**: Vista detallada con enlaces a redes, WhatsApp, ubicación y menú

## Instalación

1. Sube la carpeta `simple-cards-listings` al directorio `/wp-content/plugins/`
2. Activa el plugin desde el menú "Plugins" en WordPress
3. Configura las opciones en "Establecimientos > Configuración"

## Shortcodes

### Grid de Establecimientos

```
[scl_grid]
```

Parámetros opcionales:

- `categoria=""` - Filtrar por slug de categoría
- `limit="-1"` - Límite de resultados (-1 para todos)
- `columns="3"` - Número de columnas (2-6)

Ejemplo:

```
[scl_grid categoria="restaurantes" columns="4" limit="12"]
```

### Formulario de Solicitud

```
[scl_solicitud]
```

Solo visible para usuarios registrados. Permite solicitar el registro de un nuevo establecimiento.

### Panel de Usuario

```
[scl_user_dashboard]
```

Dashboard para que los usuarios gestionen sus establecimientos.

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
│   │   └── admin.js             # JavaScript del admin
│   └── images/
│       └── placeholder.png      # Imagen placeholder
├── includes/
│   ├── class-scl-post-types.php # Registro de CPT
│   ├── class-scl-taxonomies.php # Registro de taxonomías
│   ├── class-scl-metaboxes.php  # Metaboxes personalizados
│   ├── class-scl-shortcodes.php # Shortcodes
│   ├── class-scl-ajax-handlers.php # Manejadores AJAX
│   ├── class-scl-notifications.php # Sistema de notificaciones
│   ├── class-scl-logger.php     # Sistema de logs
│   ├── class-scl-user-dashboard.php # Dashboard de usuario
│   ├── class-scl-permissions.php # Sistema de permisos
│   └── admin/
│       └── class-scl-admin.php  # Funcionalidades de admin
└── languages/                    # Archivos de traducción
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
