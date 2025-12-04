/**
 * Carga un fragmento HTML (partial) y lo inserta en un contenedor.
 * Opcionalmente, asigna un evento click a un botón dentro del partial.
 *
 * @param {string} ruta - La URL del partial a cargar.
 * @param {string} contenedorId - El ID del elemento contenedor donde se insertará el HTML.
 * @param {string} botonId - (Opcional) El ID del botón al que se le asignará el callback.
 * @param {function} callback - (Opcional) La función a ejecutar cuando se hace click en el botón.
 * @returns {Promise<void>} Una promesa que se resuelve cuando la carga finaliza.
 */
function cargarPartial(ruta, contenedorId, botonId, callback) {
  return fetch(ruta)
    .then(r => r.text())
    .then(html => {
      const contenedor = document.getElementById(contenedorId);
      if (!contenedor) {
        console.warn(`No se encontró el contenedor ${contenedorId} al cargar ${ruta}`);
        return;
      }

      contenedor.innerHTML = html;

      if (globalThis.feather) {
        feather.replace();
      }

      if (botonId && typeof callback === 'function') {
        const btn = document.getElementById(botonId);
        if (btn) {
          btn.addEventListener('click', callback);
        }
      }
    })
    .catch(err => console.error(`Error cargando ${ruta}:`, err));
}


/**
 * Inicializa los botones de acción (Nueva, Editar, Eliminar, Filtro) cargando sus partials
 * y asignando los callbacks correspondientes definidos globalmente.
 */
function initButtons() {
  const basePath = globalThis.BASE_URL || '';

  // Botón "Nueva"
  (function () {
    const page = document.body?.dataset?.page || document.body?.className || '';
    let callback = null;

    // Elegir callback según la página (mis-materias usa abrirModalCrearMateria)
    if (String(page).includes('mis-materias') && typeof globalThis.abrirModalCrearMateria === 'function') {
      callback = globalThis.abrirModalCrearMateria;
    } else if (typeof globalThis.abrirModalNueva === 'function') {
      callback = globalThis.abrirModalNueva;
    }

    // Envolver el callback para desactivar primero el modo edición,
    // preservando el comportamiento de alternancia para el botón Editar.
    let callbackToUse = callback;
    if (typeof callback === 'function') {
      callbackToUse = function (e) {
        try {
          if (typeof globalThis.desactivarModoEdicion === 'function') {
            globalThis.desactivarModoEdicion();
          }
        } catch (err) {
          console.error('Error al desactivar modo edición:', err);
        }
        try { return callback(e); } catch (err) { console.error('Error en callback:', err); }
      };
    }

    cargarPartial(
      basePath + 'partials/boton-nueva.html',
      'contenedor-boton-nueva',
      'boton-nueva',
      callbackToUse
    );
  })();

  // Botón "Editar"
  cargarPartial(
    basePath + 'partials/boton-editar.html',
    'contenedor-boton-editar',
    'boton-editar',
    globalThis.actualizarColumnaAcciones || null
  );

  // Botón "Eliminar"
  cargarPartial(
    basePath + 'partials/boton-eliminar.html',
    'contenedor-boton-eliminar',
    'boton-eliminar',
    globalThis.botonEliminarMasivo || null
  );

  // Botón "Filtro"
  cargarPartial(
    basePath + 'partials/boton-filtro.html',
    'contenedor-boton-filtro',
    'boton-filtro',
    globalThis.abrirModalFiltro || null
  );
}

document.addEventListener('DOMContentLoaded', initButtons);

if (typeof module !== 'undefined' && module.exports) {
  module.exports = { cargarPartial, initButtons };
}
