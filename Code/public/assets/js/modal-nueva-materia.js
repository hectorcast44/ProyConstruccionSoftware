/**
 * Abre el modal para crear o editar una materia.
 * Si se proporciona `data`, se pre-llena el formulario para edición.
 *
 * @param {Object|null} data Datos de la materia a editar (opcional).
 * @returns {void}
 */
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

        // Intentar cargar tipos si no se han cargado
        if (typeof cargarTiposParaModal === 'function') {
          cargarTiposParaModal();
        }

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

/**
 * Inicializa los eventos del modal de nueva materia (submit, cerrar).
 * Configura la lógica de envío del formulario, incluyendo validación de porcentajes.
 *
 * @returns {void}
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

      try { console.debug && console.debug('modal-nueva-materia: create/update response', { payload, json, targetId, isEdit }); } catch (e) {}

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
 *
 * @param {Object} data Datos de la materia (id, nombre, calif_minima, tipos).
 * @returns {void}
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
 * Cargar tipos globales y configurar los controles del modal de tipos.
 *
 * @returns {Promise<void>}
 */
async function cargarTiposParaModal() {
  const contenedor = document.getElementById('tipos-checkboxes');
  const botonCrear = document.getElementById('btn-crear-tipo-inline');
  const inputNuevo = document.getElementById('nuevo-tipo-nombre');

  if (!contenedor) return;

  try {
    const respuesta = await fetch((globalThis.BASE_URL || '') + 'api/tipos-actividad', {
      credentials: 'same-origin'
    });
    const texto = await respuesta.text();
    let json = null;
    try { json = JSON.parse(texto); } catch (e) { json = null; }

    const tipos = (json && Array.isArray(json.data)) ? json.data : [];

    // Renderizar lista
    contenedor.innerHTML = '';
    tipos.forEach(tipo => {
      const label = document.createElement('label');
      label.style.display = 'flex';
      label.style.alignItems = 'center';
      label.style.gap = '8px';
      label.style.marginBottom = '4px';

      // Checkbox
      const ch = document.createElement('input');
      ch.type = 'checkbox';
      ch.value = String(tipo.id_tipo_actividad);
      ch.name = 'tipos[]';

      // Span con nombre y badge de eliminar
      const span = document.createElement('span');
      span.style.flex = '1';
      span.style.display = 'flex';
      span.style.justifyContent = 'space-between';
      span.style.alignItems = 'center';

      const textoSpan = document.createElement('span');
      textoSpan.textContent = tipo.nombre_tipo;

      // Botón eliminar (x)
      const btnDel = document.createElement('span');
      btnDel.innerHTML = '&times;';
      btnDel.style.cursor = 'pointer';
      btnDel.style.color = '#999';
      btnDel.style.fontWeight = 'bold';
      btnDel.style.marginLeft = '8px';
      btnDel.title = 'Eliminar tipo';
      btnDel.dataset.id = tipo.id_tipo_actividad;

      // Evento eliminar
      btnDel.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        manejarEliminacionTipo(label, tipo.id_tipo_actividad);
      });

      span.appendChild(textoSpan);
      span.appendChild(btnDel);

      label.appendChild(ch);
      label.appendChild(span);
      contenedor.appendChild(label);
    });

  } catch (error) {
    console.warn('No se pudieron cargar tipos:', error);
    contenedor.innerHTML = '<p style="color:red">Error cargando tipos</p>';
  }

  // Configurar creación inline si no está configurada
  if (botonCrear && inputNuevo && !botonCrear.dataset.configured) {
    botonCrear.dataset.configured = 'true';
    botonCrear.addEventListener('click', async (e) => {
      e.preventDefault();
      const nombre = String(inputNuevo.value || '').trim();
      if (!nombre) {
        if (typeof showToast === 'function') showToast('Ingrese nombre del tipo', { type: 'error' });
        return;
      }

      try {
        const res = await fetch((globalThis.BASE_URL || '') + 'api/tipos-actividad', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ nombre_tipo: nombre })
        });
        const txt = await res.text();
        let json = null; try { json = JSON.parse(txt); } catch (e) { }

        if (!res.ok) throw new Error(json?.message || ('HTTP ' + res.status));

        if (typeof showToast === 'function') showToast(json?.message || 'Tipo creado', { type: 'success' });
        inputNuevo.value = '';

        // Recargar lista
        cargarTiposParaModal();

      } catch (err) {
        console.error('Error creando tipo:', err);
        if (typeof showToast === 'function') showToast('Error: ' + (err.message || err), { type: 'error' });
      }
    });
  }
}

/**
 * Obtener referencias de un tipo de actividad desde la API.
 *
 * @param {number|string} idTipo ID del tipo de actividad.
 * @returns {Promise<Object>} Datos de las referencias.
 */
async function obtenerReferenciasTipo(idTipo) {
  const respuesta = await fetch(
    `${globalThis.BASE_URL || ''}api/tipos-actividad?id=${encodeURIComponent(idTipo)}`,
    { credentials: 'same-origin' }
  );
  const texto = await respuesta.text();
  const json = JSON.parse(texto); 
  if (!respuesta.ok) throw new Error(json?.message || 'Error obteniendo referencias');
  return json.data; // Debería devolver objeto con conteo de referencias
}

/**
 * Gestionar el flujo completo de eliminación de un tipo de actividad.
 *
 * @param {HTMLElement} etiqueta Elemento DOM que representa el tipo en la lista.
 * @param {number|string} idTipo ID del tipo de actividad.
 * @returns {Promise<void>}
 */
async function manejarEliminacionTipo(etiqueta, idTipo) {
  if (!confirm('¿Estás seguro de eliminar este tipo de actividad?')) return;

  try {
    const res = await fetch(`${globalThis.BASE_URL || ''}api/tipos-actividad?id=${encodeURIComponent(idTipo)}`, {
      method: 'DELETE',
      credentials: 'same-origin'
    });

    const txt = await res.text();
    let json;
    try {
      json = JSON.parse(txt);
    } catch (e) {
      console.error('Error parseando JSON: ', e);
      throw e; 
    }


    if (res.ok) {
      etiqueta.remove();
      if (typeof showToast === 'function')
        showToast('Tipo eliminado', { type: 'success' });
      return;
    }

    const msg = json?.message || txt;

    if (msg.toLowerCase().includes('referenc') || msg.toLowerCase().includes('asociadas')) {

      if (confirm('Este tipo tiene actividades o ponderaciones asociadas. ¿Deseas forzar la eliminación (se borrarán las actividades asociadas)?')) {

        const resForce = await fetch(`${globalThis.BASE_URL || ''}api/tipos-actividad?id=${encodeURIComponent(idTipo)}&force=1`, {
          method: 'DELETE',
          credentials: 'same-origin'
        });

        const txtForce = await resForce.text();
        let jsonForce = null; 
        try { 
          jsonForce = JSON.parse(txtForce); 
        }  catch (e) {
            console.error('Error parseando jsonForce: ', e);
            throw e; 
        }

        if (resForce.ok) {
          etiqueta.remove();
          if (typeof showToast === 'function') {
            showToast('Tipo eliminado forzosamente', { type: 'success' });
          }
        } else if (typeof showToast === 'function') {
          showToast('Error eliminando: ' + (jsonForce?.message || txtForce), { type: 'error' });
        } else {
          console.warn('Error eliminando:', (jsonForce?.message || txtForce));
        }

      }

    } else if (typeof showToast === 'function') {
      showToast('Error eliminando: ' + msg, { type: 'error' });
    } else {
      console.warn('Error eliminando:', msg);
    }

  } catch (err) {
    console.error('Error delete tipo:', err);
    if (typeof showToast === 'function') {
      showToast('Error de conexión al eliminar', { type: 'error' });
    } else {
      console.error('Error de conexión al eliminar');
    }
  }
}


/**
 * Al cargar el documento, intentar inicializar el modal de tipos cuando exista.
 */
document.addEventListener('DOMContentLoaded', () => {
  const intentarCargar = () => {
    if (document.getElementById('tipos-checkboxes')) {
      cargarTiposParaModal();
    } else {
      // Reintentar por si el modal se carga dinámicamente
      setTimeout(intentarCargar, 500);
    }
  };
  intentarCargar();
  intentarCargar();
});

if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    abrirModalCrearMateria,
    inicializarModalNuevaMateria,
    prefilarModalMateria,
    cargarTiposParaModal,
    obtenerReferenciasTipo,
    manejarEliminacionTipo
  };
}
