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
     * Verificar si el usuario tiene un rol de gestor (configurado en opciones del plugin)
     *
     * @param int $user_id ID del usuario.
     * @return bool
     */
    public static function is_manager_role($user_id)
    {
        if (! $user_id) {
            return false;
        }

        $user = get_userdata($user_id);
        if (! $user) {
            return false;
        }

        // Obtener roles configurados como gestores
        $options = get_option('scl_options', array());
        $manager_roles = isset($options['manager_roles']) ? (array) $options['manager_roles'] : array('author');

        // Verificar si el usuario tiene alguno de los roles gestores
        $user_roles = (array) $user->roles;
        foreach ($manager_roles as $role) {
            if (in_array($role, $user_roles)) {
                return true;
            }
        }

        return false;
    }

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

        // Hook para integración con Advanced Role Manager
        $can_edit = apply_filters('scl_check_permission', false, 'edit_others_establecimientos', $user_id);
        if ($can_edit) {
            return true;
        }

        // Capacidad para editar de otros
        if (user_can($user_id, 'edit_others_establecimientos')) {
            return true;
        }

        // Los administradores pueden editar todo
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Los usuarios con rol 'author' pueden editar todo
        $user = get_userdata($user_id);
        if ($user && in_array('author', (array) $user->roles)) {
            return true;
        }

        $post = get_post($post_id);

        if (! $post) {
            return false;
        }

        // El autor puede editar su propio establecimiento sin importar capacidades
        if ((int) $post->post_author === (int) $user_id) {
            return true;
        }

        return false;
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

        // Hook para integración con Advanced Role Manager
        $can_delete = apply_filters('scl_check_permission', false, 'delete_others_establecimientos', $user_id);
        if ($can_delete) {
            return true;
        }

        // Capacidad para eliminar de otros
        if (user_can($user_id, 'delete_others_establecimientos')) {
            return true;
        }

        // Los administradores pueden eliminar todo
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Los usuarios con rol gestor pueden eliminar todo
        if (self::is_manager_role($user_id)) {
            return true;
        }

        $post = get_post($post_id);

        if (! $post) {
            return false;
        }

        // El autor puede eliminar su propio establecimiento sin importar capacidades
        if ((int) $post->post_author === (int) $user_id) {
            return true;
        }

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

        // Usuarios con capacidad de edición pueden ver todo
        if (user_can($user_id, 'edit_others_establecimientos')) {
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

        // Hook para integración con Advanced Role Manager
        $can_create = apply_filters('scl_check_permission', false, 'create_establecimientos', $user_id);
        if ($can_create) {
            return true;
        }

        // Los administradores siempre pueden crear
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Por defecto, cualquier usuario registrado puede crear (como solicitud)
        return is_user_logged_in();
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

        // Hook para integración con Advanced Role Manager
        $can_approve = apply_filters('scl_check_permission', false, 'approve_establecimientos', $user_id);
        if ($can_approve) {
            return true;
        }

        // Verificar capacidad específica
        if (user_can($user_id, 'approve_establecimientos')) {
            return true;
        }

        // Los administradores siempre pueden aprobar
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Editores también pueden aprobar
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

        // Hook para integración con Advanced Role Manager
        $can_manage = apply_filters('scl_check_permission', false, 'manage_establecimiento_terms', $user_id);
        if ($can_manage) {
            return true;
        }

        // Verificar capacidad específica
        if (user_can($user_id, 'manage_establecimiento_terms')) {
            return true;
        }

        // Los administradores siempre pueden gestionar categorías
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Capacidad estándar de WordPress
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

        // Hook para integración con Advanced Role Manager
        $can_view = apply_filters('scl_check_permission', false, 'view_scl_logs', $user_id);
        if ($can_view) {
            return true;
        }

        // Verificar capacidad específica
        if (user_can($user_id, 'view_scl_logs')) {
            return true;
        }

        // Solo administradores pueden ver logs por defecto
        return user_can($user_id, 'manage_options');
    }

    /**
     * Verificar si el usuario puede gestionar configuración de SCL
     *
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_manage_settings($user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Hook para integración con Advanced Role Manager
        $can_manage = apply_filters('scl_check_permission', false, 'manage_scl_settings', $user_id);
        if ($can_manage) {
            return true;
        }

        // Verificar capacidad específica
        if (user_can($user_id, 'manage_scl_settings')) {
            return true;
        }

        // Solo administradores pueden gestionar configuración por defecto
        return user_can($user_id, 'manage_options');
    }

    /**
     * Verificar si el usuario puede crear promociones
     *
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_create_promocion($user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Hook para integración con Advanced Role Manager
        $can_create = apply_filters('scl_check_permission', false, 'create_promociones', $user_id);
        if ($can_create) {
            return true;
        }

        // Los administradores siempre pueden crear
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Por defecto, usuarios que pueden crear establecimientos pueden crear promociones
        return self::can_create($user_id);
    }

    /**
     * Verificar si el usuario puede editar una promoción
     *
     * @param int $post_id ID del post.
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_edit_promocion($post_id, $user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Hook para integración con Advanced Role Manager
        $can_edit = apply_filters('scl_check_permission', false, 'edit_others_promociones', $user_id);
        if ($can_edit) {
            return true;
        }

        // Capacidad para editar de otros
        if (user_can($user_id, 'edit_others_promociones')) {
            return true;
        }

        // Los administradores pueden editar todo
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Los usuarios con rol gestor pueden editar todo
        if (self::is_manager_role($user_id)) {
            return true;
        }

        $post = get_post($post_id);

        if (! $post) {
            return false;
        }

        // El autor puede editar su propia promoción si tiene la capacidad

        // Permitir al dueño del establecimiento editar promociones de su establecimiento
        $establecimiento_id = get_post_meta($post_id, '_scl_establecimiento_id', true);
        if ($establecimiento_id) {
            $establecimiento = get_post($establecimiento_id);
            if ($establecimiento && (int) $establecimiento->post_author === (int) $user_id) {
                return true;
            }
        }

        // El autor puede editar su propia promoción sin importar capacidades
        if ((int) $post->post_author === (int) $user_id) {
            return true;
        }

        return false;
    }

    /**
     * Verificar si el usuario puede eliminar una promoción
     *
     * @param int $post_id ID del post.
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_delete_promocion($post_id, $user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Hook para integración con Advanced Role Manager
        $can_delete = apply_filters('scl_check_permission', false, 'delete_others_promociones', $user_id);
        if ($can_delete) {
            return true;
        }

        // Capacidad para eliminar de otros
        if (user_can($user_id, 'delete_others_promociones')) {
            return true;
        }

        // Los administradores pueden eliminar todo
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Los usuarios con rol gestor pueden eliminar todo
        if (self::is_manager_role($user_id)) {
            return true;
        }

        $post = get_post($post_id);

        if (! $post) {
            return false;
        }

        // Permitir al dueño del establecimiento eliminar promociones de su establecimiento
        $establecimiento_id = get_post_meta($post_id, '_scl_establecimiento_id', true);
        if ($establecimiento_id) {
            $establecimiento = get_post($establecimiento_id);
            if ($establecimiento && (int) $establecimiento->post_author === (int) $user_id) {
                return true;
            }
        }

        // El autor puede eliminar su propia promoción sin importar capacidades
        if ((int) $post->post_author === (int) $user_id) {
            return true;
        }

        return false;
    }

    /**
     * Verificar si el usuario puede aprobar promociones
     *
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_approve_promocion($user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Hook para integración con Advanced Role Manager
        $can_approve = apply_filters('scl_check_permission', false, 'approve_promociones', $user_id);
        if ($can_approve) {
            return true;
        }

        // Verificar capacidad específica
        if (user_can($user_id, 'approve_promociones')) {
            return true;
        }

        // Los administradores siempre pueden aprobar
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Editores también pueden aprobar
        return user_can($user_id, 'edit_others_posts');
    }

    /**
     * Filtrar query de establecimientos por permisos
     *
     * @param WP_Query $query Query a filtrar.
     */
    public static function filter_query_by_permissions($query)
    {
        // Solo filtrar en admin y en la consulta principal
        if (! is_admin() || ! $query->is_main_query()) {
            return;
        }

        $post_type = $query->get('post_type');

        if (!in_array($post_type, array('establecimiento', 'promocion'))) {
            return;
        }

        $user_id = get_current_user_id();

        // Administradores ven todo
        if (user_can($user_id, 'manage_options')) {
            return;
        }

        // Para establecimientos
        if ($post_type === 'establecimiento') {
            // Verificar permiso usando el sistema de integración con ARM
            $can_edit_others = apply_filters('scl_check_permission', false, 'edit_others_establecimientos', $user_id);

            // Si no usa ARM, verificar capacidad nativa
            if (!$can_edit_others && user_can($user_id, 'edit_others_establecimientos')) {
                $can_edit_others = true;
            }

            // Si tiene permiso para ver de otros, ver todo
            if ($can_edit_others) {
                return;
            }

            // Verificar si al menos puede editar propios
            $can_edit = apply_filters('scl_check_permission', false, 'edit_establecimientos', $user_id);
            if (!$can_edit && !is_user_logged_in()) {
                // No tiene permisos, no mostrar nada
                $query->set('author', 0);
                return;
            }

            // Solo ver propios
            $query->set('author', $user_id);
        }

        // Para promociones
        if ($post_type === 'promocion') {
            // Verificar permiso usando el sistema de integración con ARM
            $can_edit_others = apply_filters('scl_check_permission', false, 'edit_others_promociones', $user_id);

            // Si no usa ARM, verificar capacidad nativa
            if (!$can_edit_others && user_can($user_id, 'edit_others_promociones')) {
                $can_edit_others = true;
            }

            // Si tiene permiso para ver de otros, ver todo
            if ($can_edit_others) {
                return;
            }

            // Verificar si al menos puede editar propios
            $can_edit = apply_filters('scl_check_permission', false, 'edit_promociones', $user_id);
            if (!$can_edit && !is_user_logged_in()) {
                // No tiene permisos, no mostrar nada
                $query->set('author', 0);
                return;
            }

            // Solo ver propios
            $query->set('author', $user_id);
        }
    }
}

// Filtrar query en admin
add_action('pre_get_posts', array('SCL_Permissions', 'filter_query_by_permissions'));
