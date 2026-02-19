# Integración con Advanced Role Manager

## Descripción

Este documento describe la integración del plugin **Simple Cards Listings** con el sistema de control de acceso del plugin **Advanced Role Manager** (simple-access-control-pryw).

## Capacidades Personalizadas

El sistema de control de acceso ahora reconoce y puede gestionar las siguientes capacidades personalizadas para Simple Cards Listings:

### Establecimientos

- `create_establecimientos` - Crear nuevos establecimientos
- `edit_establecimientos` - Editar establecimientos propios
- `edit_others_establecimientos` - Editar establecimientos de otros usuarios
- `delete_establecimientos` - Eliminar establecimientos propios
- `delete_others_establecimientos` - Eliminar establecimientos de otros usuarios
- `approve_establecimientos` - Aprobar establecimientos (publicarlos)
- `manage_establecimiento_terms` - Gestionar categorías y etiquetas de establecimientos

### Promociones

- `create_promociones` - Crear nuevas promociones
- `edit_promociones` - Editar promociones propias
- `edit_others_promociones` - Editar promociones de otros usuarios
- `delete_promociones` - Eliminar promociones propias
- `delete_others_promociones` - Eliminar promociones de otros usuarios
- `approve_promociones` - Aprobar promociones (publicarlas)

### Sistema

- `view_scl_logs` - Ver los registros de actividad del sistema
- `manage_scl_settings` - Gestionar la configuración del plugin

## Cómo Asignar Permisos

### Desde el Panel de Advanced Role Manager

1. Ve a **Roles y Permisos** > **Roles** en el menú de administración de WordPress
2. Selecciona el rol que deseas modificar
3. En la lista de capacidades agrupadas, encontrarás tres nuevas secciones:
   - **Establecimientos** - Permisos relacionados con establecimientos
   - **Promociones** - Permisos relacionados con promociones
   - **Sistema SCL** - Permisos del sistema

4. Marca las capacidades que deseas asignar al rol
5. Guarda los cambios

### Desde la Página de Integraciones

1. Ve a **Roles y Permisos** > **Integraciones**
2. Encontrarás una sección dedicada a **Simple Cards Listings**
3. Ahí se muestra la lista completa de capacidades disponibles
4. Haz clic en "Ir a Gestión de Roles" para configurar los permisos

## Funcionamiento de los Permisos

### Permisos Jerárquicos

Los permisos funcionan de manera jerárquica:

- Si un usuario tiene `edit_others_establecimientos`, puede editar cualquier establecimiento
- Si solo tiene `edit_establecimientos`, solo puede editar sus propios establecimientos
- Lo mismo aplica para promociones

### Aprobación de Contenido

Los usuarios con la capacidad `approve_establecimientos` o `approve_promociones` pueden:

- Cambiar el estado de publicación de pendiente a publicado
- Ver todos los establecimientos/promociones pendientes de aprobación
- Gestionar el flujo de trabajo de contenido

### Filtrado Automático

El sistema filtra automáticamente los listados en el panel de administración:

- Los usuarios sin `edit_others_establecimientos` solo verán sus propios establecimientos
- Los usuarios sin `edit_others_promociones` solo verán sus propias promociones
- Los administradores y usuarios con permisos avanzados ven todo el contenido

## Compatibilidad con Versiones Anteriores

La integración mantiene la compatibilidad con versiones anteriores:

- Los administradores (`manage_options`) mantienen acceso completo
- Los usuarios existentes mantienen sus permisos basados en capacidades estándar de WordPress
- Las capacidades personalizadas son opcionales - el sistema funciona con o sin ellas

## Roles Sugeridos

### Gestor de Contenido

Asignar las siguientes capacidades:

- `edit_others_establecimientos`
- `edit_others_promociones`
- `approve_establecimientos`
- `approve_promociones`
- `view_scl_logs`

### Colaborador

Asignar las siguientes capacidades:

- `create_establecimientos`
- `edit_establecimientos`
- `create_promociones`
- `edit_promociones`

### Moderador

Asignar las siguientes capacidades:

- `edit_others_establecimientos`
- `approve_establecimientos`
- `approve_promociones`
- `view_scl_logs`

## Desarrollo - Uso Programático

### Verificar Permisos en Código

```php
// Verificar si el usuario puede crear establecimientos
if (SCL_Permissions::can_create()) {
    // Código...
}

// Verificar si el usuario puede editar un establecimiento específico
if (SCL_Permissions::can_edit($post_id)) {
    // Código...
}

// Verificar si el usuario puede aprobar establecimientos
if (SCL_Permissions::can_approve()) {
    // Código...
}

// Verificar si el usuario puede ver logs
if (SCL_Permissions::can_view_logs()) {
    // Código...
}

// Para promociones
if (SCL_Permissions::can_create_promocion()) {
    // Código...
}

if (SCL_Permissions::can_edit_promocion($post_id)) {
    // Código...
}
```

### Hook de Integración

Se expone un filtro para verificación de permisos:

```php
$can_edit = apply_filters('scl_check_permission', false, 'edit_others_establecimientos', $user_id);
```

Este filtro es usado internamente por la integración con Advanced Role Manager.

## Archivos Modificados

### Advanced Role Manager (simple-access-control-pryw)

- `includes/class-arm-integrations.php` - Añadida integración con SCL
- `includes/class-arm-capability-manager.php` - Añadido agrupamiento de capacidades SCL

### Simple Cards Listings

- `includes/class-scl-permissions.php` - Actualizado para usar capacidades personalizadas
- `includes/admin/class-scl-admin.php` - Actualizado para verificar permisos con capacidades
- `includes/class-scl-metaboxes.php` - Actualizado para verificar permisos con capacidades
- `includes/class-scl-cupones.php` - Actualizado para verificar permisos con capacidades

## Troubleshooting

### Los permisos no se aplican

1. Verifica que el usuario tenga el rol correcto asignado
2. Verifica que las capacidades estén asignadas al rol
3. Limpia la caché de WordPress y del navegador
4. Verifica que ambos plugins estén activos

### No aparecen las capacidades de SCL

1. Verifica que el plugin Simple Cards Listings esté activo
2. Ve a Roles y Permisos > Integraciones para verificar que la integración está activa
3. Recarga la página de gestión de roles

### Los usuarios no pueden ver sus propios establecimientos

Asegúrate de que el usuario tenga al menos la capacidad `read` de WordPress y que:

- Tenga `edit_establecimientos` para ver los propios
- O tenga `edit_others_establecimientos` para ver todos

## Soporte

Para soporte sobre esta integración, contacta al desarrollador del plugin o revisa la documentación en:

- Advanced Role Manager: `simple-access-control-pryw/README.md`
- Simple Cards Listings: `simple-cards-listings/README.md`

---

**Última actualización:** 19 de febrero de 2026
**Versión:** 1.0.0
