/**
 * JavaScript para grid de cupones
 *
 * @package SimpleCardsListings
 * @since 1.1.0
 */

(function ($) {
  "use strict";

  let searchTimeout = null;

  $(document).ready(function () {
    initCuponesGrid();
    initCuponModal();
    initAutoOpenModal();
  });

  /**
   * Inicializar grid de cupones
   */
  function initCuponesGrid() {
    // Búsqueda en tiempo real
    $(document).on("input", "#scl-cupones-search", function () {
      clearTimeout(searchTimeout);
      const searchTerm = $(this).val();

      searchTimeout = setTimeout(function () {
        searchCupones(searchTerm);
      }, 400);
    });

    // Click en botón de búsqueda
    $(document).on("click", ".scl-search-button", function (e) {
      e.preventDefault();
      const searchTerm = $("#scl-cupones-search").val();
      searchCupones(searchTerm);
    });

    // Abrir modal al hacer click en cupón
    $(document).on("click", ".scl-ver-cupon", function (e) {
      e.preventDefault();
      const cuponCard = $(this).closest(".scl-cupon-card");
      const cuponId = cuponCard.data("id");
      openCuponModal(cuponId);
    });
  }

  /**
   * Buscar cupones via AJAX
   */
  function searchCupones(searchTerm) {
    const $grid = $("#scl-cupones-grid");
    const $noResults = $("#scl-cupones-no-results");

    $.ajax({
      url: sclData.ajaxUrl,
      type: "POST",
      data: {
        action: "scl_search_cupones",
        nonce: sclData.nonce,
        search: searchTerm,
      },
      beforeSend: function () {
        $grid.addClass("scl-loading");
      },
      success: function (response) {
        if (response.success) {
          $grid.html(response.data.html);

          if (response.data.found === 0) {
            $noResults.show();
            $grid.hide();
          } else {
            $noResults.hide();
            $grid.show();
          }
        }
      },
      complete: function () {
        $grid.removeClass("scl-loading");
      },
    });
  }

  /**
   * Abrir modal de cupón
   */
  function openCuponModal(cuponId) {
    const $modal = $("#scl-cupon-modal");
    const $modalBody = $("#scl-cupon-modal-body");

    $.ajax({
      url: sclData.ajaxUrl,
      type: "POST",
      data: {
        action: "scl_get_cupon",
        nonce: sclData.nonce,
        post_id: cuponId,
      },
      beforeSend: function () {
        $modalBody.html('<div class="scl-loading-spinner"></div>');
        $modal.fadeIn(300);
        $("body").addClass("scl-modal-open");
      },
      success: function (response) {
        if (response.success) {
          const cupon = response.data.cupon;
          const html = renderCuponModalContent(cupon);
          $modalBody.html(html);
        } else {
          $modalBody.html(
            '<p class="scl-error">' + response.data.message + "</p>",
          );
        }
      },
      error: function () {
        $modalBody.html('<p class="scl-error">Error al cargar el cupón.</p>');
      },
    });
  }

  /**
   * Renderizar contenido del modal
   */
  function renderCuponModalContent(cupon) {
    let html = '<div class="scl-cupon-modal-inner">';

    // Imagen
    html += '<div class="scl-cupon-modal-imagen">';
    html += '<img src="' + cupon.imagen + '" alt="' + cupon.titulo + '">';
    if (cupon.destacado) {
      html += '<div class="scl-cupon-badge-destacado">⭐ Destacado</div>';
    }
    html += "</div>";

    // Información
    html += '<div class="scl-cupon-modal-info">';

    if (cupon.establecimiento) {
      html += '<div class="scl-cupon-establecimiento">';
      html +=
        '<a href="' +
        cupon.establecimiento.url +
        '" target="_blank">' +
        cupon.establecimiento.titulo +
        "</a>";
      html += "</div>";
    }

    html += '<h2 class="scl-cupon-modal-titulo">' + cupon.titulo + "</h2>";
    html +=
      '<div class="scl-cupon-modal-descripcion">' +
      cupon.descripcion +
      "</div>";

    // Fechas
    if (cupon.fecha_inicio && cupon.fecha_fin) {
      html += '<div class="scl-cupon-fechas">';
      html +=
        '<div class="scl-cupon-fecha"><strong>Válido desde:</strong> ' +
        cupon.fecha_inicio +
        "</div>";
      html +=
        '<div class="scl-cupon-fecha"><strong>Válido hasta:</strong> ' +
        cupon.fecha_fin +
        "</div>";
      html += "</div>";
    }

    // Botones de acción
    html += '<div class="scl-cupon-acciones">';

    // Botón compartir
    html +=
      '<button type="button" class="scl-btn scl-btn-secondary scl-share-cupon" data-url="' +
      cupon.share_url +
      '">';
    html +=
      '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path><polyline points="16 6 12 2 8 6"></polyline><line x1="12" y1="2" x2="12" y2="15"></line></svg>';
    html += " Compartir";
    html += "</button>";

    // Botón descargar imagen
    html +=
      '<button type="button" class="scl-btn scl-btn-secondary scl-download-cupon" data-image="' +
      cupon.imagen +
      '">';
    html +=
      '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>';
    html += " Descargar";
    html += "</button>";

    html += "</div>"; // .scl-cupon-acciones
    html += "</div>"; // .scl-cupon-modal-info
    html += "</div>"; // .scl-cupon-modal-inner

    return html;
  }

  /**
   * Inicializar modal
   */
  function initCuponModal() {
    // Cerrar modal
    $(document).on(
      "click",
      ".scl-modal-close, .scl-modal-overlay",
      function () {
        closeCuponModal();
      },
    );

    // Cerrar con ESC
    $(document).on("keydown", function (e) {
      if (e.key === "Escape" && $("#scl-cupon-modal").is(":visible")) {
        closeCuponModal();
      }
    });

    // Compartir cupón
    $(document).on("click", ".scl-share-cupon", function () {
      const url = $(this).data("url");

      // Copiar al portapapeles
      if (navigator.clipboard) {
        navigator.clipboard
          .writeText(url)
          .then(function () {
            showNotification("Enlace copiado al portapapeles", "success");
          })
          .catch(function () {
            fallbackCopyToClipboard(url);
          });
      } else {
        fallbackCopyToClipboard(url);
      }
    });

    // Descargar imagen
    $(document).on("click", ".scl-download-cupon", function () {
      const imageUrl = $(this).data("image");
      downloadImage(imageUrl);
    });
  }

  /**
   * Cerrar modal
   */
  function closeCuponModal() {
    $("#scl-cupon-modal").fadeOut(300);
    $("body").removeClass("scl-modal-open");
  }

  /**
   * Fallback para copiar al portapapeles
   */
  function fallbackCopyToClipboard(text) {
    const $temp = $("<input>");
    $("body").append($temp);
    $temp.val(text).select();
    try {
      document.execCommand("copy");
      showNotification("Enlace copiado al portapapeles", "success");
    } catch (err) {
      showNotification("No se pudo copiar el enlace", "error");
    }
    $temp.remove();
  }

  /**
   * Descargar imagen
   */
  function downloadImage(imageUrl) {
    const link = document.createElement("a");
    link.href = imageUrl;
    link.download = "cupon-" + Date.now() + ".jpg";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    showNotification("Imagen descargada", "success");
  }

  /**
   * Mostrar notificación
   */
  function showNotification(message, type) {
    const $notification = $(
      '<div class="scl-notification scl-notification-' +
        type +
        '">' +
        message +
        "</div>",
    );
    $("body").append($notification);

    setTimeout(function () {
      $notification.addClass("scl-notification-show");
    }, 10);

    setTimeout(function () {
      $notification.removeClass("scl-notification-show");
      setTimeout(function () {
        $notification.remove();
      }, 300);
    }, 3000);
  }

  /**
   * Auto-abrir modal si hay cupon_id en URL
   */
  function initAutoOpenModal() {
    const urlParams = new URLSearchParams(window.location.search);
    const cuponId = urlParams.get("cupon_id");

    if (cuponId && $(".scl-cupones-container").length > 0) {
      openCuponModal(cuponId);

      // Limpiar URL sin recargar
      if (window.history && window.history.replaceState) {
        const newUrl =
          window.location.protocol +
          "//" +
          window.location.host +
          window.location.pathname;
        window.history.replaceState({ path: newUrl }, "", newUrl);
      }
    }
  }
})(jQuery);
