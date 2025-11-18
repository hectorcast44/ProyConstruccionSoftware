function abrirModalNueva() {
  let modal = document.getElementById('modal-nueva');

  if (!modal) {
    // cargar parcial del modal (una sola vez)
    fetch('../partials/modal-nueva.html')
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
  const form = document.getElementById('form-actividad');

  if (!modal || !form) return;

  // cerrar
  cerrar.addEventListener('click', () => {
    modal.close();
  });

  // submit del form: leer datos y agregar fila a la tabla (tbody)
  form.addEventListener('submit', (e) => {
    e.preventDefault();

    const data = Object.fromEntries(new FormData(form).entries());

    // Escapar texto sencillo para evitar inyección
    function esc(s){ 
      return String(s || '')
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'",'&#39;'); 
    }

    const tbody = document.getElementById('tabla-body');
    if (!tbody) {
      console.error('No se encontró #tbody en la página.');
      return;
    }

    const editIndex = form.dataset.editIndex;

    if (editIndex !== undefined) {
    const filas = tbody.querySelectorAll('tr');
    const tr = filas[editIndex];
    const tds = tr.querySelectorAll('td');

    tds[0].textContent = esc(data.fecha || '');
    tds[1].textContent = esc(data.actividad);
    tds[2].textContent = esc(data.materia);
    tds[3].textContent = esc(data.tipo || '');
    tds[4].textContent = esc(data.progreso || '');

    modal.close();
    form.reset();

    // limpiar el estado
    delete form.dataset.editIndex;

  } else {
    const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${esc(data.fecha || '')}</td>
        <td>${esc(data.actividad)}</td>
        <td>${esc(data.materia)}</td>
        <td>${esc(data.tipo || '')}</td>
        <td>
          <span class="progress-badge progress-encurso" data-progreso="en curso">
            En curso
          </span>
        </td>
      `;

    tbody.appendChild(tr);
    form.reset();
    modal.close();
    verificarTablaVacia();
  }

    // volver a inicializar iconos (si necesitas)
    if (window.feather) feather.replace();
  });
}