function cargarPartial(ruta, contenedorId, botonId, callback) {
  fetch(ruta)
    .then(r => r.text())
    .then(html => {
      document.getElementById(contenedorId).innerHTML = html;

      if (window.feather) feather.replace();

      if (botonId && callback) {
        const btn = document.getElementById(botonId);
        if (btn) btn.addEventListener('click', callback);
      }
    })
    .catch(err => console.error(`Error cargando ${ruta}:`, err));
}

document.addEventListener('DOMContentLoaded', () => {
  const basePath = globalThis.BASE_URL || '';

  cargarPartial(
    basePath + 'partials/boton-nueva.html',
    'contenedor-boton-nueva',
    'boton-nueva',
    abrirModalNueva
  );

  cargarPartial(
    basePath + 'partials/boton-editar.html',
    'contenedor-boton-editar',
    'boton-editar',
    actualizarColumnaAcciones
  );

  cargarPartial(
    basePath + 'partials/boton-eliminar.html',
    'contenedor-boton-eliminar',
    'boton-eliminar',
    botonEliminarMasivo
  );

  cargarPartial(
    basePath + 'partials/boton-filtro.html',
    'contenedor-boton-filtro',
    'boton-filtro',
    abrirModalFiltro
  );
});
