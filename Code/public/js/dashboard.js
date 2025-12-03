/* ==========================================================
   HELPERS GENERALES
   ========================================================== */

/**
 * Obtener la URL base configurada globalmente.
 *
 * @returns {string} URL base de la aplicación.
 */
function obtenerBaseUrl() {
  return globalThis.BASE_URL || '';
}

/**
 * Mostrar un mensaje emergente si showToast está disponible.
 *
 * @param {string} mensaje Mensaje a mostrar.
 * @param {{type?: string}} [opciones] Opciones de visualización.
 * @returns {void}
 */
function mostrarToastSeguro(mensaje, opciones = {}) {
  if (typeof showToast === 'function') {
    showToast(mensaje, opciones);
  } else {
    const tipo = opciones.type === 'error' ? 'Error' : 'Info';
    console[opciones.type === 'error' ? 'error' : 'log'](`${tipo}: ${mensaje}`);
  }
}

/**
 * Analizar una cadena como JSON de forma segura.
 *
 * @param {string} texto Cadena a convertir.
 * @returns {any|null} Objeto JSON o null si falla.
 */
function parsearJsonSeguro(texto) {
  try {
    return JSON.parse(texto);
  } catch (error) {
    console.warn('No se pudo parsear JSON de forma segura:', error);
    return null;
  }
}

/**
 * Escapar caracteres especiales HTML en una cadena.
 *
 * @param {string} texto Texto a escapar.
 * @returns {string} Texto seguro para insertar en HTML.
 */
function escapeHtml(texto) {
  return String(texto || '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}

/**
 * Actualiza el título y el texto del botón del modal de actividad
 * según el modo actual (crear/editar).
 *
 * @param {'crear'|'editar'} modo
 * @returns {void}
 */
function actualizarUiModalActividad(modo) {
  const titulo = document.getElementById('modal-actividad-titulo');
  const btnSubmit = document.getElementById('modal-actividad-submit');

  if (!titulo || !btnSubmit) {
    return;
  }

  if (modo === 'editar') {
    titulo.textContent = 'Editar actividad';
    btnSubmit.textContent = 'Guardar';
  } else {
    // modo "crear" por defecto
    titulo.textContent = 'Nueva actividad';
    btnSubmit.textContent = 'Crear';
  }
}

/* ==========================================================
   ESTADO Y NORMALIZADORES
   ========================================================== */

let modoEdicionActivo = false;
let actividadesGlobales = [];
let calendarInstance = null;
let vistaActual = 'tabla'; // 'tabla' | 'calendario'

/**
 * Normaliza distintos valores de estado desde la API/BD
 * a los tres estados usados en la UI.
 *
 * @param {any} raw Valor crudo del estado.
 * @returns {'listo'|'en curso'|'pendiente'} Estado normalizado.
 */
function normalizeEstado(raw) {
  if (!raw && raw !== 0) {
    return 'pendiente';
  }

  const s = String(raw).toLowerCase().trim();

  if (s === 'listo') {
    return 'listo';
  }

  if (s === 'en curso' || s === 'encurso' || s === 'en_curso') {
    return 'en curso';
  }

  if (s === 'pendiente') {
    return 'pendiente';
  }

  return 'pendiente';
}

/**
 * Obtiene la clase CSS para el tag del tipo de actividad.
 * Usa UIHelpers.TagStyleManager si está disponible; de lo contrario,
 * aplica un mapeo local por nombre.
 *
 * @param {object} tipo - Objeto con información del tipo de actividad.
 * @returns {string} Clase CSS.
 */
function obtenerTagClassPorTipo(tipo) {
  const key = tipo.id_tipo ?? tipo.id_tipo_actividad ?? tipo.nombre;
  const raw = String(key || '').toLowerCase().trim();

  // Intentar usar TagStyleManager solo si existe y expone getClassFor
  if (
    globalThis.UIHelpers &&
    UIHelpers.TagStyleManager &&
    typeof UIHelpers.TagStyleManager.getClassFor === 'function'
  ) {
    return UIHelpers.TagStyleManager.getClassFor(key);
  }

  // Fallback sencillo (igual que en mis-actividades)
  if (raw.includes('ejerc')) return 'tag-rojo';
  if (raw.includes('examen')) return 'tag-azul';
  if (raw.includes('proyecto')) return 'tag-verde';
  if (raw.includes('tarea') || raw.includes('trabajo')) return 'tag-naranja';

  return 'tag-agua';
}

/**
 * Mapear nombre de tipo de actividad a clase CSS.
 *
 * @param {any} raw Texto crudo del tipo.
 * @returns {string} Clase CSS asociada.
 */
function tipoClase(raw) {
  if (raw === undefined || raw === null) {
    return 'tag-agua';
  }

  const s = String(raw).toLowerCase().trim();

  if (s.includes('ejerc')) return 'tag-rojo';
  if (s.includes('examen')) return 'tag-azul';
  if (s.includes('proyecto')) return 'tag-verde';
  if (s.includes('tarea') || s.includes('trabajo')) return 'tag-naranja';

  return 'tag-agua';
}


/* ==========================================================
   INICIALIZACIÓN AL CARGAR EL DOCUMENTO
   ========================================================== */

document.addEventListener('DOMContentLoaded', verificarTablaVacia);
document.addEventListener('DOMContentLoaded', () => {
  cargarActividadesDesdeAPI();
});

/* ==========================================================
   VERIFICAR TABLA VACÍA
   ========================================================== */

/**
 * Si la tabla está vacía, oculta/ muestra los elementos correctos.
 *
 * @returns {void}
 */
function verificarTablaVacia() {
  const tabla = document.getElementById('tabla');
  const btnEditar = document.getElementById('contenedor-boton-editar');
  const btnEliminar = document.getElementById('contenedor-boton-eliminar');
  const btnFiltro = document.getElementById('contenedor-boton-filtro');
  const filas = document.querySelectorAll('#tabla-body tr');
  const msg = document.getElementById('mensaje-vacio');
  const tablaVacia = document.getElementById('tabla-vacia');
  const searchbar = document.getElementById('search-box');

  if (!tabla && !document.getElementById('tabla-body') && !msg && !tablaVacia) {
    return;
  }

  let visibleFilas = 0;
  for (const fila of filas) {
    if ((fila.style.display || '') !== 'none') {
      visibleFilas += 1;
    }
  }
  const totalFilas = filas.length;

  if (totalFilas === 0) {
    msg?.classList.remove('oculto');
    tablaVacia?.classList.add('oculto');
    tabla?.classList.add('oculto');
    btnEditar?.classList.add('oculto');
    btnEliminar?.classList.add('oculto');
    btnFiltro?.classList.add('oculto');
    searchbar?.classList.add('oculto');
    return;
  }

  if (visibleFilas === 0) {
    msg?.classList.add('oculto');
    tablaVacia?.classList.remove('oculto');
    tabla?.classList.add('oculto');
    btnEditar?.classList.add('oculto');
    btnEliminar?.classList.add('oculto');
    btnFiltro?.classList.remove('oculto');
    searchbar?.classList.remove('oculto');
    return;
  }

  msg?.classList.add('oculto');
  tablaVacia?.classList.add('oculto');
  tabla?.classList.remove('oculto');
  btnEditar?.classList.remove('oculto');
  btnEliminar?.classList.remove('oculto');
  btnFiltro?.classList.remove('oculto');
  searchbar?.classList.remove('oculto');
}

/* ==========================================================
   CARGA DE ACTIVIDADES DESDE API
   ========================================================== */

async function obtenerMateriasDesdeApi(baseUrl) {
  const respuesta = await fetch(baseUrl + 'api/materias', {
    credentials: 'same-origin'
  });

  const texto = await respuesta.text();
  const json = parsearJsonSeguro(texto);

  if (!json) {
    throw new Error('Respuesta inválida de /api/materias');
  }

  if (!respuesta.ok) {
    const mensaje = json.message || `HTTP ${respuesta.status}`;
    throw new Error(mensaje);
  }

  return json.data || [];
}

async function obtenerActividadesPorMateria(baseUrl, materias) {
  const resultados = [];

  for (const materia of materias) {
    const url = `${baseUrl}api/actividades?id_materia=${encodeURIComponent(materia.id)}`;
    const respuesta = await fetch(url, { credentials: 'same-origin' });
    const texto = await respuesta.text();
    const json = parsearJsonSeguro(texto);

    if (!json) {
      throw new Error('Respuesta inválida de /api/actividades');
    }

    if (!respuesta.ok) {
      const mensaje = json.message || `HTTP ${respuesta.status}`;
      throw new Error(mensaje);
    }

    resultados.push({
      materia,
      data: json.data
    });
  }

  return resultados;
}

function construirFilasActividades(resultados) {
  const filas = [];

  for (const resultado of resultados) {
    const materia = resultado.materia;
    const data = resultado.data || {};
    const secciones = data.secciones || [];

    for (const seccion of secciones) {
      const tipoNombre = seccion.nombre_tipo || seccion.nombre || '';
      const actividades = seccion.actividades || [];

      for (const actividad of actividades) {
        const puntosObtenidos =
          actividad.puntos_obtenidos ??
          actividad.obtenido ??
          null;

        const puntosPosibles =
          actividad.puntos_posibles ??
          actividad.maximo ??
          null;

        filas.push({
          id: actividad.id_actividad || actividad.id || '',
          fecha: actividad.fecha_entrega || actividad.fecha || '',
          nombre: actividad.nombre || actividad.nombre_actividad || '',
          materia: materia.nombre || '',
          tipo: tipoNombre,
          estado: normalizeEstado(
            actividad.estado ||
            (puntosObtenidos !== null ? 'listo' : 'pendiente')
          ),
          obtenido: puntosObtenidos,
          maximo: puntosPosibles,
          id_materia: materia.id || materia.id_materia || materia.idMateria || null,
          id_tipo_actividad:
            seccion.id_tipo_actividad || seccion.id_tipo || seccion.idTipo || null
        });
      }
    }
  }

  return filas;
}


function generarBadgeProgreso(estado) {
  const est = normalizeEstado(estado);
  let clase = 'progress-encurso';
  let etiqueta = 'En curso';

  if (est === 'pendiente') {
    clase = 'progress-sininiciar';
    etiqueta = 'Pendiente';
  } else if (est === 'listo') {
    clase = 'progress-completado';
    etiqueta = 'Listo';
  }

  return `<span class="progress-badge ${clase}" data-progreso="${escapeHtml(
    est
  )}">${escapeHtml(etiqueta)}</span>`;
}

function pintarFilasActividades(cuerpoTabla, filas) {
  cuerpoTabla.innerHTML = '';

  if (filas.length === 0) {
    verificarTablaVacia();
    return;
  }

  for (const fila of filas) {
    const tr = document.createElement('tr');
    const progreso = generarBadgeProgreso(fila.estado);

    tr.dataset.idActividad = fila.id;

    if (fila.id_materia) {
      tr.dataset.idMateria = fila.id_materia;
    }

    if (fila.id_tipo_actividad) {
      tr.dataset.idTipo = fila.id_tipo_actividad;
    }

    // Guardar puntos en data-* (no se muestran en la tabla)
    tr.dataset.maximo = fila.maximo ?? '';
    tr.dataset.obtenido = fila.obtenido ?? '';

    const claseTipo = tipoClase(fila.tipo);

    tr.innerHTML = `
      <td>${escapeHtml(fila.fecha)}</td>
      <td>${escapeHtml(fila.nombre)}</td>
      <td>${escapeHtml(fila.materia)}</td>
      <td><span class="tag ${claseTipo}">${escapeHtml(fila.tipo)}</span></td>
      <td>${progreso}</td>
    `;

    cuerpoTabla.appendChild(tr);
  }

  globalThis.feather?.replace();
  verificarTablaVacia();
}

function manejarErrorCargaActividades(error, cuerpoTabla) {
  console.error('Error cargando actividades:', error);

  if (cuerpoTabla) {
    const mensaje = error?.message ?? String(error);
    cuerpoTabla.innerHTML = `<tr><td colspan="5">Error: ${escapeHtml(mensaje)}</td></tr>`;
  }

  verificarTablaVacia();
}

async function cargarActividadesDesdeAPI() {
  const base = obtenerBaseUrl();
  const cuerpoTabla = document.getElementById('tabla-body');

  if (!cuerpoTabla) {
    return;
  }

  cuerpoTabla.innerHTML = '<tr><td colspan="5">Cargando actividades...</td></tr>';

  try {
    const materias = await obtenerMateriasDesdeApi(base);
    const resultados = await obtenerActividadesPorMateria(base, materias);
    const filas = construirFilasActividades(resultados);
    actividadesGlobales = filas;
    pintarFilasActividades(cuerpoTabla, filas);
    actualizarCalendarioSiExiste();
  } catch (error) {
    manejarErrorCargaActividades(error, cuerpoTabla);
  }
}

/* ==========================================================
   ELIMINACIÓN MASIVA DE FILAS
   ========================================================== */

async function confirmarEliminacionMasiva() {
  if (typeof showConfirm === 'function') {
    return showConfirm(
      'Confirmar eliminación',
      '¿Estás seguro de que deseas eliminar todas las actividades listadas? ' +
      'Esta acción eliminará las actividades del servidor y no se podrá deshacer.'
    );
  }

  return new Promise((resolver) => {
    const dialogo = document.createElement('dialog');
    dialogo.className = 'confirm-dialog';
    dialogo.innerHTML = `
      <div class="modal-eliminar-masivo">
        <h3 style="margin:0 0 6px 0">Confirmar eliminación</h3>
        <p style="margin:0 0 12px 0">
          ¿Estás seguro de que deseas eliminar todas las actividades listadas?
          Esta acción eliminará las actividades del servidor y no se podrá deshacer.
        </p>
        <div style="display:flex;gap:8px;justify-content:flex-end;">
          <button id="__temp_confirm_cancel" class="btn-secondary">Cancelar</button>
          <button id="__temp_confirm_ok" class="btn-secondary">Eliminar</button>
        </div>
      </div>
    `;

    document.body.appendChild(dialogo);

    try {
      dialogo.showModal();
    } catch (error_) {
      console.warn('El navegador no soporta <dialog>:', error_);
    }

    const botonAceptar = dialogo.querySelector('#__temp_confirm_ok');
    const botonCancelar = dialogo.querySelector('#__temp_confirm_cancel');

    const finalizar = (resultado) => {
      try {
        dialogo.close();
        dialogo.remove();
      } catch (error_) {
        console.warn('Error cerrando diálogo de confirmación:', error_);
      }
      resolver(resultado);
    };

    botonAceptar?.addEventListener('click', () => finalizar(true));
    botonCancelar?.addEventListener('click', () => finalizar(false));
    dialogo.addEventListener('cancel', () => finalizar(false));

    botonAceptar?.focus();
  });
}

/* helpers para reducir complejidad de borrado masivo */

function obtenerFilasTabla() {
  return Array.from(document.querySelectorAll('#tabla-body tr'));
}

function separarFilasPorId(filas) {
  const filasConId = [];
  const filasSinId = [];

  for (const fila of filas) {
    const id = fila.dataset.idActividad || '';
    const idNum = Number(String(id).trim());

    if (Number.isFinite(idNum) && idNum > 0) {
      filasConId.push({ el: fila, idNum });
    } else {
      filasSinId.push(fila);
    }
  }

  return { filasConId, filasSinId };
}

function eliminarFilasSinId(filasSinId) {
  for (const fila of filasSinId) {
    fila.remove();
  }
}

async function borrarActividadesConId(base, filasConId) {
  const promesas = filasConId.map((item) => {
    const url = `${base}api/actividades?id=${encodeURIComponent(item.idNum)}`;

    return fetch(url, {
      method: 'DELETE',
      credentials: 'same-origin'
    })
      .then(async (respuesta) => {
        const texto = await respuesta.text();
        let cuerpo = texto;
        let json = parsearJsonSeguro(texto);

        if (!respuesta.ok) {
          let mensaje;

          if (json?.message) {
            mensaje = json.message;
          } else if (typeof cuerpo === 'string') {
            mensaje = cuerpo;
          } else {
            mensaje = `HTTP ${respuesta.status}`;
          }

          throw new Error(`HTTP ${respuesta.status}: ${mensaje}`);
        }

        if (!json) {
          json = { message: cuerpo };
        }

        return {
          id: item.idNum,
          ok: true,
          message: json.message ?? cuerpo
        };
      })
      .catch((error) => ({
        id: item.idNum,
        ok: false,
        error
      }));
  });

  return Promise.all(promesas);
}

function construirMensajeErroresBorrado(errores) {
  const partes = [];
  for (const errorItem of errores) {
    const detalle = errorItem.error?.message
      ? errorItem.error.message
      : errorItem.error;
    partes.push(`id=${errorItem.id} -> ${detalle}`);
  }
  return partes.join('\n');
}

function recargarDespuesDeBorrado() {
  try {
    if (typeof cargarActividadesDesdeAPI === 'function') {
      cargarActividadesDesdeAPI();
    } else {
      globalThis.location.reload();
    }
  } catch (error_) {
    console.error('Error recargando actividades después de borrado masivo:', error_);
    globalThis.location.reload();
  }
}

async function botonEliminarMasivo() {
  const filas = obtenerFilasTabla();

  if (!filas.length) {
    return;
  }

  const confirmar = await confirmarEliminacionMasiva();

  if (!confirmar) {
    return;
  }

  const base = obtenerBaseUrl();
  const { filasConId, filasSinId } = separarFilasPorId(filas);

  eliminarFilasSinId(filasSinId);

  if (filasConId.length === 0) {
    verificarTablaVacia();
    return;
  }

  const resultados = await borrarActividadesConId(base, filasConId);
  const errores = resultados.filter((r) => !r.ok);

  if (errores.length) {
    const detalles = construirMensajeErroresBorrado(errores);
    mostrarToastSeguro(
      `Ocurrieron errores al eliminar algunas actividades:\n${detalles}`,
      { type: 'error' }
    );
  }

  recargarDespuesDeBorrado();
}

/* ==========================================================
   BUSCADOR
   ========================================================== */

function normalizarTextoBusqueda(texto) {
  if (!texto) {
    return '';
  }

  try {
    return String(texto)
      .normalize('NFD')
      .replaceAll(/\p{Diacritic}/gu, '')
      .replaceAll(/\s+/g, ' ')
      .toLowerCase()
      .trim();
  } catch (error) {
    console.warn('Unicode normalization not supported, using fallback:', error);
    return String(texto)
      .replaceAll(/[ÁÀÂÄáàâä]/g, 'a')
      .replaceAll(/[ÉÈÊËéèêë]/g, 'e')
      .replaceAll(/[ÍÌÎÏíìîï]/g, 'i')
      .replaceAll(/[ÓÒÔÖóòôö]/g, 'o')
      .replaceAll(/[ÚÙÛÜúùûü]/g, 'u')
      .replaceAll(/\s+/g, ' ')
      .toLowerCase()
      .trim();
  }
}

function aplicarFiltroBusqueda(textoFiltro) {
  const filtro = normalizarTextoBusqueda(textoFiltro);
  const filas = document.querySelectorAll('#tabla-body tr');
  const tabla = document.getElementById('tabla');
  const tablaVacia = document.getElementById('tabla-vacia');

  if (filtro === '') {
    for (const fila of filas) {
      fila.style.display = '';
    }
    verificarTablaVacia();
    return;
  }

  let hayCoincidencias = false;

  for (const fila of filas) {
    const textoFila = normalizarTextoBusqueda(fila.innerText || '');
    const coincide = textoFila.includes(filtro);

    fila.style.display = coincide ? '' : 'none';

    if (coincide) {
      hayCoincidencias = true;
    }
  }

  if (hayCoincidencias) {
    tabla?.classList.remove('oculto');
    tablaVacia?.classList.add('oculto');
  } else {
    tabla?.classList.add('oculto');
    tablaVacia?.classList.remove('oculto');
  }
}

function inicializarBuscador() {
  const searchInput =
    document.getElementById('d-search-input') ||
    document.getElementById('search-input') ||
    document.querySelector('.search-input');

  const searchToggle = document.getElementById('search-toggle');
  const searchWrapper = document.getElementById('dashboard-search-input-wrapper');

  if (!searchInput && !searchToggle) {
    return;
  }

  if (searchToggle) {
    searchToggle.addEventListener('click', () => {
      if (searchWrapper?.classList.contains('oculto')) {
        searchWrapper.classList.remove('oculto');

        setTimeout(() => {
          try {
            searchInput?.focus();
          } catch (error) {
            console.warn('No se pudo enfocar el buscador:', error);
          }
        }, 10);
      } else {
        if (searchInput) {
          searchInput.value = '';
        }
        searchWrapper?.classList.add('oculto');
        verificarTablaVacia();
      }
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', () => {
      aplicarFiltroBusqueda(searchInput.value || '');
    });
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', inicializarBuscador);
} else {
  inicializarBuscador();
}

/* ==========================================================
   COLUMNA DE ACCIONES EDITAR / ELIMINAR
   ========================================================== */

function actualizarColumnaAcciones() {
  modoEdicionActivo = !modoEdicionActivo;

  const theadRow = document.querySelector('#tabla thead tr');
  const filas = document.querySelectorAll('#tabla-body tr');

  if (modoEdicionActivo) {
    activarColumnaAcciones(theadRow, filas);
  } else {
    desactivarColumnaAcciones(theadRow, filas);
  }
}

/* ============================
   Helpers columna de acciones
   ============================ */

function asegurarHeaderAcciones(theadRow) {
  if (!theadRow) {
    return;
  }

  if (!document.getElementById('th-acciones')) {
    const th = document.createElement('th');
    th.id = 'th-acciones';
    theadRow.appendChild(th);
  }
}

function quitarHeaderAcciones() {
  const th = document.getElementById('th-acciones');
  if (th) {
    th.remove();
  }
}

function crearCeldaAcciones(index) {
  const td = document.createElement('td');
  td.classList.add('col-acciones');
  td.innerHTML = `
    <div class="filter-btns">
      <button class="btn-edit-fila" data-index="${index}">
        <i data-feather="edit-3"></i>
      </button>
      <button class="btn-delete-fila" data-index="${index}">
        <i data-feather="trash-2"></i>
      </button>
    </div>
  `;
  return td;
}

function agregarAccionesAFilas(filas) {
  let index = 0;

  for (const tr of filas) {
    if (!tr.querySelector('.col-acciones')) {
      const td = crearCeldaAcciones(index);
      tr.appendChild(td);
    }
    index += 1;
  }

  globalThis.feather?.replace();
}

function quitarAccionesDeFilas(filas) {
  for (const tr of filas) {
    const td = tr.querySelector('.col-acciones');
    if (td) {
      td.remove();
    }
  }
}

function activarColumnaAcciones(theadRow, filas) {
  asegurarHeaderAcciones(theadRow);
  agregarAccionesAFilas(filas);
}

function desactivarColumnaAcciones(theadRow, filas) {
  quitarHeaderAcciones();
  quitarAccionesDeFilas(filas);
}

document.addEventListener('click', (evento) => {
  const botonEditar = evento.target.closest('.btn-edit-fila');
  if (botonEditar) {
    const indice = Number(botonEditar.dataset.index);
    editarFila(indice);
    return;
  }

  const botonEliminar = evento.target.closest('.btn-delete-fila');
  if (botonEliminar) {
    const indice = Number(botonEliminar.dataset.index);
    eliminarFila(indice);
  }
});

/* ==========================================================
   EDICIÓN INDIVIDUAL DE FILAS
   ========================================================== */

/**
 * Acción al pulsar el botón de editar para una fila.
 *
 * @param {number} index Índice de la fila en el tbody.
 * @returns {void}
 */
async function editarFila(index) {
  const filas = document.querySelectorAll('#tabla-body tr');
  const tr = filas[index];

  if (!tr) {
    return;
  }

  const celdas = tr.querySelectorAll('td');

  if (typeof abrirModalNueva === 'function') {
    await abrirModalNueva();
  }

  actualizarUiModalActividad('editar');
  completarFormularioDesdeFila(tr, celdas, index);
}

/* =========================================
   Helpers para edición de una fila
   ========================================= */

/**
 * Obtiene los IDs almacenados como data-* en la fila.
 *
 * @param {HTMLTableRowElement} tr
 * @returns {{idActividad: string, idMateria: string, idTipo: string}}
 */
function obtenerIdsDesdeFila(tr) {
  return {
    idActividad: tr.dataset.idActividad || '',
    idMateria: tr.dataset.idMateria || '',
    idTipo: tr.dataset.idTipo || ''
  };
}

/**
 * Obtiene los puntos máximo y obtenidos desde los data-* de la fila.
 *
 * @param {HTMLTableRowElement} tr
 * @returns {{maximo: string, obtenido: string}}
 */
function obtenerPuntosDesdeFila(tr) {
  return {
    maximo: tr.dataset.maximo || '',
    obtenido: tr.dataset.obtenido || ''
  };
}

/**
 * Devuelve referencias a los campos del formulario de actividad.
 *
 * @returns {{
 *  formulario: HTMLFormElement|null,
 *  campoIdOculto: HTMLInputElement|null,
 *  campoFecha: HTMLInputElement|null,
 *  campoNombre: HTMLInputElement|null,
 *  selectMateria: HTMLSelectElement|null,
 *  selectTipo: HTMLSelectElement|null,
 *  campoPuntosMax: HTMLInputElement|null,
 *  campoPuntos: HTMLInputElement|null
 * }}
 */
function obtenerCamposFormularioActividad() {
  return {
    formulario: document.getElementById('form-actividad'),
    campoIdOculto: document.getElementById('id_actividad'),
    campoFecha: document.querySelector('#form-actividad [name="fecha"]'),
    campoNombre: document.querySelector('#form-actividad [name="actividad"]'),
    selectMateria: document.querySelector('#form-actividad select[name="materia"]'),
    selectTipo: document.querySelector('#form-actividad select[name="tipo"]'),
    campoPuntosMax: document.querySelector('#form-actividad [name="puntaje-max"]'),
    campoPuntos: document.querySelector('#form-actividad [name="puntaje"]')
  };
}

/**
 * Rellena los campos de texto básicos (fecha y nombre) desde las celdas.
 *
 * @param {NodeListOf<HTMLTableCellElement>} celdas
 * @param {HTMLInputElement|null} campoFecha
 * @param {HTMLInputElement|null} campoNombre
 * @returns {void}
 */
function rellenarCamposTextoBasicos(celdas, campoFecha, campoNombre) {
  if (campoFecha && celdas[0]) {
    campoFecha.value = celdas[0].textContent.trim();
  }

  if (campoNombre && celdas[1]) {
    campoNombre.value = celdas[1].textContent.trim();
  }
}

/**
 * Selecciona una opción en un <select>:
 * - Primero intenta por el valor (id)
 * - Si no encuentra, intenta por el texto visible de la celda
 *
 * @param {HTMLSelectElement|null} selectEl
 * @param {string} idValor
 * @param {HTMLTableCellElement} [textoCelda]
 * @returns {void}
 */
function seleccionarOpcionEnSelect(selectEl, idValor, textoCelda) {
  if (!selectEl) {
    return;
  }

  const texto = textoCelda ? textoCelda.textContent.trim() : '';

  if (idValor) {
    const idStr = String(idValor);
    for (const opcion of selectEl.options) {
      if (opcion.value === idStr) {
        selectEl.value = idStr;
        return;
      }
    }
  }

  if (!texto) {
    return;
  }

  for (const opcion of selectEl.options) {
    if (opcion.textContent.trim() === texto) {
      selectEl.value = opcion.value;
      return;
    }
  }
}

/**
 * 
 * @param {*} selectEl 
 * @param {*} minOptions 
 * @param {*} timeoutMs 
 */
async function esperarOpcionesSelect(selectEl, minOptions = 2, timeoutMs = 1000) {
  if (!selectEl) {
    return;
  }

  const inicio = Date.now();
  while (
    selectEl.options.length < minOptions &&
    Date.now() - inicio < timeoutMs
  ) {
    await new Promise((resolver) => setTimeout(resolver, 50));
  }
}


/**
 * 
 * @param {*} tr 
 * @param {*} celdas 
 * @param {*} index 
 */
async function completarFormularioDesdeFila(tr, celdas, index) {
  const {
    formulario,
    campoIdOculto,
    campoFecha,
    campoNombre,
    selectMateria,
    selectTipo,
    campoPuntosMax,
    campoPuntos
  } = obtenerCamposFormularioActividad();

  const { idActividad, idMateria, idTipo } = obtenerIdsDesdeFila(tr);
  const { maximo, obtenido } = obtenerPuntosDesdeFila(tr);

  // 1) Campos básicos: fecha y nombre
  rellenarCamposTextoBasicos(celdas, campoFecha, campoNombre);

  // 2) Puntos máximo y obtenido
  if (campoPuntosMax) {
    campoPuntosMax.value = maximo || '';
  }

  if (campoPuntos) {
    campoPuntos.value = obtenido || '';
  }

  // 3) Seleccionar materia directamente (sin disparar change)
  seleccionarOpcionEnSelect(selectMateria, idMateria, celdas[2]);

  // 4) Seleccionar tipo directamente usando el id o el texto
  seleccionarOpcionEnSelect(selectTipo, idTipo, celdas[3]);

  if (selectTipo) {
    selectTipo.disabled = false;
  }

  // 5) ID oculto e índice de edición
  if (campoIdOculto && idActividad) {
    campoIdOculto.value = String(idActividad);
  }

  if (formulario) {
    formulario.dataset.editIndex = String(index);
  }
}


/**
 * Intenta desactivar el modo edición si está activo,
 * para mantener coherente la columna de acciones.
 *
 * @returns {void}
 */
function desactivarModoEdicion() {
  try {
    if (modoEdicionActivo !== undefined && modoEdicionActivo) {
      if (typeof actualizarColumnaAcciones === 'function') {
        actualizarColumnaAcciones();
      } else {
        modoEdicionActivo = false;
      }
    }
  } catch (error) {
    console.error('desactivarModoEdicion error', error);
    modoEdicionActivo = false;
  }
}
globalThis.desactivarModoEdicion = desactivarModoEdicion;

/**
 * Elimina una fila (actividad) individual, tanto del servidor como de la UI.
 *
 * @param {number} index Índice de la fila en el tbody.
 * @returns {void}
 */
function eliminarFila(index) {
  (async () => {
    const filas = document.querySelectorAll('#tabla-body tr');
    const tr = filas[index];

    if (!tr) {
      return;
    }

    const idRaw = tr.dataset.idActividad || '';
    const idNum = Number(String(idRaw).trim());

    if (!Number.isFinite(idNum) || idNum <= 0) {
      tr.remove();
      verificarTablaVacia();
      return;
    }

    const base = obtenerBaseUrl();

    try {
      const respuesta = await fetch(
        `${base}api/actividades?id=${encodeURIComponent(idNum)}`,
        {
          method: 'DELETE',
          credentials: 'same-origin'
        }
      );

      const texto = await respuesta.text();
      const json = parsearJsonSeguro(texto);

      if (!respuesta.ok) {
        let mensaje;

        if (json?.message) {
          mensaje = json.message;
        } else if (typeof texto === 'string') {
          mensaje = texto;
        } else {
          mensaje = `HTTP ${respuesta.status}`;
        }

        mostrarToastSeguro(`No se pudo eliminar: ${mensaje}`, { type: 'error' });
        return;
      }

      mostrarToastSeguro(json?.message || 'Actividad eliminada', { type: 'success' });

      try {
        if (typeof cargarActividadesDesdeAPI === 'function') {
          cargarActividadesDesdeAPI();
          // Desactivar modo edición después de recargar
          desactivarModoEdicion();
        } else {
          tr.remove();
          verificarTablaVacia();
          desactivarModoEdicion();
        }
      } catch (error_) {
        console.error('Error recargando actividades tras eliminar:', error_);
        tr.remove();
        verificarTablaVacia();
        desactivarModoEdicion();
      }
    } catch (error) {
      console.error('Error eliminando actividad:', error);
      mostrarToastSeguro(
        `Error eliminando actividad: ${error?.message ? error.message : String(error)}`,
        { type: 'error' }
      );
    }
  })();
}

/* ==========================================================
   CAMBIO DE PROGRESO (BADGE CLICABLE)
   ========================================================== */

document.addEventListener('click', (evento) => {
  const badge = evento.target.classList.contains('progress-badge')
    ? evento.target
    : evento.target.closest('.progress-badge');

  if (badge) {
    cambiarProgreso(badge);
  }
});

const ESTADOS_PROGRESO = ['pendiente', 'en curso', 'listo'];

function obtenerSiguienteEstado(actual) {
  const idx = ESTADOS_PROGRESO.indexOf(actual);
  const indiceValido = idx === -1 ? 0 : idx;
  return ESTADOS_PROGRESO[(indiceValido + 1) % ESTADOS_PROGRESO.length];
}

function aplicarEstadoEnUi(elemento, estado) {
  elemento.dataset.progreso = estado;
  elemento.textContent = capitalizar(estado);
  elemento.classList.remove('progress-sininiciar', 'progress-encurso', 'progress-completado');

  if (estado === 'pendiente') {
    elemento.classList.add('progress-sininiciar');
  } else if (estado === 'en curso') {
    elemento.classList.add('progress-encurso');
  } else {
    elemento.classList.add('progress-completado');
  }
}

function obtenerContextoActividadDesdeBadge(elemento) {
  const fila = elemento.closest('tr');

  if (!fila) {
    throw new Error('Fila de actividad no encontrada');
  }

  const idActividad = fila.dataset.idActividad || '';
  const idMateria = fila.dataset.idMateria || '';
  const idTipo = fila.dataset.idTipo || '';

  const celdas = fila.querySelectorAll('td');
  const fecha = celdas[0] ? celdas[0].textContent.trim() : '';
  const nombre = celdas[1] ? celdas[1].textContent.trim() : '';

  return {
    fila,
    idActividad,
    idMateria,
    idTipo,
    fecha,
    nombre
  };
}

function validarContextoActividad(ctx) {
  return Boolean(ctx.idActividad && ctx.idMateria && ctx.idTipo && ctx.nombre);
}

function construirPayloadProgreso(ctx, estado) {
  return {
    id_actividad: Number(ctx.idActividad),
    id_materia: Number(ctx.idMateria),
    id_tipo_actividad: Number(ctx.idTipo),
    nombre_actividad: ctx.nombre,
    fecha_entrega: ctx.fecha || undefined,
    estado
  };
}

async function persistirProgresoEnServidor(base, payload) {
  const respuesta = await fetch(`${base}api/actividades`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });

  const texto = await respuesta.text();
  const json = parsearJsonSeguro(texto);

  if (!respuesta.ok) {
    const mensaje = json?.message || texto || `HTTP ${respuesta.status}`;
    throw new Error(mensaje);
  }
}

function cambiarProgreso(elemento) {
  const base = obtenerBaseUrl();

  const actual = normalizeEstado(elemento.dataset.progreso || elemento.textContent || '');
  const siguiente = obtenerSiguienteEstado(actual);
  const anterior = actual;

  aplicarEstadoEnUi(elemento, siguiente);

  (async () => {
    try {
      const contexto = obtenerContextoActividadDesdeBadge(elemento);

      if (!validarContextoActividad(contexto)) {
        mostrarToastSeguro(
          'No se pueden persistir los cambios: faltan datos de la actividad',
          { type: 'error' }
        );
        aplicarEstadoEnUi(elemento, anterior);
        return;
      }

      const payload = construirPayloadProgreso(contexto, siguiente);
      await persistirProgresoEnServidor(base, payload);
    } catch (error) {
      console.error('Error persistiendo progreso:', error);
      mostrarToastSeguro(
        `No se pudo guardar el progreso: ${error?.message ? error.message : String(error)
        }`,
        { type: 'error' }
      );
      aplicarEstadoEnUi(elemento, anterior);
    }
  })();
}

/**
 * Capitalizar la primera letra de un texto.
 *
 * @param {string} texto Texto original.
 * @returns {string} Texto con primera letra mayúscula.
 */
function capitalizar(texto) {
  if (!texto) {
    return '';
  }
  return texto.charAt(0).toUpperCase() + texto.slice(1);
}

/* ==========================================================
   VISTA CALENDARIO
   ========================================================== */

function actualizarCalendarioSiExiste() {
  if (calendarInstance) {
    calendarInstance.removeAllEvents();
    const eventos = mapearActividadesAEventos(actividadesGlobales);
    calendarInstance.addEventSource(eventos);
  }
}

function mapearActividadesAEventos(actividades) {
  return actividades.map(act => {
    // Asumimos que act.fecha es YYYY-MM-DD. Si tiene hora, FullCalendar lo maneja.
    // Si no tiene fecha, no se puede mostrar.
    if (!act.fecha) return null;

    let color = '#3788d8'; // default blue
    const tipo = (act.tipo || '').toLowerCase();

    // Usar los mismos colores que los tags si es posible, o aproximados
    if (tipo.includes('examen')) color = '#1976d2'; // Azul fuerte
    else if (tipo.includes('proyecto')) color = '#2e7d32'; // Verde fuerte
    else if (tipo.includes('tarea') || tipo.includes('trabajo')) color = '#ef6c00'; // Naranja fuerte
    else if (tipo.includes('quiz')) color = '#7b1fa2'; // Púrpura fuerte
    else if (tipo.includes('ejerc')) color = '#c2185b'; // Rosa fuerte

    return {
      title: act.nombre,
      start: act.fecha,
      allDay: true, // Asumimos todo el día por ahora
      backgroundColor: color,
      borderColor: color,
      extendedProps: {
        materia: act.materia,
        tipo: act.tipo
      }
    };
  }).filter(e => e !== null);
}

function inicializarCalendario() {
  const calendarEl = document.getElementById('calendar-view');
  if (!calendarEl) return;

  // Verificar si FullCalendar está cargado
  if (typeof FullCalendar === 'undefined') {
    console.error('FullCalendar no está cargado');
    return;
  }

  calendarInstance = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    locale: 'es',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,listMonth'
    },
    buttonText: {
      today: 'Hoy',
      month: 'Mes',
      list: 'Lista'
    },
    events: mapearActividadesAEventos(actividadesGlobales),
    eventClick: function (info) {
      // Opcional: Mostrar detalles al hacer click
      const props = info.event.extendedProps;
      mostrarToastSeguro(`${info.event.title} (${props.materia})`, { type: 'info' });
    }
  });

  calendarInstance.render();
}

function toggleVista() {
  const tabla = document.getElementById('tabla');
  const calendarEl = document.getElementById('calendar-view');
  const btn = document.getElementById('btn-toggle-view');
  const icon = btn.querySelector('i');
  const text = btn.querySelector('span');
  const searchBox = document.getElementById('search-box');
  const btnFiltro = document.getElementById('contenedor-boton-filtro');

  if (vistaActual === 'tabla') {
    // Cambiar a calendario
    vistaActual = 'calendario';
    tabla.classList.add('oculto'); // Usar clase oculto existente o style
    tabla.style.display = 'none'; // Asegurar ocultamiento
    calendarEl.style.display = 'block';

    // Actualizar botón
    if (icon) icon.setAttribute('data-feather', 'list');
    if (text) text.textContent = 'Tabla';

    // Inicializar si es la primera vez
    if (!calendarInstance) {
      inicializarCalendario();
    } else {
      calendarInstance.updateSize(); // Reajustar tamaño por si acaso
    }

  } else {
    // Cambiar a tabla
    vistaActual = 'tabla';
    tabla.style.display = ''; // Restaurar display original (table)
    tabla.classList.remove('oculto');
    calendarEl.style.display = 'none';

    // Actualizar botón
    if (icon) icon.setAttribute('data-feather', 'calendar');
    if (text) text.textContent = 'Calendario';
  }

  if (globalThis.feather) globalThis.feather.replace();
}

// Inicializar listener del botón
document.addEventListener('DOMContentLoaded', () => {
  const btnToggle = document.getElementById('btn-toggle-view');
  if (btnToggle) {
    btnToggle.addEventListener('click', toggleVista);
  }
});
