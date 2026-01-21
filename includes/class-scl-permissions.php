<?php

/**
 * Sistema de permisos
 *
 * @package SimpleCardsListings
 * @since 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar permisos del plugin
 */
class SCL_Permissions
{

    /**
     * Verificar si el usuario puede editar un establecimiento
     *
     * @param int $post_id ID del post.
     * @param int $user_id ID del usuario (opcional, usa el actual por defecto).
     * @return bool
     */
    public static function can_edit($post_id, $user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Los administradores pueden editar todo
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Los editores también pueden editar todo
        if (user_can($user_id, 'edit_others_posts')) {
            return true;
        }

        $post = get_post($post_id);

        if (! $post) {
            return false;
        }

        // Solo el autor puede editar su propio establecimiento
        return (int) $post->post_author === (int) $user_id;
    }

    /**
     * Verificar si el usuario puede eliminar un establecimiento
     *
     * @param int $post_id ID del post.
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_delete($post_id, $user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Los administradores pueden eliminar todo
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Solo administradores pueden eliminar
        return false;
    }

    /**
     * Verificar si el usuario puede ver un establecimiento
     *
     * @param int $post_id ID del post.
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_view($post_id, $user_id = 0)
    {
        $post = get_post($post_id);

        if (! $post) {
            return false;
        }

        // Posts publicados son visibles para todos
        if ('publish' === $post->post_status) {
            return true;
        }

        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Administradores pueden ver todo
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // El autor puede ver sus propios posts
        return (int) $post->post_author === (int) $user_id;
    }

    /**
     * Verificar si el usuario puede crear establecimientos
     *
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_create($user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Cualquier usuario registrado puede crear (como solicitud)
        return true;
    }

    /**
     * Verificar si el usuario puede aprobar establecimientos
     *
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_approve($user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Solo administradores y editores pueden aprobar
        return user_can($user_id, 'edit_others_posts');
    }

    /**
     * Verificar si el usuario puede gestionar categorías
     *
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_manage_terms($user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Solo administradores pueden gestionar categorías y tags
        return user_can($user_id, 'manage_categories');
    }

    /**
     * Verificar si el usuario puede ver logs
     *
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_view_logs($user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Solo administradores pueden ver logs
        return user_can($user_id, 'manage_options');
    }

    /**
     * Filtrar query de establecimientos por permisos
     *
     * @param WP_Query $query Query a filtrar.
     */
    public static function filter_query_by_permissions($query)
    {
        if (! is_admin()) {
            return;
        }

        if ('establecimiento' !== $query->get('post_type')) {
            return;
        }

        $user_id = get_current_user_id();

        // Si no es admin ni editor, solo ver propios
        if (! user_can($user_id, 'edit_others_posts')) {
            $query->set('author', $user_id);
        }
    }
}

// Filtrar query en admin
add_action('pre_get_posts', array('SCL_Permissions', 'filter_query_by_permissions'));
