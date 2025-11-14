document.addEventListener('DOMContentLoaded', () => {
  const secciones = [
    {
      id: 'tarea',
      nombre: 'Tareas',
      actividades: [
        { nombre: 'ADA1', obtenido: 20, maximo: 20 },
        { nombre: 'ADA2', obtenido: 20, maximo: 20 }
      ]
    },
    {
      id: 'examen',
      nombre: 'Exámenes',
      actividades: [
        { nombre: 'PD1', obtenido: 15, maximo: 30 },
        { nombre: 'PD2', obtenido: 0, maximo: 30 }
      ]
    }
  ];

  const contenedor = document.getElementById('lista-usuarios');
  const buscadorInput = document.getElementById('buscador-menu');
  const buscadorWrapper = document.querySelector('.search-wrapper');
  const buscadorBtn = document.getElementById('search-toggle');

  if (!contenedor) return;

  // genera filas de la tabla usando el tema común (table-theme.css)
  function filasTabla(actividades = []) {
    if (!actividades.length) {
      return `
        <tr>
          <td colspan="2" class="right">Sin registros</td>
        </tr>
      `;
    }

    return actividades.map(a => `
      <tr>
        <td>${a.nombre}</td>
        <td class="right">${a.obtenido} / ${a.maximo}</td>
      </tr>
    `).join('');
  }

  // crea un bloque tipo card + acordeón para cada sección (Tareas, Exámenes, etc.)
  function crearBloqueSeccion(sec) {
    // envoltura externa (recibe la sombra si quieres en CSS)
    const shell = document.createElement('div');
    shell.className = 'item-shell';

    // bloque card + detalle
    const block = document.createElement('div');
    block.className = 'item-block';
    block.dataset.sectionId = sec.id;

    // header morado (usa .usuario-card para que tome el estilo del CSS de cards)
    const header = document.createElement('div');
    header.className = 'usuario-card';
    header.innerHTML = `
      <div class="contenedor-nombre-icono">
        <span class="icono-usuario-card">
          <i data-feather="layers"></i>
        </span>
        <h3 class="nombre-usuario-card">${sec.nombre}</h3>
      </div>
    `;

    // panel blanco (acordeón) con la tabla usando table-theme.css
    const detail = document.createElement('div');
    detail.className = 'item-detail';
    detail.innerHTML = `
      <div class="item-table-wrapper">
        <table class="item-table">
          <thead>
            <tr>
              <th>Actividad</th>
              <th>Puntuación</th>
            </tr>
          </thead>
          <tbody>
            ${filasTabla(sec.actividades)}
          </tbody>
        </table>
      </div>
    `;

    block.appendChild(header);
    block.appendChild(detail);
    shell.appendChild(block);
    return shell;
  }

  function render(seccionesMostrar) {
    contenedor.innerHTML = '';

    seccionesMostrar.forEach(sec => {
      const bloque = crearBloqueSeccion(sec);
      contenedor.appendChild(bloque);
    });

    // volver a dibujar íconos feather
    if (window.feather) feather.replace();
  }

  // abrir/cerrar con clic en la card morada (header)
  contenedor.addEventListener('click', e => {
    const header = e.target.closest('.usuario-card');
    if (!header) return;

    const block = header.closest('.item-block');
    if (!block) return;

    block.classList.toggle('open');
  });

  // filtro por texto (filtra actividades por nombre, pero mantiene las secciones)
  function filtrar() {
    const term = (buscadorInput?.value || '').toLowerCase();

    const filtradas = secciones.map(sec => {
      const acts = sec.actividades.filter(a =>
        a.nombre.toLowerCase().includes(term)
      );
      return { ...sec, actividades: acts };
    });

    render(filtradas);
  }

  // eventos del buscador
  if (buscadorInput) {
    buscadorInput.addEventListener('input', filtrar);
  }

  if (buscadorBtn && buscadorWrapper) {
    buscadorBtn.addEventListener('click', () => {
      buscadorWrapper.classList.toggle('active');
      if (buscadorWrapper.classList.contains('active')) {
        buscadorInput.focus();
      } else {
        buscadorInput.value = '';
        render(secciones);
      }
    });
  }

  // primera renderización
  render(secciones);
});
