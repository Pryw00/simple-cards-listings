<?php

/**
 * Gestión de Cupones Promocionales
 *
 * @package SimpleCardsListings
 * @since 1.1.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar cupones promocionales
 */
class SCL_Cupones
{

    /**
     * Inicializar
     */
    public static function init()
    {
        // Registrar CPT y taxonomía
        self::register_post_type();
        self::register_taxonomy();

        add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
        add_action('save_post_promocion', array(__CLASS__, 'save_meta'), 10, 2);

        // Filtros para query de promociones
        add_filter('manage_promocion_posts_columns', array(__CLASS__, 'set_custom_columns'));
        add_action('manage_promocion_posts_custom_column', array(__CLASS__, 'custom_column_content'), 10, 2);

        // Permisos
        add_filter('map_meta_cap', array(__CLASS__, 'map_promocion_meta_cap'), 10, 4);
    }

    /**
     * Registrar Custom Post Type de cupones
     */
    public static function register_post_type()
    {
        $labels = array(
            'name'                  => _x('Promociones', 'Post type general name', 'simple-cards-listings'),
            'singular_name'         => _x('Promoción', 'Post type singular name', 'simple-cards-listings'),
            'menu_name'             => _x('Promociones', 'Admin Menu text', 'simple-cards-listings'),
            'name_admin_bar'        => _x('Promoción', 'Add New on Toolbar', 'simple-cards-listings'),
            'add_new'               => __('Agregar Nueva', 'simple-cards-listings'),
            'add_new_item'          => __('Agregar Nueva Promoción', 'simple-cards-listings'),
            'new_item'              => __('Nueva Promoción', 'simple-cards-listings'),
            'edit_item'             => __('Editar Promoción', 'simple-cards-listings'),
            'view_item'             => __('Ver Promoción', 'simple-cards-listings'),
            'all_items'             => __('Todas las Promociones', 'simple-cards-listings'),
            'search_items'          => __('Buscar Promociones', 'simple-cards-listings'),
            'parent_item_colon'     => __('Promociones Padre:', 'simple-cards-listings'),
            'not_found'             => __('No se encontraron promociones.', 'simple-cards-listings'),
            'not_found_in_trash'    => __('No se encontraron promociones en la papelera.', 'simple-cards-listings'),
            'featured_image'        => _x('Imagen de la Promoción', 'Overrides the "Featured Image" phrase', 'simple-cards-listings'),
            'set_featured_image'    => _x('Establecer imagen de la promoción', 'Overrides the "Set featured image" phrase', 'simple-cards-listings'),
            'remove_featured_image' => _x('Remover imagen de la promoción', 'Overrides the "Remove featured image" phrase', 'simple-cards-listings'),
            'use_featured_image'    => _x('Usar como imagen de la promoción', 'Overrides the "Use as featured image" phrase', 'simple-cards-listings'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'menu_icon'          => 'dashicons-tickets-alt',
            'query_var'          => true,
            'rewrite'            => array('slug' => 'promocion'),
            'capability_type'    => 'post',
            'map_meta_cap'       => true,
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 26,
            'supports'           => array('title', 'editor', 'thumbnail', 'author'),
        );

        register_post_type('promocion', $args);
    }

    /**
     * Registrar taxonomía para categorías de cupones
     */
    public static function register_taxonomy()
    {
        $labels = array(
            'name'              => _x('Categorías de Promociones', 'taxonomy general name', 'simple-cards-listings'),
            'singular_name'     => _x('Categoría de Promoción', 'taxonomy singular name', 'simple-cards-listings'),
            'search_items'      => __('Buscar Categorías', 'simple-cards-listings'),
            'all_items'         => __('Todas las Categorías', 'simple-cards-listings'),
            'parent_item'       => __('Categoría Padre', 'simple-cards-listings'),
            'parent_item_colon' => __('Categoría Padre:', 'simple-cards-listings'),
            'edit_item'         => __('Editar Categoría', 'simple-cards-listings'),
            'update_item'       => __('Actualizar Categoría', 'simple-cards-listings'),
            'add_new_item'      => __('Agregar Nueva Categoría', 'simple-cards-listings'),
            'new_item_name'     => __('Nuevo Nombre de Categoría', 'simple-cards-listings'),
            'menu_name'         => __('Categorías', 'simple-cards-listings'),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'categoria-promocion'),
        );

        register_taxonomy('categoria_promocion', array('promocion'), $args);
    }

    /**
     * Agregar metaboxes
     */
    public static function add_meta_boxes()
    {
        add_meta_box(
            'scl_promocion_datos',
            __('Datos de la Promoción', 'simple-cards-listings'),
            array(__CLASS__, 'render_datos_metabox'),
            'promocion',
            'normal',
            'high'
        );
    }

    /**
     * Renderizar metabox de datos del cupón
     */
    public static function render_datos_metabox($post)
    {
        wp_nonce_field('scl_promocion_meta_nonce', 'scl_promocion_meta_nonce');

        $establecimiento_id = get_post_meta($post->ID, '_scl_establecimiento_id', true);
        $fecha_inicio = get_post_meta($post->ID, '_scl_fecha_inicio', true);
        $fecha_fin = get_post_meta($post->ID, '_scl_fecha_fin', true);

        // Obtener establecimientos del usuario o todos si tiene permisos
        if (SCL_Permissions::can_edit_promocion(0) || user_can(get_current_user_id(), 'edit_others_promociones')) {
            $establecimientos = get_posts(array(
                'post_type'      => 'establecimiento',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
            ));
        } else {
            $establecimientos = get_posts(array(
                'post_type'      => 'establecimiento',
                'post_status'    => 'publish',
                'author'         => get_current_user_id(),
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
            ));
        }

?>
        <div class="scl-metabox-field">
            <label for="scl_establecimiento_id">
                <?php esc_html_e('Establecimiento *', 'simple-cards-listings'); ?>
            </label>
            <select name="scl_establecimiento_id" id="scl_establecimiento_id" required style="width: 100%;">
                <option value=""><?php esc_html_e('Seleccionar establecimiento', 'simple-cards-listings'); ?></option>
                <?php foreach ($establecimientos as $establecimiento) : ?>
                    <option value="<?php echo esc_attr($establecimiento->ID); ?>" <?php selected($establecimiento_id, $establecimiento->ID); ?>>
                        <?php echo esc_html($establecimiento->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php esc_html_e('Establecimiento al que pertenece este cupón.', 'simple-cards-listings'); ?></p>
        </div>

        <div class="scl-metabox-field">
            <label for="scl_fecha_inicio">
                <?php esc_html_e('Fecha y Hora de Inicio *', 'simple-cards-listings'); ?>
            </label>
            <input type="datetime-local" name="scl_fecha_inicio" id="scl_fecha_inicio" value="<?php echo esc_attr($fecha_inicio); ?>" required style="width: 100%;">
            <p class="description"><?php esc_html_e('Fecha y hora desde cuando el cupón estará activo.', 'simple-cards-listings'); ?></p>
        </div>

        <div class="scl-metabox-field">
            <label for="scl_fecha_fin">
                <?php esc_html_e('Fecha y Hora de Fin *', 'simple-cards-listings'); ?>
            </label>
            <input type="datetime-local" name="scl_fecha_fin" id="scl_fecha_fin" value="<?php echo esc_attr($fecha_fin); ?>" required style="width: 100%;">
            <p class="description"><?php esc_html_e('Fecha y hora de expiración del cupón.', 'simple-cards-listings'); ?></p>
        </div>

        <style>
            .scl-metabox-field {
                margin-bottom: 20px;
            }

            .scl-metabox-field label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
            }

            .scl-metabox-field input[type="text"],
            .scl-metabox-field input[type="datetime-local"],
            .scl-metabox-field select,
            .scl-metabox-field textarea {
                width: 100%;
                padding: 8px;
            }
        </style>
<?php
    }

    /**
     * Guardar metadatos del cupón
     */
    public static function save_meta($post_id, $post)
    {
        // Verificar nonce
        if (!isset($_POST['scl_promocion_meta_nonce']) || !wp_verify_nonce($_POST['scl_promocion_meta_nonce'], 'scl_promocion_meta_nonce')) {
            return;
        }

        // Verificar autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Verificar permisos usando el sistema de capacidades
        if (!SCL_Permissions::can_edit_promocion($post_id)) {
            return;
        }

        // Guardar establecimiento
        if (isset($_POST['scl_establecimiento_id'])) {
            update_post_meta($post_id, '_scl_establecimiento_id', absint($_POST['scl_establecimiento_id']));
        }

        // Guardar fechas
        if (isset($_POST['scl_fecha_inicio'])) {
            update_post_meta($post_id, '_scl_fecha_inicio', sanitize_text_field($_POST['scl_fecha_inicio']));
        }

        if (isset($_POST['scl_fecha_fin'])) {
            update_post_meta($post_id, '_scl_fecha_fin', sanitize_text_field($_POST['scl_fecha_fin']));
        }

        // Notificar al administrador cuando se crea una nueva promoción
        // Solo si es creación (no actualización)
        if ($post instanceof WP_Post && $post->post_type === 'promocion' && $post->post_status === 'publish' && $post->post_date === $post->post_modified) {
            if (method_exists('SCL_Notifications', 'notify_new_promotion')) {
                SCL_Notifications::notify_new_promotion($post_id);
            }
        }
    }

    /**
     * Definir columnas personalizadas en listado de cupones
     */
    public static function set_custom_columns($columns)
    {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = __('Nombre del Cupón', 'simple-cards-listings');
        $new_columns['establecimiento'] = __('Establecimiento', 'simple-cards-listings');
        $new_columns['vigencia'] = __('Vigencia', 'simple-cards-listings');
        $new_columns['thumbnail'] = __('Imagen', 'simple-cards-listings');
        $new_columns['date'] = $columns['date'];

        return $new_columns;
    }

    /**
     * Contenido de columnas personalizadas
     */
    public static function custom_column_content($column, $post_id)
    {
        switch ($column) {
            case 'establecimiento':
                $est_id = get_post_meta($post_id, '_scl_establecimiento_id', true);
                if ($est_id) {
                    $est = get_post($est_id);
                    if ($est) {
                        echo '<a href="' . get_edit_post_link($est_id) . '">' . esc_html($est->post_title) . '</a>';
                    }
                } else {
                    echo '—';
                }
                break;

            case 'vigencia':
                $fecha_inicio = get_post_meta($post_id, '_scl_fecha_inicio', true);
                $fecha_fin = get_post_meta($post_id, '_scl_fecha_fin', true);

                if ($fecha_inicio && $fecha_fin) {
                    $inicio = strtotime($fecha_inicio);
                    $fin = strtotime($fecha_fin);
                    $ahora = current_time('timestamp');

                    echo '<strong>' . date_i18n('d/m/Y H:i', $inicio) . '</strong><br>';
                    echo date_i18n('d/m/Y H:i', $fin);

                    if ($ahora < $inicio) {
                        echo '<br><span style="color: #0073aa;">⏳ Próximo</span>';
                    } elseif ($ahora > $fin) {
                        echo '<br><span style="color: #999;">⏹ Expirado</span>';
                    } else {
                        echo '<br><span style="color: #46b450;">✓ Activo</span>';
                    }
                } else {
                    echo '—';
                }
                break;

            case 'thumbnail':
                if (has_post_thumbnail($post_id)) {
                    echo get_the_post_thumbnail($post_id, array(50, 50));
                } else {
                    echo '—';
                }
                break;
        }
    }

    /**
     * Control de permisos: usuarios solo pueden editar promociones de sus establecimientos
     */
    public static function map_promocion_meta_cap($caps, $cap, $user_id, $args)
    {
        // Solo aplicar a capacidades de posts y si es una promoción
        if (!in_array($cap, array('edit_post', 'delete_post', 'read_post'))) {
            return $caps;
        }

        if (empty($args[0])) {
            return $caps;
        }

        // Obtener la promoción
        $post = get_post($args[0]);
        if (!$post || 'promocion' !== $post->post_type) {
            return $caps;
        }

        // Usar el sistema de permisos integrado
        if ($cap === 'edit_post') {
            if (SCL_Permissions::can_edit_promocion($post->ID, $user_id)) {
                return array('read');
            }
        } elseif ($cap === 'delete_post') {
            if (SCL_Permissions::can_delete_promocion($post->ID, $user_id)) {
                return array('read');
            }
        } elseif ($cap === 'read_post') {
            // El autor puede leer sus propias promociones
            if ((int) $post->post_author === (int) $user_id) {
                return array('read');
            }

            // Verificar permiso usando el sistema de integración con ARM
            $can_read_others = apply_filters('scl_check_permission', false, 'edit_others_promociones', $user_id);

            // Si no usa ARM, verificar capacidad nativa
            if (!$can_read_others && user_can($user_id, 'edit_others_promociones')) {
                $can_read_others = true;
            }

            // Usuarios con permiso para editar de otros pueden leer
            if ($can_read_others) {
                return array('read');
            }
        }

        // Retornar las capacidades originales en lugar de bloquear completamente
        return $caps;
    }

    /**
     * Verificar si un usuario puede editar una promoción basado en fecha de inicio
     */
    public static function can_edit_promocion($post_id, $user_id = null)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        // Usar el sistema de permisos integrado
        if (user_can($user_id, 'manage_options') || user_can($user_id, 'edit_others_promociones')) {
            return true;
        }

        $fecha_inicio = get_post_meta($post_id, '_scl_fecha_inicio', true);
        if (! $fecha_inicio) {
            return true; // Si no tiene fecha, permitir edición
        }

        // Obtener configuración de días previos permitidos
        $dias_previos = get_option('scl_promocion_dias_previos_edicion', 7);

        $inicio_timestamp = strtotime($fecha_inicio);
        $ahora = current_time('timestamp');
        $limite_edicion = $inicio_timestamp - ($dias_previos * DAY_IN_SECONDS);

        return $ahora <= $limite_edicion;
    }
}
