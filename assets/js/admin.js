/**
 * Simple Cards Listings - Admin JavaScript
 *
 * @package SimpleCardsListings
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * SCL Admin Application
     */
    const SCLAdmin = {
        /**
         * Inicializar
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Vincular eventos
         */
        bindEvents: function() {
            // Subir imagen
            $(document).on('click', '.scl-upload-image', this.uploadImage.bind(this));
            $(document).on('click', '.scl-remove-image', this.removeImage.bind(this));

            // Subir archivo
            $(document).on('click', '.scl-upload-file', this.uploadFile.bind(this));
            $(document).on('click', '.scl-remove-file', this.removeFile.bind(this));
        },

        /**
         * Subir imagen
         */
        uploadImage: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const $field = $button.closest('.scl-image-field');
            const $input = $field.find('input[type="hidden"]');
            const $preview = $field.find('.scl-image-preview');
            const $removeBtn = $field.find('.scl-remove-image');

            // Abrir media library
            const frame = wp.media({
                title: scl_admin.i18n.select_image,
                button: {
                    text: scl_admin.i18n.use_image
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            frame.on('select', function() {
                const attachment = frame.state().get('selection').first().toJSON();
                
                $input.val(attachment.id);
                $preview.html('<img src="' + (attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url) + '" alt="">');
                $removeBtn.show();
            });

            frame.open();
        },

        /**
         * Quitar imagen
         */
        removeImage: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const $field = $button.closest('.scl-image-field');
            const $input = $field.find('input[type="hidden"]');
            const $preview = $field.find('.scl-image-preview');

            $input.val('');
            $preview.empty();
            $button.hide();
        },

        /**
         * Subir archivo
         */
        uploadFile: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const $field = $button.closest('.scl-file-field');
            const $input = $field.find('input[type="hidden"]');
            const $preview = $field.find('.scl-file-preview');
            const $removeBtn = $field.find('.scl-remove-file');

            // Abrir media library
            const frame = wp.media({
                title: scl_admin.i18n.select_file,
                button: {
                    text: scl_admin.i18n.use_file
                },
                multiple: false,
                library: {
                    type: 'application/pdf'
                }
            });

            frame.on('select', function() {
                const attachment = frame.state().get('selection').first().toJSON();
                
                $input.val(attachment.id);
                $preview.html('<a href="' + attachment.url + '" target="_blank">' + attachment.filename + '</a>');
                $removeBtn.show();
            });

            frame.open();
        },

        /**
         * Quitar archivo
         */
        removeFile: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const $field = $button.closest('.scl-file-field');
            const $input = $field.find('input[type="hidden"]');
            const $preview = $field.find('.scl-file-preview');

            $input.val('');
            $preview.empty();
            $button.hide();
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        SCLAdmin.init();
    });

})(jQuery);
