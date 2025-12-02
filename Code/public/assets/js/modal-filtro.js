window.abrirModalFiltro = function abrirModalFiltro() {
    // Intentar obtener el elemento del modal; si no existe, cargamos el parcial.
    let modal = document.getElementById('modal-filtro');

    function showModal() {
        if (!modal) modal = document.getElementById('modal-filtro');
        if (modal) modal.classList.remove('oculto');
    }

    if (!modal) {
        // cargar parcial del modal (una sola vez)
        const base = globalThis.BASE_URL || '';
        // cache-bust para evitar versiones antiguas
        const url = base + 'partials/modal-filtro.html?t=' + Date.now();
        fetch(url)
            .then(r => r.text())
            .then(html => {
                document.body.insertAdjacentHTML('beforeend', html);
                // inicializar listeners del modal
                inicializarModalFiltro();

                // renderizar iconos dentro del modal (después de insertar)
                if (window.feather) feather.replace();

                // mostrar modal
                modal = document.getElementById('modal-filtro');
                if (modal) showModal();
            })
            .catch(err => console.error('Error cargando modal-filtro:', err));
        return;
    }

    // Si ya existe un modal, pero su contenido es la versión antigua (input en lugar de select),
    // recargamos el partial y lo reemplazamos para asegurar que el campo sea un <select>.
    const materiaEl = modal.querySelector('#filtro-materia');
    const isInputOld = materiaEl && materiaEl.tagName && materiaEl.tagName.toLowerCase() === 'input';
    if (isInputOld) {
        const base = globalThis.BASE_URL || '';
        const url = base + 'partials/modal-filtro.html?t=' + Date.now();
        fetch(url)
            .then(r => r.text())
            .then(html => {
                // reemplazar nodo modal antiguo por la versión nueva
                modal.outerHTML = html;
                // reinicializar referencia y listeners
                modal = document.getElementById('modal-filtro');
                inicializarModalFiltro();
                if (window.feather) feather.replace();
                if (modal) showModal();
            })
            .catch(err => {
                console.error('Error recargando modal-filtro:', err);
                showModal();
            });
        return;
    }

    // Si ya existe, asegurarnos de repoblar los tipos por si hubo cambios
    if (typeof window.poblarTiposFiltro === 'function') {
        window.poblarTiposFiltro();
    }
    showModal();
};

function inicializarModalFiltro() {
    const modal = document.getElementById('modal-filtro');
    if (!modal) return;

    // Cerrar modal
    const cerrar = document.getElementById('cerrar-filtro');
    if (cerrar) cerrar.addEventListener('click', () => modal.classList.add('oculto'));

    // Poblar select de materias desde la API
    const materiaSelect = document.getElementById('filtro-materia');
    function poblarMaterias() {
        if (!materiaSelect) return;
        const base = globalThis.BASE_URL || '';
        fetch(base + 'api/materias', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(payload => {
                // La API responde { status: 'success', data: [...] } o directamente un array.
                let list = [];
                if (Array.isArray(payload)) list = payload;
                else if (payload && Array.isArray(payload.data)) list = payload.data;
                else {
                    // nothing to populate
                    materiaSelect.innerHTML = '<option value="">Todas</option>';
                    return;
                }

                // Limpiar exceptuando la primera opción
                materiaSelect.innerHTML = '<option value="">Todas</option>';
                list.forEach(m => {
                    const opt = document.createElement('option');
                    const nombre = (m.nombre || m.nombre_materia || m.nombreMateria || m.title || m.name || '').toString();
                    opt.value = nombre.toLowerCase();
                    opt.textContent = nombre;
                    materiaSelect.appendChild(opt);
                });
            })
            .catch(err => {
                console.error('No se pudieron cargar materias para el filtro:', err);
            });
    }
    poblarMaterias();

    // Poblar select de tipos desde la API
    function poblarTipos() {
        const tipoSelect = document.getElementById('filtro-tipo');
        if (!tipoSelect) {
            console.warn('Select filtro-tipo no encontrado');
            return;
        }

        const base = globalThis.BASE_URL || '';
        const url = base + 'api/tipos-actividad';
        console.log('Cargando tipos desde:', url);

        fetch(url, { credentials: 'same-origin' })
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(payload => {
                console.log('Tipos cargados:', payload);
                let list = [];
                if (Array.isArray(payload)) list = payload;
                else if (payload && Array.isArray(payload.data)) list = payload.data;
                else {
                    tipoSelect.innerHTML = '<option value="">Todos</option>';
                    return;
                }

                tipoSelect.innerHTML = '<option value="">Todos</option>';
                list.forEach(t => {
                    const opt = document.createElement('option');
                    const nombre = (t.nombre || t.nombre_tipo || '').toString();
                    opt.value = nombre.toLowerCase();
                    opt.textContent = nombre;
                    tipoSelect.appendChild(opt);
                });
            })
            .catch(err => {
                console.error('No se pudieron cargar tipos para el filtro:', err);
                // Fallback visual para el usuario
                tipoSelect.innerHTML = '<option value="">Error cargando</option>';
            });
    }
    poblarTipos();
    window.poblarTiposFiltro = poblarTipos;

    // Botón para restablecer todos los filtros a su estado por defecto
    const resetBtn = document.getElementById('reset-filtros');
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            const tipoElR = document.getElementById('filtro-tipo');
            const progresoElR = document.getElementById('filtro-progreso');
            const materiaElR = document.getElementById('filtro-materia');
            const fechaMinElR = document.getElementById('filtro-fecha-min');
            const fechaMaxElR = document.getElementById('filtro-fecha-max');

            if (tipoElR) tipoElR.value = '';
            if (progresoElR) progresoElR.value = '';
            if (materiaElR) materiaElR.value = '';
            if (fechaMinElR) fechaMinElR.value = '';
            if (fechaMaxElR) fechaMaxElR.value = '';
        });
    }

    // Aplicar filtros
    const aplicar = document.getElementById('aplicar-filtros');
    if (!aplicar) return;

    aplicar.addEventListener('click', () => {
        const tipoEl = document.getElementById('filtro-tipo');
        const progresoEl = document.getElementById('filtro-progreso');
        const materiaEl = document.getElementById('filtro-materia');
        const fechaMinEl = document.getElementById('filtro-fecha-min');
        const fechaMaxEl = document.getElementById('filtro-fecha-max');

        const tipo = tipoEl ? (tipoEl.value || '').toLowerCase() : '';
        const progreso = progresoEl ? (progresoEl.value || '').toLowerCase() : '';
        const materia = materiaEl ? (materiaEl.value || '').toLowerCase() : '';
        const fechaMin = fechaMinEl ? fechaMinEl.value : '';
        const fechaMax = fechaMaxEl ? fechaMaxEl.value : '';

        const filas = document.querySelectorAll('#tabla-body tr');
        if (!filas) return;

        let hayCoincidencias = false;
        filas.forEach(fila => {
            const children = fila.children;
            const fecha = children[0] ? children[0].innerText.trim() : '';
            const actividad = children[1] ? children[1].innerText.toLowerCase() : '';
            const materiaFila = children[2] ? children[2].innerText.toLowerCase() : '';
            const tipoFila = children[3] ? children[3].innerText.toLowerCase() : '';
            let progresoFila = children[4] ? children[4].innerText.toLowerCase() : '';
            // Normalizar etiquetas de progreso a los valores usados en el filtro
            if (progresoFila.includes('sin') || progresoFila.includes('sininiciar') || progresoFila.includes('sin iniciar')) progresoFila = 'pendiente';
            if (progresoFila.includes('pendiente')) progresoFila = 'pendiente';
            if (progresoFila.includes('en curso') || progresoFila.includes('encurso')) progresoFila = 'en curso';
            if (progresoFila.includes('listo') || progresoFila.includes('completado')) progresoFila = 'listo';

            let mostrar = true;
            if (tipo && !tipoFila.includes(tipo)) mostrar = false;
            if (progreso && progreso !== '' && progreso !== progresoFila) mostrar = false;
            if (materia && materia !== '' && !materiaFila.includes(materia)) mostrar = false;
            if (fechaMin && fecha < fechaMin) mostrar = false;
            if (fechaMax && fecha > fechaMax) mostrar = false;

            fila.style.display = mostrar ? '' : 'none';
            if (mostrar) hayCoincidencias = true;
        });

        // Delegar la lógica de mostrar/ocultar la tabla al verificador global si existe
        if (typeof verificarTablaVacia === 'function') {
            try { verificarTablaVacia(); } catch (e) { console.error('verificarTablaVacia error:', e); }
        } else {
            // Fallback: mostrar/ocultar tabla y mensaje de coincidencias
            const tabla = document.getElementById('tabla');
            const tablaVacia = document.getElementById('tabla-vacia');
            if (hayCoincidencias) {
                if (tabla) tabla.classList.remove('oculto');
                if (tablaVacia) tablaVacia.classList.add('oculto');
            } else {
                if (tabla) tabla.classList.add('oculto');
                if (tablaVacia) tablaVacia.classList.remove('oculto');
            }
        }

        modal.classList.add('oculto');
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('modal-filtro')) inicializarModalFiltro();
    });
} else {
    if (document.getElementById('modal-filtro')) inicializarModalFiltro();
}