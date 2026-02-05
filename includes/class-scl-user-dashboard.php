<?php

/**
 * Dashboard de usuario frontend
 *
 * @package SimpleCardsListings
 * @since 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar el dashboard de usuario
 */
class SCL_User_Dashboard
{

    /**
     * Inicializar dashboard
     */
    public static function init()
    {
        // Las funcionalidades principales están en los shortcodes y AJAX handlers
        // Esta clase maneja funcionalidades adicionales del dashboard

        add_action('wp_ajax_scl_get_user_stats', array(__CLASS__, 'get_user_stats'));
        add_action('wp_ajax_scl_get_user_cupones', array(__CLASS__, 'get_user_cupones'));
    }

    /**
     * Obtener estadísticas del usuario
     */
    public static function get_user_stats()
    {
        check_ajax_referer('scl_nonce', 'nonce');

        if (! is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Debes iniciar sesión.', 'simple-cards-listings')));
        }

        $user_id = get_current_user_id();

        $stats = array(
            'total'    => self::count_user_posts($user_id),
            'approved' => self::count_user_posts($user_id, 'publish'),
            'pending'  => self::count_user_posts($user_id, 'pending'),
            'draft'    => self::count_user_posts($user_id, 'draft'),
        );

        wp_send_json_success($stats);
    }

    /**
     * Contar posts del usuario
     *
     * @param int    $user_id ID del usuario.
     * @param string $status  Estado del post.
     * @return int
     */
    public static function count_user_posts($user_id, $status = '')
    {
        $args = array(
            'post_type'      => 'establecimiento',
            'author'         => $user_id,
            'posts_per_page' => -1,
            'fields'         => 'ids',
        );

        if ($status) {
            $args['post_status'] = $status;
        } else {
            $args['post_status'] = array('publish', 'pending', 'draft');
        }

        $query = new WP_Query($args);

        return $query->found_posts;
    }

    /**
     * Obtener establecimientos del usuario
     *
     * @param int    $user_id ID del usuario.
     * @param string $status  Estado del post.
     * @param int    $limit   Límite de resultados.
     * @return array
     */
    public static function get_user_establecimientos($user_id, $status = '', $limit = -1)
    {
        $args = array(
            'post_type'      => 'establecimiento',
            'author'         => $user_id,
            'posts_per_page' => $limit,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        if ($status) {
            $args['post_status'] = $status;
        } else {
            $args['post_status'] = array('publish', 'pending', 'draft');
        }

        $query = new WP_Query($args);

        $establecimientos = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();

                $logo_id = SCL_Metaboxes::get_meta($post_id, 'logo');

                $establecimientos[] = array(
                    'id'       => $post_id,
                    'title'    => get_the_title(),
                    'status'   => get_post_status(),
                    'date'     => get_the_date(),
                    'logo_url' => $logo_id ? wp_get_attachment_image_url($logo_id, 'thumbnail') : '',
                    'edit_url' => '#', // Manejado via AJAX
                    'view_url' => get_permalink(),
                );
            }
            wp_reset_postdata();
        }

        return $establecimientos;
    }

    /**
     * Verificar si el usuario tiene establecimientos
     *
     * @param int $user_id ID del usuario.
     * @return bool
     */
    public static function user_has_establecimientos($user_id)
    {
        return self::count_user_posts($user_id) > 0;
    }

    /**
     * Obtener cupones del usuario (de todos sus establecimientos)
     */
    public static function get_user_cupones()
    {
        check_ajax_referer('scl_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Debes iniciar sesión.', 'simple-cards-listings')));
        }

        $user_id = get_current_user_id();

        // Obtener IDs de establecimientos del usuario
        $establecimientos_query = new WP_Query(array(
            'post_type' => 'establecimiento',
            'author' => $user_id,
            'posts_per_page' => -1,
            'fields' => 'ids',
        ));

        $establecimiento_ids = $establecimientos_query->posts;

        if (empty($establecimiento_ids)) {
            wp_send_json_success(array(
                'cupones' => array(),
                'total' => 0,
            ));
        }

        // Obtener cupones de esos establecimientos
        $cupones_query = new WP_Query(array(
            'post_type' => 'promocion',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_scl_establecimiento_id',
                    'value' => $establecimiento_ids,
                    'compare' => 'IN',
                ),
            ),
            'orderby' => 'date',
            'order' => 'DESC',
        ));

        $cupones = array();

        if ($cupones_query->have_posts()) {
            while ($cupones_query->have_posts()) {
                $cupones_query->the_post();
                $cupon_id = get_the_ID();

                $establecimiento_id = get_post_meta($cupon_id, '_scl_establecimiento_id', true);
                $establecimiento = get_post($establecimiento_id);

                $fecha_inicio = get_post_meta($cupon_id, '_scl_fecha_inicio', true);
                $fecha_fin = get_post_meta($cupon_id, '_scl_fecha_fin', true);

                $ahora = current_time('timestamp');
                $fin_ts = $fecha_fin ? strtotime($fecha_fin) : 0;
                $estado = 'activo';

                if ($fin_ts > 0 && $fin_ts < $ahora) {
                    $estado = 'expirado';
                } elseif (get_post_status($cupon_id) !== 'publish') {
                    $estado = 'pendiente';
                }

                $cupones[] = array(
                    'id' => $cupon_id,
                    'titulo' => get_the_title(),
                    'establecimiento' => $establecimiento ? $establecimiento->post_title : '',
                    'fecha_inicio' => $fecha_inicio ? date_i18n(get_option('date_format'), strtotime($fecha_inicio)) : '',
                    'fecha_fin' => $fecha_fin ? date_i18n(get_option('date_format'), strtotime($fecha_fin)) : '',
                    'destacado' => get_post_meta($cupon_id, '_scl_destacado', true) == '1',
                    'imagen_url' => get_the_post_thumbnail_url($cupon_id, 'thumbnail'),
                    'estado' => $estado,
                    'can_edit' => current_user_can('edit_post', $cupon_id),
                    'can_delete' => current_user_can('delete_post', $cupon_id),
                );
            }
            wp_reset_postdata();
        }

        wp_send_json_success(array(
            'cupones' => $cupones,
            'total' => count($cupones),
        ));
    }
}
