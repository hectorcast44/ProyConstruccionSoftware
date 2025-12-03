/**
 * Helpers genéricos para la UI.
 *
 * Proporciona:
 *  - TagStyleManager: asigna clases de color pastel consistentes a los tags.
 *  - initAccordionGrid: controla la interacción de las cards tipo acordeón.
 *  - initSearchBar: gestiona la barra de búsqueda flotante y sus eventos.
 */
(function (global) {
  'use strict';

  // ==========================================================================
  // TagStyleManager
  //   Asigna una clase de tag pastel consistente por "clave" (id o nombre).
  // ==========================================================================

  const TagStyleManager = (function () {
    /**
     * Paleta de clases CSS para tags pastel.
     * Se usan de forma cíclica conforme aparecen nuevos tipos.
     * @type {string[]}
     */
    const PALETTE = [
      'tag-rojo',
      'tag-azul',
      'tag-amarillo',
      'tag-morado',
      'tag-naranja',
      'tag-agua',
      'tag-magenta',
      'tag-verde',
      'tag-rosa',
      'tag-azul-claro',
      'tag-melocoton',
      'tag-lavanda',
      'tag-verde-lima',
      'tag-dorado',
      'tag-durazno'
    ];

    /**
     * Mapa clave → clase CSS asignada.
     * Mantiene la consistencia entre recargas de datos.
     * @type {Map<string, string>}
     */
    const assigned = new Map();

    /**
     * Devuelve una clase de la paleta para una clave (id o nombre de tipo).
     * Mientras haya menos o igual a PALETTE.length claves distintas,
     * no se repiten colores.
     *
     * @param {string|number|null|undefined} key Clave lógica del tipo.
     * @returns {string} Clase CSS a aplicar en el tag.
     */
    function getClassFor(key) {
      if (key === undefined || key === null) {
        return 'tag-lavanda';
      }

      const normalizedKey = String(key);

      // Si ya se asignó antes, devolver siempre la misma clase
      if (assigned.has(normalizedKey)) {
        return assigned.get(normalizedKey);
      }

      // Nuevo tipo → siguiente color de la paleta (uso cíclico)
      const index = assigned.size % PALETTE.length;
      const cssClass = PALETTE[index];

      assigned.set(normalizedKey, cssClass);
      return cssClass;
    }

    return { getClassFor };
  })();

  // ==========================================================================
  // Accordion grid
  //   Maneja abrir/cerrar cards y el menú contextual de .accordion-card
  // ==========================================================================

  /**
   * Inicializa el comportamiento de acordeón sobre un grid de cards.
   *
   * - Abre/cierra el panel de una card al hacer clic en el header.
   * - Abre/cierra el menú contextual de la card (tres puntitos).
   * - Cierra menús de otras cards cuando se abre uno nuevo.
   *
   * @param {HTMLElement|null} container Contenedor que envuelve las .accordion-card.
   * @returns {void}
   */
  function initAccordionGrid(container) {
    if (!container) return;

    container.addEventListener('click', event => {
      const menuToggle = event.target.closest('.accordion-card__menu-toggle');
      const menuItemDetalle = event.target.closest('.js-card-detail');
      const header = event.target.closest('.accordion-card__header');
      const actions = event.target.closest('.accordion-card__actions');

      // Abrir/cerrar menú contextual (tres puntitos)
      if (menuToggle) {
        event.stopPropagation();

        const card = menuToggle.closest('.accordion-card');
        const menu = card.querySelector('.accordion-card__menu');

        const openMenus = document.querySelectorAll(
          '.accordion-card__menu.accordion-card__menu--visible'
        );

        for (const m of openMenus) {
          if (m !== menu) {
            m.classList.remove('accordion-card__menu--visible');
          }
        }

        menu.classList.toggle('accordion-card__menu--visible');
        return;
      }

      // Clic en "Detalles" dentro del menú contextual
      if (menuItemDetalle) {
        event.stopPropagation();

        const card = menuItemDetalle.closest('.accordion-card');
        card.classList.toggle('open');

        const menu = menuItemDetalle.closest('.accordion-card__menu');
        if (menu) {
          menu.classList.remove('accordion-card__menu--visible');
        }
        return;
      }

      // Clic directo en el header (pero no en el área de acciones)
      if (header && !actions) {
        const card = header.closest('.accordion-card');
        card.classList.toggle('open');

        const openMenus = document.querySelectorAll(
          '.accordion-card__menu.accordion-card__menu--visible'
        );

        for (const m of openMenus) {
          m.classList.remove('accordion-card__menu--visible');
        }
      }
    });

    // Cerrar menú contextual al hacer clic fuera de las acciones
    document.addEventListener('click', event => {
      if (!event.target.closest('.accordion-card__actions')) {
        const openMenus = document.querySelectorAll(
          '.accordion-card__menu.accordion-card__menu--visible'
        );

        for (const m of openMenus) {
          m.classList.remove('accordion-card__menu--visible');
        }
      }
    });
  }

  // ==========================================================================
  // Search bar
  //   Maneja el input + botón de mostrar/ocultar y notifica cambios
  // ==========================================================================

  /**
   * Inicializa la barra de búsqueda flotante.
   *
   * - Llama a `onFilter(valor)` cada vez que cambia el texto del input.
   * - Muestra u oculta el wrapper al hacer clic en el botón de toggle.
   * - Limpia el input y notifica filtro vacío al cerrar la barra.
   *
   * @param {Object} params Parámetros de configuración.
   * @param {HTMLInputElement|null} params.input Campo de texto donde se escribe el filtro.
   * @param {HTMLButtonElement|null} params.toggleBtn Botón que abre/cierra la barra.
   * @param {HTMLElement|null} params.wrapper Contenedor visual de la barra.
   * @param {(valor:string) => void} params.onFilter Callback que se ejecuta al cambiar el filtro.
   * @returns {void}
   */
  function initSearchBar({ input, toggleBtn, wrapper, onFilter }) {
    if (!input && !toggleBtn) return;

    if (input && typeof onFilter === 'function') {
      input.addEventListener('input', () => {
        onFilter(input.value);
      });
    }

    if (toggleBtn && wrapper) {
      toggleBtn.addEventListener('click', () => {
        wrapper.classList.toggle('active');

        if (wrapper.classList.contains('active')) {
          if (input) input.focus();
        } else if (input) {
          input.value = '';
          if (typeof onFilter === 'function') {
            onFilter('');
          }
        }
      });
    }
  }

  /**
   * Namespace global para utilidades de interfaz.
   * @type {{TagStyleManager: {getClassFor: function(string|number|null|undefined):string},
   *         initAccordionGrid: function(HTMLElement|null):void,
   *         initSearchBar: function(Object):void}}
   */
  global.UIHelpers = {
    TagStyleManager,
    initAccordionGrid,
    initSearchBar
  };
})(globalThis);

if (typeof module !== 'undefined' && module.exports) {
  module.exports = globalThis.UIHelpers;
}
