# Migración de Cupones a Promociones

## ⚠️ Cambio Importante

El post type ha sido renombrado de `cupon` a `promocion` para evitar conflictos con WooCommerce.

## Cambios Realizados

### Base de Datos

- Post Type: `cupon` → `promocion`
- Taxonomía: `categoria_cupon` → `categoria_promocion`
- Capabilities: `edit_cupon` → `edit_promocion`, `delete_cupon` → `delete_promocion`
- Slug URL: `/cupon/` → `/promocion/`

### Meta Keys (SIN CAMBIOS)

Los meta keys se mantienen igual:

- `_scl_establecimiento_id`
- `_scl_fecha_inicio`
- `_scl_fecha_fin`
- `_scl_destacado`

## Migración de Datos Existentes

Si ya tenías cupones creados con el post_type antiguo, necesitas migrar los datos.

### Opción 1: Script SQL (Recomendado)

Ejecuta este SQL en phpMyAdmin o WP-CLI:

```sql
-- Cambiar post_type de cupon a promocion
UPDATE wp_posts
SET post_type = 'promocion'
WHERE post_type = 'cupon';

-- Cambiar taxonomía
UPDATE wp_term_taxonomy
SET taxonomy = 'categoria_promocion'
WHERE taxonomy = 'categoria_cupon';

-- Limpiar cache
DELETE FROM wp_options WHERE option_name LIKE '_transient_%';
DELETE FROM wp_options WHERE option_name LIKE '_site_transient_%';
```

**IMPORTANTE**: Reemplaza `wp_` con el prefijo de tu base de datos si es diferente.

### Opción 2: Plugin WP-CLI

Si tienes WP-CLI instalado:

```bash
wp db query "UPDATE wp_posts SET post_type = 'promocion' WHERE post_type = 'cupon';"
wp db query "UPDATE wp_term_taxonomy SET taxonomy = 'categoria_promocion' WHERE taxonomy = 'categoria_cupon';"
wp cache flush
```

### Opción 3: Crear Nuevas Promociones

Si solo tienes pocos cupones, es más seguro:

1. Anota la información de los cupones existentes
2. Elimina los cupones antiguos
3. Crea nuevas promociones con la misma información

## Después de la Migración

1. **Flush Rewrite Rules**:
   - Ve a WP Admin → Ajustes → Enlaces permanentes
   - Click en "Guardar cambios" (sin modificar nada)

2. **Verificar**:
   - Ve a WP Admin → Deberías ver el menú "Promociones"
   - Las promociones migradas deberían aparecer en el listado

3. **Probar**:
   - Edita una promoción
   - Crea una nueva promoción de prueba
   - Verifica el shortcode `[scl_cupones]` en el frontend

## URLs Afectadas

### Antes:

- `/cupon/nombre-del-cupon/`
- `/categoria-cupon/nombre-categoria/`
- `/wp-admin/post-new.php?post_type=cupon`

### Después:

- `/promocion/nombre-de-la-promocion/`
- `/categoria-promocion/nombre-categoria/`
- `/wp-admin/post-new.php?post_type=promocion`

**Nota**: WordPress debería crear redirecciones automáticas 301 de las URLs antiguas a las nuevas.

## Solución de Problemas

### No aparecen las promociones migradas

1. Verifica que la consulta SQL se ejecutó correctamente
2. Limpia el cache: `DELETE FROM wp_options WHERE option_name LIKE '_transient_%';`
3. Flush rewrite rules nuevamente

### Las categorías no aparecen

1. Verifica la tabla `wp_term_taxonomy`
2. Asegúrate de que `taxonomy = 'categoria_promocion'`
3. Reconstruye las categorías si es necesario

### Error de permisos

1. Los capabilities cambiaron
2. Sal y vuelve a entrar al admin
3. Si persiste, desactiva y reactiva el plugin

## Notas Importantes

- ✅ Los meta datos (\_scl_establecimiento_id, fechas, etc.) NO necesitan migración
- ✅ Las imágenes destacadas se mantienen automáticamente
- ✅ Los autores de los posts se mantienen
- ⚠️ Las URLs cambiarán (se crean redirecciones automáticas)
- ⚠️ Si tienes enlaces hardcoded a `/cupon/`, actualízalos a `/promocion/`

## Estado Actual

**Versión**: 1.1.0
**Post Type**: `promocion`
**Taxonomía**: `categoria_promocion`
**Compatibilidad**: Sin conflictos con WooCommerce ✅
