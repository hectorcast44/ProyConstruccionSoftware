window.abrirModalFiltro = function abrirModalFiltro() {
    let modal = document.getElementById('modal-filtro');

    function showModal() {
        modal.classList.remove('oculto');
    }

    if (!modal) {
        // cargar parcial del modal (una sola vez)
        fetch('../partials/modal-filtro.html')
            .then(r => r.text())
            .then(html => {
                document.body.insertAdjacentHTML('beforeend', html);

                // renderizar iconos dentro del modal
                if (window.feather) feather.replace();

                // inicializar listeners del modal
                inicializarModalFiltro();

                // mostrar modal
                modal = document.getElementById('modal-filtro');
                if (modal) showModal();
            })
            .catch(err => console.error('Error cargando modal-filtro:', err));
        return;
    }

    showModal();
};

function inicializarModalFiltro() {
    const modal = document.getElementById('modal-filtro');
    if (!modal) return;

    // Cerrar modal
    const cerrar = document.getElementById('cerrar-filtro');
    if (cerrar) cerrar.addEventListener('click', () => modal.classList.add('oculto'));

    // Aplicar filtros
    const aplicar = document.getElementById('aplicar-filtros');
    if (!aplicar) return;

    aplicar.addEventListener('click', () => {
        const tipoEl = document.getElementById('filtro-tipo');
        const progresoEl = document.getElementById('filtro-progreso');
        const materiaEl = document.getElementById('filtro-materia');
        const fechaMinEl = document.getElementById('filtro-fecha-min');
        const fechaMaxEl = document.getElementById('filtro-fecha-max');

        const tipo = tipoEl ? tipoEl.value.toLowerCase() : '';
        const progreso = progresoEl ? progresoEl.value.toLowerCase() : '';
        const materia = materiaEl ? materiaEl.value.toLowerCase() : '';
        const fechaMin = fechaMinEl ? fechaMinEl.value : '';
        const fechaMax = fechaMaxEl ? fechaMaxEl.value : '';

        const filas = document.querySelectorAll('#tabla-body tr');
        if (!filas) return;

        filas.forEach(fila => {
            const children = fila.children;
            const fecha = children[0] ? children[0].innerText.trim() : '';
            const actividad = children[1] ? children[1].innerText.toLowerCase() : '';
            const materiaFila = children[2] ? children[2].innerText.toLowerCase() : '';
            const tipoFila = children[3] ? children[3].innerText.toLowerCase() : '';
            const progresoFila = children[4] ? children[4].innerText.toLowerCase() : '';

            let mostrar = true;
            if (tipo && !tipoFila.includes(tipo)) mostrar = false;
            if (progreso && !progresoFila.includes(progreso)) mostrar = false;
            if (materia && !materiaFila.includes(materia)) mostrar = false;
            if (fechaMin && fecha < fechaMin) mostrar = false;
            if (fechaMax && fecha > fechaMax) mostrar = false;

            fila.style.display = mostrar ? '' : 'none';
        });

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