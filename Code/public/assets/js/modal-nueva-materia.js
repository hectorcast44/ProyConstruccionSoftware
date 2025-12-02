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
  if (typeof globalThis.showToast === 'function') {
    globalThis.showToast(mensaje, opciones);
  } else {
    const tipo = opciones.type === 'error' ? 'Error' : 'Info';
    const metodo = opciones.type === 'error' ? 'error' : 'log';
    console[metodo](`${tipo}: ${mensaje}`);
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
    console.warn('No se pudo parsear JSON:', error);
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
 * Construir el payload para crear o actualizar una materia.
 *
 * @param {HTMLFormElement} formulario Formulario origen de los datos.
 * @returns {{
 *   id_materia: number|undefined,
 *   nombre_materia: string,
 *   calif_minima: number,
 *   tipos: number[]
 * }} Objeto listo para enviar al API.
 */
function construirPayloadMateria(formulario) {
  const datosFormulario = new FormData(formulario);
  const idMateria = formulario.dataset.editId ? Number(formulario.dataset.editId) : undefined;

  const valorNombre = datosFormulario.get('nombre_materia');
  let nombreMateria = '';
  if (typeof valorNombre === 'string') {
    nombreMateria = valorNombre.trim();
  }

  const valorCalifMinima = datosFormulario.get('calif_minima');
  const califMinima = valorCalifMinima ? Number(valorCalifMinima) : 70;

  const tiposSeleccionados = Array.from(
    document.querySelectorAll('#tipos-checkboxes input[type="checkbox"]')
  )
    .filter((checkbox) => checkbox.checked)
    .map((checkbox) => Number(checkbox.value))
    .filter(Number.isFinite);

  return {
    id_materia: idMateria,
    nombre_materia: nombreMateria,
    calif_minima: Number.isFinite(califMinima) ? califMinima : 70,
    tipos: tiposSeleccionados
  };
}

/**
 * Validar que el nombre de la materia no esté vacío.
 *
 * @param {string} nombreMateria Nombre de la materia.
 * @returns {boolean} Verdadero si el nombre es válido.
 */
function validarNombreMateria(nombreMateria) {
  return Boolean(String(nombreMateria || '').trim());
}

/**
 * Enviar al servidor la creación o actualización de una materia.
 *
 * @param {object} payload Datos de la materia.
 * @returns {Promise<any>} Respuesta JSON del servidor.
 * @throws {Error} Cuando la respuesta no es correcta o el JSON es inválido.
 */
async function enviarMateria(payload) {
  const respuesta = await fetch(obtenerBaseUrl() + 'api/materias', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });

  const texto = await respuesta.text();
  const json = parsearJsonSeguro(texto);

  if (!json) {
    throw new Error('Respuesta inválida del servidor');
  }

  if (!respuesta.ok) {
    const mensajeError = json.message || `HTTP ${respuesta.status}`;
    throw new Error(mensajeError);
  }

  return json;
}

/**
 * Refrescar la lista de materias en pantalla.
 *
 * @returns {void}
 */
function refrescarMaterias() {
  if (typeof globalThis.cargarMateriasDesdeAPI === 'function') {
    globalThis.cargarMateriasDesdeAPI();
  } else {
    globalThis.location.reload();
  }
}

/**
 * Gestionar el flujo posterior a la creación o edición de una materia.
 *
 * @param {any} respuesta Respuesta JSON del servidor.
 * @param {object} payload Datos enviados al servidor.
 * @param {HTMLFormElement} formulario Formulario de la materia.
 * @param {HTMLDialogElement} modal Modal de creación/edición de materia.
 * @returns {void}
 */
function manejarExitoMateria(respuesta, payload, formulario, modal) {
  formulario.reset();
  delete formulario.dataset.editId;
  modal.close();

  const nuevoId = respuesta?.id_materia ?? respuesta?.id ?? null;
  const esCreacion = !payload.id_materia && nuevoId;

  if (esCreacion && nuevoId && typeof globalThis.abrirModalPonderacion === 'function') {
    try {
      globalThis.abrirModalPonderacion(nuevoId);
    } catch (error) {
      console.warn('No se pudo abrir modal de ponderación:', error);
      refrescarMaterias();
    }
  } else {
    refrescarMaterias();
  }

  const mensaje = respuesta.message || 'Materia creada';
  mostrarToastSeguro(mensaje, { type: 'success' });
}

/**
 * Gestionar el error al intentar crear o actualizar una materia.
 *
 * @param {Error} error Error capturado en el flujo de creación.
 * @returns {void}
 */
function manejarErrorMateria(error) {
  console.error('Error creando materia:', error);
  const mensaje = error?.message || String(error);
  mostrarToastSeguro(`Error al crear materia: ${mensaje}`, { type: 'error' });
}

/**
 * Abrir el modal de creación o edición de materia.
 *
 * Si el modal no existe, lo carga dinámicamente y luego lo muestra.
 *
 * @param {object|null} [datosMateria] Datos para prellenar el formulario.
 * @returns {void}
 */
function abrirModalCrearMateria(datosMateria = null) {
  const modal = document.getElementById('modal-nueva-materia');

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

  if (datosMateria) {
    prefilarModalMateria(datosMateria);
  }

  modal.showModal();
}

/**
 * Inicializar el comportamiento del modal de nueva materia.
 *
 * Configura:
 *  - Botón de cierre.
 *  - Envío del formulario (crear/editar).
 *
 * @returns {void}
 */
function inicializarModalNuevaMateria() {
  const modal = document.getElementById('modal-nueva-materia');
  const botonCerrar = document.getElementById('cerrar-modal-materia');
  const formulario = document.getElementById('form-materia');

  if (!modal || !formulario) {
    return;
  }

  if (botonCerrar) {
    botonCerrar.addEventListener('click', () => modal.close());
  }

  formulario.addEventListener('submit', async (evento) => {
    evento.preventDefault();

    const payload = construirPayloadMateria(formulario);

    if (!validarNombreMateria(payload.nombre_materia)) {
      mostrarToastSeguro('Ingrese el nombre de la materia', { type: 'error' });
      return;
    }

    try {
      const respuesta = await enviarMateria(payload);
      manejarExitoMateria(respuesta, payload, formulario, modal);
    } catch (error) {
      manejarErrorMateria(error);
    }
  });
}

/**
 * Prefilar el modal de materia con datos existentes para edición.
 *
 * @param {object} datos Datos de la materia.
 * @returns {void}
 */
function prefilarModalMateria(datos) {
  const formulario = document.getElementById('form-materia');

  if (!formulario) {
    return;
  }

  const idMateria = datos.id ?? datos.id_materia ?? datos.idMateria ?? null;
  const nombre = datos.nombre ?? datos.nombre_materia ?? datos.nombreMateria ?? '';
  const calificacionMinima = datos.calif_minima
    ?? datos.calif_min
    ?? datos.calificacion_minima
    ?? '';

  if (idMateria) {
    formulario.dataset.editId = String(idMateria);
  }

  const campoNombre = formulario.querySelector('[name="nombre_materia"]');
  const campoCalifMinima = formulario.querySelector('[name="calif_minima"]');

  if (campoNombre) {
    campoNombre.value = nombre;
  }

  if (campoCalifMinima) {
    campoCalifMinima.value = calificacionMinima;
  }

  try {
    const contenedorTipos = document.getElementById('tipos-checkboxes');

    if (contenedorTipos && Array.isArray(datos.tipos)) {
      const idsTipos = new Set(
        datos.tipos
          .map((tipo) => Number(
            tipo.id_tipo_actividad
            ?? tipo.id_tipo
            ?? tipo.id
            ?? tipo.idTipo
            ?? 0
          ))
          .filter((id) => id > 0)
      );

      const checkboxes = contenedorTipos.querySelectorAll('input[type="checkbox"]');
      for (const checkbox of checkboxes) {
        const valor = Number(checkbox.value);
        checkbox.checked = idsTipos.has(valor);
      }
    }
  } catch (error) {
    console.warn('Error prellenando tipos de materia:', error);
  }
}

/**
 * Renderizar la lista de tipos de actividad en el contenedor.
 *
 * @param {HTMLElement} contenedor Nodo contenedor de los checkboxes.
 * @param {Array<any>} tipos Lista de tipos de actividad.
 * @returns {void}
 */
function renderizarListaTipos(contenedor, tipos) {
  contenedor.innerHTML = tipos
    .map((tipo) => {
      const id = tipo.id_tipo_actividad ?? tipo.id ?? tipo.idTipo ?? '';
      const nombre = tipo.nombre_tipo ?? tipo.nombre ?? tipo.nombreTipo ?? '';
      const idSeguro = String(id);

      return `
        <label class="tipo-item" data-id="${idSeguro}">
          <input type="checkbox" value="${idSeguro}">
          <span class="tipo-nombre">${escapeHtml(nombre)}</span>
          <button
            type="button"
            class="btn-eliminar-tipo"
            title="Eliminar tipo"
            style="margin-left:auto;background:transparent;border:0;color:#c0392b;cursor:pointer;"
          >&times;</button>
        </label>
      `;
    })
    .join('');
}

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

      const texto = await respuesta.text();
      const json = parsearJsonSeguro(texto);

      if (!respuesta.ok) {
        const mensajeError = json?.message || `HTTP ${respuesta.status}`;
        throw new Error(mensajeError);
      }

      const nuevoId = json?.id ?? null;
      const etiqueta = document.createElement('label');

      etiqueta.className = 'tipo-item';
      etiqueta.dataset.id = String(nuevoId ?? '');
      etiqueta.style.display = 'flex';
      etiqueta.style.alignItems = 'center';
      etiqueta.style.gap = '8px';

      const checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.value = String(nuevoId ?? '');
      checkbox.checked = true;

      etiqueta.appendChild(checkbox);
      etiqueta.appendChild(document.createTextNode(` ${nombre}`));

      contenedor.prepend(etiqueta);
      inputNuevo.value = '';

      const mensaje = json?.message || 'Tipo creado';
      mostrarToastSeguro(mensaje, { type: 'success' });
    } catch (error) {
      console.error('Error creando tipo inline:', error);
      const mensaje = error?.message || String(error);
      mostrarToastSeguro(`Error creando tipo: ${mensaje}`, { type: 'error' });
    }
  });
}

/**
 * Mostrar una confirmación de eliminación usando showConfirm o un <dialog>.
 *
 * @param {string} titulo Título del cuadro de confirmación.
 * @param {string} mensaje Mensaje a mostrar al usuario.
 * @returns {Promise<boolean>} Verdadero si el usuario confirma.
 */
async function solicitarConfirmacion(titulo, mensaje) {
  if (typeof globalThis.showConfirm === 'function') {
    return globalThis.showConfirm(titulo, mensaje);
  }

  return new Promise((resolver) => {
    const dialogo = document.createElement('dialog');
    dialogo.className = 'confirm-dialog';
    dialogo.innerHTML = `
      <div style="padding:16px;border-radius:8px;max-width:480px;width:92%;box-shadow:0 10px 30px rgba(0,0,0,0.12);">
        <h3 style="margin:0 0 6px 0">${escapeHtml(titulo)}</h3>
        <p style="margin:0 0 12px 0">${escapeHtml(mensaje)}</p>
        <div style="display:flex;gap:8px;justify-content:flex-end;">
          <button id="__temp_cancel_tipo" style="background:#eee;border:0;padding:8px 12px;border-radius:6px;">Cancelar</button>
          <button id="__temp_ok_tipo" style="background:#d9534f;color:#fff;border:0;padding:8px 12px;border-radius:6px;">Eliminar</button>
        </div>
      </div>
    `;

    document.body.appendChild(dialogo);

    try {
      dialogo.showModal();
    } catch (error) {
      console.warn('El navegador no soporta <dialog>.', error);
    }

    const botonAceptar = dialogo.querySelector('#__temp_ok_tipo');
    const botonCancelar = dialogo.querySelector('#__temp_cancel_tipo');

    const finalizar = (resultado) => {
      try {
        dialogo.close();
        dialogo.remove();
      } catch (error) {
        console.warn('Error cerrando diálogo de confirmación:', error);
      }
      resolver(resultado);
    };

    if (botonAceptar) {
      botonAceptar.addEventListener('click', () => finalizar(true));
    }

    if (botonCancelar) {
      botonCancelar.addEventListener('click', () => finalizar(false));
    }

    dialogo.addEventListener('cancel', () => finalizar(false));
  });
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

  return json?.data?.referencias ?? null;
}

/**
 * Construir el mensaje de confirmación de eliminación según referencias.
 *
 * @param {{actividades?:number, ponderaciones?:number}|null} referencias Referencias del tipo.
 * @returns {string} Mensaje para mostrar al usuario.
 */
function construirMensajeEliminacion(referencias) {
  if (!referencias) {
    return '¿Eliminar este tipo de actividad?';
  }

  const actividades = referencias.actividades || 0;
  const ponderaciones = referencias.ponderaciones || 0;

  if (actividades > 0) {
    return `Este tipo tiene ${actividades} actividad(es) y ${ponderaciones} ponderación(es). Al confirmar, las actividades serán ELIMINADAS.`;
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
      const jsonDelete = await eliminarTipoEnServidor(idTipo, false);
      etiqueta.remove();
      mostrarToastSeguro(jsonDelete?.message || 'Tipo eliminado', { type: 'success' });
      refrescarTiposGlobales();
      return;
    } catch (error) {
      const mensajePrimario = error?.message || String(error);

      if (!mensajePrimario.toLowerCase().includes('referenc')) {
        mostrarToastSeguro(`No se pudo eliminar: ${mensajePrimario}`, { type: 'error' });
        return;
      }

      const confirmarForzar = await solicitarConfirmacion(
        'Forzar eliminación',
        'Este tipo tiene actividades asociadas. ¿Deseas eliminar también esas actividades y continuar?'
      );

      if (!confirmarForzar) {
        return;
      }

      const jsonForzada = await eliminarTipoEnServidor(idTipo, true);
      etiqueta.remove();
      mostrarToastSeguro(jsonForzada?.message || 'Tipo y actividades eliminadas', { type: 'success' });
      refrescarTiposGlobales();
    }
  } catch (error) {
    console.error('Error al eliminar tipo:', error);
    const mensaje = error?.message || String(error);
    mostrarToastSeguro(`Error al eliminar tipo: ${mensaje}`, { type: 'error' });
  }
}

/**
 * Configurar el comportamiento de eliminación de tipos de actividad en el contenedor.
 *
 * @param {HTMLElement} contenedor Nodo contenedor de tipos.
 * @returns {void}
 */
function configurarEliminacionTipo(contenedor) {
  contenedor.addEventListener('click', (evento) => {
    const boton = evento.target.closest('.btn-eliminar-tipo');
    if (!boton) {
      return;
    }

    const etiqueta = boton.closest('.tipo-item');
    if (!etiqueta) {
      return;
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

  if (!contenedor) {
    return;
  }

  await cargarTiposDesdeApi(contenedor);
  configurarCreacionTipoInline(contenedor, botonCrear, inputNuevo);
  configurarEliminacionTipo(contenedor);
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
