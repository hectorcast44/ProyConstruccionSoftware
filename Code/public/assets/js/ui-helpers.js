/**
 * Módulo de utilidades de interfaz de usuario.
 * Proporciona helpers para colores de etiquetas, acordeones y barra de búsqueda.
 * Se expone globalmente como `UIHelpers`.
 */
const UIHelpers = (() => {

  /**
   * Gestor de estilos para etiquetas (tags).
   * Asigna colores pastel consistentes basados en el texto de la etiqueta.
   */
  const TagStyleManager = {
    colors: [
      { bg: '#e3f2fd', text: '#1976d2' }, // Azul
      { bg: '#e8f5e9', text: '#2e7d32' }, // Verde
      { bg: '#fff3e0', text: '#ef6c00' }, // Naranja
      { bg: '#f3e5f5', text: '#7b1fa2' }, // Púrpura
      { bg: '#e0f7fa', text: '#0097a7' }, // Cyan
      { bg: '#fce4ec', text: '#c2185b' }, // Rosa
      { bg: '#f1f8e9', text: '#558b2f' }, // Verde claro
      { bg: '#fff8e1', text: '#ffa000' }  // Ámbar
    ],
    cache: new Map(),

    /**
     * Obtiene el estilo (fondo y color de texto) para una etiqueta dada.
     * Si la etiqueta ya tiene un estilo asignado, lo devuelve de la caché.
     * Si no, genera uno nuevo basado en el hash del texto.
     *
     * @param {string} text Texto de la etiqueta.
     * @returns {Object} Objeto con propiedades `bg` y `text`.
     */
    getStyle(text) {
      if (!text) return this.colors[0];
      if (this.cache.has(text)) return this.cache.get(text);

      // Hash simple para consistencia
      let hash = 0;
      for (let i = 0; i < text.length; i++) {
        hash = text.charCodeAt(i) + ((hash << 5) - hash);
      }

      const index = Math.abs(hash) % this.colors.length;
      const style = this.colors[index];
      this.cache.set(text, style);
      return style;
    },

    /**
     * Aplica el estilo calculado a un elemento DOM.
     *
     * @param {HTMLElement} element Elemento al que aplicar el estilo.
     * @param {string} text Texto base para calcular el color.
     */
    applyStyle(element, text) {
      const style = this.getStyle(text);
      element.style.backgroundColor = style.bg;
      element.style.color = style.text;
    }
  };

  /**
   * Inicializa la funcionalidad de acordeón para tarjetas de materias.
   * Maneja la apertura/cierre de paneles y la interacción con menús contextuales.
   *
   * @returns {void}
   */
  function initAccordionGrid() {
    const grid = document.querySelector('.materias-grid');
    if (!grid) return;

    grid.addEventListener('click', (e) => {
      // Manejo de menú contextual (tres puntos)
      const menuBtn = e.target.closest('.materia-menu-btn');
      if (menuBtn) {
        e.stopPropagation();
        // Cerrar otros menús abiertos
        document.querySelectorAll('.materia-menu-content.show').forEach(m => {
          if (m !== menuBtn.nextElementSibling) m.classList.remove('show');
        });
        const menu = menuBtn.nextElementSibling;
        menu.classList.toggle('show');
        return;
      }

      // Cerrar menús al hacer click fuera
      if (!e.target.closest('.materia-menu-content')) {
        document.querySelectorAll('.materia-menu-content.show').forEach(m => {
          m.classList.remove('show');
        });
      }

      // Manejo del acordeón (expandir/contraer tarjeta)
      const header = e.target.closest('.materia-header');
      // Ignorar si el click fue en botones de acción dentro del header
      if (header && !e.target.closest('.materia-actions') && !e.target.closest('.materia-menu')) {
        const card = header.closest('.materia-card');
        const content = card.querySelector('.materia-content');
        const icon = header.querySelector('.chevron-icon');

        // Toggle actual
        const isClosed = content.style.maxHeight === '0px' || !content.style.maxHeight;

        if (isClosed) {
          // Abrir
          content.style.maxHeight = content.scrollHeight + 'px';
          content.style.opacity = '1';
          content.style.marginTop = '1rem';
          if (icon) icon.style.transform = 'rotate(180deg)';
        } else {
          // Cerrar
          content.style.maxHeight = '0px';
          content.style.opacity = '0';
          content.style.marginTop = '0';
          if (icon) icon.style.transform = 'rotate(0deg)';
        }
      }
    });

    // Cerrar menús al hacer click en cualquier parte del documento
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.materia-menu')) {
        document.querySelectorAll('.materia-menu-content.show').forEach(m => {
          m.classList.remove('show');
        });
      }
    });
  }

  /**
   * Inicializa la barra de búsqueda flotante.
   * Configura el toggle de visibilidad y el filtrado en tiempo real.
   *
   * @returns {void}
   */
  function initSearchBar() {
    const searchContainer = document.querySelector('.floating-search');
    const searchInput = document.getElementById('search-input');
    const searchToggle = document.getElementById('search-toggle');
    const searchClose = document.getElementById('search-close');

    if (!searchContainer || !searchInput || !searchToggle) return;

    // Toggle búsqueda
    searchToggle.addEventListener('click', () => {
      searchContainer.classList.add('active');
      searchInput.focus();
    });

    if (searchClose) {
      searchClose.addEventListener('click', () => {
        searchContainer.classList.remove('active');
        searchInput.value = '';
        // Disparar evento input para limpiar filtro
        searchInput.dispatchEvent(new Event('input'));
      });
    }

    // Cerrar con Escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && searchContainer.classList.contains('active')) {
        searchContainer.classList.remove('active');
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('input'));
      }
    });

    // Filtrado
    searchInput.addEventListener('input', (e) => {
      const term = e.target.value.toLowerCase().trim();
      const cards = document.querySelectorAll('.materia-card');
      let visibleCount = 0;

      cards.forEach(card => {
        const title = card.querySelector('h3')?.textContent.toLowerCase() || '';
        const tags = Array.from(card.querySelectorAll('.tag')).map(t => t.textContent.toLowerCase());

        const matches = title.includes(term) || tags.some(tag => tag.includes(term));

        if (matches) {
          card.style.display = '';
          // Si hay término de búsqueda, expandir automáticamente los resultados
          if (term.length > 0) {
            const content = card.querySelector('.materia-content');
            const icon = card.querySelector('.chevron-icon');
            if (content) {
              content.style.maxHeight = content.scrollHeight + 'px';
              content.style.opacity = '1';
              content.style.marginTop = '1rem';
            }
            if (icon) icon.style.transform = 'rotate(180deg)';
          }
          visibleCount++;
        } else {
          card.style.display = 'none';
        }
      });

      // Mostrar estado vacío si no hay resultados
      const emptyState = document.querySelector('.empty-state');
      if (emptyState) {
        if (visibleCount === 0 && cards.length > 0) {
          emptyState.style.display = 'flex';
          const msg = emptyState.querySelector('p');
          if (msg) msg.textContent = `No se encontraron materias que coincidan con "${term}"`;
        } else {
          emptyState.style.display = 'none';
        }
      }
    });
  }

  return {
    TagStyleManager,
    initAccordionGrid,
    initSearchBar
  };
})();

// Exponer globalmente
window.UIHelpers = UIHelpers;

if (typeof module !== 'undefined' && module.exports) {
  module.exports = UIHelpers;
}
