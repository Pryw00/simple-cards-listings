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
     * Timer para debounce de búsqueda
     */
    searchTimer: null,

    /**
     * Flag de inicialización
     */
    initialized: false,

    /**
     * Configuración de paginación
     */
    paginationConfig: {
      type: "default",
      perPage: 12,
      currentPage: 1,
      maxPages: 1,
      categoriaFilter: "",
      isLoading: false,
    },

    /**
     * Inicializar
     */
    init: function () {
      // Evitar inicialización múltiple
      if (this.initialized) {
        return;
      }
      this.initialized = true;

      this.loadPaginationConfig();
      this.bindEvents();
      this.initPagination();
    },

    /**
     * Cargar configuración de paginación desde el contenedor
     */
    loadPaginationConfig: function () {
      const $container = $(".scl-container");
      if ($container.length) {
        this.paginationConfig.type =
          $container.data("pagination-type") || "default";
        this.paginationConfig.perPage =
          parseInt($container.data("per-page")) || 12;
        this.paginationConfig.categoriaFilter =
          $container.data("categoria-filter") || "";
        this.paginationConfig.isGold = $container.data("is-gold")
          ? true
          : false;
        this.paginationConfig.onlyLink =
          $container.data("only-link") || "false";

        // Parsear niveles del data attribute
        const levelsData = $container.data("levels");
        if (levelsData) {
          try {
            this.paginationConfig.levels =
              typeof levelsData === "string"
                ? JSON.parse(levelsData)
                : levelsData;
          } catch (e) {
            this.paginationConfig.levels = [];
          }
        } else {
          this.paginationConfig.levels = [];
        }
      }
    },

    /**
     * Inicializar paginación según tipo
     */
    initPagination: function () {
      if (this.paginationConfig.type === "lazy") {
        this.initLazyLoad();
      } else if (this.paginationConfig.type === "load_more") {
        this.initLoadMore();
      } else {
        this.initDefaultPagination();
      }
    },

    /**
     * Vincular eventos
     */
    bindEvents: function () {
      // Desvincular eventos previos para evitar duplicados
      $(document).off("input", "#scl-search-input");
      $(document).off("focus", "#scl-search-input");
      $(document).off("blur", "#scl-search-input");
      $(document).off("mousedown", ".scl-suggestion-item");
      $(document).off("change", "#scl-category-filter");
      $(document).off("click", ".scl-load-more-btn");
      $(document).off("click", ".scl-pagination a");
      $(document).off("click", ".scl-card-item");
      $(document).off("click", ".scl-modal-close, .scl-modal-overlay");
      $(document).off("click", ".scl-tab-btn");

      // Eventos para promociones (cupones)
      $(document).off("change", "#scl-cupones-category-filter");
      $(document).off("input", "#scl-cupones-search");
      $(document).on(
        "change",
        "#scl-cupones-category-filter",
        this.handleCuponesCategoryFilter.bind(this),
      );
      $(document).on(
        "input",
        "#scl-cupones-search",
        this.handleCuponesSearch.bind(this),
      );

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

      // Filtro de categoría
      $(document).on(
        "change",
        "#scl-category-filter",
        this.handleCategoryFilter.bind(this),
      );

      // Filtro de ubicación
      $(document).on(
        "change",
        "#scl-ubicacion-filter",
        this.handleUbicacionFilter.bind(this),
      );

      // Paginación
      $(document).on(
        "click",
        ".scl-load-more-btn",
        this.handleLoadMoreClick.bind(this),
      );
      $(document).on(
        "click",
        ".scl-pagination a",
        this.handlePaginationClick.bind(this),
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

      // Pestañas del dashboard
      $(document).on("click", ".scl-tab-btn", this.handleTabClick.bind(this));

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
      const term = e.target.value.trim();

      clearTimeout(this.searchTimer);

      this.searchTimer = setTimeout(() => {
        this.performSearch(term);
      }, 400);
    },

    /**
     * Manejar filtro de categoría en promociones
     */
    handleCuponesCategoryFilter: function (e) {
      e.preventDefault();
      e.stopPropagation();
      const categorySlug = $(e.target).val();
      const searchTerm = $("#scl-cupones-search").val().trim();
      this.performCuponesSearch(searchTerm, categorySlug);
    },

    /**
     * Manejar búsqueda en promociones
     */
    handleCuponesSearch: function (e) {
      const term = e.target.value.trim();
      const categorySlug = $("#scl-cupones-category-filter").val() || "";
      clearTimeout(this.cuponesSearchTimer);
      this.cuponesSearchTimer = setTimeout(() => {
        this.performCuponesSearch(term, categorySlug);
      }, 400);
    },

    /**
     * Realizar búsqueda/filtrado de promociones (AJAX)
     */
    performCuponesSearch: function (searchTerm, categorySlug) {
      const $grid = $("#scl-cupones-grid");
      const $noResults = $("#scl-cupones-no-results");
      const $container = $(".scl-cupones-container");
      const categoriaBase = $container.data("categoria-base") || "";
      const levels = $container.data("levels") || "";

      $grid.html('<div class="scl-loading"></div>');
      $noResults.hide();

      const requestData = {
        action: "scl_search_cupones",
        nonce: scl_ajax.nonce,
        search_term: searchTerm,
        category_selected: categorySlug,
        categoria_base: categoriaBase,
        levels: JSON.stringify(levels),
      };
      console.log("SCL Cupones Request:", requestData);

      $.ajax({
        url: scl_ajax.ajax_url,
        type: "POST",
        data: requestData,
        success: function (response) {
          console.log("SCL Cupones Debug:", response);
          if (response.success && response.data && response.data.html) {
            $grid.html(response.data.html);
            $noResults.hide();
          } else {
            $grid.html("");
            $noResults.show();
          }
        },
        error: function () {
          $grid.html(
            '<p class="scl-message scl-message-error">' +
              (scl_ajax.i18n ? scl_ajax.i18n.error : "Error") +
              "</p>",
          );
        },
      });
    },

    /**
     * Manejar filtro de categoría
     */
    handleCategoryFilter: function (e) {
      e.preventDefault();
      e.stopPropagation();

      const categorySlug = $(e.target).val();
      const searchTerm = $("#scl-search-input").val().trim();
      const ubicacionSlug = $("#scl-ubicacion-filter").val() || "";

      // Evitar ejecuciones múltiples
      if (this.paginationConfig.isLoading) {
        return;
      }

      // Siempre recargar con AJAX cuando cambia el filtro
      this.paginationConfig.currentPage = 1;
      this.performSearch(searchTerm, categorySlug, ubicacionSlug);
    },

    /**
     * Manejar cambio de filtro de ubicación
     */
    handleUbicacionFilter: function (e) {
      e.preventDefault();
      e.stopPropagation();

      const ubicacionSlug = $(e.target).val();
      const searchTerm = $("#scl-search-input").val().trim();
      const categorySlug = $("#scl-category-filter").val() || "";

      // Evitar ejecuciones múltiples
      if (this.paginationConfig.isLoading) {
        return;
      }

      // Siempre recargar con AJAX cuando cambia el filtro
      this.paginationConfig.currentPage = 1;
      this.performSearch(searchTerm, categorySlug, ubicacionSlug);
    },

    /**
     * Realizar búsqueda (por AJAX, busca en TODA la base de datos)
     */
    performSearch: function (searchTerm, categorySlug, ubicacionSlug) {
      const self = this;
      const $grid = $("#scl-grid");
      const $noResults = $("#scl-no-results");
      const $pagination = $(".scl-pagination-wrapper");

      categorySlug = categorySlug || $("#scl-category-filter").val() || "";
      ubicacionSlug = ubicacionSlug || $("#scl-ubicacion-filter").val() || "";
      searchTerm = searchTerm || "";

      // Si no hay búsqueda ni filtro, recargar página inicial
      if (!searchTerm && !categorySlug && !ubicacionSlug) {
        this.reloadGrid("", "", "");
        return;
      }

      if (this.paginationConfig.isLoading) return;
      this.paginationConfig.isLoading = true;

      // Mostrar loading
      $grid.html('<div class="scl-loading"></div>');
      $noResults.hide();
      $pagination.hide(); // Ocultar paginación durante búsqueda

      $.ajax({
        url: scl_ajax.ajax_url,
        type: "POST",
        data: {
          action: "scl_search",
          nonce: scl_ajax.nonce,
          search_term: searchTerm,
          categoria_filter: this.paginationConfig.categoriaFilter,
          category_selected: categorySlug,
          ubicacion_selected: ubicacionSlug,
          is_gold: this.paginationConfig.isGold || false,
          levels: JSON.stringify(this.paginationConfig.levels || []),
          only_link: this.paginationConfig.onlyLink || "false",
        },
        success: function (response) {
          if (response.success) {
            if (response.data.html) {
              $grid.html(response.data.html);
              $noResults.hide();
            } else {
              $grid.html("");
              $noResults.show();
            }
            // No mostrar paginación cuando hay búsqueda activa
          } else {
            $grid.html("");
            $noResults.show();
          }
        },
        error: function () {
          $grid.html(
            '<p class="scl-message scl-message-error">' +
              scl_ajax.i18n.error +
              "</p>",
          );
        },
        complete: function () {
          self.paginationConfig.isLoading = false;
        },
      });
    },

    /**
     * Recargar grid con AJAX
     */
    reloadGrid: function (searchTerm, categorySlug, ubicacionSlug) {
      const self = this;
      const $grid = $("#scl-grid");
      const $pagination = $(".scl-pagination-wrapper");

      if (this.paginationConfig.isLoading) return;
      this.paginationConfig.isLoading = true;

      // Mostrar loading
      $grid.html('<div class="scl-loading"></div>');

      $.ajax({
        url: scl_ajax.ajax_url,
        type: "POST",
        data: {
          action: "scl_load_more",
          nonce: scl_ajax.nonce,
          page: 1,
          per_page: this.paginationConfig.perPage,
          categoria_filter: this.paginationConfig.categoriaFilter,
          category_selected: categorySlug,
          ubicacion_selected: ubicacionSlug,
          search_term: searchTerm,
          is_gold: this.paginationConfig.isGold || false,
          levels: JSON.stringify(this.paginationConfig.levels || []),
          only_link: this.paginationConfig.onlyLink || "false",
        },
        success: function (response) {
          if (response.success) {
            $grid.html(response.data.html);
            self.paginationConfig.maxPages = response.data.max_pages;
            self.updatePaginationControls(1, response.data.has_more);
            $pagination.show(); // Mostrar paginación
          }
        },
        error: function () {
          $grid.html(
            '<p class="scl-message scl-message-error">' +
              scl_ajax.i18n.error +
              "</p>",
          );
        },
        complete: function () {
          self.paginationConfig.isLoading = false;
        },
      });
    },

    /**
     * Inicializar Lazy Load
     */
    initLazyLoad: function () {
      const self = this;
      const $window = $(window);
      const $loader = $(".scl-lazy-loader");

      if (!$loader.length) return;

      this.paginationConfig.maxPages = parseInt($loader.data("max-pages")) || 1;

      $window.on("scroll", function () {
        if (self.paginationConfig.isLoading) return;
        if (self.paginationConfig.currentPage >= self.paginationConfig.maxPages)
          return;

        const scrollTop = $window.scrollTop();
        const windowHeight = $window.height();
        const documentHeight = $(document).height();

        // Si está cerca del final (200px antes)
        if (scrollTop + windowHeight >= documentHeight - 200) {
          self.loadMoreEstablecimientos();
        }
      });
    },

    /**
     * Inicializar botón "Cargar más"
     */
    initLoadMore: function () {
      const $btn = $(".scl-load-more-btn");
      if ($btn.length) {
        this.paginationConfig.maxPages = parseInt($btn.data("max-pages")) || 1;
      }
    },

    /**
     * Inicializar paginación tradicional
     */
    initDefaultPagination: function () {
      const $pagination = $(".scl-pagination");
      if ($pagination.length) {
        this.paginationConfig.maxPages =
          parseInt($pagination.data("max-pages")) || 1;
      }
    },

    /**
     * Manejar click en botón "Cargar más"
     */
    handleLoadMoreClick: function (e) {
      e.preventDefault();
      this.loadMoreEstablecimientos();
    },

    /**
     * Manejar click en paginación tradicional
     */
    handlePaginationClick: function (e) {
      e.preventDefault();
      const $link = $(e.currentTarget);
      const href = $link.attr("href");

      // Extraer número de página del href
      const match = href.match(/paged=(\d+)/);
      if (match) {
        const page = parseInt(match[1]);
        this.loadPage(page, true);
      }
    },

    /**
     * Cargar más establecimientos (para lazy y load_more)
     */
    loadMoreEstablecimientos: function () {
      const nextPage = this.paginationConfig.currentPage + 1;
      this.loadPage(nextPage, false);
    },

    /**
     * Cargar página específica
     */
    loadPage: function (page, replace) {
      const self = this;
      const $grid = $("#scl-grid");
      const categorySelected = $("#scl-category-filter").val() || "";
      const ubicacionSelected = $("#scl-ubicacion-filter").val() || "";
      const searchTerm = $("#scl-search-input").val().toLowerCase().trim();

      if (this.paginationConfig.isLoading) return;
      if (page > this.paginationConfig.maxPages) return;

      this.paginationConfig.isLoading = true;

      // Mostrar loader
      if (this.paginationConfig.type === "lazy") {
        $(".scl-lazy-loader").show();
      } else if (this.paginationConfig.type === "load_more") {
        $(".scl-load-more-btn").prop("disabled", true).text("Cargando...");
      } else if (replace) {
        $grid.html('<div class="scl-loading"></div>');
        $("html, body").animate({ scrollTop: $grid.offset().top - 100 }, 300);
      }

      $.ajax({
        url: scl_ajax.ajax_url,
        type: "POST",
        data: {
          action: "scl_load_more",
          nonce: scl_ajax.nonce,
          page: page,
          per_page: this.paginationConfig.perPage,
          categoria_filter: this.paginationConfig.categoriaFilter,
          category_selected: categorySelected,
          ubicacion_selected: ubicacionSelected,
          search_term: searchTerm,
          is_gold: this.paginationConfig.isGold || false,
          levels: JSON.stringify(this.paginationConfig.levels || []),
          only_link: this.paginationConfig.onlyLink || "false",
        },
        success: function (response) {
          if (response.success) {
            if (replace) {
              $grid.html(response.data.html);
            } else {
              $grid.append(response.data.html);
            }
            self.paginationConfig.currentPage = page;
            self.paginationConfig.maxPages = response.data.max_pages;
            self.updatePaginationControls(page, response.data.has_more);
          }
        },
        error: function () {
          if (replace) {
            $grid.html(
              '<p class="scl-message scl-message-error">' +
                scl_ajax.i18n.error +
                "</p>",
            );
          }
        },
        complete: function () {
          self.paginationConfig.isLoading = false;
          if (self.paginationConfig.type === "lazy") {
            $(".scl-lazy-loader").hide();
          } else if (self.paginationConfig.type === "load_more") {
            $(".scl-load-more-btn")
              .prop("disabled", false)
              .text(scl_ajax.i18n.load_more || "Cargar más");
          }
        },
      });
    },

    /**
     * Actualizar controles de paginación
     */
    updatePaginationControls: function (currentPage, hasMore) {
      if (this.paginationConfig.type === "load_more") {
        const $btn = $(".scl-load-more-btn");
        $btn.data("page", currentPage);
        if (!hasMore) {
          $btn.hide();
        } else {
          $btn.show();
        }
      } else if (this.paginationConfig.type === "default") {
        // Regenerar links de paginación
        this.regeneratePaginationLinks(currentPage);
      }
    },

    /**
     * Regenerar links de paginación
     */
    regeneratePaginationLinks: function (currentPage) {
      const $pagination = $(".scl-pagination");
      const maxPages = this.paginationConfig.maxPages;

      if (!$pagination.length || maxPages <= 1) return;

      let html = "";

      // Botón anterior
      if (currentPage > 1) {
        html +=
          '<a href="?paged=' +
          (currentPage - 1) +
          '" class="prev page-numbers">&laquo;</a>';
      }

      // Números de página
      for (let i = 1; i <= maxPages; i++) {
        if (i === currentPage) {
          html += '<span class="page-numbers current">' + i + "</span>";
        } else {
          html +=
            '<a href="?paged=' + i + '" class="page-numbers">' + i + "</a>";
        }
      }

      // Botón siguiente
      if (currentPage < maxPages) {
        html +=
          '<a href="?paged=' +
          (currentPage + 1) +
          '" class="next page-numbers">&raquo;</a>';
      }

      $pagination.html(html);
      $pagination.data("current-page", currentPage);
    },

    /**
     * Filtrar establecimientos (búsqueda local en el lado del cliente)
     */
    filterEstablecimientos: function (term, categorySlug) {
      const $grid = $("#scl-grid");
      const $noResults = $("#scl-no-results");
      let visibleCount = 0;

      // Si no hay término de búsqueda ni categoría, mostrar todos
      if (!term && !categorySlug) {
        $grid.find(".scl-card-item").removeClass("scl-hidden");
        $noResults.hide();
        return;
      }

      // Permitir búsqueda por todas las palabras (AND)
      const terms = term ? term.split(/\s+/).filter(Boolean) : [];

      this.establecimientosData.forEach((item) => {
        const $card = $grid.find('.scl-card-item[data-id="' + item.id + '"]');
        if (!$card.length) return;

        const searchableText = [
          item.title,
          item.description,
          ...(item.categories || []),
          ...(item.tags || []),
        ]
          .join(" ")
          .toLowerCase();

        // Verificar si coincide con el término de búsqueda
        const textMatch =
          terms.length === 0 ||
          terms.every((word) => searchableText.includes(word));

        // Verificar si coincide con la categoría
        const categoryMatch =
          !categorySlug ||
          (item.categories &&
            item.categories.some(
              (cat) => cat.toLowerCase() === categorySlug.toLowerCase(),
            ));

        if (textMatch && categoryMatch) {
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
      this.performSearch(term);
    },

    /**
     * Abrir modal de establecimiento
     */
    openModal: function (e) {
      const $target = $(e.currentTarget);

      // No abrir modal si el elemento es un enlace (only_link=true)
      if ($target.is("a") || !$target.data("id")) {
        return;
      }

      const postId = $target.data("id");
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
        $("#scl-modal, #scl-promocion-modal").hide();
        $("body").removeClass("scl-modal-open").css("overflow", "");
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

    /**
     * Manejar click en pestañas del dashboard
     */
    handleTabClick: function (e) {
      e.preventDefault();
      const $btn = $(e.currentTarget);
      const tab = $btn.data("tab");

      // Actualizar botones
      $(".scl-tab-btn").removeClass("active");
      $btn.addClass("active");

      // Actualizar contenido
      $(".scl-tab-content").removeClass("active");
      $(`.scl-tab-content[data-tab="${tab}"]`).addClass("active");
    },

    /**
     * Abrir modal de nueva promoción
     */
    openPromocionModal: function (promocionId) {
      const $modal = $("#scl-promocion-modal");
      const $form = $("#scl-promocion-form");
      const $titulo = $("#scl-promocion-modal-titulo");

      // Limpiar formulario
      $form[0].reset();
      $("#scl-promocion-id").val("");
      $("#scl-promo-imagen-preview").hide();
      $("#scl-promo-form-message").hide();

      if (promocionId) {
        // Modo edición - cargar datos
        $titulo.text("Editar Promoción");
        this.loadPromocionData(promocionId);
      } else {
        // Modo crear
        $titulo.text("Nueva Promoción");
      }

      $modal.fadeIn(300);
      $("body").addClass("scl-modal-open");
    },

    /**
     * Cargar datos de promoción para editar
     */
    loadPromocionData: function (promocionId) {
      const self = this;

      $.ajax({
        url: scl_ajax.ajax_url,
        type: "POST",
        data: {
          action: "scl_get_cupon",
          nonce: scl_ajax.nonce,
          post_id: promocionId,
        },
        success: function (response) {
          if (response.success) {
            const data = response.data.cupon;
            $("#scl-promocion-id").val(data.id);
            $("#scl-promo-titulo").val(data.titulo);
            $("#scl-promo-descripcion").val(
              data.descripcion.replace(/<[^>]*>/g, ""),
            );
            $("#scl-promo-establecimiento").val(
              data.establecimiento ? data.establecimiento.id : "",
            );

            // Cargar fechas desde meta
            self.loadPromocionMeta(promocionId);

            // Mostrar imagen actual
            if (data.imagen) {
              $("#scl-promo-imagen-preview img").attr("src", data.imagen);
              $("#scl-promo-imagen-preview").show();
            }
          }
        },
      });
    },

    /**
     * Cargar meta de promoción (fechas)
     */
    loadPromocionMeta: function (promocionId) {
      $.ajax({
        url: scl_ajax.ajax_url,
        type: "POST",
        data: {
          action: "scl_get_promocion_meta",
          nonce: scl_ajax.nonce,
          promocion_id: promocionId,
        },
        success: function (response) {
          if (response.success && response.data) {
            if (response.data.fecha_inicio) {
              $("#scl-promo-fecha-inicio").val(response.data.fecha_inicio);
            }
            if (response.data.fecha_fin) {
              $("#scl-promo-fecha-fin").val(response.data.fecha_fin);
            }
          }
        },
      });
    },

    /**
     * Enviar formulario de promoción
     */
    submitPromocionForm: function (e) {
      e.preventDefault();

      const $form = $("#scl-promocion-form");
      const $button = $("#scl-promo-submit");
      const $message = $("#scl-promo-form-message");
      const formData = new FormData($form[0]);

      formData.append("action", "scl_submit_cupon");
      formData.append("nonce", scl_ajax.nonce);

      $button.prop("disabled", true).text("Guardando...");
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
              .text("Promoción guardada exitosamente")
              .show();

            setTimeout(function () {
              location.reload();
            }, 1500);
          } else {
            $message
              .removeClass("success")
              .addClass("error")
              .text(response.data.message || "Error al guardar la promoción")
              .show();
            $button.prop("disabled", false).text("Guardar Promoción");
          }
        },
        error: function () {
          $message
            .removeClass("success")
            .addClass("error")
            .text("Error de conexión")
            .show();
          $button.prop("disabled", false).text("Guardar Promoción");
        },
      });
    },

    /**
     * Eliminar promoción
     */
    deletePromocion: function (promocionId) {
      if (!confirm("¿Estás seguro de eliminar esta promoción?")) {
        return;
      }

      $.ajax({
        url: scl_ajax.ajax_url,
        type: "POST",
        data: {
          action: "scl_delete_cupon",
          nonce: scl_ajax.nonce,
          cupon_id: promocionId,
        },
        success: function (response) {
          if (response.success) {
            location.reload();
          } else {
            alert(response.data.message || "Error al eliminar la promoción");
          }
        },
      });
    },
  };

  // Inicializar cuando el DOM esté listo
  $(document).ready(function () {
    SCL.init();

    // Botón crear promoción
    $(document).on(
      "click",
      "#scl-btn-nueva-promocion, #scl-btn-nueva-promocion-inline",
      function (e) {
        e.preventDefault();
        SCL.openPromocionModal();
      },
    );

    // Botón editar promoción
    $(document).on("click", ".scl-btn-editar-promocion", function (e) {
      e.preventDefault();
      const promocionId = $(this).data("id");
      SCL.openPromocionModal(promocionId);
    });

    // Botón eliminar promoción
    $(document).on("click", ".scl-btn-eliminar-promocion", function (e) {
      e.preventDefault();
      const promocionId = $(this).data("id");
      SCL.deletePromocion(promocionId);
    });

    // Submit del formulario de promoción
    $(document).on("submit", "#scl-promocion-form", function (e) {
      SCL.submitPromocionForm(e);
    });

    // Preview de imagen
    $(document).on("change", "#scl-promo-imagen", function (e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
          $("#scl-promo-imagen-preview img").attr("src", e.target.result);
          $("#scl-promo-imagen-preview").show();
        };
        reader.readAsDataURL(file);
      }
    });
  });
})(jQuery);
