function cargarPartial(ruta, contenedorId, botonId, callback) {
  fetch(ruta)
    .then(r => r.text())
    .then(html => {
      const contenedor = document.getElementById(contenedorId);
      if (!contenedor) {
        console.warn(`No se encontró el contenedor ${contenedorId} al cargar ${ruta}`);
        return;
      }

      contenedor.innerHTML = html;

      if (window.feather) {
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

document.addEventListener('DOMContentLoaded', () => {
  const basePath = globalThis.BASE_URL || '';

  // Botón "Nueva"
  // Botón "Nueva" - elegir callback según la página (mis-materias usa abrirModalCrearMateria)
  (function() {
    const page = document.body?.dataset?.page || document.body?.className || '';
    let callback = null;
    if (String(page).includes('mis-materias') && typeof globalThis.abrirModalCrearMateria === 'function') {
      callback = globalThis.abrirModalCrearMateria;
    } else if (typeof globalThis.abrirModalNueva === 'function') {
      callback = globalThis.abrirModalNueva;
    }

    // Wrap only the 'Nueva' callback so it first deactivates edit mode, preserving toggle behavior for the Edit button
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
        try { return callback(e); } catch (err) { console.error('callback error', err); }
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
});
