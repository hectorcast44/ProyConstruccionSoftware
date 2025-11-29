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
  cargarPartial(
    basePath + 'partials/boton-nueva.html',
    'contenedor-boton-nueva',
    'boton-nueva',
    globalThis.abrirModalNueva || null
  );

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
