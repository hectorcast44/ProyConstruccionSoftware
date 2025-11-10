document.addEventListener('DOMContentLoaded', () => {
  // datos de ejemplo
  const materias = [
    {
      id: 1,
      nombre: 'INFERENCIA',
      tipos: [
        { nombre: 'Tarea', obtenido: 40, maximo: 40 },
        { nombre: 'Proyecto', obtenido: 5, maximo: 10 },
        { nombre: 'Examen', obtenido: 25, maximo: 50 }
      ]
    },
    {
      id: 2,
      nombre: 'PROGRAMACIÓN WEB',
      tipos: [
        { nombre: 'Tarea', obtenido: 10, maximo: 10 },
        { nombre: 'Práctica', obtenido: 15, maximo: 20 },
        { nombre: 'Proyecto', obtenido: 30, maximo: 30 },
        { nombre: 'Examen', obtenido: 15, maximo: 40 }
      ]
    },
    {
      id: 3,
      nombre: 'BASES DE DATOS',
      tipos: [
        { nombre: 'Tarea', obtenido: 8, maximo: 10 },
        { nombre: 'Exposición', obtenido: 10, maximo: 10 },
        { nombre: 'Proyecto', obtenido: 12, maximo: 20 },
        { nombre: 'Examen parcial', obtenido: 20, maximo: 30 },
        { nombre: 'Examen final', obtenido: 0, maximo: 30 }
      ]
    }
  ];

  const listaUsuariosDiv = document.getElementById('lista-usuarios');
  const buscadorInput = document.getElementById('buscador-menu');
  const buscadorWrapper = document.querySelector('.search-wrapper');
  const buscadorBtn = document.getElementById('search-toggle');

  function generarFilasTipos(tipos = []) {
    if (!tipos.length) {
      return `
        <tr>
          <td colspan="2" class="right">Sin registros</td>
        </tr>
      `;
    }

    return tipos.map(tipo => {
      const tl = tipo.nombre.toLowerCase();
      let tagClass = '';
      if (tl.includes('tarea')) tagClass = 'tag-tarea';
      else if (tl.includes('proyecto')) tagClass = 'tag-proyecto';
      else if (tl.includes('examen')) tagClass = 'tag-examen';
      else tagClass = 'tag-otro';

      return `
        <tr>
          <td><span class="tag ${tagClass}">${tipo.nombre}</span></td>
          <td class="right">${tipo.obtenido} / ${tipo.maximo}</td>
        </tr>
      `;
    }).join('');
  }

  function crearBloqueMateria(materia) {
    // shell = elemento que recibe la sombra
    const shell = document.createElement('div');
    shell.classList.add('item-shell');

    // bloque (card + detalle)
    const bloque = document.createElement('div');
    bloque.classList.add('item-block');

    // card morada
    const card = document.createElement('div');
    card.classList.add('usuario-card');
    card.innerHTML = `
      <div class="contenedor-nombre-icono">
        <span class="icono-usuario-card">
          <i data-feather="book-open"></i>
        </span>
        <h3 class="nombre-usuario-card">${materia.nombre}</h3>
      </div>
    `;

    // panel blanco con la tabla
    const panel = document.createElement('div');
    panel.classList.add('item-detail');
    panel.innerHTML = `
      <div class="item-table-wrapper">
        <table class="item-table">
          <thead>
            <tr>
              <th>Tipo</th>
              <th>Valor</th>
            </tr>
          </thead>
          <tbody>
            ${generarFilasTipos(materia.tipos)}
          </tbody>
        </table>
      </div>
    `;

    // juntar
    bloque.appendChild(card);
    bloque.appendChild(panel);
    shell.appendChild(bloque);
    return shell;
  }

  function renderizarMaterias(lista) {
    listaUsuariosDiv.innerHTML = '';
    lista.forEach(m => {
      const bloque = crearBloqueMateria(m);
      listaUsuariosDiv.appendChild(bloque);
    });

    // volver a dibujar íconos
    if (window.feather) feather.replace();
  }

  function filtrarYRenderizar() {
    const termino = (buscadorInput?.value || '').toLowerCase();
    const filtradas = materias.filter(m =>
      m.nombre.toLowerCase().includes(termino)
    );
    renderizarMaterias(filtradas);
  }

  // click sobre la card para abrir/cerrar
  listaUsuariosDiv.addEventListener('click', e => {
    const card = e.target.closest('.usuario-card');
    if (!card) return;
    const bloque = card.parentElement; // .item-block
    bloque.classList.toggle('open');
  });

  // buscador
  if (buscadorInput) {
    buscadorInput.addEventListener('input', filtrarYRenderizar);
  }

  if (buscadorBtn && buscadorWrapper) {
    buscadorBtn.addEventListener('click', () => {
      buscadorWrapper.classList.toggle('active');
      if (buscadorWrapper.classList.contains('active')) {
        buscadorInput.focus();
      } else {
        buscadorInput.value = '';
        filtrarYRenderizar();
      }
    });
  }

  // primera carga
  renderizarMaterias(materias);
});
