let modoEdicionActivo = false;

document.addEventListener('DOMContentLoaded', verificarTablaVacia);

// Si la tabla está vacía, oculta elementos y muestra mensaje
function verificarTablaVacia() {
    const grupoFiltros = document.getElementById('content-group');
    const tabla = document.getElementById('tabla');
    const btnEditar = document.getElementById('contenedor-boton-editar');
    const btnEliminar = document.getElementById('contenedor-boton-eliminar');
    const filas = document.querySelectorAll('#tabla-body tr');
    const msg = document.getElementById('mensaje-vacio');

    if (filas.length === 0) {
        msg.classList.remove('oculto');
        tabla.classList.add('oculto');
        grupoFiltros.classList.add('oculto');
        btnEditar.classList.add('oculto');
        btnEliminar.classList.add('oculto');
    } else {
        msg.classList.add('oculto');
        tabla.classList.remove('oculto');
        grupoFiltros.classList.remove('oculto');
        btnEditar.classList.remove('oculto');
        btnEliminar.classList.remove('oculto');
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

// Barra de busqueda funcional
// Barra de búsqueda funcional + manejo de tabla vacía
document.getElementById("d-search-input").addEventListener("input", function () {
    const filtro = this.value.toLowerCase();
    const filas = document.querySelectorAll("#tabla-body tr");
    const tabla = document.getElementById("tabla");
    const mensajeVacio = document.getElementById("tabla-vacia");

    let hayCoincidencias = false;

    filas.forEach(fila => {
        const textoFila = fila.innerText.toLowerCase();
        const coincide = textoFila.includes(filtro);

        fila.style.display = coincide ? "" : "none";
        if (coincide) hayCoincidencias = true;
    });

    if (hayCoincidencias) {
        tabla.classList.remove("oculto");
        mensajeVacio.classList.add("oculto");
    } else {
        tabla.classList.add("oculto");
        mensajeVacio.classList.remove("oculto");
    }
});


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