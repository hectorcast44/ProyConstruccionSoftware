fetch('../partials/boton-nueva.html')
  .then(r => r.text())
  .then(html => {
    document.getElementById('contenedor-boton').innerHTML = html;
    if (window.feather) feather.replace();
    const btn = document.getElementById('abrir-boton-nueva');
    if (btn) btn.addEventListener('click', abrirModalNueva);
  })
  .catch(err => console.error('Error al cargar el botón:', err));