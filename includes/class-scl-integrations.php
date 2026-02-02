<?php

/**
 * Clase de integración con otros plugins
 *
 * Esta clase expone funcionalidad de Simple Cards Listings
 * para que otros plugins puedan interactuar con establecimientos
 *
 * @package SimpleCardsListings
 * @since 1.1.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase de integración entre plugins
 */
class SCL_Integrations
{

    /**
     * Constructor
     */
    public function __construct()
    {
        // Inicializar integraciones cuando todos los plugins estén cargados
        add_action('plugins_loaded', array($this, 'init_integrations'), 20);
    }

    /**
     * Inicializar integraciones con otros plugins
     */
    public function init_integrations()
    {
        // Integración con Event Show
        if ($this->is_event_show_active()) {
            // Agregar columna de eventos en la lista de establecimientos
            add_filter('manage_establecimiento_posts_columns', array($this, 'add_events_column'));
            add_action('manage_establecimiento_posts_custom_column', array($this, 'render_events_column'), 10, 2);
        }

        // Hook para que otros plugins puedan extender las integraciones
        do_action('scl_integrations_loaded', $this);
    }

    /**
     * Verificar si Event Show está activo
     *
     * @return bool
     */
    public function is_event_show_active()
    {
        return class_exists('Event_Show');
    }

    /**
     * Agregar columna de eventos organizados
     *
     * @param array $columns
     * @return array
     */
    public function add_events_column($columns)
    {
        $new_columns = array();

        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;

            // Agregar después del título
            if ($key === 'title') {
                $new_columns['organized_events'] = __('Eventos Organizados', 'simple-cards-listings');
            }
        }

        return $new_columns;
    }

    /**
     * Renderizar columna de eventos organizados
     *
     * @param string $column_name
     * @param int    $post_id
     */
    public function render_events_column($column_name, $post_id)
    {
        if ($column_name === 'organized_events') {
            // Verificar si Event_Show_Integrations existe
            if (class_exists('Event_Show_Integrations')) {
                $events = Event_Show_Integrations::get_events_by_organizer($post_id);

                if (!empty($events)) {
                    echo '<strong>' . count($events) . '</strong> evento(s)';
                    echo '<br><small>';

                    $event_titles = array_slice($events, 0, 3);
                    foreach ($event_titles as $event) {
                        echo '• ' . esc_html($event->post_title) . '<br>';
                    }

                    if (count($events) > 3) {
                        echo '• ' . sprintf(__('y %d más...', 'simple-cards-listings'), count($events) - 3);
                    }

                    echo '</small>';
                } else {
                    echo '—';
                }
            } else {
                echo '—';
            }
        }
    }

    /**
     * Obtener establecimientos disponibles
     *
     * Método público para que otros plugins puedan obtener establecimientos
     *
     * @param array $args Argumentos para la consulta
     * @return WP_Post[]
     */
    public static function get_establecimientos($args = array())
    {
        $defaults = array(
            'post_type'      => 'establecimiento',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        $args = wp_parse_args($args, $defaults);

        return get_posts($args);
    }

    /**
     * Obtener información completa de un establecimiento
     *
     * @param int $establecimiento_id
     * @return array|null
     */
    public static function get_establecimiento_data($establecimiento_id)
    {
        $establecimiento = get_post($establecimiento_id);

        if (!$establecimiento || $establecimiento->post_type !== 'establecimiento') {
            return null;
        }

        $data = array(
            'id'          => $establecimiento->ID,
            'title'       => $establecimiento->post_title,
            'content'     => $establecimiento->post_content,
            'url'         => get_permalink($establecimiento->ID),
            'logo'        => get_the_post_thumbnail_url($establecimiento->ID, 'medium'),
            'author_id'   => $establecimiento->post_author,
        );

        // Obtener metadatos personalizados si existen
        $meta_fields = array(
            'direccion',
            'telefono',
            'email',
            'horario',
            'website',
            'categoria',
        );

        foreach ($meta_fields as $field) {
            $meta_value = get_post_meta($establecimiento_id, '_scl_' . $field, true);
            if (!empty($meta_value)) {
                $data[$field] = $meta_value;
            }
        }

        return apply_filters('scl_establecimiento_data', $data, $establecimiento_id);
    }

    /**
     * Verificar si un usuario es propietario de un establecimiento
     *
     * @param int $user_id
     * @param int $establecimiento_id
     * @return bool
     */
    public static function is_user_owner($user_id, $establecimiento_id)
    {
        $establecimiento = get_post($establecimiento_id);

        if (!$establecimiento || $establecimiento->post_type !== 'establecimiento') {
            return false;
        }

        return (int) $establecimiento->post_author === (int) $user_id;
    }

    /**
     * Obtener establecimientos de un usuario
     *
     * @param int   $user_id
     * @param array $args Argumentos adicionales
     * @return WP_Post[]
     */
    public static function get_user_establecimientos($user_id, $args = array())
    {
        $defaults = array(
            'post_type'      => 'establecimiento',
            'posts_per_page' => -1,
            'post_status'    => array('publish', 'draft', 'pending'),
            'author'         => $user_id,
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        $args = wp_parse_args($args, $defaults);

        return get_posts($args);
    }

    /**
     * Agregar hook cuando un establecimiento es asignado como organizador
     *
     * Este método puede ser usado por otros desarrolladores para extender funcionalidad
     */
    public static function on_assigned_as_organizer($event_id, $establecimiento_id)
    {
        // Incrementar contador de eventos organizados
        $count = get_post_meta($establecimiento_id, '_scl_organized_events_count', true);
        $count = $count ? (int) $count + 1 : 1;
        update_post_meta($establecimiento_id, '_scl_organized_events_count', $count);

        // Hook para que otros plugins puedan ejecutar acciones
        do_action('scl_assigned_as_organizer', $event_id, $establecimiento_id);
    }
}

// Hook para conectar con Event Show cuando se asigna un organizador
add_action('event_show_organizer_assigned', array('SCL_Integrations', 'on_assigned_as_organizer'), 10, 2);
