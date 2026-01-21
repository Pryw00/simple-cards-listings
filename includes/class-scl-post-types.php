<?php
/**
 * Registro del Custom Post Type Establecimiento
 *
 * @package SimpleCardsListings
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para registrar el CPT Establecimiento
 */
class SCL_Post_Types {

    /**
     * Inicializar registro del CPT
     */
    public static function init() {
        self::register_establecimiento_cpt();
    }

    /**
     * Registrar Custom Post Type Establecimiento
     */
    public static function register_establecimiento_cpt() {
        $labels = array(
            'name'                  => _x( 'Establecimientos', 'Post type general name', 'simple-cards-listings' ),
            'singular_name'         => _x( 'Establecimiento', 'Post type singular name', 'simple-cards-listings' ),
            'menu_name'             => _x( 'Establecimientos', 'Admin Menu text', 'simple-cards-listings' ),
            'name_admin_bar'        => _x( 'Establecimiento', 'Add New on Toolbar', 'simple-cards-listings' ),
            'add_new'               => __( 'Añadir nuevo', 'simple-cards-listings' ),
            'add_new_item'          => __( 'Añadir nuevo establecimiento', 'simple-cards-listings' ),
            'new_item'              => __( 'Nuevo establecimiento', 'simple-cards-listings' ),
            'edit_item'             => __( 'Editar establecimiento', 'simple-cards-listings' ),
            'view_item'             => __( 'Ver establecimiento', 'simple-cards-listings' ),
            'all_items'             => __( 'Todos los establecimientos', 'simple-cards-listings' ),
            'search_items'          => __( 'Buscar establecimientos', 'simple-cards-listings' ),
            'parent_item_colon'     => __( 'Establecimiento padre:', 'simple-cards-listings' ),
            'not_found'             => __( 'No se encontraron establecimientos.', 'simple-cards-listings' ),
            'not_found_in_trash'    => __( 'No hay establecimientos en la papelera.', 'simple-cards-listings' ),
            'featured_image'        => _x( 'Logo del establecimiento', 'Overrides the "Featured Image"', 'simple-cards-listings' ),
            'set_featured_image'    => _x( 'Establecer logo', 'Overrides the "Set featured image"', 'simple-cards-listings' ),
            'remove_featured_image' => _x( 'Quitar logo', 'Overrides the "Remove featured image"', 'simple-cards-listings' ),
            'use_featured_image'    => _x( 'Usar como logo', 'Overrides the "Use as featured image"', 'simple-cards-listings' ),
            'archives'              => _x( 'Archivo de establecimientos', 'The post type archive label', 'simple-cards-listings' ),
            'insert_into_item'      => _x( 'Insertar en establecimiento', 'Overrides the "Insert into post"', 'simple-cards-listings' ),
            'uploaded_to_this_item' => _x( 'Subido a este establecimiento', 'Overrides the "Uploaded to this post"', 'simple-cards-listings' ),
            'filter_items_list'     => _x( 'Filtrar lista de establecimientos', 'Screen reader text', 'simple-cards-listings' ),
            'items_list_navigation' => _x( 'Navegación de lista de establecimientos', 'Screen reader text', 'simple-cards-listings' ),
            'items_list'            => _x( 'Lista de establecimientos', 'Screen reader text', 'simple-cards-listings' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'establecimiento' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 25,
            'menu_icon'          => 'dashicons-store',
            'supports'           => array( 'title', 'editor', 'thumbnail', 'author' ),
            'show_in_rest'       => true,
        );

        register_post_type( 'establecimiento', $args );
    }
}
