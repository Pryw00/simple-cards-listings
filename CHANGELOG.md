# Changelog - Simple Cards Listings

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

## [1.3.0] - 2026-02-19

### Añadido

#### Integración con Advanced Role Manager

- Sistema de permisos completamente rediseñado para integración con Advanced Role Manager
- Soporte para capacidades personalizadas de WordPress en lugar de verificaciones hardcodeadas
- Nuevos métodos en `SCL_Permissions`:
  - `can_manage_settings()` - Verificar permisos de configuración
  - `can_create_promocion()` - Verificar creación de promociones
  - `can_edit_promocion()` - Verificar edición de promociones
  - `can_delete_promocion()` - Verificar eliminación de promociones
  - `can_approve_promocion()` - Verificar aprobación de promociones
- Hook `scl_check_permission` para integración con sistemas externos de permisos
- Filtrado automático de queries por permisos para promociones
- Documentación completa de integración en `INTEGRATION-ACCESS-CONTROL.md`

### Mejorado

- `class-scl-permissions.php`: Refactorizado completamente para usar sistema de capacidades
- `class-scl-admin.php`: Actualizado para usar verificaciones de permisos del sistema integrado
- `class-scl-metaboxes.php`: Mejorada verificación de permisos para campo de propietario
- `class-scl-cupones.php`: Actualizado sistema de permisos para promociones
- Verificaciones de permisos ahora utilizan `apply_filters` para permitir extensibilidad

### Compatibilidad

- Mantiene compatibilidad con versiones anteriores
- Los administradores conservan acceso completo sin necesidad de capacidades adicionales
- Las capacidades personalizadas son opcionales - el sistema funciona con y sin ellas

## [1.2.2] - Anterior

### Características Existentes

- Sistema de establecimientos (Custom Post Type)
- Sistema de promociones/cupones
- Gestión de categorías y etiquetas
- Sistema de logs de actividad
- Dashboard de usuario
- Integración con Event Show
- Shortcodes para mostrar establecimientos
- Sistema de notificaciones
- Campos personalizados para establecimientos:
  - Logo
  - Menú PDF
  - Redes sociales (WhatsApp, Instagram, TikTok, Facebook)
  - Sitio web
  - Dirección y Google Maps
  - Imagen del establecimiento
- Campos personalizados para promociones:
  - Establecimiento asociado
  - Fecha de inicio y fin
  - Estado de vigencia
- Panel de configuración con:
  - Email de notificaciones
  - Columnas del grid
  - Retención de logs
  - Personalización de modales

---

**Nota**: Para información sobre versiones anteriores a 1.2.2, consulta los commits del repositorio.
