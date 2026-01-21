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
        add_shortcode('scl_solicitud', array(__CLASS__, 'render_solicitud_form'));
        add_shortcode('scl_user_dashboard', array(__CLASS__, 'render_user_dashboard'));
    }

    /**
     * Shortcode: Grid de establecimientos
     * [scl_grid categoria="" limit="-1" columns="3"]
     *
     * @param array $atts Atributos del shortcode.
     * @return string
     */
    public static function render_grid($atts)
    {
        $atts = shortcode_atts(array(
            'categoria' => '',
            'limit'     => -1,
            'columns'   => 3,
        ), $atts, 'scl_grid');

        $args = array(
            'post_type'      => 'establecimiento',
            'post_status'    => 'publish',
            'posts_per_page' => intval($atts['limit']),
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        // Filtrar por categoría si se especifica
        if (! empty($atts['categoria'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'categoria_establecimiento',
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field($atts['categoria']),
                ),
            );
        }

        $establecimientos = new WP_Query($args);

        // Obtener categorías para el filtro
        $categorias = get_terms(array(
            'taxonomy'   => 'categoria_establecimiento',
            'hide_empty' => true,
        ));

        // Obtener tags para sugerencias
        $tags = get_terms(array(
            'taxonomy'   => 'tag_busqueda',
            'hide_empty' => true,
        ));

        ob_start();
?>
        <div class="scl-container">
            <!-- Buscador -->
            <div class="scl-search-wrapper">
                <div class="scl-search-box">
                    <input
                        type="text"
                        id="scl-search-input"
                        class="scl-search-input"
                        placeholder="<?php esc_attr_e('Buscar establecimiento...', 'simple-cards-listings'); ?>"
                        autocomplete="off">
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

        <!-- Datos para JavaScript -->
        <script type="application/json" id="scl-establecimientos-data">
            <?php
            $data = array();
            if ($establecimientos->have_posts()) {
                $establecimientos->rewind_posts();
                while ($establecimientos->have_posts()) {
                    $establecimientos->the_post();
                    $post_id = get_the_ID();

                    // Obtener términos
                    $cats = wp_get_post_terms($post_id, 'categoria_establecimiento', array('fields' => 'names'));
                    $tags_terms = wp_get_post_terms($post_id, 'tag_busqueda', array('fields' => 'names'));

                    $data[] = array(
                        'id'          => $post_id,
                        'title'       => get_the_title(),
                        'description' => get_the_excerpt(),
                        'categories'  => is_array($cats) ? $cats : array(),
                        'tags'        => is_array($tags_terms) ? $tags_terms : array(),
                    );
                }
                wp_reset_postdata();
            }
            echo wp_json_encode($data);
            ?>
        </script>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderizar item de la carta (logo)
     *
     * @param int $post_id ID del post.
     * @return string
     */
    public static function render_card_item($post_id)
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

        ob_start();
    ?>
        <div class="scl-card-item" data-id="<?php echo esc_attr($post_id); ?>">
            <div class="scl-card-logo">
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($title); ?>">
            </div>
        </div>
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

        ob_start();
    ?>
        <div class="scl-dashboard-wrapper">
            <div class="scl-dashboard-header">
                <h2><?php esc_html_e('Mis Establecimientos', 'simple-cards-listings'); ?></h2>
                <a href="#scl-nuevo-establecimiento" class="scl-btn scl-btn-primary scl-btn-nuevo">
                    <?php esc_html_e('Solicitar nuevo establecimiento', 'simple-cards-listings'); ?>
                </a>
            </div>

            <?php if ($establecimientos->have_posts()) : ?>
                <div class="scl-dashboard-table-wrapper">
                    <table class="scl-dashboard-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Logo', 'simple-cards-listings'); ?></th>
                                <th><?php esc_html_e('Nombre', 'simple-cards-listings'); ?></th>
                                <th><?php esc_html_e('Estado', 'simple-cards-listings'); ?></th>
                                <th><?php esc_html_e('Fecha', 'simple-cards-listings'); ?></th>
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
                                    <td><?php echo get_the_date(); ?></td>
                                    <td class="scl-td-actions">
                                        <button type="button" class="scl-btn scl-btn-small scl-btn-edit" data-id="<?php echo esc_attr($post_id); ?>">
                                            <?php esc_html_e('Editar', 'simple-cards-listings'); ?>
                                        </button>
                                        <?php if ('publish' === $status) : ?>
                                            <button type="button" class="scl-btn scl-btn-small scl-btn-view" data-id="<?php echo esc_attr($post_id); ?>">
                                                <?php esc_html_e('Ver', 'simple-cards-listings'); ?>
                                            </button>
                                        <?php endif; ?>
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

            <!-- Sección para solicitar nuevo establecimiento -->
            <div id="scl-nuevo-establecimiento" class="scl-dashboard-section">
                <?php echo self::render_solicitud_form(array()); ?>
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
}
