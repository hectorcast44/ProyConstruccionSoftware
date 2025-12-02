/**
 * Abre el modal para crear o editar una materia.
 * Si se proporciona `data`, se pre-llena el formulario para edición.
 * @param {Object|null} data Datos de la materia a editar (opcional).
 */
function abrirModalCrearMateria(data = null) {
  // Si se llama desde un evento (click), data es un Event. Lo tratamos como null.
  if (data instanceof Event) data = null;
  let modal = document.getElementById('modal-nueva-materia');
  const basePath = globalThis.BASE_URL || '';

  if (!modal) {
    fetch(obtenerBaseUrl() + 'partials/modal-nueva-materia.html')
      .then((respuesta) => respuesta.text())
      .then((html) => {
        document.body.insertAdjacentHTML('beforeend', html);
        inicializarModalNuevaMateria();

        globalThis.feather?.replace();

        const modalCreado = document.getElementById('modal-nueva-materia');
        if (!modalCreado) {
          console.error('No se encontró el modal de nueva materia después de insertarlo.');
          return;
        }

        if (datosMateria) {
          prefilarModalMateria(datosMateria);
        }

        modalCreado.showModal();
      })
      .catch((error) => {
        console.error('Error cargando modal materia:', error);
      });

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

/**
 * Inicializa los eventos del modal de nueva materia (submit, cerrar).
 * Configura la lógica de envío del formulario, incluyendo validación de porcentajes.
 */
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

/**
 * Cargar tipos de actividad desde la API y pintarlos en el contenedor.
 *
 * @param {HTMLElement} contenedor Nodo contenedor de tipos.
 * @returns {Promise<void>}
 */
async function cargarTiposDesdeApi(contenedor) {
  try {
    const respuesta = await fetch(obtenerBaseUrl() + 'api/tipos-actividad', {
      credentials: 'same-origin'
    });

    const texto = await respuesta.text();
    const json = parsearJsonSeguro(texto);
    const tipos = Array.isArray(json?.data) ? json.data : [];

    renderizarListaTipos(contenedor, tipos);
  } catch (error) {
    console.warn('No se pudieron cargar tipos:', error);
  }
}

/**
 * Configurar el botón de creación rápida de tipo dentro del modal.
 *
 * @param {HTMLElement} contenedor Nodo contenedor de tipos.
 * @param {HTMLButtonElement|null} botonCrear Botón de crear tipo.
 * @param {HTMLInputElement|null} inputNuevo Campo de texto para el nombre del tipo.
 * @returns {void}
 */
function configurarCreacionTipoInline(contenedor, botonCrear, inputNuevo) {
  if (!botonCrear || !inputNuevo) {
    return;
  }

  botonCrear.addEventListener('click', async () => {
    const nombre = String(inputNuevo.value || '').trim();

    if (!nombre) {
      mostrarToastSeguro('Ingrese nombre del tipo', { type: 'error' });
      return;
    }

    try {
      const respuesta = await fetch(obtenerBaseUrl() + 'api/tipos-actividad', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nombre_tipo: nombre })
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

/**
 * Rellena el modal con los datos de una materia existente para su edición.
 * Marca los checkboxes de los tipos de actividad asociados y preserva sus porcentajes.
 * @param {Object} data Datos de la materia (id, nombre, calif_minima, tipos).
 */
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

/**
 * Obtener referencias de un tipo de actividad desde la API.
 *
 * @param {string} idTipo Identificador del tipo de actividad.
 * @returns {Promise<{actividades?:number, ponderaciones?:number}|null>} Referencias o null.
 */
async function obtenerReferenciasTipo(idTipo) {
  const respuesta = await fetch(
    `${obtenerBaseUrl()}api/tipos-actividad?id=${encodeURIComponent(idTipo)}`,
    { credentials: 'same-origin' }
  );

  const texto = await respuesta.text();
  const json = parsearJsonSeguro(texto);

  if (!respuesta.ok) {
    const mensajeError = json?.message || `HTTP ${respuesta.status}`;
    throw new Error(mensajeError);
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

  if (ponderaciones > 0) {
    return `Este tipo está presente en ${ponderaciones} ponderación(es). Al confirmar, las ponderaciones serán eliminadas.`;
  }

  return '¿Eliminar este tipo de actividad?';
}

/**
 * Enviar al servidor la petición de eliminación de un tipo.
 *
 * @param {string} idTipo Identificador del tipo.
 * @param {boolean} forzar Indica si se debe forzar la eliminación.
 * @returns {Promise<any>} Respuesta JSON del servidor.
 */
async function eliminarTipoEnServidor(idTipo, forzar) {
  const sufijoForce = forzar ? '&force=1' : '';
  const url = `${obtenerBaseUrl()}api/tipos-actividad?id=${encodeURIComponent(idTipo)}${sufijoForce}`;

  const respuesta = await fetch(url, {
    method: 'DELETE',
    credentials: 'same-origin'
  });

  const texto = await respuesta.text();
  const json = parsearJsonSeguro(texto);

  if (!respuesta.ok) {
    const mensajeError = json?.message || texto || `HTTP ${respuesta.status}`;
    throw new Error(mensajeError);
  }

  return json;
}

/**
 * Refrescar los selectores o listas globales de tipos si existen.
 *
 * @returns {void}
 */
function refrescarTiposGlobales() {
  if (typeof globalThis.cargarTiposParaModal === 'function') {
    globalThis.cargarTiposParaModal();
  }

  if (typeof globalThis.poblarSelectsModal === 'function') {
    globalThis.poblarSelectsModal();
  }
}

/**
 * Gestionar el flujo completo de eliminación de un tipo de actividad.
 *
 * @param {HTMLElement} etiqueta Nodo de la etiqueta del tipo.
 * @param {string} idTipo Identificador del tipo.
 * @returns {Promise<void>}
 */
async function manejarEliminacionTipo(etiqueta, idTipo) {
  try {
    const referencias = await obtenerReferenciasTipo(idTipo);
    const mensaje = construirMensajeEliminacion(referencias);
    const confirmar = await solicitarConfirmacion('Confirmar eliminación', mensaje);

    if (!confirmar) {
      return;
    }

    try {
      const r = await fetch((globalThis.BASE_URL || '') + 'api/tipos-actividad?id=' + encodeURIComponent(id), { credentials: 'same-origin' });
      const txt = await r.text(); let json = null; try { json = JSON.parse(txt); } catch (e) { json = null; }
      if (!r.ok) {
        const msg = json?.message || ('HTTP ' + r.status);
        throw new Error(msg);
      }

      if (!mensajePrimario.toLowerCase().includes('referenc')) {
        mostrarToastSeguro(`No se pudo eliminar: ${mensajePrimario}`, { type: 'error' });
        return;
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

    const idTipo = etiqueta.dataset.id;
    if (!idTipo) {
      return;
    }

    void manejarEliminacionTipo(etiqueta, idTipo);
  });
}

/**
 * Cargar tipos globales y configurar los controles del modal de tipos.
 *
 * @returns {Promise<void>}
 */
async function cargarTiposParaModal() {
  const contenedor = document.getElementById('tipos-checkboxes');
  const botonCrear = document.getElementById('btn-crear-tipo-inline');
  const inputNuevo = document.getElementById('nuevo-tipo-nombre');

  function escapeHtml(s) { return String(s || '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", "&#39;"); }
}

/**
 * Al cargar el documento, intentar inicializar el modal de tipos cuando exista.
 *
 * @returns {void}
 */
document.addEventListener('DOMContentLoaded', () => {
  const intentarCargar = () => {
    if (document.getElementById('tipos-checkboxes')) {
      void cargarTiposParaModal();
    } else {
      setTimeout(intentarCargar, 300);
    }
  };

  intentarCargar();
});
