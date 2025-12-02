function abrirModalCrearMateria(data = null) {
  // Si se llama desde un evento (click), data es un Event. Lo tratamos como null.
  if (data instanceof Event) data = null;
  let modal = document.getElementById('modal-nueva-materia');
  const basePath = globalThis.BASE_URL || '';

  if (!modal) {
    fetch(basePath + 'partials/modal-nueva-materia.html')
      .then(r => r.text())
      .then(html => {
        document.body.insertAdjacentHTML('beforeend', html);
        inicializarModalNuevaMateria();
        if (window.feather) feather.replace();
        const m = document.getElementById('modal-nueva-materia');
        if (data) prefilarModalMateria(data);
        m.showModal();
      })
      .catch(err => console.error('Error cargando modal materia:', err));
    return;
  }

  // si ya existe el modal, prefilar si se pasó data, sino resetear
  if (data) {
    prefilarModalMateria(data);
  } else {
    if (typeof window.resetModalMateria === 'function') {
      window.resetModalMateria();
    }
  }
  modal.showModal();
}

function inicializarModalNuevaMateria() {
  const modal = document.getElementById('modal-nueva-materia');
  const cerrar = document.getElementById('cerrar-modal-materia');
  const form = document.getElementById('form-materia');
  if (!modal || !form) return;

  // Resetear textos al abrir/cerrar por si acaso
  const resetTexts = () => {
    const modalTitle = document.querySelector('#modal-nueva-materia h2');
    const submitBtn = document.getElementById('crear-materia');
    if (modalTitle) modalTitle.textContent = 'Nueva Materia';
    if (submitBtn) submitBtn.innerHTML = '<span>Crear</span><i data-feather="plus-circle"></i>';
    delete form.dataset.editId;
    form.reset();
    // limpiar datasets de porcentajes
    const checks = document.querySelectorAll('#tipos-checkboxes input[type="checkbox"]');
    checks.forEach(c => delete c.dataset.porcentaje);
    if (window.feather) feather.replace();
  };

  // Exponer reset para que pueda ser llamado desde fuera
  window.resetModalMateria = resetTexts;

  if (cerrar) cerrar.addEventListener('click', () => { modal.close(); resetTexts(); });

  console.log('Inicializando modal materia: listener attached');
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const f = new FormData(form);

    // Construir tipos preservando porcentajes si existen
    let tiposPayload = Array.from(document.querySelectorAll('#tipos-checkboxes input[type="checkbox"]'))
      .filter(ch => ch.checked)
      .map(ch => {
        const id = Number(ch.value);
        const obj = { id: id };
        // Solo enviar porcentaje si existe en el dataset (preservar valor)
        if (ch.dataset.porcentaje !== undefined) {
          obj.porcentaje = Number(ch.dataset.porcentaje);
        }
        return obj;
      });

    // Calcular suma de porcentajes para ver si es válida (100%)
    const suma = tiposPayload.reduce((acc, t) => acc + (t.porcentaje || 0), 0);
    // Guardar copia para prefill
    const tiposParaPrefill = tiposPayload.map(t => ({ ...t }));

    // Si la suma no es 100 (con margen), y estamos enviando porcentajes, 
    // significa que el usuario modificó la selección (borró uno o agregó uno que rompe la suma).
    // En ese caso, NO enviamos porcentajes para que el backend no valide y resetee a 0.
    // El usuario los ajustará en el modal de ponderación.
    if (Math.abs(suma - 100) > 0.1) {
      tiposPayload = tiposPayload.map(t => ({ id: t.id })); // Quitar porcentaje
    }

    const payload = {
      id_materia: form.dataset.editId ? Number(form.dataset.editId) : undefined,
      nombre_materia: f.get('nombre_materia') || '',
      calif_minima: f.get('calif_minima') ? Number(f.get('calif_minima')) : 70,
      tipos: tiposPayload
    };

    if (!payload.nombre_materia) {
      if (typeof showToast === 'function') showToast('Ingrese el nombre de la materia', { type: 'error' });
      else console.warn('Ingrese el nombre de la materia');
      return;
    }

    try {
      const res = await fetch((globalThis.BASE_URL || '') + 'api/materias', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const txt = await res.text();
      let json = null;
      try { json = JSON.parse(txt); } catch (e) { throw new Error('Respuesta inválida del servidor'); }
      if (!res.ok) throw new Error(json.message || ('HTTP ' + res.status));

      // éxito: cerrar y recargar la página para reflejar cambio
      const isEdit = !!payload.id_materia;
      const targetId = payload.id_materia || json?.id_materia || json?.id;

      resetTexts();
      modal.close();

      // Si es creación nueva O edición, abrir modal de ponderación si hay tipos seleccionados
      if (targetId && typeof window.abrirModalPonderacion === 'function') {
        try {
          setTimeout(() => window.abrirModalPonderacion(targetId, tiposParaPrefill), 300);
        } catch (e) { console.warn('No se pudo abrir modal de ponderación:', e); }
      }

      // Refrescar lista de fondo
      if (typeof window.cargarMateriasDesdeAPI === 'function') window.cargarMateriasDesdeAPI();
      else location.reload();

      if (typeof showToast === 'function') showToast(json.message || (isEdit ? 'Materia actualizada' : 'Materia creada'), { type: 'success' });

    } catch (err) {
      console.error('Error guardando materia:', err);
      if (typeof showToast === 'function') showToast('Error: ' + (err.message || err), { type: 'error' });
    }
  });
}

// Rellena el modal con datos de la materia para editar
function prefilarModalMateria(data) {
  const form = document.getElementById('form-materia');
  if (!form) return;

  // Actualizar título y botón para modo edición
  const modalTitle = document.querySelector('#modal-nueva-materia h2');
  const submitBtn = document.getElementById('crear-materia');

  if (modalTitle) modalTitle.textContent = `Editar ${data.nombre ?? data.nombre_materia ?? 'Materia'}`;
  if (submitBtn) submitBtn.innerHTML = '<span>Actualizar</span><i data-feather="refresh-cw"></i>';

  // data puede venir con diferentes nombres según la API
  const id = data.id ?? data.id_materia ?? data.idMateria ?? null;
  const nombre = data.nombre ?? data.nombre_materia ?? data.nombreMateria ?? '';
  const calif = data.calif_minima ?? data.calif_min ?? data.calificacion_minima ?? '';

  if (id) form.dataset.editId = String(id);
  if (form.querySelector('[name="nombre_materia"]')) form.querySelector('[name="nombre_materia"]').value = nombre;
  if (form.querySelector('[name="calif_minima"]')) form.querySelector('[name="calif_minima"]').value = calif;
  // marcar checkboxes si vienen tipos
  try {
    const checkContainer = document.getElementById('tipos-checkboxes');
    if (checkContainer && Array.isArray(data.tipos)) {
      // convertir a ids
      const tiposMap = {};
      data.tipos.forEach(t => {
        const tid = Number(t.id_tipo_actividad ?? t.id_tipo ?? t.id ?? t.id_tipo ?? 0);
        if (tid > 0) {
          tiposMap[tid] = t.porcentaje ?? t.porcentaje ?? 0;
        }
      });

      Array.from(checkContainer.querySelectorAll('input[type="checkbox"]')).forEach(ch => {
        const val = Number(ch.value);
        if (tiposMap.hasOwnProperty(val)) {
          ch.checked = true;
          // Guardar el porcentaje existente para no perderlo al actualizar
          ch.dataset.porcentaje = tiposMap[val];
        } else {
          ch.checked = false;
          delete ch.dataset.porcentaje;
        }
      });
    }
  } catch (e) { }
}

// Cargar tipos globales y renderizar checkboxes
async function cargarTiposParaModal() {
  const base = globalThis.BASE_URL || '';
  const container = document.getElementById('tipos-checkboxes');
  const btnCrear = document.getElementById('btn-crear-tipo-inline');
  const inputNuevo = document.getElementById('nuevo-tipo-nombre');
  if (!container) return;

  // helper para renderizar
  function renderLista(tipos) {
    container.innerHTML = tipos.map(t => {
      const id = t.id_tipo_actividad ?? t.id ?? t.idTipo ?? '';
      const nombre = t.nombre_tipo ?? t.nombre ?? t.nombreTipo ?? '';
      return `<label class="tipo-item" data-id="${String(id)}"><input type="checkbox" value="${String(id)}"> <span class="tipo-nombre">${escapeHtml(nombre)}</span><button type="button" class="btn-eliminar-tipo" title="Eliminar tipo" style="margin-left:auto;background:transparent;border:0;color:#c0392b;cursor:pointer;">&times;</button></label>`;
    }).join('');
  }

  try {
    const r = await fetch(base + 'api/tipos-actividad', { credentials: 'same-origin' });
    const txt = await r.text();
    let json = null; try { json = JSON.parse(txt); } catch (e) { json = null; }
    const tipos = (json && Array.isArray(json.data)) ? json.data : [];
    renderLista(tipos);
  } catch (e) {
    console.warn('No se pudieron cargar tipos:', e);
  }

  // Crear tipo inline
  if (btnCrear && inputNuevo) {
    btnCrear.addEventListener('click', async () => {
      const name = String(inputNuevo.value || '').trim();
      if (!name) { if (typeof showToast === 'function') showToast('Ingrese nombre del tipo', { type: 'error' }); return; }
      try {
        const res = await fetch((globalThis.BASE_URL || '') + 'api/tipos-actividad', {
          method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ nombre_tipo: name })
        });
        const txt = await res.text(); let json = null; try { json = JSON.parse(txt); } catch (e) { json = null; }
        if (!res.ok) throw new Error(json?.message || ('HTTP ' + res.status));

        // añadir checkbox nuevo y marcarlo
        const newId = json?.id ?? null;
        const label = document.createElement('label');
        label.style.display = 'flex'; label.style.alignItems = 'center'; label.style.gap = '8px';
        const ch = document.createElement('input'); ch.type = 'checkbox'; ch.value = String(newId ?? ''); ch.checked = true;
        label.appendChild(ch);
        label.appendChild(document.createTextNode(' ' + name));
        container.prepend(label);
        inputNuevo.value = '';
        if (typeof showToast === 'function') showToast(json?.message || 'Tipo creado', { type: 'success' });
      } catch (err) {
        console.error('Error creando tipo inline:', err);
        if (typeof showToast === 'function') showToast('Error creando tipo: ' + (err.message || err), { type: 'error' });
      }
    });
  }

  // Delegated listener: eliminar tipo
  container.addEventListener('click', async (ev) => {
    const btn = ev.target.closest('.btn-eliminar-tipo');
    if (!btn) return;
    const label = btn.closest('.tipo-item');
    if (!label) return;
    const id = label.dataset.id;
    if (!id) return;

    // obtener referencias para mostrar confirmación clara
    try {
      const r = await fetch((globalThis.BASE_URL || '') + 'api/tipos-actividad?id=' + encodeURIComponent(id), { credentials: 'same-origin' });
      const txt = await r.text(); let json = null; try { json = JSON.parse(txt); } catch (e) { json = null; }
      if (!r.ok) {
        const msg = json?.message || ('HTTP ' + r.status);
        throw new Error(msg);
      }

      const refs = json?.data?.referencias ?? null;
      let mensaje = '¿Eliminar este tipo de actividad?';
      if (refs) {
        const a = refs.actividades || 0;
        const p = refs.ponderaciones || 0;
        if (a > 0) mensaje = `Este tipo tiene ${a} actividad(es) y ${p} ponderación(es). Al confirmar, las actividades serán ELIMINADAS.`;
        else if (p > 0) mensaje = `Este tipo está presente en ${p} ponderación(es). Al confirmar, las ponderaciones serán eliminadas.`;
      }

      const confirmar = (typeof showConfirm === 'function') ? await showConfirm('Confirmar eliminación', mensaje) : await (async () => {
        // Usar <dialog> nativo para que quede por encima de otros modales
        return new Promise(resolve => {
          const dlg = document.createElement('dialog');
          dlg.className = 'confirm-dialog';
          dlg.innerHTML = `
            <div style="padding:16px;border-radius:8px;max-width:480px;width:92%;box-shadow:0 10px 30px rgba(0,0,0,0.12);">
              <h3 style="margin:0 0 6px 0">Confirmar eliminación</h3>
              <p style="margin:0 0 12px 0">${escapeHtml(mensaje)}</p>
              <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button id="__temp_cancel_tipo" style="background:#eee;border:0;padding:8px 12px;border-radius:6px;">Cancelar</button>
                <button id="__temp_ok_tipo" style="background:#d9534f;color:#fff;border:0;padding:8px 12px;border-radius:6px;">Eliminar</button>
              </div>
            </div>
          `;
          document.body.appendChild(dlg);
          // showModal asegura que el dialog esté por encima del modal existente
          try { dlg.showModal(); } catch (e) { /* fallback if not supported */ }
          const ok = dlg.querySelector('#__temp_ok_tipo');
          const cancel = dlg.querySelector('#__temp_cancel_tipo');
          ok && ok.focus();
          const cleanup = (res) => { try { dlg.close(); dlg.remove(); } catch (e) { }; resolve(res); };
          ok && ok.addEventListener('click', () => cleanup(true));
          cancel && cancel.addEventListener('click', () => cleanup(false));
          dlg.addEventListener('cancel', () => cleanup(false));
        });
      })();

      if (!confirmar) return;

      // intentar eliminar sin force para detectar si el servidor exige force
      const res = await fetch((globalThis.BASE_URL || '') + 'api/tipos-actividad?id=' + encodeURIComponent(id), { method: 'DELETE', credentials: 'same-origin' });
      const txt2 = await res.text(); let json2 = null; try { json2 = JSON.parse(txt2); } catch (e) { json2 = null; }
      if (res.ok) {
        // eliminado exitoso
        label.remove();
        if (typeof showToast === 'function') showToast(json2?.message || 'Tipo eliminado', { type: 'success' });
        // refrescar selects globales si existen
        try { if (typeof cargarTiposParaModal === 'function') cargarTiposParaModal(); } catch (e) { }
        try { if (typeof poblarSelectsModal === 'function') poblarSelectsModal(); } catch (e) { }
        return;
      }

      // Si no ok y el mensaje indica referencias, preguntar por force
      const errMsg = json2?.message || txt2 || ('HTTP ' + res.status);
      if (String(errMsg).toLowerCase().includes('referenc')) {
        // pedir confirmación explícita para forzar borrado de actividades
        const confirmarForce = (typeof showConfirm === 'function') ? await showConfirm('Forzar eliminación', 'Este tipo tiene actividades asociadas. ¿Deseas eliminar también esas actividades y continuar?') : confirm('Este tipo tiene actividades asociadas. ¿Eliminar también esas actividades?');
        if (!confirmarForce) return;

        // llamar con force=1
        const resf = await fetch((globalThis.BASE_URL || '') + 'api/tipos-actividad?id=' + encodeURIComponent(id) + '&force=1', { method: 'DELETE', credentials: 'same-origin' });
        const txtf = await resf.text(); let jsonf = null; try { jsonf = JSON.parse(txtf); } catch (e) { jsonf = null; }
        if (!resf.ok) {
          if (typeof showToast === 'function') showToast('No se pudo eliminar: ' + (jsonf?.message || txtf), { type: 'error' });
          else console.error('No se pudo eliminar: ', jsonf || txtf);
          return;
        }

        // success with force
        label.remove();
        if (typeof showToast === 'function') showToast(jsonf?.message || 'Tipo y actividades eliminadas', { type: 'success' });
        try { if (typeof cargarTiposParaModal === 'function') cargarTiposParaModal(); } catch (e) { }
        try { if (typeof poblarSelectsModal === 'function') poblarSelectsModal(); } catch (e) { }
      } else {
        if (typeof showToast === 'function') showToast('No se pudo eliminar: ' + errMsg, { type: 'error' });
        else console.error('No se pudo eliminar:', errMsg);
      }
    } catch (err) {
      console.error('Error al eliminar tipo:', err);
      if (typeof showToast === 'function') showToast('Error al eliminar tipo: ' + (err.message || err), { type: 'error' });
    }
  });

  function escapeHtml(s) { return String(s || '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", "&#39;"); }
}

// Al cargar el modal por primera vez, solicitar tipos
document.addEventListener('DOMContentLoaded', () => {
  // Si el modal ya fue cargado dinámicamente, intentar cargar tipos cuando exista
  const tryLoad = () => { if (document.getElementById('tipos-checkboxes')) cargarTiposParaModal(); else setTimeout(tryLoad, 300); };
  tryLoad();
});
