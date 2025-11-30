let modoEdicionActivo = false;

document.addEventListener('DOMContentLoaded', verificarTablaVacia);

// Al cargar la página, obtener actividades desde la API y renderizarlas
document.addEventListener('DOMContentLoaded', () => {
    cargarActividadesDesdeAPI();
});

// Si la tabla está vacía, oculta elementos y muestra mensaje
function verificarTablaVacia() {
    const grupoFiltros = document.getElementById('content-group');
    const tabla = document.getElementById('tabla');
    const btnEditar = document.getElementById('contenedor-boton-editar');
    const btnEliminar = document.getElementById('contenedor-boton-eliminar');
    const filas = document.querySelectorAll('#tabla-body tr');
    const msg = document.getElementById('mensaje-vacio');

    // Si la página no contiene elementos del dashboard (tabla y mensaje), salir temprano.
    if (!tabla && !document.getElementById('tabla-body') && !msg) return;

    // A partir de aquí, usamos comprobaciones defensivas antes de tocar classList
    const hayFilas = filas.length > 0;

    if (!hayFilas) {
        if (msg) msg.classList.remove('oculto');
        if (tabla) tabla.classList.add('oculto');
        if (grupoFiltros) grupoFiltros.classList.add('oculto');
        if (btnEditar) btnEditar.classList.add('oculto');
        if (btnEliminar) btnEliminar.classList.add('oculto');
    } else {
        if (msg) msg.classList.add('oculto');
        if (tabla) tabla.classList.remove('oculto');
        if (grupoFiltros) grupoFiltros.classList.remove('oculto');
        if (btnEditar) btnEditar.classList.remove('oculto');
        if (btnEliminar) btnEliminar.classList.remove('oculto');
    }
}


// Cargar actividades desde la API: primero obtenemos las materias, luego por cada materia sus actividades
function cargarActividadesDesdeAPI(){
    const base = globalThis.BASE_URL || '';
    const urlMaterias = base + 'api/materias';
    const tbody = document.getElementById('tabla-body');
    if (!tbody) return;

    // mostrar carga temporal
    tbody.innerHTML = '<tr><td colspan="5">Cargando actividades...</td></tr>';

    fetch(urlMaterias, { credentials: 'same-origin' })
        .then(async r => {
            const txt = await r.text();
            let json = null;
            try { json = JSON.parse(txt); } catch(e){ throw new Error('Respuesta inválida de /api/materias'); }
            if (!r.ok) throw new Error(json.message || ('HTTP ' + r.status));
            return json.data || [];
        })
        .then(async materias => {
            // Para cada materia hacemos una petición de actividades
            const promesas = materias.map(m => {
                const urlActs = base + 'api/actividades?id_materia=' + encodeURIComponent(m.id);
                return fetch(urlActs, { credentials: 'same-origin' })
                    .then(async r => {
                        const txt = await r.text();
                        let json = null;
                        try { json = JSON.parse(txt); } catch(e){ throw new Error('Respuesta inválida de /api/actividades'); }
                        if (!r.ok) throw new Error(json.message || ('HTTP ' + r.status));
                        return { materia: m, data: json.data };
                    });
            });

            return Promise.all(promesas);
        })
        .then(resultados => {
            // Vaciar tabla
            const filas = [];

            resultados.forEach(res => {
                const materia = res.materia;
                const data = res.data || {};

                // data.secciones -> cada sección contiene actividades
                const secciones = data.secciones || [];
                secciones.forEach(sec => {
                    const tipoNombre = sec.nombre_tipo || sec.nombre || '';
                    (sec.actividades || []).forEach(act => {
                        filas.push({
                            id: act.id_actividad || act.id || '',
                            fecha: act.fecha_entrega || act.fecha || '',
                            nombre: act.nombre || act.nombre_actividad || '',
                            materia: materia.nombre || '',
                            tipo: tipoNombre,
                            estado: (act.estado || act.obtenido !== null) ? (act.estado || (act.obtenido !== null ? 'completada' : 'en curso')) : 'en curso',
                            obtenido: act.obtenido ?? null,
                            maximo: act.maximo ?? null
                        });
                    });
                });
            });

            // Renderizar filas
            tbody.innerHTML = '';
            if (filas.length === 0) {
                verificarTablaVacia();
                return;
            }

            filas.forEach(f => {
                const tr = document.createElement('tr');
                const progreso = generarBadgeProgreso(f.estado, f.obtenido, f.maximo);
                tr.dataset.idActividad = f.id;
                tr.innerHTML = `
                    <td>${escapeHtml(f.fecha)}</td>
                    <td>${escapeHtml(f.nombre)}</td>
                    <td>${escapeHtml(f.materia)}</td>
                    <td>${escapeHtml(f.tipo)}</td>
                    <td>${progreso}</td>
                `;
                tbody.appendChild(tr);
            });

            // inicializar iconos si los hay
            if (window.feather) feather.replace();

            verificarTablaVacia();
        })
        .catch(err => {
            console.error('Error cargando actividades:', err);
            const tbody = document.getElementById('tabla-body');
            if (tbody) tbody.innerHTML = `<tr><td colspan="5">Error: ${escapeHtml(err.message)}</td></tr>`;
            verificarTablaVacia();
        });

    function escapeHtml(s){ return String(s||'').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'","&#39;"); }

    function generarBadgeProgreso(estado, obtenido, maximo){
        const est = (estado || '').toLowerCase();
        let clase = 'progress-encurso';
        let label = 'En curso';
        if (est === 'sin iniciar' || est === 'sininiciar') { clase = 'progress-sininiciar'; label = 'Sin iniciar'; }
        else if (est === 'completada' || est === 'listo' || est === 'completado') { clase = 'progress-completado'; label = 'Completada'; }

        // Si hay puntaje, mostrar puntos
        if (obtenido !== null && maximo !== null) {
            label = `${obtenido}/${maximo}`;
        }

        return `<span class="progress-badge ${clase}" data-progreso="${escapeHtml(est)}">${escapeHtml(label)}</span>`;
    }
}

// Borrar todas las filas de la tabla
function botonEliminarMasivo() {
    const filas = document.querySelectorAll('#tabla-body tr');
    const confirmar = window.confirm('¿Estás seguro de que deseas eliminar todas las actividades? Esta acción no se puede deshacer.');
    if (!confirmar) return;
    filas.forEach(tr => tr.remove());
    verificarTablaVacia();
}

// Barra de busqueda funcional (compatible con diferentes templates)
function inicializarBuscador() {
    const searchInput = document.getElementById('d-search-input') || document.getElementById('search-input') || document.querySelector('.search-input');
    if (!searchInput) return;

    searchInput.addEventListener('input', function () {
        const filtro = this.value.toLowerCase();
        const filas = document.querySelectorAll('#tabla-body tr');
        const tabla = document.getElementById('tabla');
        const mensajeVacio = document.getElementById('mensaje-vacio') || document.getElementById('tabla-vacia');

        let hayCoincidencias = false;

        filas.forEach(fila => {
            const textoFila = fila.innerText.toLowerCase();
            const coincide = textoFila.includes(filtro);

            fila.style.display = coincide ? '' : 'none';
            if (coincide) hayCoincidencias = true;
        });

        if (hayCoincidencias) {
            if (tabla) tabla.classList.remove('oculto');
            if (mensajeVacio) mensajeVacio.classList.add('oculto');
        } else {
            if (tabla) tabla.classList.add('oculto');
            if (mensajeVacio) mensajeVacio.classList.remove('oculto');
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarBuscador);
} else {
    inicializarBuscador();
}


// Actualizar la columna de acciones (editar/eliminar) en la tabla
function actualizarColumnaAcciones() {
    modoEdicionActivo = !modoEdicionActivo;

    const theadRow = document.querySelector('#tabla thead tr');
    const filas = document.querySelectorAll('#tabla-body tr');
    // const tr = filas[index];
    // const selectProgreso = tr.querySelector('.select-progreso');

    // document.querySelector('#form-actividad').dataset.progreso = selectProgreso.value;

    if (modoEdicionActivo) {
        // Agregamos la columna "Acciones" al header
        if (!document.getElementById('th-acciones')) {
            const th = document.createElement('th');
            th.id = 'th-acciones';
            // th.textContent = 'Acciones';
            theadRow.appendChild(th);
        }

        // Agregar botones a cada fila
        filas.forEach((tr, index) => {
            // evitar duplicar
            if (tr.querySelector('.col-acciones')) return;

            const td = document.createElement('td');
            td.classList.add('col-acciones');
            td.innerHTML = `
                <div class="filter-btns">
                    <button class="btn-edit-fila" data-index="${index}">
                        <i data-feather="edit-3"></i>
                    </button>
                    <button class="btn-delete-fila" data-index="${index}">
                        <i data-feather="trash-2"></i>
                    </button>
                </div>
            `;
            tr.appendChild(td);
            // inicializar iconos
            if (window.feather) feather.replace();
        });
        } else {
            // Quitar header
            const th = document.getElementById('th-acciones');
            if (th) th.remove();

            // Quitar columna de cada fila
            filas.forEach(tr => {
            const td = tr.querySelector('.col-acciones');
            if (td) td.remove();
            });
        }
    }

document.addEventListener('click', (e) => {
    const btnEdit = e.target.closest('.btn-edit-fila');
    if (btnEdit) {
        const index = Number(btnEdit.dataset.index);
        editarFila(index);
        return;
    }

    const btnDelete = e.target.closest('.btn-delete-fila');
    if (btnDelete) {
        const index = Number(btnDelete.dataset.index);
        eliminarFila(index);
        return;
    }
});

function editarFila(index) {
    const filas = document.querySelectorAll('#tabla-body tr');
    const tr = filas[index];
    const tds = tr.querySelectorAll('td');

    abrirModalNueva();

    // rellenas (le das un mini tiempo para que cargue si el modal es insertado dinámicamente)
    setTimeout(() => {
        document.querySelector('#form-actividad [name="fecha"]').value        = tds[0].textContent;
        document.querySelector('#form-actividad [name="actividad"]').value    = tds[1].textContent;
        document.querySelector('#form-actividad [name="materia"]').value      = tds[2].textContent;
        document.querySelector('#form-actividad [name="tipo"]').value         = tds[3].textContent;
        // el progreso se maneja al guardar

        // marcar el índice que se está editando
        document.getElementById('form-actividad').dataset.editIndex = index;
    }, 50);
}

function eliminarFila(index) {
    const filas = document.querySelectorAll('#tabla-body tr');
    if (filas[index]) filas[index].remove();
    verificarTablaVacia();
}

document.addEventListener('click', (e) => {
  if (e.target.classList.contains('progress-badge')) {
    cambiarProgreso(e.target);
  }
});

function cambiarProgreso(el) {
  const estados = ["sin iniciar", "en curso", "listo"];

  let actual = el.dataset.progreso;
  let idx = estados.indexOf(actual);

  // pasar al siguiente estado
  let siguiente = estados[(idx + 1) % estados.length];

  // actualizar dataset
  el.dataset.progreso = siguiente;

  // actualizar texto visible
  el.textContent = capitalizar(siguiente);

  // resetear clases
  el.classList.remove("progress-sininiciar", "progress-encurso", "progress-completado");

  // aplicar estilo nuevo
  if (siguiente === "sin iniciar") el.classList.add("progress-sininiciar");
  else if (siguiente === "en curso") el.classList.add("progress-encurso");
  else el.classList.add("progress-completado");
}

function capitalizar(txt) {
  return txt.charAt(0).toUpperCase() + txt.slice(1);
}  