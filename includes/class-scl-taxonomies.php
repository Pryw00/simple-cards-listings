<?php

/**
 * Registro de Taxonomías personalizadas
 *
 * @package SimpleCardsListings
 * @since 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase para registrar taxonomías del plugin
 */
class SCL_Taxonomies
{

    /**
     * Inicializar registro de taxonomías
     */
    public static function init()
    {
        self::register_categoria_establecimiento();
        self::register_tags_busqueda();
    }

    /**
     * Registrar taxonomía Categoría de Establecimiento
     */
    public static function register_categoria_establecimiento()
    {
        $labels = array(
            'name'                       => _x('Categorías', 'taxonomy general name', 'simple-cards-listings'),
            'singular_name'              => _x('Categoría', 'taxonomy singular name', 'simple-cards-listings'),
            'search_items'               => __('Buscar categorías', 'simple-cards-listings'),
            'popular_items'              => __('Categorías populares', 'simple-cards-listings'),
            'all_items'                  => __('Todas las categorías', 'simple-cards-listings'),
            'parent_item'                => __('Categoría padre', 'simple-cards-listings'),
            'parent_item_colon'          => __('Categoría padre:', 'simple-cards-listings'),
            'edit_item'                  => __('Editar categoría', 'simple-cards-listings'),
            'view_item'                  => __('Ver categoría', 'simple-cards-listings'),
            'update_item'                => __('Actualizar categoría', 'simple-cards-listings'),
            'add_new_item'               => __('Añadir nueva categoría', 'simple-cards-listings'),
            'new_item_name'              => __('Nombre de nueva categoría', 'simple-cards-listings'),
            'separate_items_with_commas' => __('Separar categorías con comas', 'simple-cards-listings'),
            'add_or_remove_items'        => __('Añadir o quitar categorías', 'simple-cards-listings'),
            'choose_from_most_used'      => __('Elegir de las más usadas', 'simple-cards-listings'),
            'not_found'                  => __('No se encontraron categorías.', 'simple-cards-listings'),
            'no_terms'                   => __('No hay categorías', 'simple-cards-listings'),
            'menu_name'                  => __('Categorías', 'simple-cards-listings'),
            'items_list_navigation'      => __('Navegación de lista de categorías', 'simple-cards-listings'),
            'items_list'                 => __('Lista de categorías', 'simple-cards-listings'),
            'back_to_items'              => __('← Volver a categorías', 'simple-cards-listings'),
        );

        $args = array(
            'labels'            => $labels,
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud'     => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'categoria-establecimiento'),
        );

        register_taxonomy('categoria_establecimiento', array('establecimiento'), $args);
    }

    /**
     * Registrar taxonomía Tags de Búsqueda
     */
    public static function register_tags_busqueda()
    {
        $labels = array(
            'name'                       => _x('Tags de Búsqueda', 'taxonomy general name', 'simple-cards-listings'),
            'singular_name'              => _x('Tag de Búsqueda', 'taxonomy singular name', 'simple-cards-listings'),
            'search_items'               => __('Buscar tags', 'simple-cards-listings'),
            'popular_items'              => __('Tags populares', 'simple-cards-listings'),
            'all_items'                  => __('Todos los tags', 'simple-cards-listings'),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __('Editar tag', 'simple-cards-listings'),
            'view_item'                  => __('Ver tag', 'simple-cards-listings'),
            'update_item'                => __('Actualizar tag', 'simple-cards-listings'),
            'add_new_item'               => __('Añadir nuevo tag', 'simple-cards-listings'),
            'new_item_name'              => __('Nombre del nuevo tag', 'simple-cards-listings'),
            'separate_items_with_commas' => __('Separar tags con comas', 'simple-cards-listings'),
            'add_or_remove_items'        => __('Añadir o quitar tags', 'simple-cards-listings'),
            'choose_from_most_used'      => __('Elegir de los más usados', 'simple-cards-listings'),
            'not_found'                  => __('No se encontraron tags.', 'simple-cards-listings'),
            'no_terms'                   => __('No hay tags', 'simple-cards-listings'),
            'menu_name'                  => __('Tags de Búsqueda', 'simple-cards-listings'),
            'items_list_navigation'      => __('Navegación de lista de tags', 'simple-cards-listings'),
            'items_list'                 => __('Lista de tags', 'simple-cards-listings'),
            'back_to_items'              => __('← Volver a tags', 'simple-cards-listings'),
        );

        $args = array(
            'labels'            => $labels,
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud'     => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'tag-busqueda'),
        );

        register_taxonomy('tag_busqueda', array('establecimiento'), $args);
    }
}
