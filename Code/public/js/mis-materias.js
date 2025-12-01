/**
 * Página "Mis materias" — render de cards tipo acordeón como en Mis Calificaciones.
 */
document.addEventListener('DOMContentLoaded', () => {
  let materias = [];

  const lista = document.getElementById('lista-materias');
  const buscadorInput = document.getElementById('buscador-materias');
  const buscadorWrapper = document.querySelector('.search-wrapper');
  const buscadorBtn = document.getElementById('search-toggle');

  if (!lista) return;

  function obtenerTagClassPorTipo(tipo) {
    const key = tipo.id_tipo ?? tipo.id_tipo_actividad ?? tipo.nombre;
    return UIHelpers.TagStyleManager.getClassFor(key);
  }

  function generarFilasTipos(tipos = []) {
    if (!tipos.length) {
      return `
        <tr>
          <td colspan="2" class="right">Sin registros</td>
        </tr>
      `;
    }

    return tipos.map(tipo => {
      const tagClass = obtenerTagClassPorTipo(tipo);
      const obtenido = Number(tipo.obtenido ?? 0);
      const maximo = Number(tipo.maximo ?? 0);

      return `
        <tr>
          <td><span class="tag ${tagClass}">${escapeHtml(tipo.nombre)}</span></td>
          <td class="right">${obtenido} / ${maximo}</td>
        </tr>
      `;
    }).join('');
  }

  function crearCardMateria(materia) {
    const wrapper = document.createElement('div');
    wrapper.classList.add('accordion-card-wrapper');

    const card = document.createElement('div');
    card.classList.add('accordion-card');
    card.dataset.idMateria = String(materia.id);

    const header = document.createElement('div');
    header.classList.add('accordion-card__header');
    header.innerHTML = `
      <div class="accordion-card__header-main">
        <span class="accordion-card__icon"><i data-feather="book-open"></i></span>
        <h3 class="accordion-card__title">${escapeHtml(materia.nombre)}</h3>
      </div>

      <div class="accordion-card__actions">
        <button class="accordion-card__menu-toggle" type="button" aria-label="Más opciones">
          <i data-feather="more-vertical"></i>
        </button>

        <div class="accordion-card__menu">
          <button class="accordion-card__menu-item js-card-edit" type="button">
            <i data-feather="edit-3"></i>
            <span>Editar</span>
          </button>
          <button class="accordion-card__menu-item js-card-delete" type="button">
            <i data-feather="trash-2"></i>
            <span>Eliminar</span>
          </button>
        </div>
      </div>
    `;

    const panel = document.createElement('div');
    panel.classList.add('accordion-card__panel');
    panel.innerHTML = `
      <div class="item-table-wrapper">
        <table class="item-table">
          <thead>
            <tr>
              <th>Tipo</th>
              <th class="right">Puntos</th>
            </tr>
          </thead>
          <tbody>
            ${generarFilasTipos(materia.tipos)}
          </tbody>
        </table>
      </div>
    `;

    card.appendChild(header);
    card.appendChild(panel);
    wrapper.appendChild(card);

    return wrapper;
  }

  function renderizarMaterias(listaMaterias) {
    lista.innerHTML = '';

    if (!listaMaterias.length) {
      lista.innerHTML = `
        <div id="mensaje-vacio" class="oculto">
            <h3>No se han registrado materias.</h3>
            <p>Presiona el botón "Nueva" para crear una.</p>
        </div>
      `;
      return;
    }

    for (const m of listaMaterias) {
      const card = crearCardMateria(m);
      lista.appendChild(card);
    }

    if (globalThis.feather) feather.replace();
  }

  function filtrarYRenderizar(valor) {
    const termino = String(valor || '').toLowerCase().trim();
    const filtradas = materias.filter(m => (m.nombre || '').toLowerCase().includes(termino));
    renderizarMaterias(filtradas);
  }

  async function cargarMateriasDesdeAPI() {
    const url = (globalThis.BASE_URL || '') + 'api/materias';

    try {
      const resp = await fetch(url, { method: 'GET', headers: { 'Accept': 'application/json' }, credentials: 'include' });
      const text = await resp.text();
      let json = null;
      try { json = JSON.parse(text); } catch { json = null; }

      if (!json?.data) {
        materias = [];
      } else {
        materias = json.data.map(m => ({
          id: m.id ?? m.id_materia ?? 0,
          nombre: m.nombre ?? m.nombre_materia ?? 'Sin nombre',
          tipos: (m.tipos || []).map(t => ({
            id_tipo: t.id_tipo ?? t.id_tipo_actividad ?? t.id,
            nombre: t.nombre ?? t.nombre_tipo ?? 'Tipo',
            obtenido: Number(t.obtenido ?? t.puntos_obtenidos ?? 0),
            maximo: Number(t.maximo ?? t.puntos_posibles ?? 0)
          }))
        }));
      }

      filtrarYRenderizar('');
    } catch (e) {
      console.error('Error al cargar materias:', e);
      materias = [];
      renderizarMaterias(materias);
    }
  }

  // Exponer para que otros módulos (modal) puedan refrescar la lista sin recargar
  window.cargarMateriasDesdeAPI = cargarMateriasDesdeAPI;

  if (lista) {
    lista.addEventListener('click', async e => {
      const btnEdit = e.target.closest('.js-card-edit');
      if (btnEdit) {
        const card = btnEdit.closest('.accordion-card');
        const idMateria = card?.dataset.idMateria;
        if (!idMateria) return;

        // pedir datos de la materia al backend y abrir modal con datos
        try {
          const base = globalThis.BASE_URL || '';
          const r = await fetch(base + 'api/materias?id=' + encodeURIComponent(idMateria), { credentials: 'same-origin' });
          const txt = await r.text();
          let json = null;
          try { json = JSON.parse(txt); } catch { throw new Error('Respuesta inválida'); }
          if (!r.ok) throw new Error(json.message || ('HTTP ' + r.status));

          // abrir modal en modo edición
          if (typeof window.abrirModalCrearMateria === 'function') {
            window.abrirModalCrearMateria(json.data);
          }
          } catch (err) {
            console.error('Error cargando materia para editar:', err);
            ensureToast('Acción rechazada', 'error');
          }

        return;
      }

      const btnDelete = e.target.closest('.js-card-delete');
      if (btnDelete) {
        const card = btnDelete.closest('.accordion-card');
        const idMateria = card?.dataset.idMateria;
        if (!idMateria) return;

        // Reemplazar confirm() nativo por un modal programático
        const confirmar = typeof showConfirm === 'function'
          ? await showConfirm('Confirmar eliminación', '¿Eliminar esta materia? Se eliminarán sus actividades relacionadas.')
          : await (async () => {
                return new Promise(resolve => {
                const dlg = document.createElement('dialog');
                dlg.className = 'confirm-dialog';
                dlg.innerHTML = `
                  <div style="padding:16px;border-radius:8px;max-width:480px;width:92%;box-shadow:0 10px 30px rgba(0,0,0,0.12);">
                    <h3 style="margin:0 0 6px 0">Confirmar eliminación</h3>
                    <p style="margin:0 0 12px 0">¿Eliminar esta materia? Se eliminarán sus actividades relacionadas.</p>
                    <div style="display:flex;gap:8px;justify-content:flex-end;">
                      <button id="__temp_cancel_mat" style="background:#eee;border:0;padding:8px 12px;border-radius:6px;">Cancelar</button>
                      <button id="__temp_ok_mat" style="background:#d9534f;color:#fff;border:0;padding:8px 12px;border-radius:6px;">Eliminar</button>
                    </div>
                  </div>
                `;
                document.body.appendChild(dlg);
                try { dlg.showModal(); } catch(e) {}
                const ok = dlg.querySelector('#__temp_ok_mat');
                const cancel = dlg.querySelector('#__temp_cancel_mat');
                ok && ok.focus();
                const cleanup = (res) => { try { dlg.close(); dlg.remove(); } catch(e){}; resolve(res); };
                ok && ok.addEventListener('click', () => cleanup(true));
                cancel && cancel.addEventListener('click', () => cleanup(false));
                dlg.addEventListener('cancel', () => cleanup(false));
              });
            })();
        if (!confirmar) return;

        try {
          const base = globalThis.BASE_URL || '';
          const r = await fetch(base + 'api/materias?id=' + encodeURIComponent(idMateria), {
            method: 'DELETE',
            credentials: 'same-origin'
          });
          const txt = await r.text();
          let json = null;
          try { json = JSON.parse(txt); } catch { json = null; }
          if (!r.ok) throw new Error(json?.message || ('HTTP ' + r.status));

          // refrescar lista
          if (typeof window.cargarMateriasDesdeAPI === 'function') window.cargarMateriasDesdeAPI();
          else location.reload();

          ensureToast(json?.message || 'Materia eliminada', 'success');
        } catch (err) {
          console.error('Error eliminando materia:', err);
          ensureToast('Acción prohibida: ' + (err.message || err), 'error');
        }

        return;
      }
    });
  }

  UIHelpers.initAccordionGrid(lista);
  UIHelpers.initSearchBar({ input: buscadorInput, toggleBtn: buscadorBtn, wrapper: buscadorWrapper, onFilter: filtrarYRenderizar });

  cargarMateriasDesdeAPI();

  // helpers
  function escapeHtml(s){ return String(s || '').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'","&#39;"); }

  // Mostrar toast seguro: intenta usar showToast global si existe, si no crea un toast mínimo
  function ensureToast(message, type = 'info', duration = 4500) {
    try {
      if (typeof showToast === 'function') {
        showToast(message, { type: type === 'error' ? 'error' : (type === 'success' ? 'success' : 'info'), duration });
        return;
      }

      // Crear contenedor si hace falta
      let container = document.getElementById('toast-container');
      if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        Object.assign(container.style, {
          position: 'fixed',
          right: '1rem',
          bottom: '1rem',
          display: 'flex',
          flexDirection: 'column',
          gap: '0.5rem',
          zIndex: 99999,
          pointerEvents: 'none'
        });
        document.body.appendChild(container);
      }

      const toast = document.createElement('div');
      toast.textContent = message;
      Object.assign(toast.style, {
        pointerEvents: 'auto',
        minWidth: '200px',
        maxWidth: '360px',
        background: type === 'error' ? '#ff4d4f' : (type === 'success' ? '#22c55e' : '#333'),
        color: '#fff',
        padding: '10px 14px',
        borderRadius: '8px',
        boxShadow: '0 6px 18px rgba(0,0,0,0.12)',
        opacity: '0',
        transform: 'translateY(8px)',
        transition: 'opacity 240ms ease, transform 240ms ease',
        fontSize: '0.95rem'
      });
      container.appendChild(toast);
      void toast.offsetWidth;
      toast.style.opacity = '1';
      toast.style.transform = 'translateY(0)';

      const hide = () => { toast.style.opacity = '0'; toast.style.transform = 'translateY(8px)'; setTimeout(() => { try { toast.remove(); } catch(e){} }, 260); };
      const timer = setTimeout(hide, duration);
      toast.addEventListener('click', () => { clearTimeout(timer); hide(); });
    } catch (e) {
      console.error('ensureToast failed', e, message);
    }
  }
});
