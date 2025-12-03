/**
 * Página "Mis calificaciones".
 *
 * Responsabilidades:
 *  - Cargar materias y su resumen de puntos desde la API.
 *  - Renderizar una card tipo acordeón por materia.
 *  - Permitir filtrar materias por nombre.
 *  - Redirigir al detalle de una materia al pulsar "Detalles".
 */


// =====================================================
// Helpers
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

function normalizar(str) {
  return (str || '')
    .normalize("NFD")
    .replaceAll(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .trim();
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
    const maximo = Number(tipo.maximo ?? 0);

    return `
        <tr>
          <td><span class="tag ${tagClass}">${tipo.nombre}</span></td>
          <td class="right">${obtenido} / ${maximo}</td>
        </tr>
      `;
  }).join('');
}


let materias = [];

function crearCardMateria(materia) {
  const wrapper = document.createElement('div');
  wrapper.classList.add('accordion-card-wrapper');

  const card = document.createElement('div');
  card.classList.add('accordion-card');
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

function renderizarMaterias(lista) {
  const listaCalificaciones = document.getElementById('lista-calificaciones');
  if (!listaCalificaciones) return;

  listaCalificaciones.innerHTML = '';

  if (!lista.length) {
    listaCalificaciones.innerHTML = `
      <div id="mensaje-vacio" class="oculto">
          <h3>Aún no hay materias por mostrar.</h3>
      </div>
    `;
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

function filtrarYRenderizar(valorDesdeSearch) {
  const buscadorInput = document.getElementById('buscador-menu');
  let fuente = "";

  if (typeof valorDesdeSearch === "string") {
    fuente = valorDesdeSearch;
  } else if (buscadorInput) {
    fuente = buscadorInput.value || "";
  }
  const termino = normalizar(fuente);

  const filtradas = materias.filter(m => {
    const nombreMateria = normalizar(m.nombre || "");
    return nombreMateria.includes(termino);
  });

  renderizarMaterias(filtradas);
}

async function cargarMateriasDesdeAPI() {
  const url = (globalThis.BASE_URL || '') + 'api/materias';

  try {
    const resp = await fetch(url, {
      method: 'GET',
      headers: { 'Accept': 'application/json' },
      credentials: 'include'
    });

    const text = await resp.text();

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

function initMisCalificaciones() {
  const listaCalificaciones = document.getElementById('lista-calificaciones');
  const buscadorInput = document.getElementById('buscador-menu');
  const buscadorWrapper = document.querySelector('.search-wrapper');
  const buscadorBtn = document.getElementById('search-toggle');

  if (listaCalificaciones) {
    listaCalificaciones.addEventListener('click', e => {
      const btnDetalle = e.target.closest('.js-card-detail');
      if (!btnDetalle) return;

      const card = btnDetalle.closest('.accordion-card');
      const idMateria = card?.dataset.idMateria;
      if (!idMateria) return;

      const destino = `mis-calificaciones/detalle?id_materia=${idMateria}`;
      globalThis.location.href = destino;
    });
  }

  if (globalThis.UIHelpers) {
    UIHelpers.initAccordionGrid(listaCalificaciones);
    UIHelpers.initSearchBar({
      input: buscadorInput,
      toggleBtn: buscadorBtn,
      wrapper: buscadorWrapper,
      onFilter: filtrarYRenderizar
    });
  }

  cargarMateriasDesdeAPI();
}

document.addEventListener('DOMContentLoaded', initMisCalificaciones);

if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    initMisCalificaciones,
    obtenerTagClassPorTipo,
    generarFilasTipos,
    crearCardMateria,
    renderizarMaterias,
    filtrarYRenderizar,
    cargarMateriasDesdeAPI
  };
}


