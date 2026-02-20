<?php

/**
 * Sistema de notificaciones por email
 *
 * @package SimpleCardsListings
 * @since 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar notificaciones del plugin
 */
class SCL_Notifications
// Asegurar funciones globales de WordPress disponibles
// No es necesario 'use' en PHP, pero aclarar para contexto
{
    /**
     * Obtener todos los emails que deben recibir notificaciones
     * Incluye: admin emails configurados + todos los usuarios con roles de notificación configurados
     * 
     * @return array Array de emails
     */
    private static function get_notification_emails()
    {
        $emails = array();

        // Obtener emails del administrador configurados
        $options = get_option('scl_options', array());
        $admin_email_raw = isset($options['notification_email']) ? $options['notification_email'] : get_option('admin_email');
        $admin_emails = array_map('trim', explode(';', $admin_email_raw));
        $emails = array_merge($emails, $admin_emails);

        // Obtener roles configurados para recibir notificaciones
        $options = get_option('scl_options', array());
        $notification_roles = isset($options['notification_roles']) ? (array) $options['notification_roles'] : array('author');

        // Obtener usuarios con cualquiera de los roles de notificación
        foreach ($notification_roles as $role) {
            $users = get_users(array(
                'role' => $role,
                'fields' => array('user_email'),
            ));

            foreach ($users as $user) {
                if (!empty($user->user_email)) {
                    $emails[] = $user->user_email;
                }
            }
        }

        // Eliminar duplicados y emails vacíos
        $emails = array_unique(array_filter($emails));

        return $emails;
    }

    public static function notify_new_promotion($post_id)
    {
        $post = get_post($post_id);
        if (! $post || $post->post_type !== 'promocion') {
            return;
        }

        // Obtener todos los emails (admin + autores)
        $notification_emails = self::get_notification_emails();

        $site_name = get_bloginfo('name');
        $author = get_userdata($post->post_author);
        $author_name = $author ? $author->display_name : __('Usuario desconocido', 'simple-cards-listings');
        $author_email = $author ? $author->user_email : '';

        $subject = sprintf(
            /* translators: 1: nombre del sitio, 2: nombre de la promoción */
            __('[%1$s] Nueva promoción creada: %2$s', 'simple-cards-listings'),
            $site_name,
            $post->post_title
        );

        $message = sprintf(
            /* translators: 1: nombre de la promoción */
            __('Se ha creado una nueva promoción.', 'simple-cards-listings')
        ) . "\n\n";
        $message .= sprintf(
            __('Nombre: %s', 'simple-cards-listings'),
            $post->post_title
        ) . "\n";
        $message .= sprintf(
            __('Creado por: %s', 'simple-cards-listings'),
            $author_name
        ) . "\n";
        if ($author_email) {
            $message .= sprintf(
                __('Email del creador: %s', 'simple-cards-listings'),
                $author_email
            ) . "\n";
        }
        $message .= sprintf(
            __('Fecha: %s', 'simple-cards-listings'),
            get_the_date('', $post_id)
        ) . "\n\n";
        $establecimiento_id = get_post_meta($post_id, '_scl_establecimiento_id', true);
        if ($establecimiento_id) {
            $establecimiento = get_post($establecimiento_id);
            if ($establecimiento) {
                $message .= sprintf(
                    __('Establecimiento: %s', 'simple-cards-listings'),
                    $establecimiento->post_title
                ) . "\n";
            }
        }
        $message .= "\n" . __('Para revisar esta promoción, visita:', 'simple-cards-listings') . "\n";
        $message .= admin_url('post.php?post=' . $post_id . '&action=edit') . "\n\n";
        $message .= "---\n";
        $message .= sprintf(
            __('Este correo fue enviado automáticamente desde %s', 'simple-cards-listings'),
            $site_name
        );
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        if ($author_email) {
            $headers[] = 'Reply-To: ' . $author_name . ' <' . $author_email . '>';
        }
        $all_sent = true;
        foreach ($notification_emails as $email) {
            $sent = wp_mail($email, $subject, $message, $headers);
            if ($sent) {
                SCL_Logger::log(
                    'notification_sent',
                    sprintf(
                        __('Notificación enviada a %1$s sobre nueva promoción: "%2$s"', 'simple-cards-listings'),
                        $email,
                        $post->post_title
                    ),
                    $post_id,
                    'notification'
                );
            } else {
                SCL_Logger::log(
                    'notification_failed',
                    sprintf(
                        __('Error al enviar notificación a %1$s sobre: "%2$s"', 'simple-cards-listings'),
                        $email,
                        $post->post_title
                    ),
                    $post_id,
                    'notification'
                );
                $all_sent = false;
            }
        }
        return $all_sent;
    }

    /**
     * Notificar al administrador sobre nueva solicitud
     *
     * @param int $post_id ID del establecimiento.
     */
    public static function notify_new_submission($post_id)
    {
        $post = get_post($post_id);

        if (! $post) {
            return;
        }

        // Obtener todos los emails (admin + autores)
        $notification_emails = self::get_notification_emails();

        $site_name   = get_bloginfo('name');
        $author      = get_userdata($post->post_author);
        $author_name = $author ? $author->display_name : __('Usuario desconocido', 'simple-cards-listings');
        $author_email = $author ? $author->user_email : '';

        // Asunto del correo
        $subject = sprintf(
            /* translators: 1: nombre del sitio, 2: nombre del establecimiento */
            __('[%1$s] Nueva solicitud de establecimiento: %2$s', 'simple-cards-listings'),
            $site_name,
            $post->post_title
        );

        // Cuerpo del correo
        $message = sprintf(
            /* translators: 1: nombre del establecimiento */
            __('Se ha recibido una nueva solicitud de registro de establecimiento.', 'simple-cards-listings')
        ) . "\n\n";

        $message .= sprintf(
            /* translators: %s: nombre del establecimiento */
            __('Nombre: %s', 'simple-cards-listings'),
            $post->post_title
        ) . "\n";

        $message .= sprintf(
            /* translators: %s: nombre del autor */
            __('Solicitado por: %s', 'simple-cards-listings'),
            $author_name
        ) . "\n";

        if ($author_email) {
            $message .= sprintf(
                /* translators: %s: email del autor */
                __('Email del solicitante: %s', 'simple-cards-listings'),
                $author_email
            ) . "\n";
        }

        $message .= sprintf(
            /* translators: %s: fecha */
            __('Fecha: %s', 'simple-cards-listings'),
            get_the_date('', $post_id)
        ) . "\n\n";

        // Descripción
        if (! empty($post->post_content)) {
            $message .= __('Descripción:', 'simple-cards-listings') . "\n";
            $message .= wp_strip_all_tags($post->post_content) . "\n\n";
        }

        // Dirección
        $direccion = SCL_Metaboxes::get_meta($post_id, 'direccion');
        if ($direccion) {
            $message .= sprintf(
                /* translators: %s: dirección */
                __('Dirección: %s', 'simple-cards-listings'),
                $direccion
            ) . "\n";
        }

        // WhatsApp
        $whatsapp = SCL_Metaboxes::get_meta($post_id, 'whatsapp');
        if ($whatsapp) {
            $message .= sprintf(
                /* translators: %s: whatsapp */
                __('WhatsApp: %s', 'simple-cards-listings'),
                $whatsapp
            ) . "\n";
        }

        $message .= "\n" . __('Para revisar y aprobar esta solicitud, visita:', 'simple-cards-listings') . "\n";
        $message .= admin_url('post.php?post=' . $post_id . '&action=edit') . "\n\n";

        $message .= __('O puedes ver todas las solicitudes pendientes en:', 'simple-cards-listings') . "\n";
        $message .= admin_url('edit.php?post_type=establecimiento&post_status=pending') . "\n\n";

        $message .= "---\n";
        $message .= sprintf(
            /* translators: %s: nombre del sitio */
            __('Este correo fue enviado automáticamente desde %s', 'simple-cards-listings'),
            $site_name
        );

        // Headers
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
        );

        if ($author_email) {
            $headers[] = 'Reply-To: ' . $author_name . ' <' . $author_email . '>';
        }

        // Enviar correo a todos los emails
        $all_sent = true;
        foreach ($notification_emails as $email) {
            $sent = wp_mail($email, $subject, $message, $headers);
            // Log del envío
            if ($sent) {
                SCL_Logger::log(
                    'notification_sent',
                    sprintf(
                        /* translators: 1: email, 2: título del establecimiento */
                        __('Notificación enviada a %1$s sobre nueva solicitud: "%2$s"', 'simple-cards-listings'),
                        $email,
                        $post->post_title
                    ),
                    $post_id,
                    'notification'
                );
            } else {
                SCL_Logger::log(
                    'notification_failed',
                    sprintf(
                        /* translators: 1: email, 2: título del establecimiento */
                        __('Error al enviar notificación a %1$s sobre: "%2$s"', 'simple-cards-listings'),
                        $email,
                        $post->post_title
                    ),
                    $post_id,
                    'notification'
                );
                $all_sent = false;
            }
        }
        return $all_sent;
    }

    /**
     * Notificar al usuario sobre cambio de estado
     *
     * @param int    $post_id    ID del establecimiento.
     * @param string $new_status Nuevo estado.
     * @param string $old_status Estado anterior.
     */
    public static function notify_status_change($post_id, $new_status, $old_status)
    {
        // Solo notificar si cambia a publicado o rechazado
        if (! in_array($new_status, array('publish', 'trash'), true)) {
            return;
        }

        if ($new_status === $old_status) {
            return;
        }

        $post = get_post($post_id);

        if (! $post || 'establecimiento' !== $post->post_type) {
            return;
        }

        $author = get_userdata($post->post_author);

        if (! $author) {
            return;
        }

        $site_name = get_bloginfo('name');

        if ('publish' === $new_status) {
            $subject = sprintf(
                /* translators: 1: nombre del sitio, 2: nombre del establecimiento */
                __('[%1$s] Tu establecimiento "%2$s" ha sido aprobado', 'simple-cards-listings'),
                $site_name,
                $post->post_title
            );

            $message = sprintf(
                /* translators: %s: nombre del usuario */
                __('Hola %s,', 'simple-cards-listings'),
                $author->display_name
            ) . "\n\n";

            $message .= sprintf(
                /* translators: %s: nombre del establecimiento */
                __('¡Buenas noticias! Tu solicitud de registro para el establecimiento "%s" ha sido aprobada.', 'simple-cards-listings'),
                $post->post_title
            ) . "\n\n";

            $message .= __('Tu establecimiento ya es visible en el directorio.', 'simple-cards-listings') . "\n";
            $message .= self::get_establishment_url($post_id) . "\n\n";
        } else {
            $subject = sprintf(
                /* translators: 1: nombre del sitio, 2: nombre del establecimiento */
                __('[%1$s] Actualización sobre tu establecimiento "%2$s"', 'simple-cards-listings'),
                $site_name,
                $post->post_title
            );

            $message = sprintf(
                /* translators: %s: nombre del usuario */
                __('Hola %s,', 'simple-cards-listings'),
                $author->display_name
            ) . "\n\n";

            $message .= sprintf(
                /* translators: %s: nombre del establecimiento */
                __('Lamentamos informarte que tu solicitud de registro para el establecimiento "%s" no ha sido aprobada.', 'simple-cards-listings'),
                $post->post_title
            ) . "\n\n";

            $message .= __('Si tienes preguntas, por favor contacta con el administrador del sitio.', 'simple-cards-listings') . "\n\n";
        }

        $message .= "---\n";
        $message .= sprintf(
            /* translators: %s: nombre del sitio */
            __('Este correo fue enviado automáticamente desde %s', 'simple-cards-listings'),
            $site_name
        );

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
        );

        wp_mail($author->user_email, $subject, $message, $headers);
    }

    /**
     * Notificar al usuario que envió la solicitud que está en revisión
     *
     * @param int $post_id ID del establecimiento.
     */
    public static function notify_user_submission_received($post_id)
    {
        $post = get_post($post_id);

        if (! $post || 'establecimiento' !== $post->post_type) {
            SCL_Logger::log(
                'notification_skipped',
                sprintf('No se pudo enviar notificación de recepción: Post inválido (ID: %d)', $post_id),
                $post_id,
                'notification'
            );
            return false;
        }

        $author = get_userdata($post->post_author);

        if (! $author || empty($author->user_email)) {
            SCL_Logger::log(
                'notification_skipped',
                sprintf('No se pudo enviar notificación de recepción: Autor sin email (Post ID: %d)', $post_id),
                $post_id,
                'notification'
            );
            return false;
        }

        $site_name = get_bloginfo('name');

        // Asunto del correo
        $subject = sprintf(
            /* translators: 1: nombre del sitio, 2: nombre del establecimiento */
            __('[%1$s] Hemos recibido tu solicitud: %2$s', 'simple-cards-listings'),
            $site_name,
            $post->post_title
        );

        // Cuerpo del correo
        $message = sprintf(
            /* translators: %s: nombre del usuario */
            __('Hola %s,', 'simple-cards-listings'),
            $author->display_name
        ) . "\n\n";

        $message .= sprintf(
            /* translators: %s: nombre del establecimiento */
            __('Gracias por enviar tu solicitud de registro para el establecimiento "%s".', 'simple-cards-listings'),
            $post->post_title
        ) . "\n\n";

        $message .= __('Tu solicitud está actualmente en revisión por nuestro equipo.', 'simple-cards-listings') . "\n";
        $message .= __('Te notificaremos por correo electrónico una vez que tu establecimiento haya sido revisado y aprobado.', 'simple-cards-listings') . "\n\n";

        $message .= __('Este proceso puede tomar algunos días. Apreciamos tu paciencia.', 'simple-cards-listings') . "\n\n";

        $message .= "---\n";
        $message .= sprintf(
            /* translators: %s: nombre del sitio */
            __('Este correo fue enviado automáticamente desde %s', 'simple-cards-listings'),
            $site_name
        );

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
        );

        // Log antes de intentar enviar
        SCL_Logger::log(
            'notification_attempting',
            sprintf(
                'Intentando enviar notificación de recepción a %s sobre: "%s"',
                $author->user_email,
                $post->post_title
            ),
            $post_id,
            'notification'
        );

        $sent = wp_mail($author->user_email, $subject, $message, $headers);

        // Log del envío
        if ($sent) {
            SCL_Logger::log(
                'notification_sent',
                sprintf(
                    /* translators: 1: email, 2: título del establecimiento */
                    __('Notificación de recepción enviada a %1$s sobre: "%2$s"', 'simple-cards-listings'),
                    $author->user_email,
                    $post->post_title
                ),
                $post_id,
                'notification'
            );
        } else {
            SCL_Logger::log(
                'notification_failed',
                sprintf(
                    /* translators: 1: email, 2: título del establecimiento */
                    __('Error al enviar notificación de recepción a %1$s sobre: "%2$s"', 'simple-cards-listings'),
                    $author->user_email,
                    $post->post_title
                ),
                $post_id,
                'notification'
            );
        }

        return $sent;
    }

    /**
     * Notificar al usuario que su establecimiento fue publicado directamente
     *
     * @param int $post_id ID del establecimiento.
     */
    public static function notify_user_establishment_published($post_id)
    {
        $post = get_post($post_id);

        if (! $post || 'establecimiento' !== $post->post_type) {
            return;
        }

        $author = get_userdata($post->post_author);

        if (! $author) {
            return;
        }

        $site_name = get_bloginfo('name');

        // Asunto del correo
        $subject = sprintf(
            /* translators: 1: nombre del sitio, 2: nombre del establecimiento */
            __('[%1$s] Tu establecimiento ha sido publicado: %2$s', 'simple-cards-listings'),
            $site_name,
            $post->post_title
        );

        // Cuerpo del correo
        $message = sprintf(
            /* translators: %s: nombre del usuario */
            __('Hola %s,', 'simple-cards-listings'),
            $author->display_name
        ) . "\n\n";

        $message .= sprintf(
            /* translators: %s: nombre del establecimiento */
            __('Tu establecimiento "%s" ha sido creado y publicado exitosamente.', 'simple-cards-listings'),
            $post->post_title
        ) . "\n\n";

        $message .= __('Tu establecimiento ya es visible en el directorio.', 'simple-cards-listings') . "\n";
        $message .= self::get_establishment_url($post_id) . "\n\n";

        $message .= "---\n";
        $message .= sprintf(
            /* translators: %s: nombre del sitio */
            __('Este correo fue enviado automáticamente desde %s', 'simple-cards-listings'),
            $site_name
        );

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
        );

        $sent = wp_mail($author->user_email, $subject, $message, $headers);

        // Log del envío
        if ($sent) {
            SCL_Logger::log(
                'notification_sent',
                sprintf(
                    /* translators: 1: email, 2: título del establecimiento */
                    __('Notificación de publicación enviada a %1$s sobre: "%2$s"', 'simple-cards-listings'),
                    $author->user_email,
                    $post->post_title
                ),
                $post_id,
                'notification'
            );
        } else {
            SCL_Logger::log(
                'notification_failed',
                sprintf(
                    /* translators: 1: email, 2: título del establecimiento */
                    __('Error al enviar notificación de publicación a %1$s sobre: "%2$s"', 'simple-cards-listings'),
                    $author->user_email,
                    $post->post_title
                ),
                $post_id,
                'notification'
            );
        }

        return $sent;
    }

    /**
     * Notificar al usuario que envió la promoción que está en revisión
     *
     * @param int $post_id ID de la promoción.
     */
    public static function notify_user_promotion_submission_received($post_id)
    {
        $post = get_post($post_id);

        if (! $post || 'promocion' !== $post->post_type) {
            SCL_Logger::log(
                'notification_skipped',
                sprintf('No se pudo enviar notificación de recepción de promoción: Post inválido (ID: %d)', $post_id),
                $post_id,
                'notification'
            );
            return false;
        }

        $author = get_userdata($post->post_author);

        if (! $author || empty($author->user_email)) {
            SCL_Logger::log(
                'notification_skipped',
                sprintf('No se pudo enviar notificación de recepción de promoción: Autor sin email (Post ID: %d)', $post_id),
                $post_id,
                'notification'
            );
            return false;
        }

        $site_name = get_bloginfo('name');

        // Obtener el establecimiento asociado
        $establecimiento_id = get_post_meta($post_id, '_scl_establecimiento_id', true);
        $establecimiento_nombre = '';
        if ($establecimiento_id) {
            $establecimiento = get_post($establecimiento_id);
            if ($establecimiento) {
                $establecimiento_nombre = $establecimiento->post_title;
            }
        }

        // Asunto del correo
        $subject = sprintf(
            /* translators: 1: nombre del sitio, 2: nombre de la promoción */
            __('[%1$s] Hemos recibido tu promoción: %2$s', 'simple-cards-listings'),
            $site_name,
            $post->post_title
        );

        // Cuerpo del correo
        $message = sprintf(
            /* translators: %s: nombre del usuario */
            __('Hola %s,', 'simple-cards-listings'),
            $author->display_name
        ) . "\n\n";

        $message .= sprintf(
            /* translators: %s: nombre de la promoción */
            __('Gracias por enviar tu promoción "%s".', 'simple-cards-listings'),
            $post->post_title
        ) . "\n\n";

        if ($establecimiento_nombre) {
            $message .= sprintf(
                /* translators: %s: nombre del establecimiento */
                __('Establecimiento: %s', 'simple-cards-listings'),
                $establecimiento_nombre
            ) . "\n\n";
        }

        $message .= __('Tu promoción está actualmente en revisión por nuestro equipo.', 'simple-cards-listings') . "\n";
        $message .= __('Te notificaremos por correo electrónico una vez que tu promoción haya sido revisada y aprobada.', 'simple-cards-listings') . "\n\n";

        $message .= __('Este proceso puede tomar algunos días. Apreciamos tu paciencia.', 'simple-cards-listings') . "\n\n";

        $message .= "---\n";
        $message .= sprintf(
            /* translators: %s: nombre del sitio */
            __('Este correo fue enviado automáticamente desde %s', 'simple-cards-listings'),
            $site_name
        );

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
        );

        // Log antes de intentar enviar
        SCL_Logger::log(
            'notification_attempting',
            sprintf(
                'Intentando enviar notificación de recepción de promoción a %s sobre: "%s"',
                $author->user_email,
                $post->post_title
            ),
            $post_id,
            'notification'
        );

        $sent = wp_mail($author->user_email, $subject, $message, $headers);

        // Log del envío
        if ($sent) {
            SCL_Logger::log(
                'notification_sent',
                sprintf(
                    /* translators: 1: email, 2: título de la promoción */
                    __('Notificación de recepción de promoción enviada a %1$s sobre: "%2$s"', 'simple-cards-listings'),
                    $author->user_email,
                    $post->post_title
                ),
                $post_id,
                'notification'
            );
        } else {
            SCL_Logger::log(
                'notification_failed',
                sprintf(
                    /* translators: 1: email, 2: título de la promoción */
                    __('Error al enviar notificación de recepción de promoción a %1$s sobre: "%2$s"', 'simple-cards-listings'),
                    $author->user_email,
                    $post->post_title
                ),
                $post_id,
                'notification'
            );
        }

        return $sent;
    }

    /**
     * Notificar al usuario que su promoción fue publicada
     *
     * @param int $post_id ID de la promoción.
     */
    public static function notify_user_promotion_published($post_id)
    {
        $post = get_post($post_id);

        if (! $post || 'promocion' !== $post->post_type) {
            return;
        }

        $author = get_userdata($post->post_author);

        if (! $author) {
            return;
        }

        $site_name = get_bloginfo('name');

        // Obtener el establecimiento asociado
        $establecimiento_id = get_post_meta($post_id, '_scl_establecimiento_id', true);
        $establecimiento_nombre = '';
        if ($establecimiento_id) {
            $establecimiento = get_post($establecimiento_id);
            if ($establecimiento) {
                $establecimiento_nombre = $establecimiento->post_title;
            }
        }

        // Asunto del correo
        $subject = sprintf(
            /* translators: 1: nombre del sitio, 2: nombre de la promoción */
            __('[%1$s] Tu promoción ha sido publicada: %2$s', 'simple-cards-listings'),
            $site_name,
            $post->post_title
        );

        // Cuerpo del correo
        $message = sprintf(
            /* translators: %s: nombre del usuario */
            __('Hola %s,', 'simple-cards-listings'),
            $author->display_name
        ) . "\n\n";

        $message .= sprintf(
            /* translators: %s: nombre de la promoción */
            __('Tu promoción "%s" ha sido aprobada y publicada exitosamente.', 'simple-cards-listings'),
            $post->post_title
        ) . "\n\n";

        if ($establecimiento_nombre) {
            $message .= sprintf(
                /* translators: %s: nombre del establecimiento */
                __('Establecimiento: %s', 'simple-cards-listings'),
                $establecimiento_nombre
            ) . "\n\n";
        }

        $message .= __('Tu promoción ya es visible en el directorio.', 'simple-cards-listings') . "\n";
        $message .= self::get_promotion_url($post_id) . "\n\n";

        $message .= "---\n";
        $message .= sprintf(
            /* translators: %s: nombre del sitio */
            __('Este correo fue enviado automáticamente desde %s', 'simple-cards-listings'),
            $site_name
        );

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
        );

        $sent = wp_mail($author->user_email, $subject, $message, $headers);

        // Log del envío
        if ($sent) {
            SCL_Logger::log(
                'notification_sent',
                sprintf(
                    /* translators: 1: email, 2: título de la promoción */
                    __('Notificación de publicación de promoción enviada a %1$s sobre: "%2$s"', 'simple-cards-listings'),
                    $author->user_email,
                    $post->post_title
                ),
                $post_id,
                'notification'
            );
        } else {
            SCL_Logger::log(
                'notification_failed',
                sprintf(
                    /* translators: 1: email, 2: título de la promoción */
                    __('Error al enviar notificación de publicación de promoción a %1$s sobre: "%2$s"', 'simple-cards-listings'),
                    $author->user_email,
                    $post->post_title
                ),
                $post_id,
                'notification'
            );
        }

        return $sent;
    }

    /**
     * Obtener la URL del establecimiento según configuración
     * 
     * @param int $post_id ID del establecimiento
     * @return string URL del establecimiento
     */
    private static function get_establishment_url($post_id)
    {
        $options = get_option('scl_options', array());
        $directory_url = isset($options['directory_url']) ? trim($options['directory_url']) : '';

        if (!empty($directory_url)) {
            // Si hay URL configurada, usarla
            return rtrim($directory_url, '/');
        }

        // Si no hay URL configurada, usar el permalink del post
        return get_permalink($post_id);
    }

    /**
     * Obtener la URL de la promoción según configuración
     * 
     * @param int $post_id ID de la promoción
     * @return string URL de la promoción
     */
    private static function get_promotion_url($post_id)
    {
        $options = get_option('scl_options', array());
        $promotions_url = isset($options['promotions_url']) ? trim($options['promotions_url']) : '';

        if (!empty($promotions_url)) {
            // Si hay URL configurada, usarla
            return rtrim($promotions_url, '/');
        }

        // Si no hay URL configurada, usar el permalink del post
        return get_permalink($post_id);
    }
}

// Hook para notificar cambios de estado
add_action('transition_post_status', function ($new_status, $old_status, $post) {
    if ('establecimiento' === $post->post_type) {
        SCL_Notifications::notify_status_change($post->ID, $new_status, $old_status);
    }

    // Notificar a usuarios cuando su promoción es aprobada
    if ('promocion' === $post->post_type) {
        // Solo notificar si cambió de otro estado a publicado
        if ('publish' === $new_status && 'publish' !== $old_status) {
            SCL_Notifications::notify_user_promotion_published($post->ID);
        }
    }
}, 10, 3);
