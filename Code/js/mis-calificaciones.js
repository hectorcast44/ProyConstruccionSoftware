/**
 * Página "Mis calificaciones".
 *
 * Responsabilidades:
 *  - Cargar materias y su resumen de puntos desde la API.
 *  - Renderizar una card tipo acordeón por materia.
 *  - Permitir filtrar materias por nombre.
 *  - Redirigir al detalle de una materia al pulsar "Detalles".
 */
document.addEventListener('DOMContentLoaded', () => {

  // =====================================================
  // Estado y referencias al DOM
  // =====================================================

  /** @type {Array<Object>} Lista de materias cargadas desde la API. */
  let materias = [];

  /** @type {HTMLElement|null} Contenedor principal de las cards de materias. */
  const listaCalificaciones = document.getElementById('lista-calificaciones');
  /** @type {HTMLInputElement|null} Input del buscador flotante. */
  const buscadorInput = /** @type {HTMLInputElement|null} */ (document.getElementById('buscador-menu'));
  /** @type {HTMLElement|null} Wrapper del buscador flotante. */
  const buscadorWrapper = document.querySelector('.search-wrapper');
  /** @type {HTMLButtonElement|null} Botón para abrir/cerrar el buscador. */
  const buscadorBtn = /** @type {HTMLButtonElement|null} */ (document.getElementById('search-toggle'));

  // =====================================================
  // Helpers de estilo / tags
  // =====================================================

  /**
   * Obtiene la clase CSS para el tag de un tipo de actividad.
   *
   * @param {Object} tipo Objeto de tipo de actividad.
   * @param {number} [tipo.id_tipo] Identificador de tipo (opcional).
   * @param {number} [tipo.id_tipo_actividad] Id alternativo de tipo.
   * @param {string} [tipo.nombre] Nombre del tipo.
   * @returns {string} Nombre de la clase CSS a aplicar.
   */
  function obtenerTagClassPorTipo(tipo) {
    const key = tipo.id_tipo ?? tipo.id_tipo_actividad ?? tipo.nombre;
    return UIHelpers.TagStyleManager.getClassFor(key);
  }

  // =====================================================
  // Render de filas y cards
  // =====================================================

  /**
   * Genera las filas HTML de la tabla para un conjunto de tipos de actividad.
   *
   * @param {Array<Object>} [tipos=[]] Arreglo de tipos con puntos obtenidos y máximos.
   * @returns {string} Cadena HTML con filas <tr> listas para insertarse en <tbody>.
   */
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

  /**
   * Crea el nodo DOM de una card de acordeón para una materia.
   *
   * @param {Object} materia Objeto de materia a representar.
   * @param {number} materia.id Identificador de la materia.
   * @param {string} materia.nombre Nombre de la materia.
   * @param {Array<Object>} materia.tipos Resumen por tipo de actividad.
   * @returns {HTMLDivElement} Wrapper con la estructura de la card.
   */
  function crearCardMateria(materia) {

    const wrapper = document.createElement('div');
    wrapper.classList.add('accordion-card-wrapper');

    const card = document.createElement('div');
    card.classList.add('accordion-card');
    // Id necesario para la redirección a la página de detalle
    card.dataset.idMateria = String(materia.id);

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

  /**
   * Renderiza la lista de materias en el contenedor principal.
   *
   * @param {Array<Object>} lista Materias a mostrar.
   * @returns {void}
   */
  function renderizarMaterias(lista) {
    if (!listaCalificaciones) return;

    listaCalificaciones.innerHTML = '';

    if (!lista.length) {
      listaCalificaciones.innerHTML = `<p class="texto-vacio">No hay materias para mostrar.</p>`;
      return;
    }

    for (const materia of lista) {
      const card = crearCardMateria(materia);
      listaCalificaciones.appendChild(card);
    }

    if (globalThis.feather) {
      feather.replace();
    }
  }

  // =====================================================
  // Filtro y búsqueda
  // =====================================================

  /**
   * Aplica el filtro de búsqueda y vuelve a pintar las materias.
   *
   * @param {string|Event} valorDesdeSearch Texto del buscador o evento.
   * @returns {void}
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

  // =====================================================
  // Carga de datos desde la API
  // =====================================================

  /**
   * Consulta la API de resumen de calificaciones y
   * actualiza la colección local de materias.
   *
   * @async
   * @returns {Promise<void>}
   */
  async function cargarMateriasDesdeAPI() {

    const url = '../php/api/calificaciones_resumen.php';

    try {
      const resp = await fetch(url, {
        method: 'GET',
        headers: { 'Accept': 'application/json' },
        credentials: 'include'
      });

      const text = await resp.text();

      /** @type {{status:string,data?:Array}|null} */
      let json = null;
      try {
        json = JSON.parse(text);
      } catch {
        console.error('No es JSON válido:', text);
      }

      if (!json?.data) {
        materias = [];
      } else {
        materias = json.data.map(m => ({
          id: m.id ?? m.id_materia ?? 0,
          nombre: m.nombre ?? m.nombre_materia ?? 'Sin nombre',
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
      console.error('Error al cargar materias:', e);
      materias = [];
      renderizarMaterias(materias);
    }
  }

  // =====================================================
  // Eventos de UI (detalle + helpers)
  // =====================================================

  /**
   * Maneja los clics dentro de la lista de calificaciones.
   * Si se hace clic en el botón "Detalles" de una card,
   * redirige a la página de detalle de la materia.
   */
  if (listaCalificaciones) {
    listaCalificaciones.addEventListener('click', e => {
      const btnDetalle = e.target.closest('.js-card-detail');
      if (!btnDetalle) return;

      const card = btnDetalle.closest('.accordion-card');
      const idMateria = card?.dataset.idMateria;
      if (!idMateria) return;

      const destino = `mis-calificaciones-detalle.html?id=${idMateria}`;
      globalThis.location.href = destino;
    });
  }

  // Inicializar helpers visuales para acordeón y barra de búsqueda
  UIHelpers.initAccordionGrid(listaCalificaciones);
  UIHelpers.initSearchBar({
    input: buscadorInput,
    toggleBtn: buscadorBtn,
    wrapper: buscadorWrapper,
    onFilter: filtrarYRenderizar
  });

  // =====================================================
  // Inicio: carga inicial de datos
  // =====================================================

  cargarMateriasDesdeAPI();
});
