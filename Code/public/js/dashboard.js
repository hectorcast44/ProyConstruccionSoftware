let modoEdicionActivo = false;

// Normaliza distintos valores de estado desde la API/BD a los tres estados usados en UI
function normalizeEstado(raw) {
    // Only accept the three canonical states: 'listo', 'en curso', 'pendiente'
    if (!raw && raw !== 0) return 'pendiente';
    const s = String(raw).toLowerCase().trim();
    if (s === 'listo') return 'listo';
    if (s === 'en curso' || s === 'encurso' || s === 'en_curso') return 'en curso';
    if (s === 'pendiente') return 'pendiente';
    // Any unknown value: default to 'pendiente'
    return 'pendiente';
}

// Mapear nombre de tipo a clase CSS (slug-like)
function tipoClase(raw) {
    if (!raw && raw !== 0) return 'tag-agua';
    const s = String(raw).toLowerCase().trim();
    if (s.includes('ejerc')) return 'tag-rojo';
    if (s.includes('examen')) return 'tag-azul';
    if (s.includes('proyecto')) return 'tag-verde';
    if (s.includes('tarea') || s.includes('trabajo')) return 'tag-naranja';
    return 'tag-agua';
}

document.addEventListener('DOMContentLoaded', verificarTablaVacia);

// Al cargar la página, obtener actividades desde la API y renderizarlas
document.addEventListener('DOMContentLoaded', () => {
    cargarActividadesDesdeAPI();
});

// Si la tabla está vacía, oculta elementos y muestra mensaje
function verificarTablaVacia() {
    const tabla = document.getElementById('tabla');
    const btnEditar = document.getElementById('contenedor-boton-editar');
    const btnEliminar = document.getElementById('contenedor-boton-eliminar');
    const btnFiltro = document.getElementById('contenedor-boton-filtro');
    const filas = document.querySelectorAll('#tabla-body tr');
    const msg = document.getElementById('mensaje-vacio');
    const tablaVacia = document.getElementById('tabla-vacia');
    const searchbar = document.getElementById('search-box');

    // Si la página no contiene elementos del dashboard (tabla y mensaje), salir temprano.
    if (!tabla && !document.getElementById('tabla-body') && !msg && !tablaVacia) return;

    // Contar sólo las filas visibles (las que no tienen display: none)
    const visibleFilas = Array.from(filas).filter(f => (f.style.display || '') !== 'none').length;
    const totalFilas = filas.length;

    // Casos:
    // - totalFilas === 0: no hay actividades registradas -> mostrar `mensaje-vacio` (global)
    // - totalFilas > 0 && visibleFilas === 0: hay actividades pero ninguna coincide con el filtro/búsqueda -> mostrar `tabla-vacia`
    // - visibleFilas > 0: mostrar tabla normalmente
    if (totalFilas === 0) {
        if (msg) msg.classList.remove('oculto');
        if (tablaVacia) tablaVacia.classList.add('oculto');
        if (tabla) tabla.classList.add('oculto');
        if (btnEditar) btnEditar.classList.add('oculto');
        if (btnEliminar) btnEliminar.classList.add('oculto');
        if (btnFiltro) btnFiltro.classList.add('oculto');
        if (searchbar) searchbar.classList.add('oculto');
    } else if (visibleFilas === 0) {
        // hay filas, pero no hay coincidencias visibles (búsqueda/filtrado)
        if (msg) msg.classList.add('oculto');
        if (tablaVacia) tablaVacia.classList.remove('oculto');
        if (tabla) tabla.classList.add('oculto');
        if (btnEditar) btnEditar.classList.add('oculto');
        if (btnEliminar) btnEliminar.classList.add('oculto');
        if (btnFiltro) btnFiltro.classList.remove('oculto');
        if (searchbar) searchbar.classList.remove('oculto');
    } else {
        // Hay filas visibles
        if (msg) msg.classList.add('oculto');
        if (tablaVacia) tablaVacia.classList.add('oculto');
        if (tabla) tabla.classList.remove('oculto');
        if (btnEditar) btnEditar.classList.remove('oculto');
        if (btnEliminar) btnEliminar.classList.remove('oculto');
        if (btnFiltro) btnFiltro.classList.remove('oculto');
        if (searchbar) searchbar.classList.remove('oculto');
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
                                    estado: normalizeEstado((act.estado || (act.obtenido !== null ? 'pendiente' : 'en curso'))),
                                    obtenido: act.obtenido ?? null,
                                    maximo: act.maximo ?? null,
                                    id_materia: materia.id || materia.id_materia || materia.idMateria || null,
                                    id_tipo_actividad: sec.id_tipo || sec.id_tipo || sec.idTipo || null
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
                if (f.id_materia) tr.dataset.idMateria = f.id_materia;
                if (f.id_tipo_actividad) tr.dataset.idTipo = f.id_tipo_actividad;
                const tipoCls = tipoClase(f.tipo);
                // (no almacenar maximo; ahora el backend permite eliminar cualquiera)
                tr.innerHTML = `
                    <td>${escapeHtml(f.fecha)}</td>
                    <td>${escapeHtml(f.nombre)}</td>
                    <td>${escapeHtml(f.materia)}</td>
                    <td><span class="tag ${tipoCls}">${escapeHtml(f.tipo)}</span></td>
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
        const est = normalizeEstado(estado);
        let clase = 'progress-encurso';
        let label = 'En curso';
        if (est === 'pendiente') { clase = 'progress-sininiciar'; label = 'Pendiente'; }
        else if (est === 'listo') { clase = 'progress-completado'; label = 'Listo'; }

        return `<span class="progress-badge ${clase}" data-progreso="${escapeHtml(est)}">${escapeHtml(label)}</span>`;
    }

    // removed inner normalizeEstado to use the global one
}

// Borrar todas las filas de la tabla
async function botonEliminarMasivo() {
    const filas = Array.from(document.querySelectorAll('#tabla-body tr'));
    if (!filas.length) return;

        // Usar modal en lugar de confirm() nativo
        const confirmar = await (typeof showConfirm === 'function'
                ? showConfirm('Confirmar eliminación', '¿Estás seguro de que deseas eliminar todas las actividades listadas? Esta acción eliminará las actividades del servidor y no se podrá deshacer.')
                : (await (async () => {
                        // fallback: crear modal mínimo si no existe showConfirm
                        return new Promise(resolve => {
                            const modalId = '__temp_confirm_modal_all_acts';
                            const overlay = document.createElement('div');
                            overlay.id = modalId;
                            Object.assign(overlay.style, { position: 'fixed', inset: '0', background: 'rgba(0,0,0,0.4)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 999999 });
                            overlay.innerHTML = `
                                <div role="dialog" aria-modal="true" class="modal-eliminar-masivo">
                                    <h3>Confirmar eliminación</h3>
                                    <p>¿Estás seguro de que deseas eliminar todas las actividades listadas? Esta acción eliminará las actividades del servidor y no se podrá deshacer.</p>
                                    <div>
                                        <button id="__temp_confirm_cancel">Cancelar</button>
                                        <button id="__temp_confirm_ok" class="btn-delete-fila">Eliminar</button>
                                    </div>
                                </div>
                            `;
                            document.body.appendChild(overlay);
                            const ok = overlay.querySelector('#__temp_confirm_ok');
                            const cancel = overlay.querySelector('#__temp_confirm_cancel');
                            ok.focus();
                            const cleanup = (res) => { try { overlay.remove(); } catch(e){}; resolve(res); };
                            ok.addEventListener('click', () => cleanup(true));
                            cancel.addEventListener('click', () => cleanup(false));
                            overlay.addEventListener('keydown', (ev) => { if (ev.key === 'Escape') cleanup(false); });
                        });
                })()) );
        if (!confirmar) return;

    const base = globalThis.BASE_URL || '';

    // Separar filas con id (persistentes) y sin id (solo cliente)
    const filasConId = filas.map(f => ({ el: f, id: f.dataset.idActividad || f.getAttribute('data-id-actividad') || '' }))
                          .map(x => ({ el: x.el, id: String(x.id).trim(), idNum: Number(String(x.id).trim()) }))
                          .filter(x => Number.isFinite(x.idNum) && x.idNum > 0);
    const filasSinId = filas.filter(f => !(f.dataset.idActividad || f.getAttribute('data-id-actividad')));

    // Eliminar primero las filas que no tienen id (solo en el cliente)
    filasSinId.forEach(f => f.remove());

    if (filasConId.length === 0) {
        verificarTablaVacia();
        return;
    }

    // Enviar DELETE para todas las filas con id (backend ahora acepta borrar calificables)
    const borrados = filasConId.map(item => {
        const url = base + 'api/actividades?id=' + encodeURIComponent(item.idNum);
        return fetch(url, {
            method: 'DELETE',
            credentials: 'same-origin'
        }).then(async r => {
            const txt = await r.text();
            let body = txt;
            let json = null;
            try { json = JSON.parse(txt); body = json; } catch { /* keep raw text */ }
            if (!r.ok) {
                const msg = (json && json.message) ? json.message : (typeof body === 'string' ? body : JSON.stringify(body));
                throw new Error(`HTTP ${r.status}: ${msg}`);
            }
            return { id: item.idNum, ok: true, message: json?.message ?? body };
        }).catch(err => ({ id: item.idNum, ok: false, error: err }));
    });

    Promise.all(borrados).then(results => {
        const errores = results.filter(r => !r.ok);
        if (errores.length) {
            console.error('Errores al eliminar actividades:', errores);
            // Construir mensaje legible
            const detalles = errores.map(e => `id=${e.id} -> ${e.error?.message || e.error}`).join('\n');
            if (typeof showToast === 'function') {
                showToast('Ocurrieron errores al eliminar algunas actividades:\n' + detalles, { type: 'error' });
            } else {
                console.error('Ocurrieron errores al eliminar algunas actividades:\n' + detalles);
            }
        }

        // Refrescar tabla desde la API para reflejar el estado real
        try {
            if (typeof cargarActividadesDesdeAPI === 'function') cargarActividadesDesdeAPI();
            else location.reload();
        } catch (e) {
            location.reload();
        }
    });
}

// Barra de busqueda funcional (compatible con diferentes templates)
function inicializarBuscador() {
    const searchInput = document.getElementById('d-search-input') || document.getElementById('search-input') || document.querySelector('.search-input');
    const searchToggle = document.getElementById('search-toggle');
    const searchWrapper = document.getElementById('dashboard-search-input-wrapper');
    if (!searchInput && !searchToggle) return;
    // Normaliza texto: quita acentos, colapsa espacios y pasa a minúsculas
    function normalizeText(s) {
        if (!s) return '';
        try {
            // Normalización Unicode para quitar diacríticos
            return String(s)
                .normalize('NFD')
                .replace(/\p{Diacritic}/gu, '')
                .replace(/\s+/g, ' ')
                .toLowerCase()
                .trim();
        } catch (e) {
            // Fallback cuando el ambiente no soporta \p{Diacritic}
            return String(s)
                .replace(/[ÁÀÂÄáàâä]/g, 'a')
                .replace(/[ÉÈÊËéèêë]/g, 'e')
                .replace(/[ÍÌÎÏíìîï]/g, 'i')
                .replace(/[ÓÒÔÖóòôö]/g, 'o')
                .replace(/[ÚÙÛÜúùûü]/g, 'u')
                .replace(/\s+/g, ' ')
                .toLowerCase()
                .trim();
        }
    }

    // Toggle button opens/collapses the search input. When collapsed, clear input.
    if (searchToggle) {
        searchToggle.addEventListener('click', function () {
            if (searchWrapper && searchWrapper.classList.contains('oculto')) {
                // expand
                searchWrapper.classList.remove('oculto');
                // focus input after a tick
                setTimeout(() => { try { (searchInput).focus(); } catch(e){} }, 10);
            } else {
                // collapse and clear
                if (searchInput) searchInput.value = '';
                if (searchWrapper) searchWrapper.classList.add('oculto');
                // restore table state
                verificarTablaVacia();
            }
        });
    }

    if (searchInput) searchInput.addEventListener('input', function () {
        const filtro = normalizeText(this.value || '');
        const filas = document.querySelectorAll('#tabla-body tr');
        const tabla = document.getElementById('tabla');
        // Para búsquedas, preferimos mostrar `#tabla-vacia`. Si no existe, caemos a `#mensaje-vacio`.
        const tablaVacia = document.getElementById('tabla-vacia');

        // Si el campo de búsqueda está vacío, restaurar estado normal (tabla completa / mensaje vacío global)
        if (filtro === '') {
            // mostrar todas las filas
            filas.forEach(fila => fila.style.display = '');
            // delegar al verificador global
            verificarTablaVacia();
            return;
        }

        let hayCoincidencias = false;

        filas.forEach(fila => {
            const textoFila = normalizeText(fila.innerText || '');
            const coincide = textoFila.includes(filtro);

            fila.style.display = coincide ? '' : 'none';
            if (coincide) hayCoincidencias = true;

            if (hayCoincidencias) {
                if (tabla) tabla.classList.remove('oculto');
                if (tablaVacia) tablaVacia.classList.add('oculto');
            } else {
                if (tabla) tabla.classList.add('oculto');
                if (tablaVacia) tablaVacia.classList.remove('oculto');
        }
        });
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

    // esperar a que los selects del modal estén poblados (si se insertó dinámicamente)
    (async () => {
        const form = document.getElementById('form-actividad');
        const hiddenId = document.getElementById('id_actividad');

        // helper que espera hasta que el select tenga una opción con el value esperado
        async function waitForOption(selectEl, value, timeout = 1200) {
            if (!selectEl) return true;
            const start = Date.now();
            while (Date.now() - start < timeout) {
                try {
                    if (Array.from(selectEl.options).some(o => String(o.value) === String(value))) return true;
                } catch (e) {}
                await new Promise(r => setTimeout(r, 50));
            }
            return false;
        }

        const idActividad = tr.dataset.idActividad || tr.getAttribute('data-id-actividad') || '';
        const idMateria = tr.dataset.idMateria || tr.getAttribute('data-id-materia') || '';
        const idTipo = tr.dataset.idTipo || tr.getAttribute('data-id-tipo') || '';

        // rellenar campos básicos (texto/fecha)
        try { if (document.querySelector('#form-actividad [name="fecha"]')) document.querySelector('#form-actividad [name="fecha"]').value = tds[0].textContent.trim(); } catch(e){}
        try { if (document.querySelector('#form-actividad [name="actividad"]')) document.querySelector('#form-actividad [name="actividad"]').value = tds[1].textContent.trim(); } catch(e){}

        // esperar y asignar selects por id si es posible, o fallback a texto si no se encuentra la opción
        const selectMateria = document.querySelector('#form-actividad select[name="materia"]');
        const selectTipo = document.querySelector('#form-actividad select[name="tipo"]');

        if (selectMateria && idMateria) {
            const ok = await waitForOption(selectMateria, idMateria);
            if (ok) selectMateria.value = String(idMateria);
        } else if (selectMateria) {
            // fallback: intentar por texto
            try {
                Array.from(selectMateria.options).forEach(o => { if (o.textContent.trim() === tds[2].textContent.trim()) selectMateria.value = o.value; });
            } catch(e){}
        }

        if (selectTipo && idTipo) {
            const ok2 = await waitForOption(selectTipo, idTipo);
            if (ok2) selectTipo.value = String(idTipo);
        } else if (selectTipo) {
            try {
                Array.from(selectTipo.options).forEach(o => { if (o.textContent.trim() === tds[3].textContent.trim()) selectTipo.value = o.value; });
            } catch(e){}
        }

        // set hidden id for submit handler
        try { if (hiddenId && idActividad) hiddenId.value = String(idActividad); } catch(e){}

        // marcar el índice que se está editando
        try { if (form) form.dataset.editIndex = index; } catch(e){}
    })();
}

// Exponer helper para desactivar el modo edición desde otros módulos
function desactivarModoEdicion() {
    try {
        if (typeof modoEdicionActivo !== 'undefined' && modoEdicionActivo) {
            // llamar a la función que quita la columna y limpia botones
            if (typeof actualizarColumnaAcciones === 'function') {
                actualizarColumnaAcciones();
            } else {
                modoEdicionActivo = false;
            }
        }
    } catch (e) {
        console.error('desactivarModoEdicion error', e);
        try { modoEdicionActivo = false; } catch(e){}
    }
}
window.desactivarModoEdicion = desactivarModoEdicion;

function eliminarFila(index) {
    (async function(){
        const filas = document.querySelectorAll('#tabla-body tr');
        const tr = filas[index];
        if (!tr) return;

        const idRaw = tr.dataset.idActividad || tr.getAttribute('data-id-actividad') || '';
        const idNum = Number(String(idRaw).trim());

        // Si no tiene id numérico, sólo eliminar del DOM
        if (!Number.isFinite(idNum) || idNum <= 0) {
            tr.remove();
            verificarTablaVacia();
            return;
        }

        const base = globalThis.BASE_URL || '';

        try {
            const res = await fetch(base + 'api/actividades?id=' + encodeURIComponent(idNum), {
                method: 'DELETE',
                credentials: 'same-origin'
            });

            const txt = await res.text();
            let json = null;
            try { json = JSON.parse(txt); } catch(e) { /* keep raw text */ }

            if (!res.ok) {
                const msg = (json && json.message) ? json.message : (typeof txt === 'string' ? txt : ('HTTP ' + res.status));
                if (typeof showToast === 'function') showToast('No se pudo eliminar: ' + msg, { type: 'error' });
                else console.error('No se pudo eliminar: ' + msg);
                return;
            }

            // Eliminado correctamente — refrescar lista o eliminar la fila localmente
            if (typeof showToast === 'function') showToast(json?.message || 'Actividad eliminada', { type: 'success' });

            try {
                if (typeof cargarActividadesDesdeAPI === 'function') {
                    cargarActividadesDesdeAPI();
                } else {
                    tr.remove();
                    verificarTablaVacia();
                }
            } catch (e) {
                tr.remove();
                verificarTablaVacia();
            }

        } catch (err) {
            console.error('Error eliminando actividad:', err);
            if (typeof showToast === 'function') showToast('Error eliminando actividad: ' + (err.message || err), { type: 'error' });
            else console.error(err);
        }
    })();
}

document.addEventListener('click', (e) => {
  if (e.target.classList.contains('progress-badge')) {
    cambiarProgreso(e.target);
  }
});

function cambiarProgreso(el) {
    const estados = ["pendiente", "en curso", "listo"];
    const base = globalThis.BASE_URL || '';

    let actual = normalizeEstado(el.dataset.progreso || el.textContent || '');
    let idx = estados.indexOf(actual);
    if (idx === -1) idx = 0;

    // pasar al siguiente estado
    const siguiente = estados[(idx + 1) % estados.length];

    // guardamos estado anterior para poder revertir si falla
    const anterior = actual;

    // actualizar UI inmediatamente
    el.dataset.progreso = siguiente;
    el.textContent = capitalizar(siguiente);
    el.classList.remove("progress-sininiciar", "progress-encurso", "progress-completado");
    if (siguiente === "pendiente") el.classList.add("progress-sininiciar");
    else if (siguiente === "en curso") el.classList.add("progress-encurso");
    else el.classList.add("progress-completado");

    // Persistir cambio en el servidor
    (async () => {
        try {
            const tr = el.closest('tr');
            if (!tr) throw new Error('Fila de actividad no encontrada');
            const idActividad = tr.dataset.idActividad || tr.getAttribute('data-id-actividad') || '';
            const idMateria = tr.dataset.idMateria || tr.getAttribute('data-id-materia') || '';
            const idTipo = tr.dataset.idTipo || tr.getAttribute('data-id-tipo') || '';
            const tds = tr.querySelectorAll('td');
            const fecha = tds[0] ? tds[0].textContent.trim() : '';
            const nombre = tds[1] ? tds[1].textContent.trim() : '';

            if (!idActividad || !idMateria || !idTipo || !nombre) {
                // no tenemos los datos necesarios para actualizar en backend
                if (typeof showToast === 'function') showToast('No se pueden persistir los cambios: faltan datos de la actividad', { type: 'error' });
                // revertir UI
                el.dataset.progreso = anterior;
                el.textContent = capitalizar(anterior);
                el.classList.remove("progress-sininiciar", "progress-encurso", "progress-completado");
                if (anterior === "pendiente") el.classList.add("progress-sininiciar");
                else if (anterior === "en curso") el.classList.add("progress-encurso");
                else el.classList.add("progress-completado");
                return;
            }

            const payload = {
                id_actividad: Number(idActividad),
                id_materia: Number(idMateria),
                id_tipo_actividad: Number(idTipo),
                nombre_actividad: nombre,
                fecha_entrega: fecha || undefined,
                estado: siguiente
            };

            const res = await fetch(base + 'api/actividades', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const txt = await res.text();
            let json = null;
            try { json = JSON.parse(txt); } catch(e) { /* keep raw */ }

            if (!res.ok) {
                const msg = json?.message || txt || ('HTTP ' + res.status);
                throw new Error(msg);
            }

            // opcional: refrescar datos más adelante para asegurar consistencia
            // setTimeout(() => { if (typeof cargarActividadesDesdeAPI === 'function') cargarActividadesDesdeAPI(); }, 300);

        } catch (err) {
            console.error('Error persisting progreso:', err);
            if (typeof showToast === 'function') showToast('No se pudo guardar el progreso: ' + (err.message || err), { type: 'error' });
            // revertir UI
            el.dataset.progreso = anterior;
            el.textContent = capitalizar(anterior);
            el.classList.remove("progress-sininiciar", "progress-encurso", "progress-completado");
            if (anterior === "pendiente") el.classList.add("progress-sininiciar");
            else if (anterior === "en curso") el.classList.add("progress-encurso");
            else el.classList.add("progress-completado");
        }
    })();
}

function capitalizar(txt) {
  return txt.charAt(0).toUpperCase() + txt.slice(1);
}  