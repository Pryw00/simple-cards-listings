<?php

/**
 * Funcionalidades de administración
 *
 * @package SimpleCardsListings
 * @since 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar el área de administración
 */
class SCL_Admin
{

    /**
     * Instancia única
     *
     * @var SCL_Admin
     */
    private static $instance = null;

    /**
     * Obtener instancia
     *
     * @return SCL_Admin
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('manage_establecimiento_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_establecimiento_posts_custom_column', array($this, 'render_custom_columns'), 10, 2);
    }

    /**
     * Agregar menú de administración
     */
    public function add_admin_menu()
    {
        // Submenú bajo Establecimientos
        add_submenu_page(
            'edit.php?post_type=establecimiento',
            __('Configuración', 'simple-cards-listings'),
            __('Configuración', 'simple-cards-listings'),
            'manage_options',
            'scl-settings',
            array($this, 'render_settings_page')
        );

        // Submenú para logs
        add_submenu_page(
            'edit.php?post_type=establecimiento',
            __('Registro de Actividad', 'simple-cards-listings'),
            __('Logs', 'simple-cards-listings'),
            'manage_options',
            'scl-logs',
            array($this, 'render_logs_page')
        );
    }

    /**
     * Registrar configuraciones
     */
    public function register_settings()
    {
        register_setting('scl_settings', 'scl_options', array(
            'type'              => 'array',
            'sanitize_callback' => array($this, 'sanitize_options'),
        ));

        // Sección general
        add_settings_section(
            'scl_general_section',
            __('Configuración General', 'simple-cards-listings'),
            array($this, 'render_general_section'),
            'scl_settings'
        );

        // Campo: email de notificaciones
        add_settings_field(
            'scl_notification_email',
            __('Email para notificaciones', 'simple-cards-listings'),
            array($this, 'render_email_field'),
            'scl_settings',
            'scl_general_section'
        );

        // Campo: columnas del grid
        add_settings_field(
            'scl_grid_columns',
            __('Columnas del grid', 'simple-cards-listings'),
            array($this, 'render_columns_field'),
            'scl_settings',
            'scl_general_section'
        );

        // Campo: días para mantener logs
        add_settings_field(
            'scl_logs_retention',
            __('Días para mantener logs', 'simple-cards-listings'),
            array($this, 'render_logs_retention_field'),
            'scl_settings',
            'scl_general_section'
        );
    }

    /**
     * Sanitizar opciones
     *
     * @param array $input Datos de entrada.
     * @return array
     */
    public function sanitize_options($input)
    {
        $sanitized = array();

        if (isset($input['notification_email'])) {
            $sanitized['notification_email'] = sanitize_email($input['notification_email']);
        }

        if (isset($input['grid_columns'])) {
            $sanitized['grid_columns'] = absint($input['grid_columns']);
            if ($sanitized['grid_columns'] < 1 || $sanitized['grid_columns'] > 6) {
                $sanitized['grid_columns'] = 3;
            }
        }

        if (isset($input['logs_retention'])) {
            $sanitized['logs_retention'] = absint($input['logs_retention']);
            if ($sanitized['logs_retention'] < 7) {
                $sanitized['logs_retention'] = 90;
            }
        }

        return $sanitized;
    }

    /**
     * Renderizar sección general
     */
    public function render_general_section()
    {
        echo '<p>' . esc_html__('Configura las opciones generales del plugin.', 'simple-cards-listings') . '</p>';
    }

    /**
     * Renderizar campo de email
     */
    public function render_email_field()
    {
        $options = get_option('scl_options', array());
        $value = isset($options['notification_email']) ? $options['notification_email'] : get_option('admin_email');
?>
        <input type="email" name="scl_options[notification_email]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php esc_html_e('Email donde se enviarán las notificaciones de nuevas solicitudes.', 'simple-cards-listings'); ?></p>
    <?php
    }

    /**
     * Renderizar campo de columnas
     */
    public function render_columns_field()
    {
        $options = get_option('scl_options', array());
        $value = isset($options['grid_columns']) ? $options['grid_columns'] : 3;
    ?>
        <select name="scl_options[grid_columns]">
            <?php for ($i = 2; $i <= 6; $i++) : ?>
                <option value="<?php echo esc_attr($i); ?>" <?php selected($value, $i); ?>>
                    <?php echo esc_html($i); ?>
                </option>
            <?php endfor; ?>
        </select>
        <p class="description"><?php esc_html_e('Número de columnas para el grid de establecimientos.', 'simple-cards-listings'); ?></p>
    <?php
    }

    /**
     * Renderizar campo de retención de logs
     */
    public function render_logs_retention_field()
    {
        $options = get_option('scl_options', array());
        $value = isset($options['logs_retention']) ? $options['logs_retention'] : 90;
    ?>
        <input type="number" name="scl_options[logs_retention]" value="<?php echo esc_attr($value); ?>" min="7" max="365" class="small-text">
        <p class="description"><?php esc_html_e('Número de días para mantener los registros de actividad.', 'simple-cards-listings'); ?></p>
    <?php
    }

    /**
     * Renderizar página de configuración
     */
    public function render_settings_page()
    {
        if (! current_user_can('manage_options')) {
            return;
        }
    ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('scl_settings');
                do_settings_sections('scl_settings');
                submit_button();
                ?>
            </form>

            <hr>

            <h2><?php esc_html_e('Shortcodes disponibles', 'simple-cards-listings'); ?></h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Shortcode', 'simple-cards-listings'); ?></th>
                        <th><?php esc_html_e('Descripción', 'simple-cards-listings'); ?></th>
                        <th><?php esc_html_e('Parámetros', 'simple-cards-listings'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[scl_grid]</code></td>
                        <td><?php esc_html_e('Muestra el grid de establecimientos con buscador.', 'simple-cards-listings'); ?></td>
                        <td>
                            <code>categoria=""</code> - <?php esc_html_e('Filtrar por slug de categoría', 'simple-cards-listings'); ?><br>
                            <code>limit="-1"</code> - <?php esc_html_e('Límite de resultados (-1 para todos)', 'simple-cards-listings'); ?><br>
                            <code>columns="3"</code> - <?php esc_html_e('Número de columnas', 'simple-cards-listings'); ?>
                        </td>
                    </tr>
                    <tr>
                        <td><code>[scl_solicitud]</code></td>
                        <td><?php esc_html_e('Formulario de solicitud de nuevo establecimiento (solo usuarios registrados).', 'simple-cards-listings'); ?></td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td><code>[scl_user_dashboard]</code></td>
                        <td><?php esc_html_e('Panel de usuario para gestionar sus establecimientos.', 'simple-cards-listings'); ?></td>
                        <td>-</td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php
    }

    /**
     * Renderizar página de logs
     */
    public function render_logs_page()
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $per_page = 20;

        $logs = SCL_Logger::get_logs(array(
            'per_page' => $per_page,
            'page'     => $page,
        ));

        $total = SCL_Logger::count_logs();
        $total_pages = ceil($total / $per_page);
    ?>
        <div class="wrap">
            <h1><?php esc_html_e('Registro de Actividad', 'simple-cards-listings'); ?></h1>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Fecha', 'simple-cards-listings'); ?></th>
                        <th><?php esc_html_e('Acción', 'simple-cards-listings'); ?></th>
                        <th><?php esc_html_e('Mensaje', 'simple-cards-listings'); ?></th>
                        <th><?php esc_html_e('Usuario', 'simple-cards-listings'); ?></th>
                        <th><?php esc_html_e('IP', 'simple-cards-listings'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logs) : ?>
                        <?php foreach ($logs as $log) : ?>
                            <?php $user = get_userdata($log->user_id); ?>
                            <tr>
                                <td><?php echo esc_html($log->created_at); ?></td>
                                <td><code><?php echo esc_html($log->action); ?></code></td>
                                <td><?php echo esc_html($log->message); ?></td>
                                <td><?php echo $user ? esc_html($user->display_name) : '-'; ?></td>
                                <td><?php echo esc_html($log->ip_address); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5"><?php esc_html_e('No hay registros.', 'simple-cards-listings'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1) : ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links(array(
                            'base'      => add_query_arg('paged', '%#%'),
                            'format'    => '',
                            'prev_text' => __('&laquo; Anterior', 'simple-cards-listings'),
                            'next_text' => __('Siguiente &raquo;', 'simple-cards-listings'),
                            'total'     => $total_pages,
                            'current'   => $page,
                        ));
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
<?php
    }

    /**
     * Añadir columnas personalizadas
     *
     * @param array $columns Columnas existentes.
     * @return array
     */
    public function add_custom_columns($columns)
    {
        $new_columns = array();

        foreach ($columns as $key => $value) {
            if ('title' === $key) {
                $new_columns['scl_logo'] = __('Logo', 'simple-cards-listings');
            }
            $new_columns[$key] = $value;
        }

        $new_columns['scl_whatsapp'] = __('WhatsApp', 'simple-cards-listings');

        return $new_columns;
    }

    /**
     * Renderizar columnas personalizadas
     *
     * @param string $column  Nombre de la columna.
     * @param int    $post_id ID del post.
     */
    public function render_custom_columns($column, $post_id)
    {
        switch ($column) {
            case 'scl_logo':
                $logo_id = SCL_Metaboxes::get_meta($post_id, 'logo');
                if ($logo_id) {
                    $logo_url = wp_get_attachment_image_url($logo_id, 'thumbnail');
                    echo '<img src="' . esc_url($logo_url) . '" style="max-width: 50px; max-height: 50px;">';
                } else {
                    echo '-';
                }
                break;

            case 'scl_whatsapp':
                $whatsapp = SCL_Metaboxes::get_meta($post_id, 'whatsapp');
                echo $whatsapp ? esc_html($whatsapp) : '-';
                break;
        }
    }
}

// Inicializar
SCL_Admin::get_instance();
