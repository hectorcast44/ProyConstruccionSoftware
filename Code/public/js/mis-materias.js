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
      const porcentaje = (tipo.porcentaje !== undefined && tipo.porcentaje !== null)
        ? `${Number(tipo.porcentaje).toFixed(0)}%`
        : '';

      return `
        <tr>
          <td>
            <span class="tag ${tagClass}">${escapeHtml(tipo.nombre)}</span>
          </td>
          <td class="right">
            ${porcentaje ? `<small class="tipo-porcentaje"> ${escapeHtml(porcentaje)}</small>` : ''}
          </td>
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
          <button class="accordion-card__menu-item js-card-ponderacion" type="button">
            <i data-feather="percent"></i>
            <span>Ponderación</span>
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

      // Después de obtener el listado, pedir detalles por materia (que incluyen tipos con porcentaje)
      try {
        const base = globalThis.BASE_URL || '';
        await Promise.all(materias.map(async (mat) => {
          try {
            const r = await fetch(base + 'api/materias?id=' + encodeURIComponent(mat.id), { credentials: 'same-origin' });
            const txt = await r.text(); let j = null; try { j = JSON.parse(txt); } catch (e) { j = null; }
            if (r.ok && j && j.data && Array.isArray(j.data.tipos)) {
              mat.tipos = j.data.tipos.map(t => ({
                id_tipo: t.id_tipo_actividad ?? t.id_tipo ?? t.id ?? 0,
                nombre: t.nombre_tipo ?? t.nombre ?? 'Tipo',
                obtenido: Number(t.puntos_obtenidos ?? t.obtenido ?? 0),
                maximo: Number(t.puntos_posibles ?? t.maximo ?? 0),
                porcentaje: t.porcentaje ?? null
              }));
            }
          } catch (e) {
            // ignore per-materia fetch errors and leave existing tipos
          }
        }));
      } catch (e) {
        // ignore
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
  // Exponer función de ponderación para que otros módulos (p. ej. modal de crear materia) la usen
  window.abrirModalPonderacion = abrirModalPonderacion;

  // Abrir modal para asignar ponderaciones por tipo en una materia
  async function abrirModalPonderacion(idMateria, prefillTipos = null) {
    const base = globalThis.BASE_URL || '';
    // intentar obtener los tipos de la materia
    const r = await fetch(base + 'api/materias?id=' + encodeURIComponent(idMateria), { credentials: 'same-origin' });
    const txt = await r.text(); let j = null; try { j = JSON.parse(txt); } catch (e) { j = null; }
    if (!r.ok || !j || !j.data) {
      throw new Error(j?.message || 'No se pudo obtener la materia');
    }

    let tipos = Array.isArray(j.data.tipos) ? j.data.tipos : [];

    // Si vienen datos pre-cargados (del frontend), usarlos para preservar los porcentajes
    // aunque en base de datos se hayan guardado en 0.
    if (prefillTipos && Array.isArray(prefillTipos)) {
      const mapPrefill = {};
      prefillTipos.forEach(pt => {
        // pt puede venir como { id: 1, porcentaje: 20 }
        const pid = pt.id ?? pt.id_tipo ?? pt.id_tipo_actividad;
        if (pid) mapPrefill[pid] = pt.porcentaje;
      });

      tipos = tipos.map(t => {
        const tid = t.id_tipo_actividad ?? t.id_tipo ?? t.id;
        // Si existe en el prefill, usamos ese porcentaje
        if (mapPrefill[tid] !== undefined) {
          // Aseguramos que sea número o string válido
          t.porcentaje = mapPrefill[tid];
        }
        return t;
      });
    }

    // Crear dialog si no existe
    let dlg = document.getElementById('modal-ponderacion');
    if (!dlg) {
      dlg = document.createElement('dialog');
      dlg.id = 'modal-ponderacion';
      dlg.className = 'contenedor-modal';
      document.body.appendChild(dlg);
    }

    // Construir contenido del modal
    const rows = tipos.map(t => {
      const idTipo = t.id_tipo_actividad ?? t.id_tipo ?? t.id ?? '';
      const nombre = t.nombre_tipo ?? t.nombre ?? '';
      const porcentaje = t.porcentaje ?? t.porcentaje ?? '';
      return `
        <div class="ponder-row modal-title" data-id="${escapeHtml(String(idTipo))}" style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
          <div style="flex:1"><strong>${escapeHtml(String(nombre))}</strong></div>
          <div class="form-ponderacion">
            <input type="number" min="0" max="100" step="0.1" class="modal-form-input" value="${escapeHtml(String(porcentaje))}" placeholder="%">
            <span>%</span>
          </div>
        </div>`;
    }).join('');

    dlg.innerHTML = `
      <form method="dialog" id="form-ponderaciones" style="padding:18px;max-width:640px;width:94%;">
        <h3 style="margin-top:0">Ponderaciones - Materia</h3>
        <div id="ponder-list" style="margin:8px 0 14px;">${rows || '<p>No hay tipos asignados a esta materia.</p>'}</div>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;">
            <button type="button" id="cancel-ponder" class="btn-secondary">Cancelar</button>
            <button type="button" id="save-ponder" class="btn-primary">Guardar</button>
          </div>
      </form>
    `;

    // show modal
    try { dlg.showModal(); } catch (e) { /* es ignorado */ }

    const form = dlg.querySelector('#form-ponderaciones');
    const btnCancel = dlg.querySelector('#cancel-ponder');
    const btnSave = dlg.querySelector('#save-ponder');

    btnCancel?.addEventListener('click', () => { try { dlg.close(); } catch (e) { } });

    // handler compartido para guardar ponderaciones (evita recarga si algo falla)
    async function handleGuardarPonderaciones(ev) {
      try { ev && ev.preventDefault(); } catch (e) { }
      // construir payload
      const inputs = Array.from(dlg.querySelectorAll('.ponder-row'));
      const tiposPayload = inputs.map(row => {
        const id = row.dataset.id;
        // usar la clase actual del input dentro de las filas
        const inp = row.querySelector('.modal-form-input');
        const val = inp ? inp.value : '';
        return { id: Number(id) || 0, porcentaje: val === '' ? 0 : Number(val) };
      }).filter(t => t.id > 0);

      try {
        const payload = { id_materia: Number(idMateria), tipos: tiposPayload };
        try { console.debug && console.debug('Ponderacion - payload', payload); } catch (e) { }
        const res = await fetch(base + 'api/materias', {
          method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const t = await res.text(); let jj = null; try { jj = JSON.parse(t); } catch (e) { jj = null; }
        try { console.debug && console.debug('Ponderacion - response', { ok: res.ok, status: res.status, body: jj || t }); } catch (e) { }
        if (!res.ok) throw new Error(jj?.message || ('HTTP ' + res.status));

        try { dlg.close(); } catch (e) { }
        ensureToast(jj?.message || 'Ponderaciones guardadas', 'success');
        if (typeof window.cargarMateriasDesdeAPI === 'function') window.cargarMateriasDesdeAPI();
      } catch (err) {
        console.error('Error guardando ponderaciones:', err);
        ensureToast('Error guardando ponderaciones: ' + (err.message || err), 'error');
      }
    }

    // Adjuntar tanto al submit del form (compatibilidad) como al click del botón guardar
    form.addEventListener('submit', handleGuardarPonderaciones);
    btnSave?.addEventListener('click', handleGuardarPonderaciones);

    return;
  }

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

      const btnPonder = e.target.closest('.js-card-ponderacion');
      if (btnPonder) {
        const card = btnPonder.closest('.accordion-card');
        const idMateria = card?.dataset.idMateria;
        if (!idMateria) return;
        // abrir modal de ponderaciones
        try {
          await abrirModalPonderacion(idMateria);
        } catch (err) {
          console.error('Error abriendo modal de ponderación:', err);
          ensureToast('No se pudo abrir la ventana de ponderaciones', 'error');
        }
        return;
      }

      const btnDelete = e.target.closest('.js-card-delete');
      if (btnDelete) {
        const card = btnDelete.closest('.accordion-card');
        const idMateria = card?.dataset.idMateria;
        if (!idMateria) return;

        // Reemplazar confirm() nativo por un modal programático con z-index alto
        const confirmar = typeof showConfirm === 'function'
          ? await showConfirm('Confirmar eliminación', '¿Eliminar esta materia? Se eliminarán sus actividades relacionadas.')
          : await (async () => {
            return new Promise(resolve => {
              const dlg = document.createElement('dialog');
              dlg.className = 'confirm-dialog';
              // Forzar posicionamiento y z-index para que quede siempre encima de otros modales
              dlg.style.position = 'fixed';
              dlg.style.zIndex = '2147483647';
              // Intentar neutralizar cualquier backdrop-filter aplicado globalmente
              dlg.style.backdropFilter = 'none';
              dlg.style.webkitBackdropFilter = 'none';
              dlg.innerHTML = `
                    <div class="contenedor-modal_eliminar-materia">
                      <div class="modal-eliminar-materia">
                        <h3 style="margin:0 0 6px 0">Confirmar eliminación</h3>
                        <p style="margin:0 0 12px 0">¿Eliminar esta materia? Se eliminarán sus actividades relacionadas.</p>
                        <div style="display:flex;gap:8px;justify-content:flex-end;">
                          <button id="__temp_cancel_mat" style="background:#eee;border:0;padding:8px 12px;border-radius:6px;cursor:pointer;">Cancelar</button>
                          <button id="__temp_ok_mat" style="background:#ff716c;color:#fff;border:0;padding:8px 12px;border-radius:6px;cursor:pointer;">Eliminar</button>
                        </div>
                      </div>
                    </div>
                  `;
              // Append at the end of body to increase stacking context priority
              document.body.appendChild(dlg);
              try { dlg.showModal(); } catch (e) {
                // fallback: focus to ensure visibility
                try { dlg.style.display = 'block'; dlg.focus(); } catch (_) { }
              }
              const ok = dlg.querySelector('#__temp_ok_mat');
              const cancel = dlg.querySelector('#__temp_cancel_mat');
              ok && ok.focus();
              const cleanup = (res) => { try { dlg.close(); dlg.remove(); } catch (e) { }; resolve(res); };
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
  function escapeHtml(s) { return String(s || '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", "&#39;"); }

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

      const hide = () => { toast.style.opacity = '0'; toast.style.transform = 'translateY(8px)'; setTimeout(() => { try { toast.remove(); } catch (e) { } }, 260); };
      const timer = setTimeout(hide, duration);
      toast.addEventListener('click', () => { clearTimeout(timer); hide(); });
    } catch (e) {
      console.error('ensureToast failed', e, message);
    }
  }
});
