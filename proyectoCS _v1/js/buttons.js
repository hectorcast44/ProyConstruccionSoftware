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
  cargarPartial(
    '../partials/boton-nueva.html',
    'contenedor-boton-nueva',
    'abrir-boton-nueva',
    abrirModalNueva
  );

  cargarPartial(
    '../partials/boton-editar.html',
    'contenedor-boton-editar',
    'abrir-boton-editar'
  );

  cargarPartial(
    '../partials/boton-eliminar.html',
    'contenedor-boton-eliminar',
    'abrir-boton-eliminar'
  );
});
