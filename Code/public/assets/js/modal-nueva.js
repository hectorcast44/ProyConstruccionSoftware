/**
 * Abre el modal de actividades en modo "crear".
 *
 * - Si el HTML del modal aún no está en el DOM, lo carga desde un parcial.
 * - Siempre limpia el formulario y el id oculto antes de mostrarlo.
 * - Devuelve una Promise que resuelve con el <dialog> o null si algo falla.
 *
 * @returns {Promise<HTMLDialogElement|null>}
 */
function abrirModalNueva() {
  return new Promise((resolve) => {
    let modal = document.getElementById('modal-nueva');

    if (!modal) {
      const basePath = globalThis.BASE_URL || '';
      fetch(basePath + 'partials/modal-nueva.html')
        .then((r) => r.text())
        .then((html) => {
          document.body.insertAdjacentHTML('beforeend', html);

          inicializarModalNueva();
          poblarSelectsModal();

          try {
            const f = document.getElementById('form-actividad');
            if (f) {
              f.reset();
              delete f.dataset.editIndex;
            }
            const hid = document.getElementById('id_actividad');
            if (hid) hid.value = '';
          } catch (e) { }

          if (window.feather) feather.replace();

          const created = document.getElementById('modal-nueva');
          try {
            created.showModal();
          } catch (e) { }
          resolve(created);
        })
        .catch((err) => {
          console.error('Error cargando modal:', err);
          resolve(null);
        });

      return;
    }

    try {
      const f = document.getElementById('form-actividad');
      if (f) {
        f.reset();
        delete f.dataset.editIndex;
      }
      const hid = document.getElementById('id_actividad');
      if (hid) hid.value = '';
    } catch (e) { }

    try {
      const titleEl = modal.querySelector('.modal-title h2');
      if (titleEl) titleEl.textContent = 'Nueva Actividad';
      const btnCrear = modal.querySelector('#crear-modal');
      if (btnCrear) {
        const span = btnCrear.querySelector('span');
        if (span) span.textContent = 'Crear';
        const ico = btnCrear.querySelector('i');
        if (ico) ico.setAttribute('data-feather', 'plus-circle');
      }
    } catch (e) { }

    try {
      modal.showModal();
    } catch (e) { }

    resolve(modal);
  });
}

/**
 * Configura un input para aceptar únicamente números enteros positivos,
 * reforzando restricciones tanto a nivel de atributos como de evento input.
 *
 * @param {HTMLInputElement|null} input
 * @returns {void}
 */
function configurarInputNumerico(input) {
  if (!input) return;

  try {
    input.type = 'number';
  } catch (e) {
    console.warn('Error inesperado asignando tipo number:', e);
  }

  input.setAttribute('inputmode', 'decimal');
  input.setAttribute('pattern', '[0-9]+([.,][0-9]+)?');
  input.setAttribute('min', '0');
  input.setAttribute('step', '0.01');

  input.addEventListener('input', (ev) => {
    // Permitir números y un solo punto decimal
    const v = ev.target.value || '';
    // Esta regex permite dígitos y un punto opcional
    // Nota: input type="number" a veces limpia el value si es inválido,
    // así que confiamos más en la validación nativa del navegador para UX,
    // pero evitamos caracteres no numéricos excepto punto.
    // const cleaned = String(v).replace(/[^0-9.]/g, '');
    // if (cleaned !== v) ev.target.value = cleaned;
  });
}

/**
 * Inicializa el modal de actividades:
 * - Gestiona cierre.
 * - Configura validación manual del formulario.
 * - Envía los datos al backend en formato JSON.
 *
 * @returns {void}
 */
function inicializarModalNueva() {
  const modal = document.getElementById('modal-nueva');
  const cerrar = document.getElementById('cerrar-modal');
  const form = document.getElementById('form-actividad');

  if (!modal || !form) return;

  if (cerrar) cerrar.addEventListener('click', () => modal.close());

  form.noValidate = true;

  try {
    const inputMax = form.querySelector('[name="puntaje-max"]');
    const inputObt = form.querySelector('[name="puntaje"]');
    configurarInputNumerico(inputMax);
    configurarInputNumerico(inputObt);
  } catch (err) {
    console.debug('No se pudieron inicializar validaciones de puntaje:', err);
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const f = new FormData(form);

    const payload = {
      ...(f.get('id_actividad') ? { id_actividad: Number(f.get('id_actividad')) } : {}),
      id_materia: f.get('materia') ? Number(f.get('materia')) : null,
      id_tipo_actividad: f.get('tipo') ? Number(f.get('tipo')) : null,
      nombre_actividad: f.get('actividad') || '',
      fecha_entrega: f.get('fecha') || '',
      estado: 'pendiente',
      puntos_posibles:
        f.get('puntaje-max') !== null && f.get('puntaje-max') !== ''
          ? Number(f.get('puntaje-max'))
          : null,
      puntos_obtenidos:
        f.get('puntaje') !== null && f.get('puntaje') !== ''
          ? Number(f.get('puntaje'))
          : null
    };

    if (
      !payload.id_materia ||
      !payload.id_tipo_actividad ||
      !payload.nombre_actividad ||
      payload.fecha_entrega === ''
    ) {
      showToast(
        'Por favor completa los campos obligatorios: Materia, Tipo, Actividad y Fecha.',
        { type: 'error' }
      );
      return;
    }

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

      try {
        json = JSON.parse(txt);
      } catch (e) {
        throw new Error('Respuesta inválida del servidor');
      }

      if (!res.ok) throw new Error(json.message || `HTTP ${res.status}`);

      form.reset();
      modal.close();

      try {
        if (typeof window.cargarActividadesDesdeAPI === 'function') {
          window.cargarActividadesDesdeAPI();
        } else if (typeof window.cargarActividades === 'function') {
          window.cargarActividades();
        }

        if (typeof window.cargarMateriasDesdeAPI === 'function') {
          window.cargarMateriasDesdeAPI();
        }

        if (typeof window.cargarDetalleMateria === 'function') {
          window.cargarDetalleMateria();
        }
      } catch (e) {
        console.warn('No se pudieron invocar funciones de recarga tras crear actividad', e);
      }

      showToast(json.message || 'Actividad creada', { type: 'success' });
    } catch (err) {
      console.error('Error creando actividad:', err);
      showToast('Error al crear actividad: ' + (err.message || err), { type: 'error' });
    }
  });
}

/**
 * Pobla los selects de Materia y Tipo dentro del modal usando la API.
 *
 * Comportamiento:
 * - Carga todas las materias disponibles.
 * - Intenta obtener un catálogo global de tipos de actividad.
 * - Cuando cambia la materia, intenta traer tipos específicos para esa materia.
 *   Si no existen, reutiliza los tipos globales o muestra un mensaje.
 *
 * @returns {Promise<void>}
 */
async function poblarSelectsModal() {
  const base = globalThis.BASE_URL || '';
  const selectMateria = document.querySelector('#modal-nueva select[name="materia"]');
  const selectTipo = document.querySelector('#modal-nueva select[name="tipo"]');

  if (!selectMateria && !selectTipo) return;

  try {
    if (selectMateria) {
      const r = await fetch(base + 'api/materias', { credentials: 'same-origin' });
      const txt = await r.text();
      const json = JSON.parse(txt);
      const materias = json.data || [];
      const options = materias
        .map((m) => {
          const id = m.id ?? m.id_materia ?? m.idMateria ?? '';
          const nombre = m.nombre ?? m.nombre_materia ?? m.nombreMateria ?? String(id);
          return `<option value="${escapeHtml(String(id))}">${escapeHtml(nombre)}</option>`;
        })
        .join('');
      selectMateria.innerHTML =
        '<option value="" disabled selected>Selecciona una materia</option>' + options;
    }

    let tiposGlobal = [];
    try {
      const r2 = await fetch(base + 'api/tipos-actividad', { credentials: 'same-origin' });
      const txt2 = await r2.text();
      const json2 = JSON.parse(txt2);
      tiposGlobal = json2 && Array.isArray(json2.data) ? json2.data : [];
    } catch (err) {
      console.warn('No se pudieron cargar tipos globales inicialmente:', err);
      tiposGlobal = [];
    }

    if (selectTipo) {
      if (tiposGlobal.length) {
        const opts = tiposGlobal
          .map((t) => {
            const id = t.id_tipo_actividad ?? t.id ?? t.idTipo ?? '';
            const nombre = t.nombre_tipo ?? t.nombre ?? t.nombreTipo ?? String(id);
            return `<option value="${escapeHtml(String(id))}">${escapeHtml(nombre)}</option>`;
          })
          .join('');
        selectTipo.innerHTML =
          '<option value="" disabled selected>Selecciona un tipo</option>' + opts;
        selectTipo.disabled = false;
      } else {
        selectTipo.innerHTML =
          '<option value="" disabled selected>No hay tipos disponibles</option>';
        selectTipo.disabled = true;
      }
    }

    if (selectMateria && selectTipo) {
      selectMateria.addEventListener('change', async () => {
        const val = selectMateria.value;

        if (!val) {
          if (tiposGlobal.length) {
            const opts = tiposGlobal
              .map((t) => {
                const id = t.id_tipo_actividad ?? t.id ?? t.idTipo ?? '';
                const nombre = t.nombre_tipo ?? t.nombre ?? t.nombreTipo ?? String(id);
                return `<option value="${escapeHtml(String(id))}">${escapeHtml(
                  nombre
                )}</option>`;
              })
              .join('');
            selectTipo.innerHTML =
              '<option value="" disabled selected>Selecciona un tipo</option>' + opts;
            selectTipo.disabled = false;
            return;
          }

          selectTipo.innerHTML =
            '<option value="" disabled selected>No hay tipos disponibles</option>';
          selectTipo.disabled = true;
          return;
        }

        try {
          const r = await fetch(
            base + 'api/materias/tipos?id=' + encodeURIComponent(val),
            { credentials: 'same-origin' }
          );
          const txt = await r.text();
          let json = null;
          try {
            json = JSON.parse(txt);
          } catch (e) {
            json = null;
          }
          const tiposMat = json && Array.isArray(json.data) ? json.data : [];

          if (!tiposMat.length) {
            if (tiposGlobal.length) {
              const opts = tiposGlobal
                .map((t) => {
                  const id = t.id_tipo_actividad ?? t.id ?? t.idTipo ?? '';
                  const nombre = t.nombre_tipo ?? t.nombre ?? t.nombreTipo ?? String(id);
                  return `<option value="${escapeHtml(String(id))}">${escapeHtml(
                    nombre
                  )}</option>`;
                })
                .join('');
              selectTipo.innerHTML =
                '<option value="" disabled selected>Selecciona un tipo</option>' + opts;
              selectTipo.disabled = false;
            } else {
              selectTipo.innerHTML =
                '<option value="" disabled selected>No hay tipos definidos para esta materia</option>';
              selectTipo.disabled = false;
            }
            return;
          }

          const opts = tiposMat
            .map((t) => {
              const id = t.id_tipo_actividad ?? t.id ?? t.idTipo ?? '';
              const nombre = t.nombre_tipo ?? t.nombre ?? t.nombreTipo ?? String(id);
              return `<option value="${escapeHtml(String(id))}">${escapeHtml(
                nombre
              )}</option>`;
            })
            .join('');

          selectTipo.innerHTML =
            '<option value="" disabled selected>Selecciona un tipo</option>' + opts;
          selectTipo.disabled = false;
        } catch (e) {
          console.warn('No se pudieron cargar tipos de materia:', e);
          selectTipo.innerHTML =
            '<option value="" disabled selected>Error cargando tipos</option>';
          selectTipo.disabled = true;
        }
      });
    }
  } catch (e) {
    console.warn('No se pudieron poblar selects del modal:', e);
  }

  function escapeHtml(s) {
    return String(s || '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#39;');
  }
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = { abrirModalNueva, inicializarModalNueva, poblarSelectsModal, showToast };
}
