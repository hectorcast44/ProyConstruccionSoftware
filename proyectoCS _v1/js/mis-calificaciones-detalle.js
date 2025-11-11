document.addEventListener('DOMContentLoaded', () => {
  // aquí ya son DOS bloques
  const secciones = [
    {
      id: 'tarea',
      nombre: 'Tarea',
      actividades: [
        { nombre: 'ADA1', obtenido: 20, maximo: 20 },
        { nombre: 'ADA2', obtenido: 20, maximo: 20 }
      ]
    },
    {
      id: 'examen',
      nombre: 'Examen',
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

  function render(seccionesMostrar) {
    contenedor.innerHTML = '';

    seccionesMostrar.forEach(sec => {
      const block = document.createElement('div');
      block.className = 'item-block';
      block.dataset.sectionId = sec.id;

      block.innerHTML = `
        <div class="item-header">
          <span>${sec.nombre}</span>
        </div>
        <div class="item-detail">
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
        </div>
      `;

      contenedor.appendChild(block);
    });

    // íconos (por si quieres poner alguno)
    if (window.feather) feather.replace();
  }

  // abrir/cerrar con clic en el header
  contenedor.addEventListener('click', e => {
    const header = e.target.closest('.item-header');
    if (!header) return;
    const block = header.parentElement;
    block.classList.toggle('open');
  });

  // filtro por texto (busca en nombre de actividad)
  function filtrar() {
    const term = (buscadorInput?.value || '').toLowerCase();

    // mapeamos cada sección filtrando sus actividades
    const filtradas = secciones.map(sec => {
      const acts = sec.actividades.filter(a =>
        a.nombre.toLowerCase().includes(term)
      );
      return { ...sec, actividades: acts };
    });

    render(filtradas);
  }

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

  // primera pinta
  render(secciones);
});
