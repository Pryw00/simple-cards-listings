<?php
/**
 * Metaboxes para el CPT Establecimiento
 *
 * @package SimpleCardsListings
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para gestionar metaboxes del establecimiento
 */
class SCL_Metaboxes {

    /**
     * Prefijo para meta keys
     */
    const PREFIX = '_scl_';

    /**
     * Campos del establecimiento
     *
     * @return array
     */
    public static function get_fields() {
        return array(
            'logo' => array(
                'label'       => __( 'Logo del establecimiento', 'simple-cards-listings' ),
                'type'        => 'image',
                'description' => __( 'Imagen del logo que se mostrará en el grid.', 'simple-cards-listings' ),
            ),
            'menu_pdf' => array(
                'label'       => __( 'Archivo PDF (Menú)', 'simple-cards-listings' ),
                'type'        => 'file',
                'description' => __( 'Archivo PDF del menú o carta del establecimiento.', 'simple-cards-listings' ),
            ),
            'menu_pdf_name' => array(
                'label'       => __( 'Nombre del archivo PDF', 'simple-cards-listings' ),
                'type'        => 'text',
                'description' => __( 'Nombre a mostrar para el enlace del PDF (ej: "Ver Menú").', 'simple-cards-listings' ),
                'default'     => 'Menu',
            ),
            'whatsapp' => array(
                'label'       => __( 'Número de WhatsApp', 'simple-cards-listings' ),
                'type'        => 'text',
                'description' => __( 'Número con código de país (ej: +593999999999).', 'simple-cards-listings' ),
                'placeholder' => '+593999999999',
            ),
            'instagram' => array(
                'label'       => __( 'Instagram', 'simple-cards-listings' ),
                'type'        => 'url',
                'description' => __( 'URL del perfil de Instagram.', 'simple-cards-listings' ),
                'placeholder' => 'https://instagram.com/usuario',
            ),
            'tiktok' => array(
                'label'       => __( 'TikTok', 'simple-cards-listings' ),
                'type'        => 'url',
                'description' => __( 'URL del perfil de TikTok.', 'simple-cards-listings' ),
                'placeholder' => 'https://tiktok.com/@usuario',
            ),
            'facebook' => array(
                'label'       => __( 'Facebook', 'simple-cards-listings' ),
                'type'        => 'url',
                'description' => __( 'URL de la página de Facebook.', 'simple-cards-listings' ),
                'placeholder' => 'https://facebook.com/pagina',
            ),
            'website' => array(
                'label'       => __( 'Sitio Web (Opcional)', 'simple-cards-listings' ),
                'type'        => 'url',
                'description' => __( 'URL del sitio web del establecimiento.', 'simple-cards-listings' ),
                'placeholder' => 'https://ejemplo.com',
            ),
            'direccion' => array(
                'label'       => __( 'Dirección', 'simple-cards-listings' ),
                'type'        => 'textarea',
                'description' => __( 'Dirección física del establecimiento.', 'simple-cards-listings' ),
            ),
            'google_maps_url' => array(
                'label'       => __( 'URL de Google Maps', 'simple-cards-listings' ),
                'type'        => 'url',
                'description' => __( 'Enlace a la ubicación en Google Maps.', 'simple-cards-listings' ),
                'placeholder' => 'https://maps.google.com/...',
            ),
            'imagen_establecimiento' => array(
                'label'       => __( 'Imagen del establecimiento', 'simple-cards-listings' ),
                'type'        => 'image',
                'description' => __( 'Foto del local o fachada del negocio.', 'simple-cards-listings' ),
            ),
        );
    }

    /**
     * Registrar metabox
     */
    public static function register() {
        add_meta_box(
            'scl_establecimiento_datos',
            __( 'Datos del Establecimiento', 'simple-cards-listings' ),
            array( __CLASS__, 'render_metabox' ),
            'establecimiento',
            'normal',
            'high'
        );
    }

    /**
     * Renderizar metabox
     *
     * @param WP_Post $post Objeto del post.
     */
    public static function render_metabox( $post ) {
        // Nonce para seguridad
        wp_nonce_field( 'scl_save_metabox', 'scl_metabox_nonce' );

        $fields = self::get_fields();
        
        echo '<div class="scl-metabox-wrapper">';
        
        foreach ( $fields as $key => $field ) {
            $meta_key = self::PREFIX . $key;
            $value    = get_post_meta( $post->ID, $meta_key, true );
            $default  = isset( $field['default'] ) ? $field['default'] : '';
            $value    = $value !== '' ? $value : $default;
            
            echo '<div class="scl-field-row">';
            echo '<label for="' . esc_attr( $meta_key ) . '" class="scl-field-label">';
            echo esc_html( $field['label'] );
            echo '</label>';
            
            echo '<div class="scl-field-input">';
            
            switch ( $field['type'] ) {
                case 'text':
                    self::render_text_field( $meta_key, $value, $field );
                    break;
                case 'url':
                    self::render_url_field( $meta_key, $value, $field );
                    break;
                case 'textarea':
                    self::render_textarea_field( $meta_key, $value, $field );
                    break;
                case 'image':
                    self::render_image_field( $meta_key, $value, $field );
                    break;
                case 'file':
                    self::render_file_field( $meta_key, $value, $field );
                    break;
            }
            
            if ( ! empty( $field['description'] ) ) {
                echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
            }
            
            echo '</div>'; // .scl-field-input
            echo '</div>'; // .scl-field-row
        }
        
        echo '</div>'; // .scl-metabox-wrapper
    }

    /**
     * Renderizar campo de texto
     */
    private static function render_text_field( $name, $value, $field ) {
        $placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
        printf(
            '<input type="text" id="%1$s" name="%1$s" value="%2$s" placeholder="%3$s" class="regular-text">',
            esc_attr( $name ),
            esc_attr( $value ),
            esc_attr( $placeholder )
        );
    }

    /**
     * Renderizar campo URL
     */
    private static function render_url_field( $name, $value, $field ) {
        $placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
        printf(
            '<input type="url" id="%1$s" name="%1$s" value="%2$s" placeholder="%3$s" class="regular-text">',
            esc_attr( $name ),
            esc_url( $value ),
            esc_attr( $placeholder )
        );
    }

    /**
     * Renderizar campo textarea
     */
    private static function render_textarea_field( $name, $value, $field ) {
        printf(
            '<textarea id="%1$s" name="%1$s" rows="3" class="large-text">%2$s</textarea>',
            esc_attr( $name ),
            esc_textarea( $value )
        );
    }

    /**
     * Renderizar campo de imagen
     */
    private static function render_image_field( $name, $value, $field ) {
        $image_url = '';
        if ( $value ) {
            $image_url = wp_get_attachment_image_url( $value, 'thumbnail' );
        }
        ?>
        <div class="scl-image-field" data-field="<?php echo esc_attr( $name ); ?>">
            <input type="hidden" id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
            <div class="scl-image-preview">
                <?php if ( $image_url ) : ?>
                    <img src="<?php echo esc_url( $image_url ); ?>" alt="">
                <?php endif; ?>
            </div>
            <button type="button" class="button scl-upload-image">
                <?php esc_html_e( 'Seleccionar imagen', 'simple-cards-listings' ); ?>
            </button>
            <button type="button" class="button scl-remove-image" <?php echo $value ? '' : 'style="display:none;"'; ?>>
                <?php esc_html_e( 'Quitar imagen', 'simple-cards-listings' ); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Renderizar campo de archivo
     */
    private static function render_file_field( $name, $value, $field ) {
        $file_url = '';
        $file_name = '';
        if ( $value ) {
            $file_url = wp_get_attachment_url( $value );
            $file_name = basename( get_attached_file( $value ) );
        }
        ?>
        <div class="scl-file-field" data-field="<?php echo esc_attr( $name ); ?>">
            <input type="hidden" id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
            <div class="scl-file-preview">
                <?php if ( $file_name ) : ?>
                    <a href="<?php echo esc_url( $file_url ); ?>" target="_blank"><?php echo esc_html( $file_name ); ?></a>
                <?php endif; ?>
            </div>
            <button type="button" class="button scl-upload-file">
                <?php esc_html_e( 'Seleccionar archivo', 'simple-cards-listings' ); ?>
            </button>
            <button type="button" class="button scl-remove-file" <?php echo $value ? '' : 'style="display:none;"'; ?>>
                <?php esc_html_e( 'Quitar archivo', 'simple-cards-listings' ); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Guardar metabox
     *
     * @param int     $post_id ID del post.
     * @param WP_Post $post    Objeto del post.
     */
    public static function save( $post_id, $post ) {
        // Verificar nonce
        if ( ! isset( $_POST['scl_metabox_nonce'] ) || ! wp_verify_nonce( $_POST['scl_metabox_nonce'], 'scl_save_metabox' ) ) {
            return;
        }

        // Verificar autoguardado
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Verificar tipo de post
        if ( 'establecimiento' !== $post->post_type ) {
            return;
        }

        // Verificar permisos
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = self::get_fields();
        $old_values = array();
        $new_values = array();

        foreach ( $fields as $key => $field ) {
            $meta_key = self::PREFIX . $key;
            $old_value = get_post_meta( $post_id, $meta_key, true );
            $old_values[ $key ] = $old_value;

            if ( isset( $_POST[ $meta_key ] ) ) {
                $new_value = self::sanitize_field( $_POST[ $meta_key ], $field['type'] );
                $new_values[ $key ] = $new_value;
                update_post_meta( $post_id, $meta_key, $new_value );
            }
        }

        // Registrar log de actualización
        if ( $old_values !== $new_values ) {
            SCL_Logger::log(
                'establecimiento_updated',
                sprintf(
                    /* translators: %s: título del establecimiento */
                    __( 'Establecimiento "%s" actualizado', 'simple-cards-listings' ),
                    $post->post_title
                ),
                $post_id,
                'establecimiento'
            );
        }
    }

    /**
     * Sanitizar valor según tipo de campo
     *
     * @param mixed  $value Valor a sanitizar.
     * @param string $type  Tipo de campo.
     * @return mixed
     */
    private static function sanitize_field( $value, $type ) {
        switch ( $type ) {
            case 'url':
                return esc_url_raw( $value );
            case 'textarea':
                return sanitize_textarea_field( $value );
            case 'image':
            case 'file':
                return absint( $value );
            default:
                return sanitize_text_field( $value );
        }
    }

    /**
     * Obtener valor de meta
     *
     * @param int    $post_id ID del post.
     * @param string $key     Clave del campo.
     * @return mixed
     */
    public static function get_meta( $post_id, $key ) {
        return get_post_meta( $post_id, self::PREFIX . $key, true );
    }
}
