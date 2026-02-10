# Tipografías del Plugin

## Configuración Actual

El plugin usa dos fuentes:

### Chunk (Títulos)
- **Uso**: Todos los títulos (h1, h2, h3, h4, h5, h6)
- **Archivos**: chunk.ttf, chunk.otf
- **Ubicación**: Esta carpeta
- **Aplicado a**: 
  - Títulos de tarjetas
  - Títulos de promociones
  - Encabezados del dashboard
  - Títulos de modales

### Montserrat (Texto general)
- **Uso**: Todo el texto del cuerpo
- **Fuente**: Google Fonts
- **Pesos disponibles**: 300, 400, 500, 600, 700, 800
- **Aplicado a**: 
  - Descripciones
  - Texto de botones
  - Formularios
  - Contenido general

## Optimización (Opcional)

Para mejor rendimiento, puedes convertir chunk.ttf a formatos web modernos:

### Formatos web modernos (recomendado):
- `chunk.woff2` (formato más eficiente)
- `chunk.woff` (soporte para navegadores antiguos)

Herramientas de conversión:
- https://www.fontsquirrel.com/tools/webfont-generator
- https://transfonter.org/

Los formatos .ttf y .otf actuales funcionan perfectamente, pero los formatos web son más ligeros y cargan más rápido.
### Formatos recomendados:

1. **WOFF2** - Formato moderno, mejor compresión (prioritario)
2. **WOFF** - Compatibilidad con navegadores antiguos
3. **TTF** - Fallback adicional

### Convertir archivos:

Si solo tienes archivos TTF u OTF, puedes convertirlos en:

- https://transfonter.org/
- https://convertio.co/es/font-converter/

### Notas:

- El CSS ya está configurado en `assets/css/public.css`
- **Poppins** (Google Fonts) se usa para textos generales
- **TituloFont** (local) se usa para títulos y encabezados
- Asegúrate de que el nombre de los archivos coincida exactamente
