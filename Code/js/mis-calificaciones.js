document.addEventListener('DOMContentLoaded', () => {

  let materias = [];

  const listaCalificaciones = document.getElementById('lista-calificaciones');
  const buscadorInput = document.getElementById('buscador-menu');
  const buscadorWrapper = document.querySelector('.search-wrapper');
  const buscadorBtn = document.getElementById('search-toggle');

  function obtenerTagClassPorTipo(tipo) {
    const key = tipo.id_tipo ?? tipo.id_tipo_actividad ?? tipo.nombre;
    return UIHelpers.TagStyleManager.getClassFor(key);
  }

  function generarFilasTipos(tipos = []) {
    if (!tipos.length) {
      return `
        <tr>
          <td colspan="2" class="right">Sin registros</td>
        </tr>
      `;
    }

    return tipos.map(tipo => {
      const tagClass = obtenerTagClassPorTipo(tipo);
      const obtenido = Number(tipo.obtenido ?? 0);
      const maximo   = Number(tipo.maximo   ?? 0);

      return `
        <tr>
          <td><span class="tag ${tagClass}">${tipo.nombre}</span></td>
          <td class="right">${obtenido} / ${maximo}</td>
        </tr>
      `;
    }).join('');
  }

  function crearCardMateria(materia) {

    const wrapper = document.createElement('div');
    wrapper.classList.add('accordion-card-wrapper');

    const card = document.createElement('div');
    card.classList.add('accordion-card');
    card.dataset.idMateria = materia.id;   // Necesario para la redirección

    const header = document.createElement('div');
    header.classList.add('accordion-card__header');
    header.innerHTML = `
      <div class="accordion-card__header-main">
        <span class="accordion-card__icon"><i data-feather="book-open"></i></span>
        <h3 class="accordion-card__title">${materia.nombre}</h3>
      </div>

      <div class="accordion-card__actions">
        <button class="accordion-card__menu-toggle" type="button" aria-label="Más opciones">
          <i data-feather="more-vertical"></i>
        </button>

        <div class="accordion-card__menu">
          <button class="accordion-card__menu-item js-card-detail" type="button">Detalles</button>
        </div>
      </div>
    `;

    const panel = document.createElement('div');
    panel.classList.add('accordion-card__panel');
    panel.innerHTML = `
      <div class="item-table-wrapper">
        <table class="item-table">
          <thead>
            <tr>
              <th>Tipo</th>
              <th class="right">Puntos</th>
            </tr>
          </thead>
          <tbody>
            ${generarFilasTipos(materia.tipos)}
          </tbody>
        </table>
      </div>
    `;

    card.appendChild(header);
    card.appendChild(panel);
    wrapper.appendChild(card);

    return wrapper;
  }

  function renderizarMaterias(lista) {
    if (!listaCalificaciones) return;

    listaCalificaciones.innerHTML = '';

    if (!lista.length) {
      listaCalificaciones.innerHTML = `<p class="texto-vacio">No hay materias para mostrar.</p>`;
      return;
    }

    lista.forEach(m => {
      const card = crearCardMateria(m);
      listaCalificaciones.appendChild(card);
    });

    if (window.feather) feather.replace();
  }

  function filtrarYRenderizar(valorDesdeSearch) {
    let fuente = '';

    if (typeof valorDesdeSearch === 'string') {
      fuente = valorDesdeSearch;
    } else if (buscadorInput) {
      fuente = buscadorInput.value || '';
    }

    const termino = fuente.toLowerCase().trim();

    const filtradas = materias.filter(m =>
      (m.nombre || '').toLowerCase().includes(termino)
    );

    renderizarMaterias(filtradas);
  }

  // ================================
  // LÓGICA DE REDIRECCIÓN A DETALLE
  // ================================

  if (listaCalificaciones) {
    listaCalificaciones.addEventListener('click', e => {

      const btnDetalle = e.target.closest('.js-card-detail');
      if (btnDetalle) {
        const card = btnDetalle.closest('.accordion-card');
        const idMateria = card.dataset.idMateria;

        if (idMateria) {
          // Construye la URL para la página de detalle
          const destino = `mis-calificaciones-detalle.html?id=${idMateria}`;
          window.location.href = destino;
        }
        return;
      }

    });
  }


  // ---------------------------------------
  // Cargar datos desde API PHP
  // ---------------------------------------
  async function cargarMateriasDesdeAPI() {

    const url = '../php/api/calificaciones_resumen.php';

    try {
      const resp = await fetch(url, {
        method: 'GET',
        headers: { 'Accept': 'application/json' },
        credentials: 'include'
      });

      const text = await resp.text();

      let json = null;
      try { json = JSON.parse(text); }
      catch { console.error("No es JSON válido:", text); }

      if (!json || !json.data) {
        materias = [];
      } else {
        materias = json.data.map(m => ({
          id: m.id ?? m.id_materia ?? 0,
          nombre: m.nombre ?? m.nombre_materia ?? "Sin nombre",
          tipos: (m.tipos || []).map(t => ({
            id_tipo: t.id_tipo ?? t.id_tipo_actividad,
            nombre: t.nombre ?? t.nombre_tipo,
            obtenido: Number(t.obtenido ?? t.puntos_obtenidos ?? 0),
            maximo: Number(t.maximo ?? t.puntos_posibles ?? 0)
          }))
        }));
      }

      filtrarYRenderizar('');

    } catch (e) {
      console.error("Error al cargar materias:", e);
      materias = [];
      renderizarMaterias(materias);
    }
  }

  // Inicializar helpers
  UIHelpers.initAccordionGrid(listaCalificaciones);
  UIHelpers.initSearchBar({
    input: buscadorInput,
    toggleBtn: buscadorBtn,
    wrapper: buscadorWrapper,
    onFilter: filtrarYRenderizar
  });

  cargarMateriasDesdeAPI();
});
