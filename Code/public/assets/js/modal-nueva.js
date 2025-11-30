function abrirModalNueva() {
  let modal = document.getElementById('modal-nueva');

  if (!modal) {
    // cargar parcial del modal (una sola vez)
    const basePath = globalThis.BASE_URL || '';
    fetch(basePath + 'partials/modal-nueva.html')
      .then(r => r.text())
      .then(html => {
        document.body.insertAdjacentHTML('beforeend', html);

        // Inicializar listeners del modal (cerrar, submit, feather)
        inicializarModalNueva();

        // renderizar iconos dentro del modal
        if (window.feather) feather.replace();

        // mostrar modal
        document.getElementById('modal-nueva').showModal();
      })
      .catch(err => console.error('Error cargando modal:', err));
    return;
  }

  modal.showModal();
}

function inicializarModalNueva() {
  const modal = document.getElementById('modal-nueva');
  const cerrar = document.getElementById('cerrar-modal');
  const form = document.getElementById('form-calificacion');

  if (!modal || !form) return;

  // cerrar
  cerrar.addEventListener('click', () => {
    modal.close();
  });

  // submit del form: leer datos y agregar fila a la tabla (tbody)
  form.addEventListener('submit', (e) => {
    e.preventDefault();

    const data = Object.fromEntries(new FormData(form).entries());

    // Validación mínima
    if (!data.actividad || !data.materia) {
      // mostrar mensaje real si quieres
      console.warn('Faltan campos obligatorios');
      return;
    }

    // Escapar texto sencillo para evitar inyección
    function esc(s) { return String(s || '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#39;'); }

    const tbody = document.getElementById('tbody');
    if (!tbody) {
      console.error('No se encontró #tbody en la página.');
      return;
    }

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${esc(data.actividad)}</td>
      <td>${esc(data.materia)}</td>
      <td>${esc(data.tipo || '')}</td>
      <td>${esc(data['puntaje-max'] || '')}</td>
      <td>${esc(data.puntaje || '')}</td>
      <td>${esc(data.fecha || '')}</td>
    `;

    tbody.appendChild(tr);

    // reset y cerrar
    form.reset();
    modal.close();

    // volver a inicializar iconos 
    if (window.feather) feather.replace();
  });
}