# Nuevas Funcionalidades - Simple Cards Listings

## Resumen de Cambios

Se han implementado 3 nuevas funcionalidades principales para mejorar el grid de establecimientos:

### 1. Sistema de Paginación Configurable

El shortcode `[scl_grid]` ahora soporta paginación con 3 tipos diferentes:

#### Parámetros del Shortcode

```php
[scl_grid
    categoria=""
    columns="3"
    per_page="12"
    pagination_type="default"
    search_placeholder=""
]
```

**Parámetros disponibles:**

- **`categoria`**: Filtrar por slug de categoría específica
- **`columns`** (default: 3): Número de columnas del grid (2-6)
- **`per_page`** (default: 12): Número de establecimientos por página
- **`pagination_type`** (default: "default"): Tipo de paginación
  - `"default"` - Paginación tradicional con números de página
  - `"lazy"` - Carga automática al hacer scroll (infinite scroll)
  - `"load_more"` - Botón "Cargar más" para cargar siguiente página
- **`search_placeholder`**: Texto personalizado para el campo de búsqueda (default: "Ejm: pizza, café, tienda...")

#### Ejemplos de Uso

**Paginación tradicional (12 por página):**

```php
[scl_grid per_page="12" pagination_type="default"]
```

**Lazy Load / Infinite Scroll (20 por página):**

```php
[scl_grid per_page="20" pagination_type="lazy"]
```

**Botón Cargar Más (15 por página):**

```php
[scl_grid per_page="15" pagination_type="load_more"]
```

**Con filtro de categoría:**

```php
[scl_grid categoria="restaurantes" per_page="10" pagination_type="lazy"]
```

**Con placeholder personalizado:**

```php
[scl_grid search_placeholder="Buscar tu restaurante favorito..."]
```

**Combinando múltiples parámetros:**

```php
[scl_grid categoria="restaurantes" columns="4" per_page="16" pagination_type="lazy" search_placeholder="¿Qué antojo tienes hoy?"]
```

### 2. Dropdown de Categorías en el Buscador

Se ha agregado un dropdown de categorías entre el campo de búsqueda y el botón de buscar.

**Comportamiento:**

- **Sin filtro de categoría en shortcode:** Muestra todas las categorías padre
- **Con filtro de categoría en shortcode:** Muestra solo las categorías hijas de la categoría filtrada
- **Si no hay categorías hijas:** El dropdown no aparece

**Ejemplo:**

Si usas `[scl_grid categoria="restaurantes"]` y "restaurantes" tiene subcategorías como "Comida rápida", "Italiana", "Mexicana", el dropdown mostrará solo esas subcategorías.

### 3. Búsqueda Global (Sin Limitaciones de Paginación)

**Problema resuelto:**
Anteriormente, si tenías 1500 establecimientos y mostrábamos solo 12 por página, el buscador solo podía filtrar entre esos 12 visibles.

**Solución implementada:**

- La búsqueda ahora funciona **del lado del servidor con AJAX**
- Busca en TODA la base de datos de establecimientos (sin límite de paginación)
- Solo carga en el DOM los resultados encontrados
- Al buscar, realiza una consulta completa a la base de datos WordPress
- Muestra TODOS los resultados encontrados (sin paginación durante búsqueda)
- Cuando se borra la búsqueda, vuelve a mostrar la primera página con paginación
- Los filtros de categoría del dropdown también se aplican en la búsqueda

## Detalles Técnicos

### Archivos Modificados

1. **`includes/class-scl-shortcodes.php`**
   - Nuevos parámetros en shortcode
   - Generación de dropdown de categorías
   - HTML para controles de paginación
   - Carga de TODOS los establecimientos en JSON para búsqueda

2. **`includes/class-scl-ajax-handlers.php`**
   - Nuevo endpoint: `scl_load_more`
   - Maneja carga de páginas adicionales
   - Soporta filtros de categoría y búsqueda

3. **`assets/js/frontend.js`**
   - Sistema de paginación completo
   - Lazy load con detección de scroll
   - Botón "Cargar más"
   - Paginación tradicional
   - Filtro de categorías
   - Búsqueda global mejorada

4. **`assets/css/frontend.css`**
   - Estilos para dropdown de categorías
   - Estilos para botón "Cargar más"
   - Estilos para paginación tradicional
   - Responsive para móviles

### Flujo de Funcionamiento

#### Carga Inicial

1. Se cargan solo los primeros X establecimientos (según `per_page`)
2. Se inicializa el tipo de paginación configurado
3. El grid muestra la primera página de resultados

#### Búsqueda

1. Usuario escribe en el buscador (con debounce de 400ms)
2. Se realiza una llamada AJAX al servidor
3. El servidor busca en TODA la base de datos usando WP_Query
4. Retorna TODOS los resultados encontrados (sin paginación)
5. El grid se actualiza mostrando todos los resultados
6. La paginación se oculta durante la búsqueda activa
7. Al borrar la búsqueda, vuelve a la primera página con paginación

#### Filtro de Categoría

1. Usuario selecciona categoría del dropdown
2. Se combina con el término de búsqueda (si existe)
3. Realiza búsqueda por AJAX aplicando ambos filtros
4. Muestra todos los resultados que coincidan

#### Paginación

- **Lazy Load:** Detecta scroll al final → Carga siguiente página automáticamente
- **Load More:** Click en botón → Añade siguiente página al grid
- **Default:** Click en número → Reemplaza contenido con esa página
- **Durante búsqueda:** La paginación se oculta y se muestran todos los resultados

## Optimización de Rendimiento

### Para sitios con muchos establecimientos (>1000):

**Búsqueda optimizada:**

- La búsqueda se realiza del lado del servidor usando las capacidades de WordPress
- Solo se carga el HTML de los resultados encontrados
- No se carga JSON completo de todos los establecimientos
- Usa índices de base de datos de WordPress para búsqueda rápida

**Tamaño de transferencia:**

- Carga inicial: Solo HTML de la primera página (ej: 12 establecimientos)
- Durante búsqueda: Solo HTML de los resultados encontrados
- Sin overhead de JSON completo

**Recomendaciones:**

- Usa `per_page="12"` o menos para carga inicial rápida
- Usa `pagination_type="lazy"` para mejor experiencia de usuario
- Considera usar categorías para segmentar el contenido
- La búsqueda tiene debounce de 400ms para evitar demasiadas peticiones AJAX

## Compatibilidad

- ✅ Compatible con versiones anteriores del shortcode
- ✅ Si no especificas los nuevos parámetros, usa valores por defecto
- ✅ Funciona en móviles y tablets
- ✅ Compatible con los filtros de búsqueda existentes

## Testing

Prueba los siguientes escenarios:

1. **Paginación básica:** `[scl_grid per_page="6" pagination_type="default"]`
2. **Lazy load:** `[scl_grid per_page="10" pagination_type="lazy"]` → Scroll hasta el final
3. **Cargar más:** `[scl_grid per_page="8" pagination_type="load_more"]` → Click en botón
4. **Con categoría:** `[scl_grid categoria="restaurantes" per_page="12"]`
5. **Búsqueda global:** Buscar algo que esté en página 3 o 4
6. **Filtro combinado:** Buscar + seleccionar categoría del dropdown
