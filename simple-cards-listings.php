<?php

/**
 * Plugin Name: Simple Cards Listings
 * Plugin URI: https://example.com/simple-cards-listings
 * Description: Plugin de directorio de cartas de contacto de negocios para WordPress, conforme a la especificación IEEE 830-1998.
 * Version: 1.0.6
 * Author: Pryw00
 * Author URI: https://example.com
 * Text Domain: simple-cards-listings
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package SimpleCardsListings
 */

// Evitar acceso directo
if (! defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('SCL_VERSION', '1.0.6');
define('SCL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SCL_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Clase principal del plugin Simple Cards Listings
 *
 * @since 1.0.0
 */
final class Simple_Cards_Listings
{

    /**
     * Instancia única del plugin
     *
     * @var Simple_Cards_Listings
     */
    private static $instance = null;

    /**
     * Obtener instancia única (Singleton)
     *
     * @return Simple_Cards_Listings
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct()
    {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Cargar archivos de dependencias
     */
    private function load_dependencies()
    {
        // Core
        require_once SCL_PLUGIN_DIR . 'includes/class-scl-post-types.php';
        require_once SCL_PLUGIN_DIR . 'includes/class-scl-taxonomies.php';
        require_once SCL_PLUGIN_DIR . 'includes/class-scl-metaboxes.php';
        require_once SCL_PLUGIN_DIR . 'includes/class-scl-shortcodes.php';
        require_once SCL_PLUGIN_DIR . 'includes/class-scl-ajax-handlers.php';
        require_once SCL_PLUGIN_DIR . 'includes/class-scl-notifications.php';
        require_once SCL_PLUGIN_DIR . 'includes/class-scl-logger.php';
        require_once SCL_PLUGIN_DIR . 'includes/class-scl-user-dashboard.php';
        require_once SCL_PLUGIN_DIR . 'includes/class-scl-permissions.php';

        // Admin
        if (is_admin()) {
            require_once SCL_PLUGIN_DIR . 'includes/admin/class-scl-admin.php';
        }
    }

    /**
     * Inicializar hooks principales
     */
    private function init_hooks()
    {
        // Activación y desactivación
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Inicialización
        add_action('init', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Inicializar componentes
        add_action('init', array('SCL_Post_Types', 'init'));
        add_action('init', array('SCL_Taxonomies', 'init'));
        add_action('add_meta_boxes', array('SCL_Metaboxes', 'register'));
        add_action('save_post', array('SCL_Metaboxes', 'save'), 10, 2);

        // Shortcodes
        SCL_Shortcodes::init();

        // Ajax handlers
        SCL_Ajax_Handlers::init();

        // Logger
        SCL_Logger::init();

        // User Dashboard
        SCL_User_Dashboard::init();
    }

    /**
     * Activar plugin
     */
    public function activate()
    {
        // Crear tabla de logs
        $this->create_logs_table();

        // Registrar CPT y taxonomías
        SCL_Post_Types::init();
        SCL_Taxonomies::init();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Crear página de dashboard de usuario
        $this->create_user_dashboard_page();

        // Log de activación
        SCL_Logger::log('plugin_activated', __('Plugin Simple Cards Listings activado', 'simple-cards-listings'));
    }

    /**
     * Desactivar plugin
     */
    public function deactivate()
    {
        flush_rewrite_rules();
        SCL_Logger::log('plugin_deactivated', __('Plugin Simple Cards Listings desactivado', 'simple-cards-listings'));
    }

    /**
     * Crear tabla de logs
     */
    private function create_logs_table()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'scl_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            action varchar(100) NOT NULL,
            message text NOT NULL,
            user_id bigint(20) DEFAULT 0,
            object_id bigint(20) DEFAULT 0,
            object_type varchar(50) DEFAULT '',
            ip_address varchar(100) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY action (action),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Crear página de dashboard de usuario
     */
    private function create_user_dashboard_page()
    {
        $page_exists = get_page_by_path('mi-cuenta-establecimientos');

        if (! $page_exists) {
            wp_insert_post(array(
                'post_title'   => __('Mi Cuenta - Establecimientos', 'simple-cards-listings'),
                'post_name'    => 'mi-cuenta-establecimientos',
                'post_content' => '[scl_user_dashboard]',
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ));
        }
    }

    /**
     * Cargar traducciones
     */
    public function load_textdomain()
    {
        load_plugin_textdomain(
            'simple-cards-listings',
            false,
            dirname(SCL_PLUGIN_BASENAME) . '/languages/'
        );
    }

    /**
     * Encolar assets del frontend
     */
    public function enqueue_frontend_assets()
    {
        // CSS
        wp_enqueue_style(
            'scl-frontend',
            SCL_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            SCL_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'scl-frontend',
            SCL_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            SCL_VERSION,
            true
        );

        // Pasar variables a JavaScript
        wp_localize_script('scl-frontend', 'scl_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('scl_nonce'),
            'i18n'     => array(
                'loading'       => __('Cargando...', 'simple-cards-listings'),
                'error'         => __('Ha ocurrido un error', 'simple-cards-listings'),
                'no_results'    => __('No se encontraron resultados', 'simple-cards-listings'),
                'close'         => __('Cerrar', 'simple-cards-listings'),
                'confirm_delete' => __('¿Estás seguro de eliminar este establecimiento?', 'simple-cards-listings'),
            ),
        ));
    }

    /**
     * Encolar assets del admin
     */
    public function enqueue_admin_assets($hook)
    {
        global $post_type;

        // Solo cargar en páginas del plugin
        if ('establecimiento' !== $post_type && strpos($hook, 'scl') === false) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style(
            'scl-admin',
            SCL_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SCL_VERSION
        );

        wp_enqueue_script(
            'scl-admin',
            SCL_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-media-utils'),
            SCL_VERSION,
            true
        );

        wp_localize_script('scl-admin', 'scl_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('scl_admin_nonce'),
            'i18n'     => array(
                'select_image' => __('Seleccionar imagen', 'simple-cards-listings'),
                'select_file'  => __('Seleccionar archivo', 'simple-cards-listings'),
                'use_image'    => __('Usar esta imagen', 'simple-cards-listings'),
                'use_file'     => __('Usar este archivo', 'simple-cards-listings'),
            ),
        ));
    }
}

/**
 * Inicializar plugin
 *
 * @return Simple_Cards_Listings
 */
function simple_cards_listings()
{
    return Simple_Cards_Listings::get_instance();
}

// Iniciar el plugin
simple_cards_listings();
