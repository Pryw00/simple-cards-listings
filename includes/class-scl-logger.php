<?php
/**
 * Sistema de logs
 *
 * @package SimpleCardsListings
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para gestionar los logs del plugin
 */
class SCL_Logger {

    /**
     * Nombre de la tabla de logs
     *
     * @var string
     */
    private static $table_name;

    /**
     * Inicializar logger
     */
    public static function init() {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'scl_logs';

        // Hook para registrar acciones
        add_action( 'save_post_establecimiento', array( __CLASS__, 'log_establecimiento_save' ), 20, 3 );
        add_action( 'delete_post', array( __CLASS__, 'log_post_delete' ) );
        add_action( 'created_categoria_establecimiento', array( __CLASS__, 'log_term_created' ), 10, 2 );
        add_action( 'edited_categoria_establecimiento', array( __CLASS__, 'log_term_edited' ), 10, 2 );
        add_action( 'delete_categoria_establecimiento', array( __CLASS__, 'log_term_deleted' ), 10, 4 );
        add_action( 'created_tag_busqueda', array( __CLASS__, 'log_tag_created' ), 10, 2 );
        add_action( 'edited_tag_busqueda', array( __CLASS__, 'log_tag_edited' ), 10, 2 );
        add_action( 'delete_tag_busqueda', array( __CLASS__, 'log_tag_deleted' ), 10, 4 );
    }

    /**
     * Registrar un log
     *
     * @param string $action      Acción realizada.
     * @param string $message     Mensaje descriptivo.
     * @param int    $object_id   ID del objeto relacionado.
     * @param string $object_type Tipo de objeto.
     */
    public static function log( $action, $message, $object_id = 0, $object_type = '' ) {
        global $wpdb;

        $user_id = get_current_user_id();
        $ip_address = self::get_client_ip();

        $data = array(
            'action'      => sanitize_key( $action ),
            'message'     => sanitize_text_field( $message ),
            'user_id'     => $user_id,
            'object_id'   => absint( $object_id ),
            'object_type' => sanitize_key( $object_type ),
            'ip_address'  => sanitize_text_field( $ip_address ),
            'created_at'  => current_time( 'mysql' ),
        );

        $format = array( '%s', '%s', '%d', '%d', '%s', '%s', '%s' );

        $wpdb->insert( self::$table_name, $data, $format );
    }

    /**
     * Obtener IP del cliente
     *
     * @return string
     */
    private static function get_client_ip() {
        $ip = '';

        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    /**
     * Log al guardar establecimiento
     *
     * @param int     $post_id ID del post.
     * @param WP_Post $post    Objeto del post.
     * @param bool    $update  Si es actualización.
     */
    public static function log_establecimiento_save( $post_id, $post, $update ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        $action = $update ? 'establecimiento_updated' : 'establecimiento_created';
        $message = $update
            ? sprintf(
                /* translators: %s: título del establecimiento */
                __( 'Establecimiento "%s" actualizado', 'simple-cards-listings' ),
                $post->post_title
            )
            : sprintf(
                /* translators: %s: título del establecimiento */
                __( 'Establecimiento "%s" creado', 'simple-cards-listings' ),
                $post->post_title
            );

        self::log( $action, $message, $post_id, 'establecimiento' );
    }

    /**
     * Log al eliminar post
     *
     * @param int $post_id ID del post.
     */
    public static function log_post_delete( $post_id ) {
        $post = get_post( $post_id );
        
        if ( ! $post || 'establecimiento' !== $post->post_type ) {
            return;
        }

        self::log(
            'establecimiento_deleted',
            sprintf(
                /* translators: %s: título del establecimiento */
                __( 'Establecimiento "%s" eliminado', 'simple-cards-listings' ),
                $post->post_title
            ),
            $post_id,
            'establecimiento'
        );
    }

    /**
     * Log al crear categoría
     *
     * @param int $term_id ID del término.
     * @param int $tt_id   ID de la taxonomía.
     */
    public static function log_term_created( $term_id, $tt_id ) {
        $term = get_term( $term_id );
        
        if ( ! $term || is_wp_error( $term ) ) {
            return;
        }

        self::log(
            'category_created',
            sprintf(
                /* translators: %s: nombre de la categoría */
                __( 'Categoría "%s" creada', 'simple-cards-listings' ),
                $term->name
            ),
            $term_id,
            'categoria_establecimiento'
        );
    }

    /**
     * Log al editar categoría
     *
     * @param int $term_id ID del término.
     * @param int $tt_id   ID de la taxonomía.
     */
    public static function log_term_edited( $term_id, $tt_id ) {
        $term = get_term( $term_id );
        
        if ( ! $term || is_wp_error( $term ) ) {
            return;
        }

        self::log(
            'category_updated',
            sprintf(
                /* translators: %s: nombre de la categoría */
                __( 'Categoría "%s" actualizada', 'simple-cards-listings' ),
                $term->name
            ),
            $term_id,
            'categoria_establecimiento'
        );
    }

    /**
     * Log al eliminar categoría
     *
     * @param int    $term_id      ID del término.
     * @param int    $tt_id        ID de la taxonomía.
     * @param object $deleted_term Término eliminado.
     * @param array  $object_ids   IDs de objetos relacionados.
     */
    public static function log_term_deleted( $term_id, $tt_id, $deleted_term, $object_ids ) {
        if ( ! $deleted_term || is_wp_error( $deleted_term ) ) {
            return;
        }

        self::log(
            'category_deleted',
            sprintf(
                /* translators: %s: nombre de la categoría */
                __( 'Categoría "%s" eliminada', 'simple-cards-listings' ),
                $deleted_term->name
            ),
            $term_id,
            'categoria_establecimiento'
        );
    }

    /**
     * Log al crear tag
     *
     * @param int $term_id ID del término.
     * @param int $tt_id   ID de la taxonomía.
     */
    public static function log_tag_created( $term_id, $tt_id ) {
        $term = get_term( $term_id );
        
        if ( ! $term || is_wp_error( $term ) ) {
            return;
        }

        self::log(
            'tag_created',
            sprintf(
                /* translators: %s: nombre del tag */
                __( 'Tag de búsqueda "%s" creado', 'simple-cards-listings' ),
                $term->name
            ),
            $term_id,
            'tag_busqueda'
        );
    }

    /**
     * Log al editar tag
     *
     * @param int $term_id ID del término.
     * @param int $tt_id   ID de la taxonomía.
     */
    public static function log_tag_edited( $term_id, $tt_id ) {
        $term = get_term( $term_id );
        
        if ( ! $term || is_wp_error( $term ) ) {
            return;
        }

        self::log(
            'tag_updated',
            sprintf(
                /* translators: %s: nombre del tag */
                __( 'Tag de búsqueda "%s" actualizado', 'simple-cards-listings' ),
                $term->name
            ),
            $term_id,
            'tag_busqueda'
        );
    }

    /**
     * Log al eliminar tag
     *
     * @param int    $term_id      ID del término.
     * @param int    $tt_id        ID de la taxonomía.
     * @param object $deleted_term Término eliminado.
     * @param array  $object_ids   IDs de objetos relacionados.
     */
    public static function log_tag_deleted( $term_id, $tt_id, $deleted_term, $object_ids ) {
        if ( ! $deleted_term || is_wp_error( $deleted_term ) ) {
            return;
        }

        self::log(
            'tag_deleted',
            sprintf(
                /* translators: %s: nombre del tag */
                __( 'Tag de búsqueda "%s" eliminado', 'simple-cards-listings' ),
                $deleted_term->name
            ),
            $term_id,
            'tag_busqueda'
        );
    }

    /**
     * Obtener logs
     *
     * @param array $args Argumentos de consulta.
     * @return array
     */
    public static function get_logs( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'per_page' => 20,
            'page'     => 1,
            'action'   => '',
            'user_id'  => 0,
            'orderby'  => 'created_at',
            'order'    => 'DESC',
        );

        $args = wp_parse_args( $args, $defaults );

        $where = array( '1=1' );
        $values = array();

        if ( ! empty( $args['action'] ) ) {
            $where[] = 'action = %s';
            $values[] = $args['action'];
        }

        if ( ! empty( $args['user_id'] ) ) {
            $where[] = 'user_id = %d';
            $values[] = absint( $args['user_id'] );
        }

        $offset = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );
        $orderby = in_array( $args['orderby'], array( 'id', 'action', 'user_id', 'created_at' ), true ) 
            ? $args['orderby'] 
            : 'created_at';
        $order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT * FROM " . self::$table_name . " WHERE " . implode( ' AND ', $where );
        $sql .= " ORDER BY {$orderby} {$order}";
        $sql .= " LIMIT %d OFFSET %d";

        $values[] = absint( $args['per_page'] );
        $values[] = $offset;

        if ( ! empty( $values ) ) {
            $sql = $wpdb->prepare( $sql, $values );
        }

        return $wpdb->get_results( $sql );
    }

    /**
     * Contar logs
     *
     * @param array $args Argumentos de consulta.
     * @return int
     */
    public static function count_logs( $args = array() ) {
        global $wpdb;

        $where = array( '1=1' );
        $values = array();

        if ( ! empty( $args['action'] ) ) {
            $where[] = 'action = %s';
            $values[] = $args['action'];
        }

        if ( ! empty( $args['user_id'] ) ) {
            $where[] = 'user_id = %d';
            $values[] = absint( $args['user_id'] );
        }

        $sql = "SELECT COUNT(*) FROM " . self::$table_name . " WHERE " . implode( ' AND ', $where );

        if ( ! empty( $values ) ) {
            $sql = $wpdb->prepare( $sql, $values );
        }

        return (int) $wpdb->get_var( $sql );
    }

    /**
     * Limpiar logs antiguos
     *
     * @param int $days Días a mantener.
     * @return int Número de registros eliminados.
     */
    public static function clean_old_logs( $days = 90 ) {
        global $wpdb;

        $date = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM " . self::$table_name . " WHERE created_at < %s",
                $date
            )
        );
    }
}
