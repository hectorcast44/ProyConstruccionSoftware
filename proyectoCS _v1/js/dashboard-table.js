const btnNuevo = document.getElementById('btn-nuevo');
const popup = document.getElementById('popup');
const cerrar = document.getElementById('cerrar');
const form = document.getElementById('form');
const tbody = document.getElementById('tbody');


btnNuevo.addEventListener('click', () => popup.classList.remove('hidden'));
cerrar.addEventListener('click', () => popup.classList.add('hidden'));


form.addEventListener('submit', (e) => {
    e.preventDefault();


    const fecha = document.getElementById('fecha').value;
    const actividad = document.getElementById('actividad').value;
    const materia = document.getElementById('materia').value;
    const tipo = document.getElementById('tipo').value;
    const progreso = document.getElementById('progreso').value;


    const tr = document.createElement('tr');
    tr.innerHTML = `
    <td class="border p-2">${fecha}</td>
    <td class="border p-2">${actividad}</td>
    <td class="border p-2">${materia}</td>
    <td class="border p-2">${tipo}</td>
    <td class="border p-2">${progreso}</td>
    `;


    tbody.appendChild(tr);
    popup.classList.add('hidden');
    form.reset();
});