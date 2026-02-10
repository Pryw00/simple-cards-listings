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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
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
     * Encolar scripts y estilos del admin
     *
     * @param string $hook Current page hook.
     */
    public function enqueue_admin_assets($hook)
    {
        // Solo cargar en la página de configuración
        if ('establecimiento_page_scl-settings' !== $hook) {
            return;
        }

        // Encolar color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        // Encolar media uploader
        wp_enqueue_media();
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

        // Sección de personalización de modales
        add_settings_section(
            'scl_modal_section',
            __('Personalización de Modales', 'simple-cards-listings'),
            array($this, 'render_modal_section'),
            'scl_settings'
        );

        // Campo: tipo de fondo del modal
        add_settings_field(
            'scl_modal_background_type',
            __('Tipo de fondo del modal', 'simple-cards-listings'),
            array($this, 'render_modal_background_type_field'),
            'scl_settings',
            'scl_modal_section'
        );

        // Campo: color de fondo del modal
        add_settings_field(
            'scl_modal_background_color',
            __('Color de fondo', 'simple-cards-listings'),
            array($this, 'render_modal_background_color_field'),
            'scl_settings',
            'scl_modal_section'
        );

        // Campo: imagen de fondo del modal
        add_settings_field(
            'scl_modal_background_image',
            __('Imagen de fondo', 'simple-cards-listings'),
            array($this, 'render_modal_background_image_field'),
            'scl_settings',
            'scl_modal_section'
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

        // Sanitizar opciones de fondo del modal
        if (isset($input['modal_background_type'])) {
            $allowed_types = array('color', 'image');
            $sanitized['modal_background_type'] = in_array($input['modal_background_type'], $allowed_types, true) ? $input['modal_background_type'] : 'color';
        }

        if (isset($input['modal_background_color'])) {
            $color = sanitize_text_field($input['modal_background_color']);
            // Validar que sea un color hexadecimal válido
            if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
                $sanitized['modal_background_color'] = $color;
            } else {
                $sanitized['modal_background_color'] = '#ece6ce';
            }
        }

        if (isset($input['modal_background_image'])) {
            $sanitized['modal_background_image'] = absint($input['modal_background_image']);
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
     * Renderizar sección de modales
     */
    public function render_modal_section()
    {
        echo '<p>' . esc_html__('Personaliza el aspecto de los modales de información de establecimientos.', 'simple-cards-listings') . '</p>';
    }

    /**
     * Renderizar campo de tipo de fondo del modal
     */
    public function render_modal_background_type_field()
    {
        $options = get_option('scl_options', array());
        $value = isset($options['modal_background_type']) ? $options['modal_background_type'] : 'color';
    ?>
        <select name="scl_options[modal_background_type]" id="scl_modal_background_type">
            <option value="color" <?php selected($value, 'color'); ?>>
                <?php esc_html_e('Color sólido', 'simple-cards-listings'); ?>
            </option>
            <option value="image" <?php selected($value, 'image'); ?>>
                <?php esc_html_e('Imagen de fondo', 'simple-cards-listings'); ?>
            </option>
        </select>
        <p class="description"><?php esc_html_e('Selecciona si deseas usar un color sólido o una imagen de fondo.', 'simple-cards-listings'); ?></p>
        <script>
            jQuery(document).ready(function($) {
                function toggleModalBackgroundFields() {
                    var type = $('#scl_modal_background_type').val();
                    if (type === 'color') {
                        $('#scl_modal_background_color').closest('tr').show();
                        $('#scl_modal_background_image').closest('tr').hide();
                    } else {
                        $('#scl_modal_background_color').closest('tr').hide();
                        $('#scl_modal_background_image').closest('tr').show();
                    }
                }
                $('#scl_modal_background_type').on('change', toggleModalBackgroundFields);
                toggleModalBackgroundFields();
            });
        </script>
    <?php
    }

    /**
     * Renderizar campo de color de fondo del modal
     */
    public function render_modal_background_color_field()
    {
        $options = get_option('scl_options', array());
        $value = isset($options['modal_background_color']) ? $options['modal_background_color'] : '#ece6ce';
    ?>
        <input type="text" name="scl_options[modal_background_color]" id="scl_modal_background_color" value="<?php echo esc_attr($value); ?>" class="scl-color-picker" data-default-color="#ece6ce">
        <p class="description"><?php esc_html_e('Color de fondo en formato hexadecimal (ejemplo: #ece6ce).', 'simple-cards-listings'); ?></p>
        <script>
            jQuery(document).ready(function($) {
                $('.scl-color-picker').wpColorPicker();
            });
        </script>
    <?php
    }

    /**
     * Renderizar campo de imagen de fondo del modal
     */
    public function render_modal_background_image_field()
    {
        $options = get_option('scl_options', array());
        $image_id = isset($options['modal_background_image']) ? $options['modal_background_image'] : 0;
        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
    ?>
        <div class="scl-image-upload-wrapper">
            <input type="hidden" name="scl_options[modal_background_image]" id="scl_modal_background_image" value="<?php echo esc_attr($image_id); ?>">
            <button type="button" class="button scl-upload-image-button">
                <?php esc_html_e('Seleccionar imagen', 'simple-cards-listings'); ?>
            </button>
            <button type="button" class="button scl-remove-image-button" style="<?php echo $image_id ? '' : 'display:none;'; ?>">
                <?php esc_html_e('Remover imagen', 'simple-cards-listings'); ?>
            </button>
            <div class="scl-image-preview" style="margin-top: 10px; <?php echo $image_id ? '' : 'display:none;'; ?>">
                <img src="<?php echo esc_url($image_url); ?>" style="max-width: 300px; height: auto; border: 1px solid #ddd;">
            </div>
            <p class="description"><?php esc_html_e('Imagen de fondo para los modales. Se recomienda una imagen con suficiente contraste para la legibilidad del texto.', 'simple-cards-listings'); ?></p>
        </div>
        <script>
            jQuery(document).ready(function($) {
                var mediaUploader;

                $('.scl-upload-image-button').on('click', function(e) {
                    e.preventDefault();

                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }

                    mediaUploader = wp.media({
                        title: '<?php esc_html_e('Seleccionar imagen de fondo', 'simple-cards-listings'); ?>',
                        button: {
                            text: '<?php esc_html_e('Usar esta imagen', 'simple-cards-listings'); ?>'
                        },
                        multiple: false
                    });

                    mediaUploader.on('select', function() {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        $('#scl_modal_background_image').val(attachment.id);
                        $('.scl-image-preview img').attr('src', attachment.url);
                        $('.scl-image-preview').show();
                        $('.scl-remove-image-button').show();
                    });

                    mediaUploader.open();
                });

                $('.scl-remove-image-button').on('click', function(e) {
                    e.preventDefault();
                    $('#scl_modal_background_image').val('');
                    $('.scl-image-preview').hide();
                    $(this).hide();
                });
            });
        </script>
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
