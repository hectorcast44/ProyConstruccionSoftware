// Helpers genéricos para UI: acordeón, búsqueda, estilos de tags

(function (global) {
  'use strict';

  // ==========================================================================
  // 1) TAG STYLE MANAGER
  //    Asigna una clase de tag pastel consistente por "clave" (id o nombre)
  // ==========================================================================

  const TagStyleManager = (function () {
    // 15 colores pastel
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


    // clave de tipo → clase CSS asignada
    const assigned = new Map();

    /**
     * Devuelve una clase de la paleta para una clave (id o nombre de tipo).
     * Mientras haya <= 15 tipos, no habrá repetidos.
     */
    function getClassFor(key) {
      if (key === undefined || key === null) {
        return 'tag-lavanda';
      }

      const k = String(key);

      // Si ya se asignó antes, devuelve la misma
      if (assigned.has(k)) {
        return assigned.get(k);
      }

      // Nuevo tipo → siguiente color de la paleta
      const index = assigned.size % PALETTE.length;
      const cssClass = PALETTE[index];
      assigned.set(k, cssClass);
      return cssClass;
    }

    return { getClassFor };
  })();


  // ==========================================================================
  // 2) ACCORDION GRID
  //    Maneja abrir/cerrar cards y menú contextual para .accordion-card
  // ==========================================================================

  /**
   * Inicializa el comportamiento de acordeón sobre un grid de cards.
   * @param {HTMLElement} container  Contenedor que envuelve las .accordion-card
   */
  function initAccordionGrid(container) {
    if (!container) return;

    container.addEventListener('click', e => {
      const menuToggle      = e.target.closest('.accordion-card__menu-toggle');
      const menuItemDetalle = e.target.closest('.js-card-detail');
      const header          = e.target.closest('.accordion-card__header');
      const actions         = e.target.closest('.accordion-card__actions');

      // Abrir/cerrar menú (tres puntitos)
      if (menuToggle) {
        e.stopPropagation();
        const card = menuToggle.closest('.accordion-card');
        const menu = card.querySelector('.accordion-card__menu');

        document
          .querySelectorAll('.accordion-card__menu.accordion-card__menu--visible')
          .forEach(m => {
            if (m !== menu) m.classList.remove('accordion-card__menu--visible');
          });

        menu.classList.toggle('accordion-card__menu--visible');
        return;
      }

      // Clic en "Detalles" dentro del menú
      if (menuItemDetalle) {
        e.stopPropagation();
        const card = menuItemDetalle.closest('.accordion-card');
        card.classList.toggle('open');

        const menu = menuItemDetalle.closest('.accordion-card__menu');
        menu.classList.remove('accordion-card__menu--visible');
        return;
      }

      // Clic directo en el header (pero no en el área de acciones)
      if (header && !actions) {
        const card = header.closest('.accordion-card');
        card.classList.toggle('open');

        document
          .querySelectorAll('.accordion-card__menu.accordion-card__menu--visible')
          .forEach(m => m.classList.remove('accordion-card__menu--visible'));

        return;
      }
    });

    // Cerrar menú contextual al hacer clic fuera
    document.addEventListener('click', e => {
      if (!e.target.closest('.accordion-card__actions')) {
        document
          .querySelectorAll('.accordion-card__menu.accordion-card__menu--visible')
          .forEach(m => m.classList.remove('accordion-card__menu--visible'));
      }
    });
  }

  // ==========================================================================
  // 3) SEARCH BAR
  //    Maneja el input + botón de mostrar/ocultar y notifica cambios
  // ==========================================================================

  /**
   * Inicializa la barra de búsqueda flotante.
   * @param {Object} cfg
   * @param {HTMLInputElement|null} cfg.input
   * @param {HTMLButtonElement|null} cfg.toggleBtn
   * @param {HTMLElement|null} cfg.wrapper
   * @param {Function} cfg.onFilter  Callback que se ejecuta al cambiar el texto
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
          input && input.focus();
        } else if (input) {
          input.value = '';
          typeof onFilter === 'function' && onFilter('');
        }
      });
    }
  }

  // Exponer en un único namespace
  global.UIHelpers = {
    TagStyleManager,
    initAccordionGrid,
    initSearchBar
  };
})(window);
