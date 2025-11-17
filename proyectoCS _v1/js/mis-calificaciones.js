// js/mis-calificaciones.js 
document.addEventListener('DOMContentLoaded', () => {
  // ---------------------------------------------------------------------------
  // Estado
  // ---------------------------------------------------------------------------
  /**
   * Estructura esperada de cada materia:
   * {
   *   id: number,
   *   nombre: string,
   *   tipos: [
   *     { id_tipo?: number, nombre: string, obtenido: number, maximo: number }
   *   ]
   * }
   */
  let materias = [];

  // ---------------------------------------------------------------------------
  // Referencias al DOM
  // ---------------------------------------------------------------------------
  const listaCalificaciones = document.getElementById('lista-calificaciones');
  const buscadorInput = document.getElementById('buscador-menu');
  const buscadorWrapper = document.querySelector('.search-wrapper');
  const buscadorBtn = document.getElementById('search-toggle');

  // ---------------------------------------------------------------------------
  // Helpers de UI (render)
  // ---------------------------------------------------------------------------

  /**
   * Obtiene la clase CSS de tag para un tipo de actividad,
   * usando el TagStyleManager para que el color sea consistente.
   * @param {{id_tipo?:number, id_tipo_actividad?:number, nombre:string}} tipo
   * @returns {string}
   */
  function obtenerTagClassPorTipo(tipo) {
    const key = tipo.id_tipo ?? tipo.id_tipo_actividad ?? tipo.nombre;
    return UIHelpers.TagStyleManager.getClassFor(key);
  }

  /**
   * Genera las filas <tr> de la tabla interna para los tipos de actividad.
   * @param {Array<{id_tipo?:number, nombre:string, obtenido:number, maximo:number}>} tipos
   * @returns {string} HTML
   */
  function generarFilasTipos(tipos = []) {
    if (!tipos || !tipos.length) {
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
          <td>
            <span class="tag ${tagClass}">${tipo.nombre}</span>
          </td>
          <td class="right">${obtenido} / ${maximo}</td>
        </tr>
      `;
    }).join('');
  }

  /**
   * Crea la card (acordeón) de una materia.
   * @param {{id:number, nombre:string, tipos:Array}} materia
   * @returns {HTMLDivElement}
   */
  function crearCardMateria(materia) {
    const wrapper = document.createElement('div');
    wrapper.classList.add('accordion-card-wrapper');

    const card = document.createElement('div');
    card.classList.add('accordion-card');

    const header = document.createElement('div');
    header.classList.add('accordion-card__header');
    header.innerHTML = `
      <div class="accordion-card__header-main">
        <span class="accordion-card__icon">
          <i data-feather="book-open"></i>
        </span>
        <h3 class="accordion-card__title">${materia.nombre}</h3>
      </div>
      <div class="accordion-card__actions">
        <button
          class="accordion-card__menu-toggle"
          type="button"
          aria-label="Más opciones"
        >
          <i data-feather="more-vertical"></i>
        </button>
        <div class="accordion-card__menu">
          <button
            class="accordion-card__menu-item js-card-detail"
            type="button"
          >
            Detalles
          </button>
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

  /**
   * Pinta la lista de materias en el grid (#lista-calificaciones).
   * @param {Array} lista
   */
  function renderizarMaterias(lista) {
    if (!listaCalificaciones) return;

    listaCalificaciones.innerHTML = '';

    if (!lista || !lista.length) {
      listaCalificaciones.innerHTML = `
        <p class="texto-vacio">No hay materias para mostrar.</p>
      `;
      return;
    }

    lista.forEach(m => {
      const card = crearCardMateria(m);
      listaCalificaciones.appendChild(card);
    });

    if (window.feather) feather.replace();
  }

  /**
   * Aplica el filtro del buscador sobre el arreglo de materias
   * y vuelve a renderizar.
   * @param {string} [valorDesdeSearch]  Texto introducido en el buscador
   */
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


  // ---------------------------------------------------------------------------
  // Carga de datos desde la API (vía Apache / Open Server)
  // ---------------------------------------------------------------------------

  async function cargarMateriasDesdeAPI() {
    const url = '../php/api/calificaciones_resumen.php';

    try {
      console.log('Llamando a:', url);

      const resp = await fetch(url, {
        method: 'GET',
        headers: {
          'Accept': 'application/json'
        },
        credentials: 'include'
      });

      const raw = await resp.text();
      console.log('Respuesta cruda de la API:', raw);

      let json = null;
      try {
        json = JSON.parse(raw);
      } catch (e) {
        console.error('No se pudo parsear JSON:', e);
      }

      console.log('JSON parseado:', json);

      if (!resp.ok) {
        console.error('HTTP no OK:', resp.status, resp.statusText);
        materias = [];
        renderizarMaterias(materias);
        return;
      }

      let listaMaterias = [];

      if (json && Array.isArray(json.data)) {
        listaMaterias = json.data;
      } else if (Array.isArray(json)) {
        listaMaterias = json;
      } else {
        console.error('Formato de respuesta no esperado. Se esperaba array o data[]');
        materias = [];
        renderizarMaterias(materias);
        return;
      }

      materias = listaMaterias.map(m => ({
        id: m.id ?? m.id_materia ?? 0,
        nombre: m.nombre ?? m.nombre_materia ?? 'Sin nombre',
        tipos: (m.tipos || []).map(t => ({
          id_tipo:   t.id_tipo ?? t.id_tipo_actividad ?? undefined,
          nombre:    t.nombre ?? t.nombre_tipo ?? 'Sin tipo',
          obtenido:  Number(t.obtenido ?? t.puntos_obtenidos ?? 0),
          maximo:    Number(t.maximo   ?? t.puntos_posibles  ?? 0)
        }))
      }));

      filtrarYRenderizar('');

    } catch (error) {
      console.error('Error de red al cargar materias:', error);
      materias = [];
      renderizarMaterias(materias);
    }
  }

  // ---------------------------------------------------------------------------
  // Inicializar helpers de UI
  // ---------------------------------------------------------------------------

  UIHelpers.initAccordionGrid(listaCalificaciones);

  UIHelpers.initSearchBar({
    input: buscadorInput,
    toggleBtn: buscadorBtn,
    wrapper: buscadorWrapper,
    onFilter: (texto) => {
      filtrarYRenderizar(texto);
    }
  });

  // ---------------------------------------------------------------------------
  // Primera carga
  // ---------------------------------------------------------------------------
  cargarMateriasDesdeAPI();
});
