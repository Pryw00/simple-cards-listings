/**
 * Simple Cards Listings - Frontend JavaScript
 *
 * @package SimpleCardsListings
 * @since 1.0.0
 */

(function ($) {
  "use strict";

  /**
   * SCL Frontend Application
   */
  const SCL = {
    /**
     * Data de establecimientos para búsqueda
     */
    establecimientosData: [],

    /**
     * Timer para debounce de búsqueda
     */
    searchTimer: null,

    /**
     * Inicializar
     */
    init: function () {
      this.loadEstablecimientosData();
      this.bindEvents();
    },

    /**
     * Cargar datos de establecimientos
     */
    loadEstablecimientosData: function () {
      const dataElement = document.getElementById("scl-establecimientos-data");
      if (dataElement) {
        try {
          this.establecimientosData = JSON.parse(dataElement.textContent);
        } catch (e) {
          console.error("Error parsing establecimientos data:", e);
        }
      }
    },

    /**
     * Vincular eventos
     */
    bindEvents: function () {
      // Búsqueda en tiempo real
      $(document).on(
        "input",
        "#scl-search-input",
        this.handleSearch.bind(this),
      );
      $(document).on(
        "focus",
        "#scl-search-input",
        this.showSuggestions.bind(this),
      );
      $(document).on(
        "blur",
        "#scl-search-input",
        this.hideSuggestions.bind(this),
      );
      $(document).on(
        "mousedown",
        ".scl-suggestion-item",
        this.selectSuggestion.bind(this),
      );

      // Click en card para abrir modal
      $(document).on("click", ".scl-card-item", this.openModal.bind(this));

      // Cerrar modal
      $(document).on(
        "click",
        ".scl-modal-close, .scl-modal-overlay",
        this.closeModal.bind(this),
      );
      $(document).on("keydown", this.handleEscKey.bind(this));

      // Formulario de solicitud
      $(document).on(
        "submit",
        "#scl-solicitud-form",
        this.handleSolicitudSubmit.bind(this),
      );

      // Dashboard - Solicitar nuevo
      $(document).on(
        "click",
        ".scl-btn-nuevo",
        this.openSolicitudModal.bind(this),
      );
      $(document).on(
        "click",
        "#scl-solicitud-modal .scl-modal-close, #scl-solicitud-modal .scl-modal-overlay",
        this.closeSolicitudModal.bind(this),
      );

      // Dashboard - Editar
      $(document).on("click", ".scl-btn-edit", this.openEditModal.bind(this));
      $(document).on(
        "click",
        "#scl-edit-modal .scl-modal-close, #scl-edit-modal .scl-modal-overlay",
        this.closeEditModal.bind(this),
      );

      // Dashboard - Ver
      $(document).on(
        "click",
        ".scl-btn-view",
        this.viewEstablecimiento.bind(this),
      );

      // Formulario de edición
      $(document).on(
        "submit",
        "#scl-edit-form",
        this.handleEditSubmit.bind(this),
      );
    },

    /**
     * Manejar búsqueda
     */
    handleSearch: function (e) {
      const term = e.target.value.toLowerCase().trim();

      clearTimeout(this.searchTimer);

      this.searchTimer = setTimeout(() => {
        this.filterEstablecimientos(term);
      }, 200);
    },

    /**
     * Filtrar establecimientos
     */
    filterEstablecimientos: function (term) {
      const $grid = $("#scl-grid");
      const $noResults = $("#scl-no-results");
      let visibleCount = 0;

      if (!term) {
        // Mostrar todos
        $grid.find(".scl-card-item").removeClass("scl-hidden");
        $noResults.hide();
        return;
      }

      // Permitir búsqueda por todas las palabras (AND)
      const terms = term.split(/\s+/).filter(Boolean);

      this.establecimientosData.forEach((item) => {
        const $card = $grid.find('.scl-card-item[data-id="' + item.id + '"]');
        const searchableText = [
          item.title,
          item.description,
          ...(item.categories || []),
          ...(item.tags || []),
        ]
          .join(" ")
          .toLowerCase();

        // Todas las palabras deben estar presentes
        const allMatch = terms.every((word) => searchableText.includes(word));
        if (allMatch) {
          $card.removeClass("scl-hidden");
          visibleCount++;
        } else {
          $card.addClass("scl-hidden");
        }
      });

      // Mostrar mensaje si no hay resultados
      if (visibleCount === 0) {
        $noResults.show();
      } else {
        $noResults.hide();
      }
    },

    /**
     * Mostrar sugerencias
     */
    showSuggestions: function () {
      const $input = $("#scl-search-input");
      const term = $input.val().toLowerCase().trim();
      const $suggestions = $("#scl-search-suggestions");

      if (term.length >= 1) {
        // Filtrar sugerencias que coincidan
        $suggestions.find(".scl-suggestion-item").each(function () {
          const suggestionText = $(this).text().toLowerCase();
          if (suggestionText.includes(term)) {
            $(this).show();
          } else {
            $(this).hide();
          }
        });

        if ($suggestions.find(".scl-suggestion-item:visible").length > 0) {
          $suggestions.show();
        }
      }
    },

    /**
     * Ocultar sugerencias
     */
    hideSuggestions: function () {
      setTimeout(() => {
        $("#scl-search-suggestions").hide();
      }, 200);
    },

    /**
     * Seleccionar sugerencia
     */
    selectSuggestion: function (e) {
      e.preventDefault();
      const term = $(e.currentTarget).data("term");
      $("#scl-search-input").val(term);
      $("#scl-search-suggestions").hide();
      this.filterEstablecimientos(term.toLowerCase());
    },

    /**
     * Abrir modal de establecimiento
     */
    openModal: function (e) {
      const postId = $(e.currentTarget).data("id");
      const $modal = $("#scl-modal");
      const $body = $("#scl-modal-body");

      // Mostrar loading
      $body.html('<div class="scl-loading"></div>');
      $modal.show();
      $("body").css("overflow", "hidden");

      // Cargar contenido via AJAX
      $.ajax({
        url: scl_ajax.ajax_url,
        type: "POST",
        data: {
          action: "scl_get_establecimiento",
          nonce: scl_ajax.nonce,
          post_id: postId,
        },
        success: function (response) {
          if (response.success) {
            $body.html(response.data.html);
          } else {
            $body.html(
              '<p class="scl-message scl-message-error">' +
                (response.data.message || scl_ajax.i18n.error) +
                "</p>",
            );
          }
        },
        error: function () {
          $body.html(
            '<p class="scl-message scl-message-error">' +
              scl_ajax.i18n.error +
              "</p>",
          );
        },
      });
    },

    /**
     * Cerrar modal
     */
    closeModal: function (e) {
      if (
        $(e.target).hasClass("scl-modal-overlay") ||
        $(e.target).hasClass("scl-modal-close") ||
        $(e.target).closest(".scl-modal-close").length
      ) {
        $("#scl-modal").hide();
        $("body").css("overflow", "");
      }
    },

    /**
     * Manejar tecla ESC
     */
    handleEscKey: function (e) {
      if (e.key === "Escape") {
        $("#scl-modal, #scl-edit-modal, #scl-solicitud-modal").hide();
        $("body").css("overflow", "");
      }
    },

    /**
     * Manejar envío de formulario de solicitud
     */
    handleSolicitudSubmit: function (e) {
      e.preventDefault();

      const $form = $(e.target);
      const $button = $form.find('button[type="submit"]');
      const $message = $("#scl-form-message");
      const formData = new FormData($form[0]);

      formData.append("action", "scl_submit_solicitud");

      // Deshabilitar botón
      $button.prop("disabled", true).text(scl_ajax.i18n.loading);
      $message.hide();

      $.ajax({
        url: scl_ajax.ajax_url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            $message
              .removeClass("error")
              .addClass("success")
              .text(response.data.message)
              .show();
            $form[0].reset();
            // Cerrar modal y recargar después de un momento si estamos en el dashboard
            setTimeout(function () {
              if ($("#scl-solicitud-modal").length) {
                $("#scl-solicitud-modal").hide();
                $("body").css("overflow", "");
                location.reload();
              }
            }, 1500);
          } else {
            $message
              .removeClass("success")
              .addClass("error")
              .text(response.data.message || scl_ajax.i18n.error)
              .show();
          }
        },
        error: function () {
          $message
            .removeClass("success")
            .addClass("error")
            .text(scl_ajax.i18n.error)
            .show();
        },
        complete: function () {
          $button
            .prop("disabled", false)
            .text($button.data("original-text") || "Enviar solicitud");
        },
      });
    },

    /**
     * Abrir modal de solicitud
     */
    openSolicitudModal: function (e) {
      e.preventDefault();
      const $modal = $("#scl-solicitud-modal");
      $modal.show();
      $("body").css("overflow", "hidden");
    },

    /**
     * Cerrar modal de solicitud
     */
    closeSolicitudModal: function (e) {
      if (
        $(e.target).hasClass("scl-modal-overlay") ||
        $(e.target).hasClass("scl-modal-close") ||
        $(e.target).closest(".scl-modal-close").length
      ) {
        $("#scl-solicitud-modal").hide();
        $("body").css("overflow", "");
      }
    },

    /**
     * Abrir modal de edición
     */
    openEditModal: function (e) {
      e.preventDefault();
      const postId = $(e.currentTarget).data("id");
      const $modal = $("#scl-edit-modal");
      const $body = $("#scl-edit-modal-body");

      // Mostrar loading
      $body.html('<div class="scl-loading"></div>');
      $modal.show();
      $("body").css("overflow", "hidden");

      // Cargar formulario via AJAX
      $.ajax({
        url: scl_ajax.ajax_url,
        type: "POST",
        data: {
          action: "scl_get_edit_form",
          nonce: scl_ajax.nonce,
          post_id: postId,
        },
        success: function (response) {
          if (response.success) {
            $body.html(response.data.html);
          } else {
            $body.html(
              '<p class="scl-message scl-message-error">' +
                (response.data.message || scl_ajax.i18n.error) +
                "</p>",
            );
          }
        },
        error: function () {
          $body.html(
            '<p class="scl-message scl-message-error">' +
              scl_ajax.i18n.error +
              "</p>",
          );
        },
      });
    },

    /**
     * Cerrar modal de edición
     */
    closeEditModal: function (e) {
      if (
        $(e.target).hasClass("scl-modal-overlay") ||
        $(e.target).hasClass("scl-modal-close") ||
        $(e.target).closest(".scl-modal-close").length
      ) {
        $("#scl-edit-modal").hide();
        $("body").css("overflow", "");
      }
    },

    /**
     * Ver establecimiento
     */
    viewEstablecimiento: function (e) {
      const postId = $(e.currentTarget).data("id");
      this.openModal({ currentTarget: { dataset: { id: postId } } });
    },

    /**
     * Manejar envío de formulario de edición
     */
    handleEditSubmit: function (e) {
      e.preventDefault();

      const $form = $(e.target);
      const $button = $form.find('button[type="submit"]');
      const $message = $("#scl-edit-message");
      const formData = new FormData($form[0]);

      formData.append("action", "scl_update_establecimiento");

      // Deshabilitar botón
      $button.prop("disabled", true).text(scl_ajax.i18n.loading);
      $message.hide();

      $.ajax({
        url: scl_ajax.ajax_url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            $message
              .removeClass("error")
              .addClass("success")
              .text(response.data.message)
              .show();
            // Recargar página después de un momento
            setTimeout(function () {
              location.reload();
            }, 1500);
          } else {
            $message
              .removeClass("success")
              .addClass("error")
              .text(response.data.message || scl_ajax.i18n.error)
              .show();
          }
        },
        error: function () {
          $message
            .removeClass("success")
            .addClass("error")
            .text(scl_ajax.i18n.error)
            .show();
        },
        complete: function () {
          $button.prop("disabled", false).text("Guardar cambios");
        },
      });
    },
  };

  // Inicializar cuando el DOM esté listo
  $(document).ready(function () {
    SCL.init();
  });
})(jQuery);
