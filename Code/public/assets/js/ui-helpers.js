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
      { bg: '#e3f2fd', text: '#1976d2' },
      { bg: '#e8f5e9', text: '#2e7d32' },
      { bg: '#fff3e0', text: '#ef6c00' },
      { bg: '#f3e5f5', text: '#7b1fa2' },
      { bg: '#e0f7fa', text: '#0097a7' },
      { bg: '#fce4ec', text: '#c2185b' },
      { bg: '#f1f8e9', text: '#558b2f' },
      { bg: '#fff8e1', text: '#ffa000' }
    ],
    cache: new Map(),
    classCache: new Map(),

    getStyle(text) {
      if (!text) return this.colors[0];
      if (this.cache.has(text)) return this.cache.get(text);

      let hash = 0;
      for (let i = 0; i < text.length; i++) {
        hash = text.codePointAt(i) + ((hash << 5) - hash);
      }

      const index = Math.abs(hash) % this.colors.length;
      const style = this.colors[index];
      this.cache.set(text, style);
      return style;
    },

    getClassFor(key) {
      const raw = String(key || '').toLowerCase().trim();
      if (this.classCache.has(raw)) return this.classCache.get(raw);

      let cssClass = 'tag-agua';

      if (raw.includes('ejerc')) cssClass = 'tag-rojo';
      else if (raw.includes('examen')) cssClass = 'tag-azul';
      else if (raw.includes('proyecto')) cssClass = 'tag-verde';
      else if (raw.includes('tarea') || raw.includes('trabajo')) cssClass = 'tag-naranja';
      else if (raw.includes('quiz')) cssClass = 'tag-morado';
      else if (raw.includes('parcial')) cssClass = 'tag-azul-claro';
      else if (raw.includes('final')) cssClass = 'tag-rojo';
      else if (raw.includes('lab') || raw.includes('laboratorio')) cssClass = 'tag-verde-lima';

      this.classCache.set(raw, cssClass);
      return cssClass;
    },

    applyStyle(element, text) {
      const style = this.getStyle(text);
      element.style.backgroundColor = style.bg;
      element.style.color = style.text;
    }
  };

  function initAccordionGrid(container) {
    let grid = container;
    if (!grid) {
      grid = document.querySelector('.accordion-card-grid') || document.querySelector('.materias-grid');
    }
    if (!grid) return;

    grid.addEventListener('click', (e) => {
      const menuToggle = e.target.closest('.accordion-card__menu-toggle');
      if (menuToggle) {
        e.stopPropagation();
        const menu = menuToggle.nextElementSibling;
        if (menu && menu.classList.contains('accordion-card__menu')) {
          document.querySelectorAll('.accordion-card__menu').forEach(m => {
            if (m !== menu) m.style.display = 'none';
          });
          menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }
        return;
      }

      const menuBtn = e.target.closest('.materia-menu-btn');
      if (menuBtn) {
        e.stopPropagation();
        document.querySelectorAll('.materia-menu-content.show').forEach(m => {
          if (m !== menuBtn.nextElementSibling) m.classList.remove('show');
        });
        const menu = menuBtn.nextElementSibling;
        menu.classList.toggle('show');
        return;
      }

      if (!e.target.closest('.accordion-card__menu') && !e.target.closest('.materia-menu-content')) {
        document.querySelectorAll('.accordion-card__menu').forEach(m => {
          m.style.display = 'none';
        });
        document.querySelectorAll('.materia-menu-content.show').forEach(m => {
          m.classList.remove('show');
        });
      }

      const accordionHeader = e.target.closest('.accordion-card__header');
      if (accordionHeader && !e.target.closest('.accordion-card__actions')) {
        const card = accordionHeader.closest('.accordion-card');
        if (card) card.classList.toggle('open');
        return;
      }

      const header = e.target.closest('.materia-header');
      if (header && !e.target.closest('.materia-actions') && !e.target.closest('.materia-menu')) {
        const card = header.closest('.materia-card');
        const content = card.querySelector('.materia-content');
        const icon = header.querySelector('.chevron-icon');

        const isClosed = content.style.maxHeight === '0px' || !content.style.maxHeight;

        if (isClosed) {
          content.style.maxHeight = content.scrollHeight + 'px';
          content.style.opacity = '1';
          content.style.marginTop = '1rem';
          if (icon) icon.style.transform = 'rotate(180deg)';
        } else {
          content.style.maxHeight = '0px';
          content.style.opacity = '0';
          content.style.marginTop = '0';
          if (icon) icon.style.transform = 'rotate(0deg)';
        }
      }
    });

    document.addEventListener('click', (e) => {
      if (!e.target.closest('.accordion-card__menu') && 
          !e.target.closest('.accordion-card__menu-toggle') && 
          !e.target.closest('.materia-menu')) {
        document.querySelectorAll('.accordion-card__menu').forEach(m => {
          m.style.display = 'none';
        });
        document.querySelectorAll('.materia-menu-content.show').forEach(m => {
          m.classList.remove('show');
        });
      }
    });
  }

  function initSearchBar(options = {}) {
    const {
      input = document.getElementById('search-input') || document.getElementById('buscador-menu') || document.getElementById('buscador-materias'),
      toggleBtn = document.getElementById('search-toggle'),
      wrapper = document.querySelector('.search-wrapper') || document.querySelector('.floating-search'),
      onFilter = null
    } = options;

    if (!input || !toggleBtn) return;

    toggleBtn.addEventListener('click', () => {
      if (wrapper) {
        wrapper.classList.add('active');
      }
      input.focus();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && wrapper && wrapper.classList.contains('active')) {
        wrapper.classList.remove('active');
        input.value = '';
        input.dispatchEvent(new Event('input'));
      }
    });

    input.addEventListener('input', (e) => {
      const term = e.target.value;

      if (typeof onFilter === 'function') {
        onFilter(term);
      } else {
        const cards = document.querySelectorAll('.materia-card');
        let visibleCount = 0;

        cards.forEach(card => {
          const title = card.querySelector('h3')?.textContent.toLowerCase() || '';
          const tags = Array.from(card.querySelectorAll('.tag')).map(t => t.textContent.toLowerCase());
          const t = term.toLowerCase().trim();

          const matches = title.includes(t) || tags.some(tag => tag.includes(t));

          if (matches) {
            card.style.display = '';
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


/**
 * Toast global pastel
 */
function showToast(message, { duration = 4000, type = 'info' } = {}) {
  try {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      Object.assign(container.style, {
        position: 'fixed',
        top: '1rem',
        left: '50%',
        transform: 'translateX(-50%)',
        display: 'flex',
        flexDirection: 'column',
        gap: '0.5rem',
        zIndex: '2147483647',
        pointerEvents: 'none',
        maxWidth: '90vw',
        width: '100%'
      });
      document.body.appendChild(container);
    }

    const dialogsAbiertos = document.querySelectorAll('dialog[open]');
    const padreObjetivo =
      dialogsAbiertos.length > 0
        ? dialogsAbiertos[dialogsAbiertos.length - 1]
        : document.body;

    if (container.parentElement !== padreObjetivo) {
      padreObjetivo.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'app-toast app-toast-' + type;
    toast.textContent = message;

    const colors = {
      error:   { bg: '#fdd7d7', text: '#9d4d5c' },
      success: { bg: '#d4edda', text: '#5a7d6b' },
      info:    { bg: '#cfe2ff', text: '#5a7e9c' }
    };

    const colorConfig = colors[type] || colors.info;

    Object.assign(toast.style, {
      pointerEvents: 'auto',
      minWidth: '250px',
      maxWidth: '500px',
      background: colorConfig.bg,
      color: colorConfig.text,
      padding: '12px 16px',
      borderRadius: '8px',
      boxShadow: '0 4px 12px rgba(0,0,0,0.1)',
      opacity: '0',
      transform: 'translateY(-8px)',
      transition: 'opacity 240ms ease, transform 240ms ease',
      fontSize: '0.95rem',
      fontWeight: '500'
    });

    container.appendChild(toast);

    void toast.offsetWidth;
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';

    const hide = () => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(-8px)';
      setTimeout(() => {
        try { toast.remove(); } catch {}
      }, 260);
    };

    const timer = setTimeout(hide, duration);

    toast.addEventListener('click', () => {
      clearTimeout(timer);
      hide();
    });

    return toast;
  } catch (e) {
    console.error('showToast error', e);
  }
}
