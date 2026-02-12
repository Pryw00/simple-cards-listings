<?php

/**
 * Manejadores AJAX
 *
 * @package SimpleCardsListings
 * @since 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar las peticiones AJAX
 */
class SCL_Ajax_Handlers
{

    /**
     * Inicializar handlers
     */
    public static function init()
    {
        // Modal de establecimiento
        add_action('wp_ajax_scl_get_establecimiento', array(__CLASS__, 'get_establecimiento'));
        add_action('wp_ajax_nopriv_scl_get_establecimiento', array(__CLASS__, 'get_establecimiento'));

        // Búsqueda en tiempo real
        add_action('wp_ajax_scl_search', array(__CLASS__, 'search_establecimientos'));
        add_action('wp_ajax_nopriv_scl_search', array(__CLASS__, 'search_establecimientos'));

        // Paginación - Cargar más establecimientos
        add_action('wp_ajax_scl_load_more', array(__CLASS__, 'load_more_establecimientos'));
        add_action('wp_ajax_nopriv_scl_load_more', array(__CLASS__, 'load_more_establecimientos'));

        // Formulario de solicitud
        add_action('wp_ajax_scl_submit_solicitud', array(__CLASS__, 'submit_solicitud'));

        // Obtener formulario de edición
        add_action('wp_ajax_scl_get_edit_form', array(__CLASS__, 'get_edit_form'));

        // Actualizar establecimiento
        add_action('wp_ajax_scl_update_establecimiento', array(__CLASS__, 'update_establecimiento'));

        // CUPONES: Modal de cupón
        add_action('wp_ajax_scl_get_cupon', array(__CLASS__, 'get_cupon'));
        add_action('wp_ajax_nopriv_scl_get_cupon', array(__CLASS__, 'get_cupon'));

        // CUPONES: Búsqueda
        add_action('wp_ajax_scl_search_cupones', array(__CLASS__, 'search_cupones'));
        add_action('wp_ajax_nopriv_scl_search_cupones', array(__CLASS__, 'search_cupones'));

        // CUPONES: Crear/editar desde frontend
        add_action('wp_ajax_scl_submit_cupon', array(__CLASS__, 'submit_cupon'));
        add_action('wp_ajax_scl_delete_cupon', array(__CLASS__, 'delete_cupon'));
        add_action('wp_ajax_scl_get_promocion_meta', array(__CLASS__, 'get_promocion_meta'));
    }

    /**
     * Obtener meta datos de una promoción (fechas)
     */
    public static function get_promocion_meta()
    {
        check_ajax_referer('scl_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Debes iniciar sesión.', 'simple-cards-listings')));
        }

        $promocion_id = isset($_POST['promocion_id']) ? intval($_POST['promocion_id']) : 0;

        if (!$promocion_id || get_post_type($promocion_id) !== 'promocion') {
            wp_send_json_error(array('message' => __('Promoción no encontrada.', 'simple-cards-listings')));
        }

        // Verificar permisos
        if (!current_user_can('edit_post', $promocion_id)) {
            wp_send_json_error(array('message' => __('No tienes permisos.', 'simple-cards-listings')));
        }

        $data = array(
            'fecha_inicio' => get_post_meta($promocion_id, '_scl_fecha_inicio', true),
            'fecha_fin' => get_post_meta($promocion_id, '_scl_fecha_fin', true),
            'establecimiento_id' => get_post_meta($promocion_id, '_scl_establecimiento_id', true),
            'destacado' => get_post_meta($promocion_id, '_scl_destacado', true),
        );

        wp_send_json_success($data);
    }

    /**
     * Obtener datos de un establecimiento para el modal
     */
    public static function get_establecimiento()
    {
        check_ajax_referer('scl_nonce', 'nonce');

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;

        if (! $post_id) {
            wp_send_json_error(array('message' => __('ID de establecimiento no válido.', 'simple-cards-listings')));
        }

        $post = get_post($post_id);

        if (! $post || 'establecimiento' !== $post->post_type || 'publish' !== $post->post_status) {
            wp_send_json_error(array('message' => __('Establecimiento no encontrado.', 'simple-cards-listings')));
        }

        // Obtener datos
        $logo_id = SCL_Metaboxes::get_meta($post_id, 'logo');
        $imagen_id = SCL_Metaboxes::get_meta($post_id, 'imagen_establecimiento');
        $menu_pdf_id = SCL_Metaboxes::get_meta($post_id, 'menu_pdf');

        $data = array(
            'id'           => $post_id,
            'title'        => $post->post_title,
            'description'  => apply_filters('the_content', $post->post_content),
            'excerpt'      => $post->post_excerpt,
            'logo_url'     => $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : '',
            'imagen_url'   => $imagen_id ? wp_get_attachment_image_url($imagen_id, 'large') : '',
            'menu_pdf_url' => $menu_pdf_id ? wp_get_attachment_url($menu_pdf_id) : '',
            'menu_pdf_name' => SCL_Metaboxes::get_meta($post_id, 'menu_pdf_name') ?: __('Menu', 'simple-cards-listings'),
            'whatsapp'     => SCL_Metaboxes::get_meta($post_id, 'whatsapp'),
            'instagram'    => SCL_Metaboxes::get_meta($post_id, 'instagram'),
            'tiktok'       => SCL_Metaboxes::get_meta($post_id, 'tiktok'),
            'facebook'     => SCL_Metaboxes::get_meta($post_id, 'facebook'),
            'website'      => SCL_Metaboxes::get_meta($post_id, 'website'),
            'direccion'    => SCL_Metaboxes::get_meta($post_id, 'direccion'),
            'google_maps'  => SCL_Metaboxes::get_meta($post_id, 'google_maps_url'),
        );

        // Renderizar HTML del modal
        $html = self::render_modal_content($data);

        wp_send_json_success(array('html' => $html));
    }

    /**
     * Renderizar contenido del modal
     *
     * @param array $data Datos del establecimiento.
     * @return string
     */
    private static function render_modal_content($data)
    {
        ob_start();
?>
        <div class="scl-modal-establecimiento">
            <div class="scl-modal-left">
                <h2 class="scl-modal-title"><?php echo esc_html($data['title']); ?></h2>

                <?php if (! empty($data['description'])) : ?>
                    <div class="scl-modal-description">
                        <?php echo wp_kses_post($data['description']); ?>
                    </div>
                <?php endif; ?>



                <!-- Botones de acción -->
                <div class="scl-modal-actions">
                    <?php if (! empty($data['whatsapp'])) :
                        $whatsapp_number = preg_replace('/[^0-9]/', '', $data['whatsapp']);
                    ?>
                        <a href="https://wa.me/<?php echo esc_attr($whatsapp_number); ?>" target="_blank" rel="noopener" class="scl-action-btn scl-btn-whatsapp">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                            </svg>
                            <?php esc_html_e('WhatsApp', 'simple-cards-listings'); ?>
                        </a>
                    <?php endif; ?>

                    <?php if (! empty($data['google_maps'])) : ?>
                        <a href="<?php echo esc_url($data['google_maps']); ?>" target="_blank" rel="noopener" class="scl-action-btn scl-btn-location">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <?php esc_html_e('Ubicación', 'simple-cards-listings'); ?>
                        </a>
                    <?php endif; ?>

                    <?php if (! empty($data['website'])) : ?>
                        <a href="<?php echo esc_url($data['website']); ?>" target="_blank" rel="noopener" class="scl-action-btn scl-btn-menu">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="2" y1="12" x2="22" y2="12" />
                                <path d="M12 2a15.3 15.3 0 0 1 0 20" />
                                <path d="M12 2a15.3 15.3 0 0 0 0 20" />
                            </svg>
                            <?php echo esc_html('Website'); ?>
                        </a>
                    <?php endif; ?>

                    <?php if (! empty($data['menu_pdf_url'])) : ?>
                        <a href="<?php echo esc_url($data['menu_pdf_url']); ?>" target="_blank" rel="noopener" class="scl-action-btn scl-btn-menu">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                            </svg>
                            <?php echo esc_html($data['menu_pdf_name']); ?>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Redes sociales -->
                <div class="scl-modal-social">
                    <?php if (! empty($data['tiktok'])) : ?>
                        <a href="<?php echo esc_url($data['tiktok']); ?>" target="_blank" rel="noopener" class="scl-social-icon" title="TikTok">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z" />
                            </svg>
                        </a>
                    <?php endif; ?>

                    <?php if (! empty($data['facebook'])) : ?>
                        <a href="<?php echo esc_url($data['facebook']); ?>" target="_blank" rel="noopener" class="scl-social-icon" title="Facebook">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" />
                            </svg>
                        </a>
                    <?php endif; ?>

                    <?php if (! empty($data['instagram'])) : ?>
                        <a href="<?php echo esc_url($data['instagram']); ?>" target="_blank" rel="noopener" class="scl-social-icon" title="Instagram">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>

            </div>

            <div class="scl-modal-right">
                <?php if (! empty($data['imagen_url'])) : ?>
                    <div class="scl-modal-image">
                        <img src="<?php echo esc_url($data['imagen_url']); ?>" alt="<?php echo esc_attr($data['title']); ?>">
                    </div>

                <?php elseif (! empty($data['logo_url'])) : ?>
                    <div class="scl-modal-image">
                        <img src="<?php echo esc_url($data['logo_url']); ?>" alt="<?php echo esc_attr($data['title']); ?>">
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Búsqueda de establecimientos
     */
    /**
     * Buscar establecimientos (AJAX - busca en TODA la base de datos)
     * Busca en: título, descripción, categorías y tags
     */
    public static function search_establecimientos()
    {
        check_ajax_referer('scl_nonce', 'nonce');

        $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
        $categoria_filter = isset($_POST['categoria_filter']) ? sanitize_text_field($_POST['categoria_filter']) : '';
        $category_selected = isset($_POST['category_selected']) ? sanitize_text_field($_POST['category_selected']) : '';
        $ubicacion_selected = isset($_POST['ubicacion_selected']) ? sanitize_text_field($_POST['ubicacion_selected']) : '';
        $is_gold = isset($_POST['is_gold']) ? (bool) $_POST['is_gold'] : false;
        $only_link = isset($_POST['only_link']) ? sanitize_text_field($_POST['only_link']) : 'false';

        // Parsear niveles
        $levels = array();
        if (isset($_POST['levels']) && !empty($_POST['levels'])) {
            $levels_json = stripslashes($_POST['levels']);
            $levels = json_decode($levels_json, true);
            if (!is_array($levels)) {
                $levels = array();
            }
        }

        // Si hay búsqueda de texto, buscar en todos los campos
        if (!empty($search_term)) {
            // Buscar IDs de posts que coincidan en título o contenido
            $args_text = array(
                'post_type'      => 'establecimiento',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                's'              => $search_term,
                'fields'         => 'ids',
            );
            $text_query = new WP_Query($args_text);
            $post_ids_text = $text_query->posts;

            // Buscar términos de categoría que coincidan
            $matching_cats = get_terms(array(
                'taxonomy'   => 'categoria_establecimiento',
                'search'     => $search_term,
                'fields'     => 'ids',
                'hide_empty' => true,
            ));

            // Buscar términos de tags que coincidan
            $matching_tags = get_terms(array(
                'taxonomy'   => 'tag_busqueda',
                'search'     => $search_term,
                'fields'     => 'ids',
                'hide_empty' => true,
            ));

            // Buscar posts que tengan esas categorías o tags
            $post_ids_tax = array();
            if (!empty($matching_cats) || !empty($matching_tags)) {
                $tax_query_search = array('relation' => 'OR');

                if (!empty($matching_cats)) {
                    $tax_query_search[] = array(
                        'taxonomy' => 'categoria_establecimiento',
                        'field'    => 'term_id',
                        'terms'    => $matching_cats,
                    );
                }

                if (!empty($matching_tags)) {
                    $tax_query_search[] = array(
                        'taxonomy' => 'tag_busqueda',
                        'field'    => 'term_id',
                        'terms'    => $matching_tags,
                    );
                }

                $args_tax = array(
                    'post_type'      => 'establecimiento',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'tax_query'      => $tax_query_search,
                    'fields'         => 'ids',
                );
                $tax_query_obj = new WP_Query($args_tax);
                $post_ids_tax = $tax_query_obj->posts;
            }

            // Combinar todos los IDs encontrados (sin duplicados)
            $found_post_ids = array_unique(array_merge($post_ids_text, $post_ids_tax));

            // Si se encontraron posts, hacer la consulta final
            if (!empty($found_post_ids)) {
                $args = array(
                    'post_type'      => 'establecimiento',
                    'post_status'    => 'publish',
                    'post__in'       => $found_post_ids,
                    'posts_per_page' => -1,
                    'orderby'        => 'title',
                    'order'          => 'ASC',
                );

                // Aplicar filtros adicionales de categoría si existen
                $tax_query = array('relation' => 'AND');

                if (!empty($categoria_filter)) {
                    $tax_query[] = array(
                        'taxonomy' => 'categoria_establecimiento',
                        'field'    => 'slug',
                        'terms'    => $categoria_filter,
                    );
                }

                if (!empty($category_selected)) {
                    $tax_query[] = array(
                        'taxonomy' => 'categoria_establecimiento',
                        'field'    => 'slug',
                        'terms'    => $category_selected,
                    );
                }

                if (count($tax_query) > 1) {
                    $args['tax_query'] = $tax_query;
                }
            } else {
                // No se encontró nada
                wp_send_json_success(array(
                    'html' => '',
                    'count' => 0,
                ));
                return;
            }
        } else {
            // Sin término de búsqueda, solo aplicar filtros de categoría
            $args = array(
                'post_type'      => 'establecimiento',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
            );

            $tax_query = array('relation' => 'AND');

            if (!empty($categoria_filter)) {
                $tax_query[] = array(
                    'taxonomy' => 'categoria_establecimiento',
                    'field'    => 'slug',
                    'terms'    => $categoria_filter,
                );
            }

            if (!empty($category_selected)) {
                $tax_query[] = array(
                    'taxonomy' => 'categoria_establecimiento',
                    'field'    => 'slug',
                    'terms'    => $category_selected,
                );
            }

            if (!empty($ubicacion_selected)) {
                $tax_query[] = array(
                    'taxonomy' => 'ubicacion_establecimiento',
                    'field'    => 'slug',
                    'terms'    => $ubicacion_selected,
                );
            }

            if (count($tax_query) > 1) {
                $args['tax_query'] = $tax_query;
            }
        }

        $query = new WP_Query($args);
        $html = '';

        if ($query->have_posts()) {
            // Si es grid gold, ordenar por nivel del usuario
            if ($is_gold && !empty($levels)) {
                // Agrupar posts por nivel
                $posts_by_level = array();
                foreach ($levels as $level_index => $level) {
                    $posts_by_level[$level_index] = array();
                }

                while ($query->have_posts()) {
                    $query->the_post();
                    $post_id = get_the_ID();
                    $author_id = get_post_field('post_author', $post_id);
                    $user = get_userdata($author_id);

                    // Determinar nivel
                    $assigned = false;
                    foreach ($levels as $level_index => $level) {
                        if (empty($level['role'])) continue;

                        if ($user && in_array($level['role'], (array) $user->roles)) {
                            $posts_by_level[$level_index][] = $post_id;
                            $assigned = true;
                            break;
                        }
                    }

                    if (!$assigned) {
                        $last_level_index = count($levels) - 1;
                        $posts_by_level[$last_level_index][] = $post_id;
                    }
                }
                wp_reset_postdata();

                // Generar HTML ordenado por prioridad
                foreach ($posts_by_level as $level_posts) {
                    foreach ($level_posts as $post_id) {
                        $html .= SCL_Shortcodes::render_card_item($post_id, true, '', '', '', $levels, $only_link);
                    }
                }
            } else {
                // Grid normal
                while ($query->have_posts()) {
                    $query->the_post();
                    $html .= SCL_Shortcodes::render_card_item(get_the_ID(), false, '', '', '', array(), $only_link);
                }
                wp_reset_postdata();
            }
        }

        wp_send_json_success(array(
            'html' => $html,
            'count' => $query->found_posts,
        ));
    }

    /**
     * Procesar solicitud de nuevo establecimiento
     */
    public static function submit_solicitud()
    {
        check_ajax_referer('scl_solicitud_nonce', 'scl_solicitud_nonce');

        if (! is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Debes iniciar sesión.', 'simple-cards-listings')));
        }

        // Validar campos requeridos
        $required_fields = array('nombre', 'descripcion', 'categoria', 'direccion');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array(
                    'message' => sprintf(
                        /* translators: %s: nombre del campo */
                        __('El campo %s es requerido.', 'simple-cards-listings'),
                        $field
                    )
                ));
            }
        }

        // Validar términos
        if (empty($_POST['terminos'])) {
            wp_send_json_error(array('message' => __('Debes aceptar los términos y condiciones.', 'simple-cards-listings')));
        }

        // Validar y subir logo
        if (empty($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => __('Debes subir un logo.', 'simple-cards-listings')));
        }

        // Crear post como borrador pendiente
        $post_data = array(
            'post_title'   => sanitize_text_field($_POST['nombre']),
            'post_content' => wp_kses_post($_POST['descripcion']),
            'post_type'    => 'establecimiento',
            'post_status'  => 'pending',
            'post_author'  => get_current_user_id(),
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => $post_id->get_error_message()));
        }

        // Asignar categoría
        if (! empty($_POST['categoria'])) {
            wp_set_object_terms($post_id, array(absint($_POST['categoria'])), 'categoria_establecimiento');
        }

        // Asignar tags
        if (! empty($_POST['tags']) && is_array($_POST['tags'])) {
            $tag_ids = array_map('absint', $_POST['tags']);
            wp_set_object_terms($post_id, $tag_ids, 'tag_busqueda');
        }

        // Asignar ubicaciones
        if (! empty($_POST['ubicaciones']) && is_array($_POST['ubicaciones'])) {
            $ubicacion_ids = array_map('absint', $_POST['ubicaciones']);
            wp_set_object_terms($post_id, $ubicacion_ids, 'ubicacion_establecimiento');
        }

        // Subir archivos
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Logo
        $logo_id = media_handle_upload('logo', $post_id);
        if (! is_wp_error($logo_id)) {
            update_post_meta($post_id, '_scl_logo', $logo_id);
        }

        // Imagen del establecimiento
        if (! empty($_FILES['imagen_establecimiento']) && $_FILES['imagen_establecimiento']['error'] === UPLOAD_ERR_OK) {
            $imagen_id = media_handle_upload('imagen_establecimiento', $post_id);
            if (! is_wp_error($imagen_id)) {
                update_post_meta($post_id, '_scl_imagen_establecimiento', $imagen_id);
            }
        }

        // PDF del menú
        if (! empty($_FILES['menu_pdf']) && $_FILES['menu_pdf']['error'] === UPLOAD_ERR_OK) {
            $pdf_id = media_handle_upload('menu_pdf', $post_id);
            if (! is_wp_error($pdf_id)) {
                update_post_meta($post_id, '_scl_menu_pdf', $pdf_id);
            }
        }

        // Guardar otros campos
        $meta_fields = array(
            'menu_pdf_name'   => 'text',
            'whatsapp'        => 'text',
            'instagram'       => 'url',
            'tiktok'          => 'url',
            'facebook'        => 'url',
            'website'         => 'url',
            'direccion'       => 'textarea',
            'google_maps_url' => 'url',
        );

        foreach ($meta_fields as $field => $type) {
            if (isset($_POST[$field])) {
                $value = '';
                switch ($type) {
                    case 'url':
                        $value = esc_url_raw($_POST[$field]);
                        break;
                    case 'textarea':
                        $value = sanitize_textarea_field($_POST[$field]);
                        break;
                    default:
                        $value = sanitize_text_field($_POST[$field]);
                }
                update_post_meta($post_id, '_scl_' . $field, $value);
            }
        }

        // Log
        SCL_Logger::log(
            'establecimiento_submitted',
            sprintf(
                /* translators: %s: título del establecimiento */
                __('Nueva solicitud de establecimiento: "%s"', 'simple-cards-listings'),
                sanitize_text_field($_POST['nombre'])
            ),
            $post_id,
            'establecimiento'
        );

        // Enviar notificación al admin
        SCL_Notifications::notify_new_submission($post_id);

        wp_send_json_success(array(
            'message' => __('Tu solicitud ha sido enviada y está pendiente de aprobación.', 'simple-cards-listings'),
            'post_id' => $post_id,
        ));
    }

    /**
     * Obtener formulario de edición
     */
    public static function get_edit_form()
    {
        check_ajax_referer('scl_nonce', 'nonce');

        if (! is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Debes iniciar sesión.', 'simple-cards-listings')));
        }

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;

        if (! $post_id) {
            wp_send_json_error(array('message' => __('ID no válido.', 'simple-cards-listings')));
        }

        // Verificar permisos
        if (! SCL_Permissions::can_edit($post_id)) {
            wp_send_json_error(array('message' => __('No tienes permiso para editar este establecimiento.', 'simple-cards-listings')));
        }

        $post = get_post($post_id);

        // Obtener datos
        $data = array(
            'id'              => $post_id,
            'title'           => $post->post_title,
            'description'     => $post->post_content,
            'logo_id'         => SCL_Metaboxes::get_meta($post_id, 'logo'),
            'imagen_id'       => SCL_Metaboxes::get_meta($post_id, 'imagen_establecimiento'),
            'menu_pdf_id'     => SCL_Metaboxes::get_meta($post_id, 'menu_pdf'),
            'menu_pdf_name'   => SCL_Metaboxes::get_meta($post_id, 'menu_pdf_name'),
            'whatsapp'        => SCL_Metaboxes::get_meta($post_id, 'whatsapp'),
            'instagram'       => SCL_Metaboxes::get_meta($post_id, 'instagram'),
            'tiktok'          => SCL_Metaboxes::get_meta($post_id, 'tiktok'),
            'facebook'        => SCL_Metaboxes::get_meta($post_id, 'facebook'),
            'website'         => SCL_Metaboxes::get_meta($post_id, 'website'),
            'direccion'       => SCL_Metaboxes::get_meta($post_id, 'direccion'),
            'google_maps_url' => SCL_Metaboxes::get_meta($post_id, 'google_maps_url'),
        );

        // Categorías y tags
        $categorias = get_terms(array('taxonomy' => 'categoria_establecimiento', 'hide_empty' => false));
        $tags = get_terms(array('taxonomy' => 'tag_busqueda', 'hide_empty' => false));
        $ubicaciones = get_terms(array('taxonomy' => 'ubicacion_establecimiento', 'hide_empty' => false));
        $selected_cats = wp_get_post_terms($post_id, 'categoria_establecimiento', array('fields' => 'ids'));
        $selected_tags = wp_get_post_terms($post_id, 'tag_busqueda', array('fields' => 'ids'));
        $selected_ubicaciones = wp_get_post_terms($post_id, 'ubicacion_establecimiento', array('fields' => 'ids'));

        $html = self::render_edit_form($data, $categorias, $tags, $ubicaciones, $selected_cats, $selected_tags, $selected_ubicaciones);

        wp_send_json_success(array('html' => $html));
    }

    /**
     * Renderizar formulario de edición
     */
    private static function render_edit_form($data, $categorias, $tags, $ubicaciones, $selected_cats, $selected_tags, $selected_ubicaciones)
    {
        $logo_url = $data['logo_id'] ? wp_get_attachment_image_url($data['logo_id'], 'thumbnail') : '';
        $imagen_url = $data['imagen_id'] ? wp_get_attachment_image_url($data['imagen_id'], 'thumbnail') : '';
        $pdf_url = $data['menu_pdf_id'] ? wp_get_attachment_url($data['menu_pdf_id']) : '';

        ob_start();
    ?>
        <h3><?php esc_html_e('Editar establecimiento', 'simple-cards-listings'); ?></h3>

        <form id="scl-edit-form" class="scl-form" enctype="multipart/form-data">
            <?php wp_nonce_field('scl_edit_nonce', 'scl_edit_nonce'); ?>
            <input type="hidden" name="post_id" value="<?php echo esc_attr($data['id']); ?>">

            <div class="scl-form-row">
                <label for="scl-edit-nombre"><?php esc_html_e('Nombre del establecimiento *', 'simple-cards-listings'); ?></label>
                <input type="text" id="scl-edit-nombre" name="nombre" value="<?php echo esc_attr($data['title']); ?>" required>
            </div>

            <div class="scl-form-row">
                <label for="scl-edit-descripcion"><?php esc_html_e('Descripción *', 'simple-cards-listings'); ?></label>
                <textarea id="scl-edit-descripcion" name="descripcion" rows="4" required><?php echo esc_textarea($data['description']); ?></textarea>
            </div>

            <div class="scl-form-row">
                <label for="scl-edit-categoria"><?php esc_html_e('Categoría *', 'simple-cards-listings'); ?></label>
                <select id="scl-edit-categoria" name="categoria" required>
                    <option value=""><?php esc_html_e('Seleccionar categoría', 'simple-cards-listings'); ?></option>
                    <?php foreach ($categorias as $cat) : ?>
                        <option value="<?php echo esc_attr($cat->term_id); ?>" <?php selected(in_array($cat->term_id, $selected_cats)); ?>>
                            <?php echo esc_html($cat->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="scl-form-row">
                <label><?php esc_html_e('Tags de búsqueda', 'simple-cards-listings'); ?></label>
                <div class="scl-checkbox-group">
                    <?php foreach ($tags as $tag) : ?>
                        <label class="scl-checkbox-label">
                            <input type="checkbox" name="tags[]" value="<?php echo esc_attr($tag->term_id); ?>"
                                <?php checked(in_array($tag->term_id, $selected_tags)); ?>>
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
                            <input type="checkbox" name="ubicaciones[]" value="<?php echo esc_attr($ubicacion->term_id); ?>"
                                <?php checked(in_array($ubicacion->term_id, $selected_ubicaciones)); ?>>
                            <?php echo esc_html($ubicacion->name); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="scl-form-row">
                <label><?php esc_html_e('Logo actual', 'simple-cards-listings'); ?></label>
                <?php if ($logo_url) : ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="" style="max-width: 100px; display: block; margin-bottom: 10px;">
                <?php endif; ?>
                <label for="scl-edit-logo"><?php esc_html_e('Cambiar logo', 'simple-cards-listings'); ?></label>
                <input type="file" id="scl-edit-logo" name="logo" accept="image/*">
            </div>

            <div class="scl-form-row">
                <label><?php esc_html_e('Imagen actual', 'simple-cards-listings'); ?></label>
                <?php if ($imagen_url) : ?>
                    <img src="<?php echo esc_url($imagen_url); ?>" alt="" style="max-width: 100px; display: block; margin-bottom: 10px;">
                <?php endif; ?>
                <label for="scl-edit-imagen"><?php esc_html_e('Cambiar imagen', 'simple-cards-listings'); ?></label>
                <input type="file" id="scl-edit-imagen" name="imagen_establecimiento" accept="image/*">
            </div>

            <div class="scl-form-row">
                <label><?php esc_html_e('PDF actual', 'simple-cards-listings'); ?></label>
                <?php if ($pdf_url) : ?>
                    <a href="<?php echo esc_url($pdf_url); ?>" target="_blank"><?php esc_html_e('Ver PDF actual', 'simple-cards-listings'); ?></a>
                <?php endif; ?>
                <label for="scl-edit-menu-pdf"><?php esc_html_e('Cambiar PDF', 'simple-cards-listings'); ?></label>
                <input type="file" id="scl-edit-menu-pdf" name="menu_pdf" accept=".pdf">
            </div>

            <div class="scl-form-row">
                <label for="scl-edit-menu-pdf-name"><?php esc_html_e('Nombre del PDF', 'simple-cards-listings'); ?></label>
                <input type="text" id="scl-edit-menu-pdf-name" name="menu_pdf_name" value="<?php echo esc_attr($data['menu_pdf_name']); ?>">
            </div>

            <div class="scl-form-row">
                <label for="scl-edit-whatsapp"><?php esc_html_e('WhatsApp', 'simple-cards-listings'); ?></label>
                <input type="text" id="scl-edit-whatsapp" name="whatsapp" value="<?php echo esc_attr($data['whatsapp']); ?>">
            </div>

            <div class="scl-form-row">
                <label for="scl-edit-instagram"><?php esc_html_e('Instagram', 'simple-cards-listings'); ?></label>
                <input type="url" id="scl-edit-instagram" name="instagram" value="<?php echo esc_url($data['instagram']); ?>">
            </div>

            <div class="scl-form-row">
                <label for="scl-edit-tiktok"><?php esc_html_e('TikTok', 'simple-cards-listings'); ?></label>
                <input type="url" id="scl-edit-tiktok" name="tiktok" value="<?php echo esc_url($data['tiktok']); ?>">
            </div>

            <div class="scl-form-row">
                <label for="scl-edit-facebook"><?php esc_html_e('Facebook', 'simple-cards-listings'); ?></label>
                <input type="url" id="scl-edit-facebook" name="facebook" value="<?php echo esc_url($data['facebook']); ?>" style="border: 1px solid #000000; !important;">
            </div>

            <div class="scl-form-row">
                <label for="scl-edit-website"><?php esc_html_e('Sitio Web', 'simple-cards-listings'); ?></label>
                <input type="url" id="scl-edit-website" name="website" value="<?php echo esc_url($data['website']); ?>">
            </div>

            <div class="scl-form-row">
                <label for="scl-edit-direccion"><?php esc_html_e('Dirección *', 'simple-cards-listings'); ?></label>
                <textarea id="scl-edit-direccion" name="direccion" rows="2" required><?php echo esc_textarea($data['direccion']); ?></textarea>
            </div>

            <div class="scl-form-row">
                <label for="scl-edit-google-maps"><?php esc_html_e('URL de Google Maps', 'simple-cards-listings'); ?></label>
                <input type="url" id="scl-edit-google-maps" name="google_maps_url" value="<?php echo esc_url($data['google_maps_url']); ?>">
            </div>

            <div class="scl-form-row">
                <button type="submit" class="scl-btn scl-btn-primary">
                    <?php esc_html_e('Guardar cambios', 'simple-cards-listings'); ?>
                </button>
            </div>

            <div id="scl-edit-message" class="scl-form-message" style="display: none;"></div>
        </form>
<?php
        return ob_get_clean();
    }

    /**
     * Actualizar establecimiento
     */
    public static function update_establecimiento()
    {
        check_ajax_referer('scl_edit_nonce', 'scl_edit_nonce');

        if (! is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Debes iniciar sesión.', 'simple-cards-listings')));
        }

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;

        if (! $post_id) {
            wp_send_json_error(array('message' => __('ID no válido.', 'simple-cards-listings')));
        }

        // Verificar permisos
        if (! SCL_Permissions::can_edit($post_id)) {
            wp_send_json_error(array('message' => __('No tienes permiso para editar este establecimiento.', 'simple-cards-listings')));
        }

        // Validar campos requeridos
        $required_fields = array('nombre', 'descripcion', 'categoria', 'direccion');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array('message' => __('Todos los campos requeridos deben completarse.', 'simple-cards-listings')));
            }
        }

        // Actualizar post
        $post_data = array(
            'ID'           => $post_id,
            'post_title'   => sanitize_text_field($_POST['nombre']),
            'post_content' => wp_kses_post($_POST['descripcion']),
        );

        $result = wp_update_post($post_data);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        // Actualizar categoría
        if (! empty($_POST['categoria'])) {
            wp_set_object_terms($post_id, array(absint($_POST['categoria'])), 'categoria_establecimiento');
        }

        // Actualizar tags
        if (! empty($_POST['tags']) && is_array($_POST['tags'])) {
            $tag_ids = array_map('absint', $_POST['tags']);
            wp_set_object_terms($post_id, $tag_ids, 'tag_busqueda');
        } else {
            wp_set_object_terms($post_id, array(), 'tag_busqueda');
        }

        // Actualizar ubicaciones
        if (! empty($_POST['ubicaciones']) && is_array($_POST['ubicaciones'])) {
            $ubicacion_ids = array_map('absint', $_POST['ubicaciones']);
            wp_set_object_terms($post_id, $ubicacion_ids, 'ubicacion_establecimiento');
        } else {
            wp_set_object_terms($post_id, array(), 'ubicacion_establecimiento');
        }

        // Subir archivos si hay nuevos
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Logo
        if (! empty($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $logo_id = media_handle_upload('logo', $post_id);
            if (! is_wp_error($logo_id)) {
                update_post_meta($post_id, '_scl_logo', $logo_id);
            }
        }

        // Imagen del establecimiento
        if (! empty($_FILES['imagen_establecimiento']) && $_FILES['imagen_establecimiento']['error'] === UPLOAD_ERR_OK) {
            $imagen_id = media_handle_upload('imagen_establecimiento', $post_id);
            if (! is_wp_error($imagen_id)) {
                update_post_meta($post_id, '_scl_imagen_establecimiento', $imagen_id);
            }
        }

        // PDF
        if (! empty($_FILES['menu_pdf']) && $_FILES['menu_pdf']['error'] === UPLOAD_ERR_OK) {
            $pdf_id = media_handle_upload('menu_pdf', $post_id);
            if (! is_wp_error($pdf_id)) {
                update_post_meta($post_id, '_scl_menu_pdf', $pdf_id);
            }
        }

        // Actualizar otros campos
        $meta_fields = array(
            'menu_pdf_name'   => 'text',
            'whatsapp'        => 'text',
            'instagram'       => 'url',
            'tiktok'          => 'url',
            'facebook'        => 'url',
            'website'         => 'url',
            'direccion'       => 'textarea',
            'google_maps_url' => 'url',
        );

        foreach ($meta_fields as $field => $type) {
            if (isset($_POST[$field])) {
                $value = '';
                switch ($type) {
                    case 'url':
                        $value = esc_url_raw($_POST[$field]);
                        break;
                    case 'textarea':
                        $value = sanitize_textarea_field($_POST[$field]);
                        break;
                    default:
                        $value = sanitize_text_field($_POST[$field]);
                }
                update_post_meta($post_id, '_scl_' . $field, $value);
            }
        }

        // Log
        SCL_Logger::log(
            'establecimiento_updated_frontend',
            sprintf(
                /* translators: %s: título del establecimiento */
                __('Establecimiento "%s" actualizado desde frontend', 'simple-cards-listings'),
                sanitize_text_field($_POST['nombre'])
            ),
            $post_id,
            'establecimiento'
        );

        wp_send_json_success(array('message' => __('Establecimiento actualizado correctamente.', 'simple-cards-listings')));
    }

    /**
     * Cargar más establecimientos (AJAX para paginación)
     */
    public static function load_more_establecimientos()
    {
        check_ajax_referer('scl_nonce', 'nonce');

        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 12;
        $categoria_filter = isset($_POST['categoria_filter']) ? sanitize_text_field($_POST['categoria_filter']) : '';
        $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
        $category_selected = isset($_POST['category_selected']) ? sanitize_text_field($_POST['category_selected']) : '';
        $ubicacion_selected = isset($_POST['ubicacion_selected']) ? sanitize_text_field($_POST['ubicacion_selected']) : '';
        $is_gold = isset($_POST['is_gold']) ? (bool) $_POST['is_gold'] : false;
        $only_link = isset($_POST['only_link']) ? sanitize_text_field($_POST['only_link']) : 'false';

        // Parsear niveles
        $levels = array();
        if (isset($_POST['levels']) && !empty($_POST['levels'])) {
            $levels_json = stripslashes($_POST['levels']);
            $levels = json_decode($levels_json, true);
            if (!is_array($levels)) {
                $levels = array();
            }
        }

        if ($per_page <= 0) {
            $per_page = 12;
        }

        // Para grid gold, necesitamos obtener TODOS los posts primero para ordenar por nivel
        $posts_per_page_query = ($is_gold && !empty($levels)) ? -1 : $per_page;

        $args = array(
            'post_type'      => 'establecimiento',
            'post_status'    => 'publish',
            'posts_per_page' => $posts_per_page_query,
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        // Solo aplicar paginación si NO es grid gold
        if (!($is_gold && !empty($levels))) {
            $args['paged'] = $page;
        }

        // Aplicar filtros de taxonomía
        $tax_query = array('relation' => 'AND');

        // Filtro de categoría desde shortcode
        if (!empty($categoria_filter)) {
            $tax_query[] = array(
                'taxonomy' => 'categoria_establecimiento',
                'field'    => 'slug',
                'terms'    => $categoria_filter,
            );
        }

        // Filtro de categoría desde dropdown
        if (!empty($category_selected)) {
            $tax_query[] = array(
                'taxonomy' => 'categoria_establecimiento',
                'field'    => 'slug',
                'terms'    => $category_selected,
            );
        }

        // Filtro de ubicación desde dropdown
        if (!empty($ubicacion_selected)) {
            $tax_query[] = array(
                'taxonomy' => 'ubicacion_establecimiento',
                'field'    => 'slug',
                'terms'    => $ubicacion_selected,
            );
        }

        if (count($tax_query) > 1) {
            $args['tax_query'] = $tax_query;
        }

        // Si hay búsqueda de texto, necesitamos manejarla diferente
        if (!empty($search_term)) {
            $args['s'] = $search_term;
        }

        $query = new WP_Query($args);
        $html = '';

        if ($query->have_posts()) {
            // Si es grid gold, necesitamos ordenar por nivel
            if ($is_gold && !empty($levels)) {
                // Agrupar posts por nivel
                $posts_by_level = array();
                foreach ($levels as $level_index => $level) {
                    $posts_by_level[$level_index] = array();
                }

                while ($query->have_posts()) {
                    $query->the_post();
                    $post_id = get_the_ID();
                    $author_id = get_post_field('post_author', $post_id);
                    $user = get_userdata($author_id);

                    // Determinar nivel
                    $assigned = false;
                    foreach ($levels as $level_index => $level) {
                        if (empty($level['role'])) continue;

                        if ($user && in_array($level['role'], (array) $user->roles)) {
                            $posts_by_level[$level_index][] = $post_id;
                            $assigned = true;
                            break;
                        }
                    }

                    if (!$assigned) {
                        $last_level_index = count($levels) - 1;
                        $posts_by_level[$last_level_index][] = $post_id;
                    }
                }
                wp_reset_postdata();

                // Combinar posts ordenados por prioridad de nivel
                $ordered_ids = array();
                foreach ($posts_by_level as $level_posts) {
                    $ordered_ids = array_merge($ordered_ids, $level_posts);
                }

                // Calcular offset y obtener solo los IDs de la página actual
                $offset = ($page - 1) * $per_page;
                $current_page_ids = array_slice($ordered_ids, $offset, $per_page);

                // Calcular total de páginas
                $total_posts = count($ordered_ids);
                $max_pages = ceil($total_posts / $per_page);

                // Generar HTML solo para los posts de la página actual
                foreach ($current_page_ids as $post_id) {
                    $html .= SCL_Shortcodes::render_card_item($post_id, true, '', '', '', $levels, $only_link);
                }

                // Enviar respuesta con paginación correcta
                wp_send_json_success(array(
                    'html' => $html,
                    'has_more' => $page < $max_pages,
                    'max_pages' => $max_pages,
                ));
                return;
            } else {
                // Grid normal
                while ($query->have_posts()) {
                    $query->the_post();
                    $html .= SCL_Shortcodes::render_card_item(get_the_ID(), false, '', '', '', array(), $only_link);
                }
                wp_reset_postdata();
            }
        };


        wp_send_json_success(array(
            'html' => $html,
            'has_more' => $query->max_num_pages > $page,
            'max_pages' => $query->max_num_pages,
        ));
    }

    /**
     * CUPONES: Obtener datos de un cupón para el modal
     */
    public static function get_cupon()
    {
        check_ajax_referer('scl_nonce', 'nonce');

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (!$post_id || get_post_type($post_id) !== 'promocion') {
            wp_send_json_error(array('message' => __('Promoción no encontrada.', 'simple-cards-listings')));
        }

        $cupon = get_post($post_id);
        if (!$cupon || $cupon->post_status !== 'publish') {
            wp_send_json_error(array('message' => __('Cupón no disponible.', 'simple-cards-listings')));
        }

        // Obtener datos del establecimiento
        $establecimiento_id = get_post_meta($post_id, '_scl_establecimiento_id', true);
        $establecimiento = null;
        if ($establecimiento_id) {
            $est = get_post($establecimiento_id);
            if ($est) {
                $establecimiento = array(
                    'id' => $est->ID,
                    'titulo' => $est->post_title,
                    'url' => get_permalink($est->ID),
                );
            }
        }

        // Fechas
        $fecha_inicio = get_post_meta($post_id, '_scl_fecha_inicio', true);
        $fecha_fin = get_post_meta($post_id, '_scl_fecha_fin', true);

        // Imagen
        $imagen_url = get_the_post_thumbnail_url($post_id, 'large');
        if (!$imagen_url) {
            $imagen_url = SCL_PLUGIN_URL . 'assets/images/cupon-placeholder.png';
        }

        // URL para compartir
        $share_url = add_query_arg('cupon_id', $post_id, home_url('/'));

        $data = array(
            'id' => $post_id,
            'titulo' => $cupon->post_title,
            'descripcion' => wpautop($cupon->post_content),
            'imagen' => $imagen_url,
            'fecha_inicio' => $fecha_inicio ? date_i18n(get_option('date_format'), strtotime($fecha_inicio)) : '',
            'fecha_fin' => $fecha_fin ? date_i18n(get_option('date_format'), strtotime($fecha_fin)) : '',
            'destacado' => get_post_meta($post_id, '_scl_destacado', true) == '1',
            'establecimiento' => $establecimiento,
            'share_url' => $share_url,
        );

        wp_send_json_success(array('cupon' => $data));
    }

    /**
     * CUPONES: Búsqueda de promociones
     */
    public static function search_cupones()
    {
        check_ajax_referer('scl_nonce', 'nonce');

        $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        // Formato de datetime-local: YYYY-MM-DDTHH:MM
        $ahora = current_time('Y-m-d\TH:i');

        // Query base
        $args = array(
            'post_type' => 'promocion',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        );

        // Meta query con OR para manejar campos vacíos
        $meta_query = array('relation' => 'AND');

        // Filtrar que no haya expirado
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key' => '_scl_fecha_fin',
                'value' => $ahora,
                'compare' => '>=',
                'type' => 'CHAR',
            ),
            array(
                'key' => '_scl_fecha_fin',
                'compare' => 'NOT EXISTS',
            ),
        );

        // Filtrar que ya haya iniciado
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key' => '_scl_fecha_inicio',
                'value' => $ahora,
                'compare' => '<=',
                'type' => 'CHAR',
            ),
            array(
                'key' => '_scl_fecha_inicio',
                'compare' => 'NOT EXISTS',
            ),
        );

        $args['meta_query'] = $meta_query;

        // Si hay búsqueda, buscar en título, contenido y establecimiento
        if (!empty($search_term)) {
            $args['s'] = $search_term;

            // También buscar por establecimiento
            $est_query = new WP_Query(array(
                'post_type' => 'establecimiento',
                's' => $search_term,
                'posts_per_page' => -1,
                'fields' => 'ids',
            ));

            if ($est_query->have_posts()) {
                $args['meta_query'][] = array(
                    'key' => '_scl_establecimiento_id',
                    'value' => $est_query->posts,
                    'compare' => 'IN',
                );
            }
        }

        $query = new WP_Query($args);
        $html = '';

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $html .= SCL_Shortcodes::render_cupon_card(get_the_ID());
            }
            wp_reset_postdata();
        }

        wp_send_json_success(array(
            'html' => $html,
            'found' => $query->found_posts,
        ));
    }

    /**
     * CUPONES: Crear o editar cupón desde frontend
     */
    public static function submit_cupon()
    {
        check_ajax_referer('scl_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Debes iniciar sesión.', 'simple-cards-listings')));
        }

        $cupon_id = isset($_POST['cupon_id']) ? intval($_POST['cupon_id']) : 0;
        $establecimiento_id = isset($_POST['establecimiento_id']) ? intval($_POST['establecimiento_id']) : 0;
        $titulo = isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '';
        $descripcion = isset($_POST['descripcion']) ? wp_kses_post($_POST['descripcion']) : '';
        $fecha_inicio = isset($_POST['fecha_inicio']) ? sanitize_text_field($_POST['fecha_inicio']) : '';
        $fecha_fin = isset($_POST['fecha_fin']) ? sanitize_text_field($_POST['fecha_fin']) : '';
        $destacado = isset($_POST['destacado']) ? '1' : '0';

        // Validar establecimiento
        if (!$establecimiento_id || get_post_type($establecimiento_id) !== 'establecimiento') {
            wp_send_json_error(array('message' => __('Establecimiento no válido.', 'simple-cards-listings')));
        }

        // Verificar permisos
        if (!current_user_can('edit_post', $establecimiento_id)) {
            wp_send_json_error(array('message' => __('No tienes permisos para este establecimiento.', 'simple-cards-listings')));
        }

        // Validar fechas
        if (empty($fecha_inicio) || empty($fecha_fin)) {
            wp_send_json_error(array('message' => __('Las fechas son obligatorias.', 'simple-cards-listings')));
        }

        $inicio_ts = strtotime($fecha_inicio);
        $fin_ts = strtotime($fecha_fin);

        if ($fin_ts <= $inicio_ts) {
            wp_send_json_error(array('message' => __('La fecha de fin debe ser posterior a la de inicio.', 'simple-cards-listings')));
        }

        // Si es edición, validar permisos
        if ($cupon_id) {
            if (!current_user_can('edit_post', $cupon_id)) {
                wp_send_json_error(array('message' => __('No puedes editar esta promoción.', 'simple-cards-listings')));
            }

            $post_data = array(
                'ID' => $cupon_id,
                'post_title' => $titulo,
                'post_content' => $descripcion,
            );

            wp_update_post($post_data);
        } else {
            // Crear nueva promoción
            $post_data = array(
                'post_type' => 'promocion',
                'post_title' => $titulo,
                'post_content' => $descripcion,
                'post_status' => 'publish', // O 'pending' si requiere aprobación
                'post_author' => get_current_user_id(),
            );

            $cupon_id = wp_insert_post($post_data);

            if (is_wp_error($cupon_id)) {
                wp_send_json_error(array('message' => $cupon_id->get_error_message()));
            }
        }

        // Actualizar meta
        update_post_meta($cupon_id, '_scl_establecimiento_id', $establecimiento_id);
        update_post_meta($cupon_id, '_scl_fecha_inicio', $fecha_inicio);
        update_post_meta($cupon_id, '_scl_fecha_fin', $fecha_fin);
        update_post_meta($cupon_id, '_scl_destacado', $destacado);

        // Manejar imagen si se subió
        if (!empty($_FILES['imagen']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $attachment_id = media_handle_upload('imagen', $cupon_id);
            if (!is_wp_error($attachment_id)) {
                set_post_thumbnail($cupon_id, $attachment_id);
            }
        }

        wp_send_json_success(array(
            'message' => __('Cupón guardado exitosamente.', 'simple-cards-listings'),
            'cupon_id' => $cupon_id,
        ));
    }

    /**
     * CUPONES: Eliminar cupón
     */
    public static function delete_cupon()
    {
        check_ajax_referer('scl_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Debes iniciar sesión.', 'simple-cards-listings')));
        }

        $cupon_id = isset($_POST['cupon_id']) ? intval($_POST['cupon_id']) : 0;

        if (!$cupon_id || get_post_type($cupon_id) !== 'promocion') {
            wp_send_json_error(array('message' => __('Promoción no encontrada.', 'simple-cards-listings')));
        }

        if (!current_user_can('delete_post', $cupon_id)) {
            wp_send_json_error(array('message' => __('No tienes permisos para eliminar esta promoción.', 'simple-cards-listings')));
        }

        $deleted = wp_delete_post($cupon_id, true);

        if (!$deleted) {
            wp_send_json_error(array('message' => __('Error al eliminar el cupón.', 'simple-cards-listings')));
        }

        wp_send_json_success(array('message' => __('Cupón eliminado exitosamente.', 'simple-cards-listings')));
    }
}
