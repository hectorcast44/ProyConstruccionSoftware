function abrirModalNueva() {
  return new Promise((resolve) => {
    let modal = document.getElementById('modal-nueva');

    if (!modal) {
      // cargar parcial del modal (una sola vez)
      const basePath = globalThis.BASE_URL || '';
      fetch(basePath + 'partials/modal-nueva.html')
        .then(r => r.text())
        .then(html => {
          document.body.insertAdjacentHTML('beforeend', html);

          // Inicializar listeners del modal (cerrar, submit, feather) y poblar selects
          inicializarModalNueva();
          poblarSelectsModal();

          // asegurar estado limpio para crear: resetear form e id oculto
          try {
            const f = document.getElementById('form-actividad');
            if (f) {
              f.reset();
              delete f.dataset.editIndex;
            }
            const hid = document.getElementById('id_actividad');
            if (hid) hid.value = '';
          } catch (e) {  }

          // renderizar iconos dentro del modal
          if (window.feather) feather.replace();

          // mostrar modal
          const created = document.getElementById('modal-nueva');
          try { created.showModal(); } catch (e) { /* ignore */ }
          resolve(created);
        })
        .catch(err => {
          console.error('Error cargando modal:', err);
          resolve(null);
        });
      return;
    }

    // al abrir (cuando ya existe), limpiar estado por defecto (crear)
    try {
      const f = document.getElementById('form-actividad');
      if (f) {
        f.reset();
        delete f.dataset.editIndex;
      }
      const hid = document.getElementById('id_actividad');
      if (hid) hid.value = '';
    } catch (e) { }

    // Restaurar texto por defecto (modo crear) cuando se abre sin editar
    try {
      const titleEl = modal.querySelector('.modal-title h2');
      if (titleEl) titleEl.textContent = 'Nueva Actividad';
      const btnCrear = modal.querySelector('#crear-modal');
      if (btnCrear) {
        const span = btnCrear.querySelector('span'); if (span) span.textContent = 'Crear';
        const ico = btnCrear.querySelector('i'); if (ico) ico.setAttribute('data-feather', 'plus-circle');
      }
    } catch (e) { }

    try { modal.showModal(); } catch (e) { /* ignore */ }
    resolve(modal);
  });
}

function inicializarModalNueva() {
  const modal = document.getElementById('modal-nueva');
  const cerrar = document.getElementById('cerrar-modal');
  const form = document.getElementById('form-actividad');

  if (!modal || !form) return;

  // cerrar
  if (cerrar) cerrar.addEventListener('click', () => modal.close());

  // Desactivar la validación nativa para controlar validaciones desde JS
  form.noValidate = true;

  // Asegurar que los campos de puntaje sólo acepten valores numéricos positivos
  try {
    const inputMax = form.querySelector('[name="puntaje-max"]');
    const inputObt = form.querySelector('[name="puntaje"]');

    if (inputMax) {
      try { inputMax.type = 'number'; } catch (e) { }
      inputMax.setAttribute('inputmode', 'numeric');
      inputMax.setAttribute('pattern', '[0-9]+');
      inputMax.setAttribute('min', '1');
      inputMax.setAttribute('step', '1');
      inputMax.addEventListener('input', (ev) => {
        const v = ev.target.value || '';
        const cleaned = String(v).replace(/[^0-9]/g, '');
        if (cleaned !== v) ev.target.value = cleaned;
      });
    }

    if (inputObt) {
      try { inputObt.type = 'number'; } catch (e) { }
      inputObt.setAttribute('inputmode', 'numeric');
      inputObt.setAttribute('pattern', '[0-9]+');
      inputObt.setAttribute('min', '1');
      inputObt.setAttribute('step', '1');
      inputObt.addEventListener('input', (ev) => {
        const v = ev.target.value || '';
        const cleaned = String(v).replace(/[^0-9]/g, '');
        if (cleaned !== v) ev.target.value = cleaned;
      });
    }
  } catch (err) {
    console.debug('No se pudieron inicializar validaciones de puntaje:', err);
  }

  // submit del form: enviar al backend via fetch (JSON)
  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Construir payload según espera ActividadController::store()
    const f = new FormData(form);

    const payload = {
      // include id_actividad when editing (modal sets hidden input)
      ...(f.get('id_actividad') ? { id_actividad: Number(f.get('id_actividad')) } : {}),
      id_materia: f.get('materia') ? Number(f.get('materia')) : null,
      id_tipo_actividad: f.get('tipo') ? Number(f.get('tipo')) : null,
      nombre_actividad: f.get('actividad') || '',
      fecha_entrega: f.get('fecha') || '',
      estado: 'pendiente',
      puntos_posibles: f.get('puntaje-max') !== null && f.get('puntaje-max') !== '' ? Number(f.get('puntaje-max')) : null,
      puntos_obtenidos: f.get('puntaje') !== null && f.get('puntaje') !== '' ? Number(f.get('puntaje')) : null
    };

    // Validación mínima (controlada por JS porque desactivamos la validación nativa)
    if (!payload.id_materia || !payload.id_tipo_actividad || !payload.nombre_actividad || payload.puntos_posibles === null || payload.fecha_entrega === '') {
      showToast('Por favor completa los campos obligatorios: Materia, Tipo, Actividad, Puntaje máximo y Fecha.', { type: 'error' });
      return;
    }

    // Validar que los puntajes sean positivos (> 0)
    if (payload.puntos_posibles !== null && Number(payload.puntos_posibles) <= 0) {
      showToast('El puntaje máximo debe ser un número positivo.', { type: 'error' });
      return;
    }

    if (payload.puntos_obtenidos !== null && Number(payload.puntos_obtenidos) <= 0) {
      showToast('El puntaje obtenido debe ser un número positivo.', { type: 'error' });
      return;
    }

    try {
      const base = globalThis.BASE_URL || '';
      const res = await fetch(base + 'api/actividades', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const txt = await res.text();
      let json = null;
      try { json = JSON.parse(txt); } catch (e) { throw new Error('Respuesta inválida del servidor'); }

      if (!res.ok) throw new Error(json.message || ('HTTP ' + res.status));

      // éxito: cerrar modal y refrescar listas si hay funciones globales
      form.reset();
      modal.close();
      try {
        if (typeof window.cargarActividadesDesdeAPI === 'function') {
          window.cargarActividadesDesdeAPI();
        } else if (typeof window.cargarActividades === 'function') {
          window.cargarActividades();
        }

        // refrescar lista de materias si existe
        if (typeof window.cargarMateriasDesdeAPI === 'function') {
          window.cargarMateriasDesdeAPI();
        }

        // refrescar vista de detalle si está presente
        if (typeof window.cargarDetalleMateria === 'function') {
          window.cargarDetalleMateria();
        }
      } catch (e) {
        console.warn('No se pudieron invocar funciones de recarga tras crear actividad', e);
      }

      // notificación no bloqueante
      showToast(json.message || 'Actividad creada', { type: 'success' });

    } catch (err) {
      console.error('Error creando actividad:', err);
      showToast('Error al crear actividad: ' + (err.message || err), { type: 'error' });
    }
  });
}

// Poblar selects de materia y tipo usando la API
async function poblarSelectsModal() {
  const base = globalThis.BASE_URL || '';
  const selectMateria = document.querySelector('#modal-nueva select[name="materia"]');
  const selectTipo = document.querySelector('#modal-nueva select[name="tipo"]');
  if (!selectMateria && !selectTipo) return;

  try {
    // cargar materias
    if (selectMateria) {
      const r = await fetch(base + 'api/materias', { credentials: 'same-origin' });
      const txt = await r.text();
      const json = JSON.parse(txt);
      const materias = json.data || [];
      const options = materias.map(m => {
        const id = m.id ?? m.id_materia ?? m.idMateria ?? '';
        const nombre = m.nombre ?? m.nombre_materia ?? m.nombreMateria ?? String(id);
        return `<option value="${escapeHtml(String(id))}">${escapeHtml(nombre)}</option>`;
      }).join('');
      selectMateria.innerHTML = '<option value="" disabled selected>Selecciona una materia</option>' + options;
    }

    // cargar tipos globales UNA VEZ
    let tiposGlobal = [];
    try {
      const r2 = await fetch(base + 'api/tipos-actividad', { credentials: 'same-origin' });
      const txt2 = await r2.text();
      const json2 = JSON.parse(txt2);
      tiposGlobal = (json2 && Array.isArray(json2.data)) ? json2.data : [];
    } catch (err) {
      console.warn('No se pudieron cargar tipos globales inicialmente:', err);
      tiposGlobal = [];
    }

    // poblar selectTipo con tipos globales (permitir elegir tipo antes de materia)
    if (selectTipo) {
      if (tiposGlobal.length) {
        const opts = tiposGlobal.map(t => {
          const id = t.id_tipo_actividad ?? t.id ?? t.idTipo ?? '';
          const nombre = t.nombre_tipo ?? t.nombre ?? t.nombreTipo ?? String(id);
          return `<option value="${escapeHtml(String(id))}">${escapeHtml(nombre)}</option>`;
        }).join('');
        selectTipo.innerHTML = '<option value="" disabled selected>Selecciona un tipo</option>' + opts;
        selectTipo.disabled = false;
      } else {
        selectTipo.innerHTML = '<option value="" disabled selected>No hay tipos disponibles</option>';
        selectTipo.disabled = true;
      }
    }

    // cuando cambie la materia, preferir cargar los tipos específicos de esa materia (si existen)
    if (selectMateria && selectTipo) {
      selectMateria.addEventListener('change', async () => {
        const val = selectMateria.value;
        if (!val) {
          // Restaurar tipos globales
          if (tiposGlobal.length) {
            const opts = tiposGlobal.map(t => {
              const id = t.id_tipo_actividad ?? t.id ?? t.idTipo ?? '';
              const nombre = t.nombre_tipo ?? t.nombre ?? t.nombreTipo ?? String(id);
              return `<option value="${escapeHtml(String(id))}">${escapeHtml(nombre)}</option>`;
            }).join('');
            selectTipo.innerHTML = '<option value="" disabled selected>Selecciona un tipo</option>' + opts;
            selectTipo.disabled = false;
            return;
          }
          selectTipo.innerHTML = '<option value="" disabled selected>No hay tipos disponibles</option>';
          selectTipo.disabled = true;
          return;
        }

        try {
          const r = await fetch(base + 'api/materias/tipos?id=' + encodeURIComponent(val), { credentials: 'same-origin' });
          const txt = await r.text(); let json = null; try { json = JSON.parse(txt); } catch(e){ json = null; }
          const tiposMat = (json && Array.isArray(json.data)) ? json.data : [];

          if (!tiposMat.length) {
            // No hay tipos asignados para la materia: mantener los tipos globales o mostrar mensaje
            if (tiposGlobal.length) {
              const opts = tiposGlobal.map(t => {
                const id = t.id_tipo_actividad ?? t.id ?? t.idTipo ?? '';
                const nombre = t.nombre_tipo ?? t.nombre ?? t.nombreTipo ?? String(id);
                return `<option value="${escapeHtml(String(id))}">${escapeHtml(nombre)}</option>`;
              }).join('');
              selectTipo.innerHTML = '<option value="" disabled selected>Selecciona un tipo</option>' + opts;
              selectTipo.disabled = false;
            } else {
              selectTipo.innerHTML = '<option value="" disabled selected>No hay tipos definidos para esta materia</option>';
              selectTipo.disabled = false;
            }
            return;
          }

          const opts = tiposMat.map(t => {
            const id = t.id_tipo_actividad ?? t.id ?? t.idTipo ?? '';
            const nombre = t.nombre_tipo ?? t.nombre ?? t.nombreTipo ?? String(id);
            return `<option value="${escapeHtml(String(id))}">${escapeHtml(nombre)}</option>`;
          }).join('');

          selectTipo.innerHTML = '<option value="" disabled selected>Selecciona un tipo</option>' + opts;
          selectTipo.disabled = false;
        } catch (e) {
          console.warn('No se pudieron cargar tipos de materia:', e);
          selectTipo.innerHTML = '<option value="" disabled selected>Error cargando tipos</option>';
          selectTipo.disabled = true;
        }
      });
    }
  } catch (e) {
    console.warn('No se pudieron poblar selects del modal:', e);
  }

  function escapeHtml(s) { return String(s || '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", "&#39;"); }
}

// Toast helper: muestra notificaciones temporales en la esquina inferior derecha
function showToast(message, { duration = 4000, type = 'info' } = {}) {
  try {
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
    toast.className = 'app-toast app-toast-' + type;
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

    // force reflow then show
    void toast.offsetWidth;
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';

    const hide = () => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(8px)';
      setTimeout(() => { try { toast.remove(); } catch (e) { } }, 260);
    };

    const timer = setTimeout(hide, duration);

    toast.addEventListener('click', () => { clearTimeout(timer); hide(); });

    return toast;
  } catch (e) {
    console.error('showToast error', e);
  }
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = { abrirModalNueva, inicializarModalNueva, poblarSelectsModal, showToast };
}