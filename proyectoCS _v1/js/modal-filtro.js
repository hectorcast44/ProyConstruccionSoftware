
// Abrir modal
function abrirModalFiltro() {
    const modal = document.getElementById("filtro-modal");
    document.getElementById("filter-button").addEventListener("click", () => {
    modal.classList.remove("oculto");
    });
}


// Cerrar modal
document.getElementById("cerrar-filtro").addEventListener("click", () => {
modal.classList.add("oculto");
});


// Lógica de filtrado
document.getElementById("aplicar-filtros").addEventListener("click", () => {
const tipo = document.getElementById("filtro-tipo").value.toLowerCase();
const progreso = document.getElementById("filtro-progreso").value.toLowerCase();
const materia = document.getElementById("filtro-materia").value.toLowerCase();
const fechaMin = document.getElementById("filtro-fecha-min").value;
const fechaMax = document.getElementById("filtro-fecha-max").value;


const filas = document.querySelectorAll("#tabla-body tr");


filas.forEach(fila => {
const fecha = fila.children[0].innerText.trim();
const actividad = fila.children[1].innerText.toLowerCase();
const materiaFila = fila.children[2].innerText.toLowerCase();
const tipoFila = fila.children[3].innerText.toLowerCase();
const progresoFila = fila.children[4].innerText.toLowerCase();


let mostrar = true;


if (tipo && !tipoFila.includes(tipo)) mostrar = false;
if (progreso && !progresoFila.includes(progreso)) mostrar = false;
if (materia && !materiaFila.includes(materia)) mostrar = false;
if (fechaMin && fecha < fechaMin) mostrar = false;
if (fechaMax && fecha > fechaMax) mostrar = false;


fila.style.display = mostrar ? "" : "none";
});


modal.classList.add("oculto");
});
