function abrirModalCrearMateria(data = null) {
  let modal = document.getElementById('modal-nueva-materia');
  const basePath = globalThis.BASE_URL || '';

  if (!modal) {
    fetch(basePath + 'partials/modal-nueva-materia.html')
      .then(r => r.text())
      .then(html => {
        document.body.insertAdjacentHTML('beforeend', html);
        inicializarModalNuevaMateria();
        if (window.feather) feather.replace();
        const m = document.getElementById('modal-nueva-materia');
        if (data) prefilarModalMateria(data);
        m.showModal();
      })
      .catch(err => console.error('Error cargando modal materia:', err));
    return;
  }

  // si ya existe el modal, prefilar si se pasó data
  if (data) prefilarModalMateria(data);
  modal.showModal();
}

function inicializarModalNuevaMateria() {
  const modal = document.getElementById('modal-nueva-materia');
  const cerrar = document.getElementById('cerrar-modal-materia');
  const form = document.getElementById('form-materia');
  if (!modal || !form) return;

  if (cerrar) cerrar.addEventListener('click', () => modal.close());

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const f = new FormData(form);
    const payload = {
      id_materia: form.dataset.editId ? Number(form.dataset.editId) : undefined,
      nombre_materia: f.get('nombre_materia') || '',
      calif_minima: f.get('calif_minima') ? Number(f.get('calif_minima')) : 70
    };

    if (!payload.nombre_materia) {
      if (typeof showToast === 'function') showToast('Ingrese el nombre de la materia', { type: 'error' });
      else console.warn('Ingrese el nombre de la materia');
      return;
    }

    try {
      const res = await fetch((globalThis.BASE_URL || '') + 'api/materias', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const txt = await res.text();
      let json = null;
      try { json = JSON.parse(txt); } catch(e){ throw new Error('Respuesta inválida del servidor'); }
      if (!res.ok) throw new Error(json.message || ('HTTP ' + res.status));

      // éxito: cerrar y recargar la página para reflejar cambio
      form.reset();
      // limpiar estado de edición
      delete form.dataset.editId;
      modal.close();
      // si existe función para recargar materias, llamarla
      if (typeof window.cargarMateriasDesdeAPI === 'function') {
        window.cargarMateriasDesdeAPI();
      } else {
        // recarga la página como fallback
        location.reload();
      }

      if (typeof showToast === 'function') showToast(json.message || 'Materia creada', { type: 'success' });
      else console.log(json.message || 'Materia creada');
    } catch (err) {
      console.error('Error creando materia:', err);
      if (typeof showToast === 'function') showToast('Error al crear materia: ' + (err.message || err), { type: 'error' });
      else console.error('Error al crear materia: ' + (err.message || err));
    }
  });
}

// Rellena el modal con datos de la materia para editar
function prefilarModalMateria(data) {
  const form = document.getElementById('form-materia');
  if (!form) return;

  // data puede venir con diferentes nombres según la API
  const id = data.id ?? data.id_materia ?? data.idMateria ?? null;
  const nombre = data.nombre ?? data.nombre_materia ?? data.nombreMateria ?? '';
  const calif = data.calif_minima ?? data.calif_min ?? data.calificacion_minima ?? '';

  if (id) form.dataset.editId = String(id);
  if (form.querySelector('[name="nombre_materia"]')) form.querySelector('[name="nombre_materia"]').value = nombre;
  if (form.querySelector('[name="calif_minima"]')) form.querySelector('[name="calif_minima"]').value = calif;
}
