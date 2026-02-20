<?php

/**
 * Shortcodes del plugin
 *
 * @package SimpleCardsListings
 * @since 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar los shortcodes
 */
class SCL_Shortcodes
{

    /**
     * Inicializar shortcodes
     */
    public static function init()
    {
        add_shortcode('scl_grid', array(__CLASS__, 'render_grid'));
        add_shortcode('scl_grid_gold', array(__CLASS__, 'render_grid_gold'));
        add_shortcode('scl_cupones', array(__CLASS__, 'render_cupones_grid'));
        add_shortcode('scl_solicitud', array(__CLASS__, 'render_solicitud_form'));
        add_shortcode('scl_user_dashboard', array(__CLASS__, 'render_user_dashboard'));

        // Hook para mostrar modal de promoción compartida en cualquier página
        add_action('wp_footer', array(__CLASS__, 'render_shared_promocion_modal'));
    }

    /**
     * Renderizar modal de promoción compartida (cuando se accede via URL con cupon_id)
     */
    public static function render_shared_promocion_modal()
    {
        // Solo si hay cupon_id en la URL y no estamos en una página con el shortcode de cupones
        if (!isset($_GET['cupon_id'])) {
            return;
        }

        $cupon_id = intval($_GET['cupon_id']);
        if (!$cupon_id || get_post_type($cupon_id) !== 'promocion') {
            return;
        }

        $cupon = get_post($cupon_id);
        if (!$cupon || $cupon->post_status !== 'publish') {
            return;
        }

        // Obtener datos de la promoción
        $imagen_url = get_the_post_thumbnail_url($cupon_id, 'large');
        if (!$imagen_url) {
            $imagen_url = SCL_PLUGIN_URL . 'assets/images/cupon-placeholder.png';
        }

        $establecimiento_id = get_post_meta($cupon_id, '_scl_establecimiento_id', true);
        $establecimiento = null;
        if ($establecimiento_id) {
            $est = get_post($establecimiento_id);
            if ($est) {
                $establecimiento = array(
                    'titulo' => $est->post_title,
                    'url' => get_permalink($est->ID),
                );
            }
        }

        $fecha_inicio = get_post_meta($cupon_id, '_scl_fecha_inicio', true);
        $fecha_fin = get_post_meta($cupon_id, '_scl_fecha_fin', true);

?>
        <!-- Modal de promoción compartida -->
        <div id="scl-shared-cupon-modal" class="scl-modal" style="display: block;">
            <div class="scl-modal-overlay" onclick="document.getElementById('scl-shared-cupon-modal').style.display='none';"></div>
            <div class="scl-modal-content scl-cupon-modal-content">
                <button type="button" class="scl-modal-close" onclick="document.getElementById('scl-shared-cupon-modal').style.display='none'; window.history.replaceState({}, '', window.location.pathname);" aria-label="<?php esc_attr_e('Cerrar', 'simple-cards-listings'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
                <div class="scl-modal-body">
                    <div class="scl-cupon-modal-inner">
                        <!-- Imagen -->
                        <div class="scl-cupon-modal-imagen">
                            <img src="<?php echo esc_url($imagen_url); ?>" alt="<?php echo esc_attr($cupon->post_title); ?>">
                        </div>

                        <!-- Información -->
                        <div class="scl-cupon-modal-info">
                            <?php if ($establecimiento) : ?>
                                <div class="scl-cupon-establecimiento">
                                    <a href="<?php echo esc_url($establecimiento['url']); ?>" target="_blank"><?php echo esc_html($establecimiento['titulo']); ?></a>
                                </div>
                            <?php endif; ?>

                            <h2 class="scl-cupon-modal-titulo"><?php echo esc_html($cupon->post_title); ?></h2>
                            <div class="scl-cupon-modal-descripcion"><?php echo wpautop($cupon->post_content); ?></div>

                            <?php if ($fecha_inicio || $fecha_fin) : ?>
                                <div class="scl-cupon-fechas">
                                    <?php if ($fecha_inicio) : ?>
                                        <div class="scl-cupon-fecha"><strong><?php esc_html_e('Válido desde:', 'simple-cards-listings'); ?></strong> <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($fecha_inicio))); ?></div>
                                    <?php endif; ?>
                                    <?php if ($fecha_fin) : ?>
                                        <div class="scl-cupon-fecha"><strong><?php esc_html_e('Válido hasta:', 'simple-cards-listings'); ?></strong> <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($fecha_fin))); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Botones de acción -->
                            <div class="scl-cupon-acciones">
                                <button type="button" class="scl-btn scl-btn-secondary" onclick="navigator.clipboard.writeText(window.location.href); alert('<?php esc_attr_e('Enlace copiado al portapapeles', 'simple-cards-listings'); ?>');">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path>
                                        <polyline points="16 6 12 2 8 6"></polyline>
                                        <line x1="12" y1="2" x2="12" y2="15"></line>
                                    </svg>
                                    <?php esc_html_e('Compartir', 'simple-cards-listings'); ?>
                                </button>

                                <a href="<?php echo esc_url($imagen_url); ?>" download class="scl-btn scl-btn-secondary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7 10 12 15 17 10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                    <?php esc_html_e('Descargar', 'simple-cards-listings'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <style>
            #scl-shared-cupon-modal {
                z-index: 999999;
            }

            body {
                overflow: hidden;
            }
        </style>
    <?php
    }

    /**
     * Shortcode: Grid de establecimientos
     * [scl_grid categoria="" columns="3" per_page="12" pagination_type="default" search_placeholder=""]
     *
     * @param array $atts Atributos del shortcode.
     * @return string
     */
    public static function render_grid($atts)
    {
        $atts = shortcode_atts(array(
            'categoria'          => '',
            'limit'              => -1,
            'columns'            => 3,
            'per_page'           => 12,
            'pagination_type'    => 'default', // default, lazy, load_more
            'search_placeholder' => '', // Texto personalizado para el buscador
            'filterloc'          => 'false', // true: mostrar filtro de ubicación
        ), $atts, 'scl_grid');

        // Determinar posts_per_page basado en paginación
        $posts_per_page = intval($atts['per_page']);
        if ($posts_per_page <= 0) {
            $posts_per_page = 12; // Default
        }

        $args = array(
            'post_type'      => 'establecimiento',
            'post_status'    => 'publish',
            'posts_per_page' => $posts_per_page,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'paged'          => 1,
        );

        // Filtrar por categoría si se especifica
        $categoria_filter = '';
        if (! empty($atts['categoria'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'categoria_establecimiento',
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field($atts['categoria']),
                ),
            );
            $categoria_filter = sanitize_text_field($atts['categoria']);
        }

        $establecimientos = new WP_Query($args);

        // Obtener categorías para el filtro dropdown
        // Si hay categoría filtrada, obtener sus hijos
        $categorias_dropdown = array();
        if (!empty($categoria_filter)) {
            $parent_term = get_term_by('slug', $categoria_filter, 'categoria_establecimiento');
            if ($parent_term) {
                $categorias_dropdown = get_terms(array(
                    'taxonomy'   => 'categoria_establecimiento',
                    'hide_empty' => true,
                    'parent'     => $parent_term->term_id,
                ));
            }
        } else {
            // Sin filtro, mostrar todas las categorías padre
            $categorias_dropdown = get_terms(array(
                'taxonomy'   => 'categoria_establecimiento',
                'hide_empty' => true,
                'parent'     => 0,
            ));
        }

        // Obtener ubicaciones si filterloc='true'
        $ubicaciones_dropdown = array();
        if ($atts['filterloc'] === 'true') {
            $ubicaciones_dropdown = get_terms(array(
                'taxonomy'   => 'ubicacion_establecimiento',
                'hide_empty' => true,
            ));
        }

        // Obtener tags para sugerencias
        $tags = get_terms(array(
            'taxonomy'   => 'tag_busqueda',
            'hide_empty' => true,
        ));

        ob_start();
    ?>
        <div class="scl-container"
            data-pagination-type="<?php echo esc_attr($atts['pagination_type']); ?>"
            data-per-page="<?php echo esc_attr($posts_per_page); ?>"
            data-categoria-filter="<?php echo esc_attr($categoria_filter); ?>"
            data-columns="<?php echo esc_attr($atts['columns']); ?>"
            data-filterloc="<?php echo esc_attr($atts['filterloc']); ?>">

            <!-- Buscador -->
            <div class="scl-search-wrapper">
                <div class="scl-search-box">
                    <input
                        type="text"
                        id="scl-search-input"
                        class="scl-search-input"
                        placeholder="<?php echo esc_attr(!empty($atts['search_placeholder']) ? $atts['search_placeholder'] : __('Buscar Establecimiento...', 'simple-cards-listings')); ?>"
                        autocomplete="off">

                    <!-- Dropdown de categorías (solo si hay categorías) -->
                    <?php if (!empty($categorias_dropdown) && !is_wp_error($categorias_dropdown)) : ?>
                        <select id="scl-category-filter" class="scl-category-filter">
                            <option value=""><?php esc_html_e('Todas las categorías', 'simple-cards-listings'); ?></option>
                            <?php foreach ($categorias_dropdown as $cat) : ?>
                                <option value="<?php echo esc_attr($cat->slug); ?>">
                                    <?php echo esc_html($cat->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>

                    <!-- Dropdown de ubicaciones (solo si filterloc='true') -->
                    <?php if (!empty($ubicaciones_dropdown) && !is_wp_error($ubicaciones_dropdown)) : ?>
                        <select id="scl-ubicacion-filter" class="scl-ubicacion-filter">
                            <option value=""><?php esc_html_e('Todas las ubicaciones', 'simple-cards-listings'); ?></option>
                            <?php foreach ($ubicaciones_dropdown as $ubicacion) : ?>
                                <option value="<?php echo esc_attr($ubicacion->slug); ?>">
                                    <?php echo esc_html($ubicacion->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>

                    <button type="button" class="scl-search-button" aria-label="<?php esc_attr_e('Buscar', 'simple-cards-listings'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </button>
                </div>

                <!-- Sugerencias de búsqueda -->
                <div id="scl-search-suggestions" class="scl-search-suggestions" style="display: none;">
                    <?php foreach ($tags as $tag) : ?>
                        <div class="scl-suggestion-item" data-term="<?php echo esc_attr($tag->name); ?>">
                            <?php echo esc_html($tag->name); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Grid de establecimientos -->
            <div class="scl-grid scl-grid-<?php echo esc_attr($atts['columns']); ?>" id="scl-grid">
                <?php if ($establecimientos->have_posts()) : ?>
                    <?php while ($establecimientos->have_posts()) : $establecimientos->the_post(); ?>
                        <?php echo self::render_card_item(get_the_ID()); ?>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else : ?>
                    <p class="scl-no-results"><?php esc_html_e('No se encontraron establecimientos.', 'simple-cards-listings'); ?></p>
                <?php endif; ?>
            </div>

            <!-- Mensaje de no resultados -->
            <div id="scl-no-results" class="scl-no-results" style="display: none;">
                <?php esc_html_e('No se encontraron resultados para tu búsqueda.', 'simple-cards-listings'); ?>
            </div>

            <!-- Área de paginación -->
            <div class="scl-pagination-wrapper">
                <?php if ($atts['pagination_type'] === 'load_more' && $establecimientos->max_num_pages > 1) : ?>
                    <button type="button" class="scl-load-more-btn" data-page="1" data-max-pages="<?php echo esc_attr($establecimientos->max_num_pages); ?>">
                        <?php esc_html_e('Cargar más', 'simple-cards-listings'); ?>
                    </button>
                <?php elseif ($atts['pagination_type'] === 'default' && $establecimientos->max_num_pages > 1) : ?>
                    <div class="scl-pagination" data-current-page="1" data-max-pages="<?php echo esc_attr($establecimientos->max_num_pages); ?>">
                        <?php
                        echo paginate_links(array(
                            'total'   => $establecimientos->max_num_pages,
                            'current' => 1,
                            'format'  => '?paged=%#%',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                        ));
                        ?>
                    </div>
                <?php endif; ?>
                <!-- Para lazy load, el scroll se detecta automáticamente -->
                <?php if ($atts['pagination_type'] === 'lazy') : ?>
                    <div class="scl-lazy-loader" style="display: none;" data-page="1" data-max-pages="<?php echo esc_attr($establecimientos->max_num_pages); ?>">
                        <div class="scl-loading"></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modal de establecimiento -->
        <div id="scl-modal" class="scl-modal" style="display: none;">
            <div class="scl-modal-overlay"></div>
            <div class="scl-modal-content">
                <button type="button" class="scl-modal-close" aria-label="<?php esc_attr_e('Cerrar', 'simple-cards-listings'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
                <div class="scl-modal-body" id="scl-modal-body">
                    <!-- Contenido cargado via AJAX -->
                </div>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Parsear parámetro de niveles
     * Formato: "{role_slug, #color, priority};{role_slug2, #color2, priority2};"
     * 
     * @param string $level_string String con los niveles
     * @return array Array de niveles parseados y ordenados por prioridad
     */
    private static function parse_levels($level_string)
    {
        $levels = array();

        if (empty($level_string)) {
            // Niveles por defecto
            return array(
                array('role' => 'socio_gold', 'color' => '#d4af37', 'priority' => 0),
                array('role' => '', 'color' => '#9ca3af', 'priority' => 999) // Sin rol = prioridad baja
            );
        }

        // Dividir por punto y coma
        $level_parts = explode(';', trim($level_string, ';'));

        foreach ($level_parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            // Extraer contenido entre llaves
            if (preg_match('/\{([^}]+)\}/', $part, $matches)) {
                $content = $matches[1];
                $values = array_map('trim', explode(',', $content));

                if (count($values) >= 3) {
                    $role = sanitize_text_field($values[0]);
                    $color = sanitize_hex_color($values[1]);
                    $priority = intval($values[2]);

                    if ($color) {
                        $levels[] = array(
                            'role' => $role,
                            'color' => $color,
                            'priority' => $priority
                        );
                    }
                }
            }
        }

        // Ordenar por prioridad (menor número = mayor prioridad)
        usort($levels, function ($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        // Agregar nivel por defecto para usuarios sin rol específico
        $levels[] = array('role' => '', 'color' => '#cccccc', 'priority' => 999);

        return $levels;
    }

    /**
     * Shortcode: Grid con múltiples niveles de socios
     * [scl_grid_gold categoria="" columns="3" per_page="12" level="{socio_gold, #ff6b35, 0};{socio_silver, #c0c0c0, 1};" only_link="true"]
     *
     * @param array $atts Atributos del shortcode.
     * @return string
     */
    public static function render_grid_gold($atts)
    {
        $atts = shortcode_atts(array(
            'categoria'          => '',
            'limit'              => -1,
            'columns'            => 3,
            'per_page'           => 12,
            'pagination_type'    => 'default', // default, lazy, load_more
            'search_placeholder' => '', // Texto personalizado para el buscador
            'level'              => '', // Niveles: "{role, color, priority};..."
            'only_link'          => 'false', // true: abre sitio web, false: abre modal
            'filterloc'          => 'false', // true: mostrar filtro de ubicación
        ), $atts, 'scl_grid_gold');

        // Determinar posts_per_page basado en paginación
        $posts_per_page = intval($atts['per_page']);
        if ($posts_per_page <= 0) {
            $posts_per_page = 12; // Default
        }

        // Parsear niveles
        $levels = self::parse_levels($atts['level']);

        // Obtener TODOS los establecimientos primero para ordenar por rol
        $args = array(
            'post_type'      => 'establecimiento',
            'post_status'    => 'publish',
            'posts_per_page' => -1, // Obtener todos
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        // Filtrar por categoría si se especifica
        $categoria_filter = '';
        if (! empty($atts['categoria'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'categoria_establecimiento',
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field($atts['categoria']),
                ),
            );
            $categoria_filter = sanitize_text_field($atts['categoria']);
        }

        $all_establecimientos = new WP_Query($args);

        // Agrupar establecimientos por nivel
        $posts_by_level = array();

        // Inicializar arrays para cada nivel
        foreach ($levels as $level_index => $level) {
            $posts_by_level[$level_index] = array();
        }

        if ($all_establecimientos->have_posts()) {
            while ($all_establecimientos->have_posts()) {
                $all_establecimientos->the_post();
                $post_id = get_the_ID();
                $author_id = get_post_field('post_author', $post_id);
                $user = get_userdata($author_id);

                // Determinar en qué nivel va este establecimiento
                $assigned = false;
                foreach ($levels as $level_index => $level) {
                    // Si el nivel no tiene rol (nivel por defecto), skip por ahora
                    if (empty($level['role'])) continue;

                    if ($user && in_array($level['role'], (array) $user->roles)) {
                        $posts_by_level[$level_index][] = $post_id;
                        $assigned = true;
                        break; // Asignar solo al primer nivel que coincida
                    }
                }

                // Si no se asignó a ningún nivel con rol, asignar al último (sin rol)
                if (!$assigned) {
                    $last_level_index = count($levels) - 1;
                    $posts_by_level[$last_level_index][] = $post_id;
                }
            }
            wp_reset_postdata();
        }

        // Combinar posts ordenados por prioridad de nivel
        $ordered_ids = array();
        foreach ($posts_by_level as $level_posts) {
            $ordered_ids = array_merge($ordered_ids, $level_posts);
        }

        // Ahora hacer la query paginada con el orden personalizado
        $paged = 1;
        $offset = 0;

        // Obtener solo los IDs para la página actual
        $current_page_ids = array_slice($ordered_ids, $offset, $posts_per_page);

        // Si no hay IDs para mostrar, mostrar mensaje
        $max_pages = ceil(count($ordered_ids) / $posts_per_page);

        // Obtener categorías para el filtro dropdown
        $categorias_dropdown = array();
        if (!empty($categoria_filter)) {
            $parent_term = get_term_by('slug', $categoria_filter, 'categoria_establecimiento');
            if ($parent_term) {
                $categorias_dropdown = get_terms(array(
                    'taxonomy'   => 'categoria_establecimiento',
                    'hide_empty' => true,
                    'parent'     => $parent_term->term_id,
                ));
            }
        } else {
            // Sin filtro, mostrar todas las categorías padre
            $categorias_dropdown = get_terms(array(
                'taxonomy'   => 'categoria_establecimiento',
                'hide_empty' => true,
                'parent'     => 0,
            ));
        }

        // Obtener ubicaciones si filterloc='true'
        $ubicaciones_dropdown = array();
        if ($atts['filterloc'] === 'true') {
            $ubicaciones_dropdown = get_terms(array(
                'taxonomy'   => 'ubicacion_establecimiento',
                'hide_empty' => true,
            ));
        }

        // Obtener tags para sugerencias
        $tags = get_terms(array(
            'taxonomy'   => 'tag_busqueda',
            'hide_empty' => true,
        ));

        ob_start();
    ?>
        <div class="scl-container scl-container-gold"
            data-pagination-type="<?php echo esc_attr($atts['pagination_type']); ?>"
            data-per-page="<?php echo esc_attr($posts_per_page); ?>"
            data-categoria-filter="<?php echo esc_attr($categoria_filter); ?>"
            data-columns="<?php echo esc_attr($atts['columns']); ?>"
            data-is-gold="1"
            data-only-link="<?php echo esc_attr($atts['only_link']); ?>"
            data-levels="<?php echo esc_attr(json_encode($levels)); ?>"
            data-filterloc="<?php echo esc_attr($atts['filterloc']); ?>">

            <!-- Buscador -->
            <div class="scl-search-wrapper">
                <div class="scl-search-box">
                    <input
                        type="text"
                        id="scl-search-input"
                        class="scl-search-input"
                        placeholder="<?php echo esc_attr(!empty($atts['search_placeholder']) ? $atts['search_placeholder'] : __('Buscar Establecimiento...', 'simple-cards-listings')); ?>"
                        autocomplete="off">

                    <!-- Dropdown de categorías (solo si hay categorías) -->
                    <?php if (!empty($categorias_dropdown) && !is_wp_error($categorias_dropdown)) : ?>
                        <select id="scl-category-filter" class="scl-category-filter">
                            <option value=""><?php esc_html_e('Todas las categorías', 'simple-cards-listings'); ?></option>
                            <?php foreach ($categorias_dropdown as $cat) : ?>
                                <option value="<?php echo esc_attr($cat->slug); ?>">
                                    <?php echo esc_html($cat->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>

                    <!-- Dropdown de ubicaciones (solo si filterloc='true') -->
                    <?php if (!empty($ubicaciones_dropdown) && !is_wp_error($ubicaciones_dropdown)) : ?>
                        <select id="scl-ubicacion-filter" class="scl-ubicacion-filter">
                            <option value=""><?php esc_html_e('Todas las ubicaciones', 'simple-cards-listings'); ?></option>
                            <?php foreach ($ubicaciones_dropdown as $ubicacion) : ?>
                                <option value="<?php echo esc_attr($ubicacion->slug); ?>">
                                    <?php echo esc_html($ubicacion->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>

                    <button type="button" class="scl-search-button" aria-label="<?php esc_attr_e('Buscar', 'simple-cards-listings'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </button>
                </div>

                <!-- Sugerencias de búsqueda -->
                <div id="scl-search-suggestions" class="scl-search-suggestions" style="display: none;">
                    <?php foreach ($tags as $tag) : ?>
                        <div class="scl-suggestion-item" data-term="<?php echo esc_attr($tag->name); ?>">
                            <?php echo esc_html($tag->name); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Grid de establecimientos -->
            <div class="scl-grid scl-grid-<?php echo esc_attr($atts['columns']); ?>" id="scl-grid">
                <?php if (!empty($current_page_ids)) : ?>
                    <?php foreach ($current_page_ids as $post_id) : ?>
                        <?php echo self::render_card_item($post_id, true, '', '', '', $levels, $atts['only_link']); ?>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p class="scl-no-results"><?php esc_html_e('No se encontraron establecimientos.', 'simple-cards-listings'); ?></p>
                <?php endif; ?>
            </div>

            <!-- Mensaje de no resultados -->
            <div id="scl-no-results" class="scl-no-results" style="display: none;">
                <?php esc_html_e('No se encontraron resultados para tu búsqueda.', 'simple-cards-listings'); ?>
            </div>

            <!-- Área de paginación -->
            <div class="scl-pagination-wrapper">
                <?php if ($atts['pagination_type'] === 'load_more' && $max_pages > 1) : ?>
                    <button type="button" class="scl-load-more-btn" data-page="1" data-max-pages="<?php echo esc_attr($max_pages); ?>">
                        <?php esc_html_e('Cargar más', 'simple-cards-listings'); ?>
                    </button>
                <?php elseif ($atts['pagination_type'] === 'default' && $max_pages > 1) : ?>
                    <div class="scl-pagination" data-current-page="1" data-max-pages="<?php echo esc_attr($max_pages); ?>">
                        <?php
                        echo paginate_links(array(
                            'total'   => $max_pages,
                            'current' => 1,
                            'format'  => '?paged=%#%',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                        ));
                        ?>
                    </div>
                <?php endif; ?>
                <!-- Para lazy load, el scroll se detecta automáticamente -->
                <?php if ($atts['pagination_type'] === 'lazy') : ?>
                    <div class="scl-lazy-loader" style="display: none;" data-page="1" data-max-pages="<?php echo esc_attr($max_pages); ?>">
                        <div class="scl-loading"></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modal de establecimiento -->
        <div id="scl-modal" class="scl-modal" style="display: none;">
            <div class="scl-modal-overlay"></div>
            <div class="scl-modal-content">
                <button type="button" class="scl-modal-close" aria-label="<?php esc_attr_e('Cerrar', 'simple-cards-listings'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
                <div class="scl-modal-body" id="scl-modal-body">
                    <!-- Contenido cargado via AJAX -->
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar item de la carta (logo)
     *
     * @param int $post_id ID del post.
     * @param bool $check_gold Si debe verificar el rol del autor.
     * @param string $role_slug (Deprecated) Slug del rol a verificar.
     * @param string $color_premium (Deprecated) Color hexadecimal para usuarios premium.
     * @param string $color_normal (Deprecated) Color hexadecimal para usuarios normales.
     * @param array $levels Array de niveles con roles, colores y prioridades.
     * @param string $only_link Si es 'true', abre el sitio web en lugar del modal.
     * @return string
     */
    public static function render_card_item($post_id, $check_gold = false, $role_slug = '', $color_premium = '', $color_normal = '', $levels = array(), $only_link = 'false')
    {
        $logo_id = SCL_Metaboxes::get_meta($post_id, 'logo');
        $logo_url = '';

        if ($logo_id) {
            $logo_url = wp_get_attachment_image_url($logo_id, 'medium');
        } elseif (has_post_thumbnail($post_id)) {
            $logo_url = get_the_post_thumbnail_url($post_id, 'medium');
        } else {
            $logo_url = SCL_PLUGIN_URL . 'assets/images/placeholder.png';
        }

        $title = get_the_title($post_id);

        // Determinar clase y color según nivel del autor
        $card_class = 'scl-card-item';
        $border_color = '';
        $shadow_color = '';

        if ($check_gold && !empty($levels)) {
            $author_id = get_post_field('post_author', $post_id);
            $user = get_userdata($author_id);

            // Buscar el nivel correspondiente al usuario
            $matched_level = null;
            $level_priority = 999;
            foreach ($levels as $level) {
                // Si el nivel no tiene rol, es el nivel por defecto
                if (empty($level['role'])) {
                    if (!$matched_level) { // Solo usar como fallback si no hay match
                        $matched_level = $level;
                        $level_priority = isset($level['priority']) ? $level['priority'] : 999;
                    }
                    continue;
                }

                if ($user && in_array($level['role'], (array) $user->roles)) {
                    $matched_level = $level;
                    $level_priority = isset($level['priority']) ? $level['priority'] : 999;
                    break; //Usar el primer nivel que coincida
                }
            }

            if ($matched_level) {
                $card_class .= ' scl-card-badge';

                // Agregar clase especial para el nivel de máxima prioridad (0)
                if ($level_priority === 0) {
                    $card_class .= ' scl-card-badge-premium';
                }

                $border_color = $matched_level['color'];
                // Crear color de sombra con opacidad
                $shadow_color = $border_color . '4D'; // 30% opacity
            }
        }

        // Obtener sitio web para only_link
        $website_url = '';
        if ($only_link === 'true') {
            $website_url = SCL_Metaboxes::get_meta($post_id, 'website');
        }

        ob_start();

        // Determinar si usar diseño de medallón o card normal
        $use_badge_design = $check_gold && !empty($levels) && !empty($matched_level);

        if ($use_badge_design) {
            // Diseño de medallón para grid gold
        ?>
            <?php if ($only_link === 'true' && !empty($website_url)) : ?>
                <a href="<?php echo esc_url($website_url); ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="<?php echo esc_attr($card_class); ?>"
                    <?php if (!empty($border_color)) : ?>
                    style="--badge-color: <?php echo esc_attr($border_color); ?>; --badge-shadow: <?php echo esc_attr($shadow_color); ?>;"
                    <?php endif; ?>>
                <?php else : ?>
                    <div class="<?php echo esc_attr($card_class); ?>"
                        data-id="<?php echo esc_attr($post_id); ?>"
                        <?php if (!empty($border_color)) : ?>
                        style="--badge-color: <?php echo esc_attr($border_color); ?>; --badge-shadow: <?php echo esc_attr($shadow_color); ?>;"
                        <?php endif; ?>>
                    <?php endif; ?>
                    <div class="scl-card-badge-outer">
                        <div class="scl-card-badge-inner">
                            <div class="scl-card-logo">
                                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($title); ?>">
                            </div>
                        </div>
                    </div>
                    <?php if ($only_link === 'true' && !empty($website_url)) : ?>
                </a>
            <?php else : ?>
                </div>
            <?php endif; ?>
        <?php
        } else {
            // Diseño normal de card para grid estándar
        ?>
            <div class="<?php echo esc_attr($card_class); ?>"
                data-id="<?php echo esc_attr($post_id); ?>">
                <div class="scl-card-logo">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($title); ?>">
                </div>
            </div>
        <?php
        }

        ?>
    <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Formulario de solicitud de nuevo establecimiento
     * [scl_solicitud]
     *
     * @return string
     */
    public static function render_solicitud_form($atts)
    {
        // Solo usuarios registrados
        if (! is_user_logged_in()) {
            return '<div class="scl-message scl-message-warning">' .
                esc_html__('Debes iniciar sesión para solicitar el registro de un establecimiento.', 'simple-cards-listings') .
                ' <a href="' . esc_url(wp_login_url(get_permalink())) . '">' .
                esc_html__('Iniciar sesión', 'simple-cards-listings') . '</a></div>';
        }

        // Obtener categorías
        $categorias = get_terms(array(
            'taxonomy'   => 'categoria_establecimiento',
            'hide_empty' => false,
        ));

        // Obtener tags
        $tags = get_terms(array(
            'taxonomy'   => 'tag_busqueda',
            'hide_empty' => false,
        ));

        // Obtener ubicaciones
        $ubicaciones = get_terms(array(
            'taxonomy'   => 'ubicacion_establecimiento',
            'hide_empty' => false,
        ));

        ob_start();
    ?>
        <div class="scl-form-wrapper">
            <h3><?php esc_html_e('Solicitar registro de establecimiento', 'simple-cards-listings'); ?></h3>

            <form id="scl-solicitud-form" class="scl-form" enctype="multipart/form-data">
                <?php wp_nonce_field('scl_solicitud_nonce', 'scl_solicitud_nonce'); ?>

                <div class="scl-form-row">
                    <label for="scl-nombre"><?php esc_html_e('Nombre del establecimiento *', 'simple-cards-listings'); ?></label>
                    <input type="text" id="scl-nombre" name="nombre" required>
                </div>

                <div class="scl-form-row">
                    <label for="scl-descripcion"><?php esc_html_e('Descripción *', 'simple-cards-listings'); ?></label>
                    <textarea id="scl-descripcion" name="descripcion" rows="4" required></textarea>
                </div>

                <div class="scl-form-row">
                    <label for="scl-categoria"><?php esc_html_e('Categoría *', 'simple-cards-listings'); ?></label>
                    <select id="scl-categoria" name="categoria" required>
                        <option value=""><?php esc_html_e('Seleccionar categoría', 'simple-cards-listings'); ?></option>
                        <?php foreach ($categorias as $cat) : ?>
                            <option value="<?php echo esc_attr($cat->term_id); ?>"><?php echo esc_html($cat->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="scl-form-row">
                    <label><?php esc_html_e('Tags de búsqueda', 'simple-cards-listings'); ?></label>
                    <div class="scl-checkbox-group">
                        <?php foreach ($tags as $tag) : ?>
                            <label class="scl-checkbox-label">
                                <input type="checkbox" name="tags[]" value="<?php echo esc_attr($tag->term_id); ?>">
                                <?php echo esc_html($tag->name); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="scl-form-row">
                    <label><?php esc_html_e('Ubicación', 'simple-cards-listings'); ?></label>
                    <div class="scl-checkbox-group">
                        <?php foreach ($ubicaciones as $ubicacion) : ?>
                            <label class="scl-checkbox-label">
                                <input type="checkbox" name="ubicaciones[]" value="<?php echo esc_attr($ubicacion->term_id); ?>">
                                <?php echo esc_html($ubicacion->name); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="scl-form-row">
                    <label for="scl-logo"><?php esc_html_e('Logo *', 'simple-cards-listings'); ?></label>
                    <input type="file" id="scl-logo" name="logo" accept="image/*" required>
                </div>

                <div class="scl-form-row">
                    <label for="scl-imagen"><?php esc_html_e('Imagen del establecimiento', 'simple-cards-listings'); ?></label>
                    <input type="file" id="scl-imagen" name="imagen_establecimiento" accept="image/*">
                </div>

                <div class="scl-form-row">
                    <label for="scl-menu-pdf"><?php esc_html_e('Archivo PDF (Menú)', 'simple-cards-listings'); ?></label>
                    <input type="file" id="scl-menu-pdf" name="menu_pdf" accept=".pdf">
                </div>

                <div class="scl-form-row">
                    <label for="scl-menu-pdf-name"><?php esc_html_e('Nombre del archivo PDF', 'simple-cards-listings'); ?></label>
                    <input type="text" id="scl-menu-pdf-name" name="menu_pdf_name" placeholder="Menu">
                </div>

                <div class="scl-form-row">
                    <label for="scl-whatsapp"><?php esc_html_e('WhatsApp', 'simple-cards-listings'); ?></label>
                    <input type="text" id="scl-whatsapp" name="whatsapp" placeholder="+593999999999">
                </div>

                <div class="scl-form-row">
                    <label for="scl-instagram"><?php esc_html_e('Instagram', 'simple-cards-listings'); ?></label>
                    <input type="url" id="scl-instagram" name="instagram" placeholder="https://instagram.com/usuario">
                </div>

                <div class="scl-form-row">
                    <label for="scl-tiktok"><?php esc_html_e('TikTok', 'simple-cards-listings'); ?></label>
                    <input type="url" id="scl-tiktok" name="tiktok" placeholder="https://tiktok.com/@usuario">
                </div>

                <div class="scl-form-row">
                    <label for="scl-facebook"><?php esc_html_e('Facebook', 'simple-cards-listings'); ?></label>
                    <input type="url" id="scl-facebook" name="facebook" placeholder="https://facebook.com/pagina">
                </div>

                <div class="scl-form-row">
                    <label for="scl-website"><?php esc_html_e('Sitio Web', 'simple-cards-listings'); ?></label>
                    <input type="url" id="scl-website" name="website" placeholder="https://ejemplo.com">
                </div>

                <div class="scl-form-row">
                    <label for="scl-direccion"><?php esc_html_e('Dirección *', 'simple-cards-listings'); ?></label>
                    <textarea id="scl-direccion" name="direccion" rows="2" required></textarea>
                </div>

                <div class="scl-form-row">
                    <label for="scl-google-maps"><?php esc_html_e('URL de Google Maps', 'simple-cards-listings'); ?></label>
                    <input type="url" id="scl-google-maps" name="google_maps_url" placeholder="https://maps.google.com/...">
                </div>

                <div class="scl-form-row">
                    <label class="scl-checkbox-label">
                        <input type="checkbox" id="scl-terminos" name="terminos" required>
                        <?php esc_html_e('Acepto los términos y condiciones y la política de privacidad *', 'simple-cards-listings'); ?>
                    </label>
                </div>

                <div class="scl-form-row">
                    <button type="submit" class="scl-btn scl-btn-primary">
                        <?php esc_html_e('Enviar solicitud', 'simple-cards-listings'); ?>
                    </button>
                </div>

                <div id="scl-form-message" class="scl-form-message" style="display: none;"></div>
            </form>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Dashboard de usuario
     * [scl_user_dashboard]
     *
     * @return string
     */
    public static function render_user_dashboard($atts)
    {
        // Solo usuarios registrados
        if (! is_user_logged_in()) {
            return '<div class="scl-message scl-message-warning">' .
                esc_html__('Debes iniciar sesión para ver tu panel de establecimientos.', 'simple-cards-listings') .
                ' <a href="' . esc_url(wp_login_url(get_permalink())) . '">' .
                esc_html__('Iniciar sesión', 'simple-cards-listings') . '</a></div>';
        }

        $user_id = get_current_user_id();

        // Obtener establecimientos del usuario
        $args = array(
            'post_type'      => 'establecimiento',
            'post_status'    => array('publish', 'pending', 'draft'),
            'author'         => $user_id,
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        $establecimientos = new WP_Query($args);

        // Obtener promociones del usuario
        $establecimiento_ids = array();
        if ($establecimientos->have_posts()) {
            while ($establecimientos->have_posts()) {
                $establecimientos->the_post();
                $establecimiento_ids[] = get_the_ID();
            }
            wp_reset_postdata();
        }

        $promociones_args = array(
            'post_type'      => 'promocion',
            'post_status'    => array('publish', 'pending', 'draft'),
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        if (!empty($establecimiento_ids)) {
            $promociones_args['meta_query'] = array(
                array(
                    'key'     => '_scl_establecimiento_id',
                    'value'   => $establecimiento_ids,
                    'compare' => 'IN',
                ),
            );
        } else {
            $promociones_args['post__in'] = array(0); // No resultados si no tiene establecimientos
        }

        $promociones = new WP_Query($promociones_args);

        ob_start();
    ?>
        <div class="scl-dashboard-wrapper">
            <!-- Pestañas -->
            <div class="scl-dashboard-tabs">
                <button type="button" class="scl-tab-btn active" data-tab="establecimientos">
                    <?php esc_html_e('Mis Establecimientos', 'simple-cards-listings'); ?>
                </button>
                <button type="button" class="scl-tab-btn" data-tab="promociones">
                    <?php esc_html_e('Mis Promociones', 'simple-cards-listings'); ?>
                    <span class="scl-tab-count"><?php echo $promociones->found_posts; ?></span>
                </button>
            </div>

            <!-- Tab de Establecimientos -->
            <div class="scl-tab-content active" data-tab="establecimientos">
                <div class="scl-dashboard-header">
                    <h2><?php esc_html_e('Mis Establecimientos', 'simple-cards-listings'); ?></h2>
                    <button type="button" class="scl-btn scl-btn-primary scl-btn-nuevo">
                        <?php esc_html_e('Solicitar nuevo establecimiento', 'simple-cards-listings'); ?>
                    </button>
                </div>

                <?php if ($establecimientos->have_posts()) : ?>
                    <?php $establecimientos->rewind_posts(); ?>
                    <div class="scl-dashboard-table-wrapper">
                        <table class="scl-dashboard-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Logo', 'simple-cards-listings'); ?></th>
                                    <th><?php esc_html_e('Nombre', 'simple-cards-listings'); ?></th>
                                    <th><?php esc_html_e('Estado', 'simple-cards-listings'); ?></th>
                                    <th><?php esc_html_e('Acciones', 'simple-cards-listings'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($establecimientos->have_posts()) : $establecimientos->the_post(); ?>
                                    <?php
                                    $post_id = get_the_ID();
                                    $status = get_post_status();
                                    $logo_id = SCL_Metaboxes::get_meta($post_id, 'logo');
                                    $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'thumbnail') : '';
                                    ?>
                                    <tr>
                                        <td class="scl-td-logo">
                                            <?php if ($logo_url) : ?>
                                                <img src="<?php echo esc_url($logo_url); ?>" alt="" width="50" height="50">
                                            <?php else : ?>
                                                <span class="scl-no-logo">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php the_title(); ?></td>
                                        <td>
                                            <?php echo self::get_status_badge($status); ?>
                                        </td>
                                        <td class="scl-td-actions">
                                            <button type="button" class="scl-btn scl-btn-small scl-btn-edit" data-id="<?php echo esc_attr($post_id); ?>">
                                                <?php esc_html_e('Editar', 'simple-cards-listings'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                <?php wp_reset_postdata(); ?>
                            </tbody>
                        </table>
                    </div>
                <?php else : ?>
                    <div class="scl-message scl-message-info">
                        <?php esc_html_e('No tienes establecimientos registrados aún.', 'simple-cards-listings'); ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab de Promociones -->
            <div class="scl-tab-content" data-tab="promociones">
                <div class="scl-dashboard-header">
                    <h2><?php esc_html_e('Mis Promociones', 'simple-cards-listings'); ?></h2>
                    <?php if (!empty($establecimiento_ids)) : ?>
                        <button type="button" class="scl-btn scl-btn-primary" id="scl-btn-nueva-promocion">
                            <?php esc_html_e('Crear Promoción', 'simple-cards-listings'); ?>
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (!empty($establecimiento_ids)) : ?>
                    <?php if ($promociones->have_posts()) : ?>
                        <div class="scl-dashboard-table-wrapper">
                            <table class="scl-dashboard-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Imagen', 'simple-cards-listings'); ?></th>
                                        <th><?php esc_html_e('Título', 'simple-cards-listings'); ?></th>
                                        <th><?php esc_html_e('Establecimiento', 'simple-cards-listings'); ?></th>
                                        <th><?php esc_html_e('Válida hasta', 'simple-cards-listings'); ?></th>
                                        <th><?php esc_html_e('Estado', 'simple-cards-listings'); ?></th>
                                        <th><?php esc_html_e('Acciones', 'simple-cards-listings'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($promociones->have_posts()) : $promociones->the_post(); ?>
                                        <?php
                                        $promo_id = get_the_ID();
                                        $promo_status = get_post_status();
                                        $imagen_url = get_the_post_thumbnail_url($promo_id, 'thumbnail');
                                        $fecha_fin = get_post_meta($promo_id, '_scl_fecha_fin', true);
                                        $establecimiento_id = get_post_meta($promo_id, '_scl_establecimiento_id', true);
                                        $establecimiento_titulo = get_the_title($establecimiento_id);

                                        // Determinar estado de la promoción
                                        $ahora = current_time('timestamp');
                                        $fin_ts = $fecha_fin ? strtotime($fecha_fin) : 0;
                                        $estado_promo = 'activo';

                                        if ($promo_status !== 'publish') {
                                            $estado_promo = 'pendiente';
                                        } elseif ($fin_ts > 0 && $fin_ts < $ahora) {
                                            $estado_promo = 'expirado';
                                        }
                                        ?>
                                        <tr>
                                            <td class="scl-td-logo">
                                                <?php if ($imagen_url) : ?>
                                                    <img src="<?php echo esc_url($imagen_url); ?>" alt="" width="50" height="50">
                                                <?php else : ?>
                                                    <span class="scl-no-logo">📄</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php the_title(); ?></td>
                                            <td><?php echo esc_html($establecimiento_titulo); ?></td>
                                            <td>
                                                <?php
                                                if ($fecha_fin) {
                                                    echo date_i18n(get_option('date_format'), strtotime($fecha_fin));
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $badges = array(
                                                    'activo' => array('label' => 'Activa', 'class' => 'scl-badge-success'),
                                                    'expirado' => array('label' => 'Expirada', 'class' => 'scl-badge-danger'),
                                                    'pendiente' => array('label' => 'Pendiente', 'class' => 'scl-badge-warning'),
                                                );
                                                $info = $badges[$estado_promo];
                                                echo sprintf(
                                                    '<span class="scl-badge %s">%s</span>',
                                                    esc_attr($info['class']),
                                                    esc_html($info['label'])
                                                );
                                                ?>
                                            </td>
                                            <td class="scl-td-actions">
                                                <button type="button" class="scl-btn scl-btn-small scl-btn-editar-promocion" data-id="<?php echo esc_attr($promo_id); ?>">
                                                    <?php esc_html_e('Editar', 'simple-cards-listings'); ?>
                                                </button>
                                                <?php if ($promo_status === 'publish') : ?>
                                                    <button type="button" class="scl-btn scl-btn-small scl-ver-cupon" data-id="<?php echo esc_attr($promo_id); ?>">
                                                        <?php esc_html_e('Ver', 'simple-cards-listings'); ?>
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" class="scl-btn scl-btn-small scl-btn-danger scl-btn-eliminar-promocion" data-id="<?php echo esc_attr($promo_id); ?>">
                                                    <?php esc_html_e('Eliminar', 'simple-cards-listings'); ?>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php wp_reset_postdata(); ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <div class="scl-message scl-message-info">
                            <?php esc_html_e('No tienes promociones creadas aún.', 'simple-cards-listings'); ?>
                            <button type="button" id="scl-btn-nueva-promocion-inline" class="scl-btn scl-btn-primary" style="margin-left: 10px;">
                                <?php esc_html_e('Crear tu primera promoción', 'simple-cards-listings'); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <div class="scl-message scl-message-warning">
                        <?php esc_html_e('Debes tener al menos un establecimiento registrado para crear promociones.', 'simple-cards-listings'); ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Modal de solicitud de nuevo establecimiento -->
            <div id="scl-solicitud-modal" class="scl-modal" style="display: none;">
                <div class="scl-modal-overlay"></div>
                <div class="scl-modal-content scl-modal-large">
                    <button type="button" class="scl-modal-close" aria-label="<?php esc_attr_e('Cerrar', 'simple-cards-listings'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18"></path>
                            <path d="m6 6 12 12"></path>
                        </svg>
                    </button>
                    <div class="scl-modal-body">
                        <?php echo self::render_solicitud_form(array()); ?>
                    </div>
                </div>
            </div>

            <!-- Modal de edición -->
            <div id="scl-edit-modal" class="scl-modal" style="display: none;">
                <div class="scl-modal-overlay"></div>
                <div class="scl-modal-content scl-modal-large">
                    <button type="button" class="scl-modal-close" aria-label="<?php esc_attr_e('Cerrar', 'simple-cards-listings'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18"></path>
                            <path d="m6 6 12 12"></path>
                        </svg>
                    </button>
                    <div class="scl-modal-body" id="scl-edit-modal-body">
                        <!-- Formulario de edición cargado via AJAX -->
                    </div>
                </div>
            </div>

            <!-- Modal de Promoción (Crear/Editar) -->
            <div id="scl-promocion-modal" class="scl-modal" style="display: none;">
                <div class="scl-modal-overlay"></div>
                <div class="scl-modal-content scl-modal-large">
                    <button type="button" class="scl-modal-close" aria-label="<?php esc_attr_e('Cerrar', 'simple-cards-listings'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18"></path>
                            <path d="m6 6 12 12"></path>
                        </svg>
                    </button>
                    <div class="scl-modal-body">
                        <h3 id="scl-promocion-modal-titulo"><?php esc_html_e('Nueva Promoción', 'simple-cards-listings'); ?></h3>
                        <form id="scl-promocion-form" enctype="multipart/form-data">
                            <input type="hidden" id="scl-promocion-id" name="cupon_id" value="">

                            <div class="scl-form-row">
                                <label for="scl-promo-titulo"><?php esc_html_e('Título de la Promoción *', 'simple-cards-listings'); ?></label>
                                <input type="text" id="scl-promo-titulo" name="titulo" required placeholder="<?php esc_attr_e('Ej: 2×1 en hamburguesas', 'simple-cards-listings'); ?>">
                            </div>

                            <div class="scl-form-row">
                                <label for="scl-promo-descripcion"><?php esc_html_e('Descripción *', 'simple-cards-listings'); ?></label>
                                <textarea id="scl-promo-descripcion" name="descripcion" rows="5" required placeholder="<?php esc_attr_e('Describe los detalles de la promoción...', 'simple-cards-listings'); ?>"></textarea>
                            </div>

                            <div class="scl-form-row">
                                <label for="scl-promo-establecimiento"><?php esc_html_e('Establecimiento *', 'simple-cards-listings'); ?></label>
                                <select id="scl-promo-establecimiento" name="establecimiento_id" required>
                                    <option value=""><?php esc_html_e('Seleccionar establecimiento', 'simple-cards-listings'); ?></option>
                                    <?php
                                    $user_establecimientos = get_posts(array(
                                        'post_type' => 'establecimiento',
                                        'author' => $user_id,
                                        'post_status' => 'publish',
                                        'posts_per_page' => -1,
                                        'orderby' => 'title',
                                        'order' => 'ASC',
                                    ));
                                    foreach ($user_establecimientos as $est) :
                                    ?>
                                        <option value="<?php echo esc_attr($est->ID); ?>"><?php echo esc_html($est->post_title); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="scl-form-row">
                                <label for="scl-promo-imagen"><?php esc_html_e('Imagen de la Promoción', 'simple-cards-listings'); ?></label>
                                <input type="file" id="scl-promo-imagen" name="imagen" accept="image/*">
                                <div id="scl-promo-imagen-preview" style="margin-top: 10px; display: none;">
                                    <img src="" alt="" style="max-width: 200px; height: auto; border-radius: 8px;">
                                </div>
                            </div>

                            <div class="scl-form-row scl-form-row-inline">
                                <div class="scl-form-col">
                                    <label for="scl-promo-fecha-inicio"><?php esc_html_e('Fecha de Inicio *', 'simple-cards-listings'); ?></label>
                                    <input type="datetime-local" id="scl-promo-fecha-inicio" name="fecha_inicio" required>
                                </div>
                                <div class="scl-form-col">
                                    <label for="scl-promo-fecha-fin"><?php esc_html_e('Fecha de Fin *', 'simple-cards-listings'); ?></label>
                                    <input type="datetime-local" id="scl-promo-fecha-fin" name="fecha_fin" required>
                                </div>
                            </div>

                            <div class="scl-form-row">
                                <button type="submit" class="scl-btn scl-btn-primary" id="scl-promo-submit">
                                    <?php esc_html_e('Guardar Promoción', 'simple-cards-listings'); ?>
                                </button>
                                <button type="button" class="scl-btn scl-btn-secondary scl-modal-close">
                                    <?php esc_html_e('Cancelar', 'simple-cards-listings'); ?>
                                </button>
                            </div>

                            <div id="scl-promo-form-message" class="scl-form-message" style="display: none;"></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Obtener badge de estado
     *
     * @param string $status Estado del post.
     * @return string
     */
    public static function get_status_badge($status)
    {
        $labels = array(
            'publish' => array(
                'label' => __('Aprobado', 'simple-cards-listings'),
                'class' => 'scl-badge-success',
            ),
            'pending' => array(
                'label' => __('Pendiente', 'simple-cards-listings'),
                'class' => 'scl-badge-warning',
            ),
            'draft' => array(
                'label' => __('Borrador', 'simple-cards-listings'),
                'class' => 'scl-badge-info',
            ),
            'trash' => array(
                'label' => __('Eliminado', 'simple-cards-listings'),
                'class' => 'scl-badge-danger',
            ),
        );

        $info = isset($labels[$status]) ? $labels[$status] : array(
            'label' => $status,
            'class' => 'scl-badge-default',
        );

        return sprintf(
            '<span class="scl-badge %s">%s</span>',
            esc_attr($info['class']),
            esc_html($info['label'])
        );
    }

    /**
     * Shortcode: Grid de promociones
     * [scl_cupones columns="3" per_page="12" search_placeholder="" level="{socio_gold, #35ff8f, 0}"]
     *
     * @param array $atts Atributos del shortcode.
     * @return string
     */
    public static function render_cupones_grid($atts)
    {

        $atts = shortcode_atts(array(
            'columns'            => 3,
            'per_page'           => 12,
            'search_placeholder' => '',
            'level'              => '', // Niveles: "{role, color, priority};..."
            'categoria'          => '', // Slug de la categoría padre
        ), $atts, 'scl_cupones');

        $posts_per_page = intval($atts['per_page']);
        if ($posts_per_page <= 0) {
            $posts_per_page = 12;
        }

        // Si se pasa categoria, filtrar solo por esa categoría padre
        $tax_query = array();
        $categoria_padre = null;
        if (!empty($atts['categoria'])) {
            $categoria_padre = get_term_by('slug', $atts['categoria'], 'categoria_promocion');
            if ($categoria_padre) {
                $tax_query[] = array(
                    'taxonomy' => 'categoria_promocion',
                    'field'    => 'term_id',
                    'terms'    => $categoria_padre->term_id,
                    'include_children' => true,
                );
            }
        }

        $args = array(
            'post_type'      => 'promocion',
            'post_status'    => 'publish',
            'posts_per_page' => $posts_per_page,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        // Obtener categorías hijas para el dropdown si hay categoria padre, si no, mostrar top-level
        if ($categoria_padre) {
            $categorias_dropdown = get_terms(array(
                'taxonomy'   => 'categoria_promocion',
                'hide_empty' => true,
                'parent'     => $categoria_padre->term_id,
            ));
        } else {
            $categorias_dropdown = get_terms(array(
                'taxonomy'   => 'categoria_promocion',
                'hide_empty' => true,
                'parent'     => 0,
            ));
        }

        // Parsear niveles si se proporcionaron
        $levels = self::parse_levels($atts['level']);
        // Si no se pasó level, no aplicar colores (array vacío)
        $has_levels = !empty($atts['level']);

        $cupones = new WP_Query($args);

        ob_start();
    ?>
        <div class="scl-cupones-container"
            <?php if ($has_levels) : ?>
            data-levels="<?php echo esc_attr(json_encode($levels)); ?>"
            <?php endif; ?>
            <?php if (!empty($atts['categoria'])) : ?>
            data-categoria-base="<?php echo esc_attr($atts['categoria']); ?>"
            <?php endif; ?>>
            <!-- Buscador -->
            <div class="scl-search-wrapper">
                <div class="scl-search-box">
                    <input
                        type="text"
                        id="scl-cupones-search"
                        class="scl-search-input"
                        placeholder="<?php echo esc_attr(!empty($atts['search_placeholder']) ? $atts['search_placeholder'] : __('Buscar promociones...', 'simple-cards-listings')); ?>"
                        autocomplete="off">
                    <!-- Dropdown de categorías (solo si hay categorías) -->
                    <?php if (!empty($categorias_dropdown) && !is_wp_error($categorias_dropdown)) : ?>
                        <select id="scl-cupones-category-filter" class="scl-category-filter">
                            <option value=""><?php esc_html_e('Todas las categorías', 'simple-cards-listings'); ?></option>
                            <?php foreach ($categorias_dropdown as $cat) : ?>
                                <option value="<?php echo esc_attr($cat->slug); ?>">
                                    <?php echo esc_html($cat->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                    <button type="button" class="scl-search-button" aria-label="<?php esc_attr_e('Buscar', 'simple-cards-listings'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Grid de cupones -->
            <div class="scl-cupones-grid scl-grid-<?php echo esc_attr($atts['columns']); ?>" id="scl-cupones-grid">
                <?php if ($cupones->have_posts()) : ?>
                    <?php while ($cupones->have_posts()) : $cupones->the_post(); ?>
                        <?php echo self::render_cupon_card(get_the_ID(), $has_levels ? $levels : array()); ?>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else : ?>
                    <p class="scl-no-results"><?php esc_html_e('No hay promociones activas en este momento.', 'simple-cards-listings'); ?></p>
                <?php endif; ?>
            </div>

            <!-- Mensaje de no resultados -->
            <div id="scl-cupones-no-results" class="scl-no-results" style="display: none;">
                <?php esc_html_e('No se encontraron promociones.', 'simple-cards-listings'); ?>
            </div>
        </div>

        <!-- Modal de cupón -->
        <div id="scl-cupon-modal" class="scl-modal" style="display: none;">
            <div class="scl-modal-overlay"></div>
            <div class="scl-modal-content scl-cupon-modal-content">
                <button type="button" class="scl-modal-close" aria-label="<?php esc_attr_e('Cerrar', 'simple-cards-listings'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
                <div class="scl-modal-body" id="scl-cupon-modal-body">
                    <!-- Contenido cargado via AJAX -->
                </div>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderizar card de cupón
     *
     * @param int $post_id ID del cupón.
     * @param array $levels Array de niveles con roles, colores y prioridades.
     * @return string
     */
    public static function render_cupon_card($post_id, $levels = array())
    {
        $imagen_url = get_the_post_thumbnail_url($post_id, 'medium');
        if (!$imagen_url) {
            $imagen_url = SCL_PLUGIN_URL . 'assets/images/cupon-placeholder.png';
        }

        $establecimiento_id = get_post_meta($post_id, '_scl_establecimiento_id', true);
        $establecimiento_nombre = '';
        $border_color = '';

        if ($establecimiento_id) {
            $est = get_post($establecimiento_id);
            if ($est) {
                $establecimiento_nombre = $est->post_title;

                // Determinar color del borde según rol del autor del establecimiento
                if (!empty($levels)) {
                    $author_id = $est->post_author;
                    $user = get_userdata($author_id);

                    if ($user) {
                        foreach ($levels as $level) {
                            if (empty($level['role'])) continue;
                            if (in_array($level['role'], (array) $user->roles)) {
                                $border_color = $level['color'];
                                break;
                            }
                        }
                    }
                }
            }
        }

        $fecha_fin = get_post_meta($post_id, '_scl_fecha_fin', true);

        $dias_restantes = '';
        if ($fecha_fin) {
            $fin_timestamp = strtotime($fecha_fin);
            $ahora = current_time('timestamp');
            $diff = $fin_timestamp - $ahora;
            $dias = floor($diff / DAY_IN_SECONDS);

            if ($dias == 0) {
                $dias_restantes = __('Expira hoy', 'simple-cards-listings');
            } elseif ($dias == 1) {
                $dias_restantes = __('Expira mañana', 'simple-cards-listings');
            } elseif ($dias > 1) {
                $dias_restantes = sprintf(__('Expira en %d días', 'simple-cards-listings'), $dias);
            }
        }

        ob_start();
    ?>
        <div class="scl-cupon-card" data-id="<?php echo esc_attr($post_id); ?>"
            <?php if (!empty($border_color)) : ?>
            style="border-color: <?php echo esc_attr($border_color); ?>; border-width: 2px; border-style: solid; box-shadow: 0 0 12px <?php echo esc_attr($border_color); ?>4D;"
            data-level-color="<?php echo esc_attr($border_color); ?>"
            <?php endif; ?>>

            <div class="scl-cupon-imagen">
                <img src="<?php echo esc_url($imagen_url); ?>" alt="<?php echo esc_attr(get_the_title($post_id)); ?>">
            </div>

            <div class="scl-cupon-info">
                <?php if ($establecimiento_nombre) : ?>
                    <div class="scl-cupon-establecimiento"><?php echo esc_html($establecimiento_nombre); ?></div>
                <?php endif; ?>

                <h3 class="scl-cupon-titulo"><?php echo esc_html(get_the_title($post_id)); ?></h3>

                <?php if ($dias_restantes) : ?>
                    <div class="scl-cupon-expira"><?php echo esc_html($dias_restantes); ?></div>
                <?php endif; ?>

                <button type="button" class="scl-btn scl-btn-primary scl-ver-cupon">
                    <?php esc_html_e('Ver cupón', 'simple-cards-listings'); ?>
                </button>
            </div>
        </div>
<?php
        return ob_get_clean();
    }
}
