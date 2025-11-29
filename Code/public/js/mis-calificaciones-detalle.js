/**
 * Página "Mis calificaciones – Detalle".
 *
 * Responsabilidades:
 *  - Leer el identificador de la materia desde la URL (?id_materia= o ?id=).
 *  - Consultar la API para obtener: datos de la materia, actividades y métricas de progreso.
 *  - Renderizar la información en bloques tipo acordeón, actualizando tabla e informe.
 *  - Mostrar diagnóstico visual (círculo, grado, estado) según progreso calculado.
 *  - Permitir filtrar actividades por nombre mediante el buscador.
 *  - Controlar la interacción del usuario con el acordeón (abrir/cerrar secciones).
 */

/**
 * Genera el HTML de las filas de una tabla para la lista de actividades.
 *
 * @param {Array<{nombre: string, obtenido: number, maximo: number}>} actividades
 *        Lista de actividades ya normalizadas.
 * @returns {string} HTML de las filas <tr> a insertar en el <tbody>.
 */
function filasTabla(actividades = []) {
  if (!actividades.length) {
    return `
      <tr>
        <td colspan="2" class="right">Sin registros</td>
      </tr>
    `;
  }

  let html = '';

  for (const actividad of actividades) {
    const obtenido = actividad.obtenido;
    const maximo = actividad.maximo;

    const textoPuntos = obtenido === null
      ? `— / ${maximo}`        // pendiente
      : `${obtenido} / ${maximo}`;  // calificada

    html += `
      <tr>
        <td>${actividad.nombre}</td>
        <td class="right">${textoPuntos}</td>
      </tr>
    `;
  }

  return html;
}


/**
 * Calcula el nivel y el estado del diagnóstico a partir del porcentaje
 * obtenido y la calificación mínima requerida.
 * @param {number} porcentajeObtenido Porcentaje actual obtenido en la materia.
 * @param {number} calificacionMinima Calificación mínima para aprobar.
 * @param {{nivel?: string, estado?: string, grado?: number}} diagnostico Diagnóstico opcional desde backend.
 * @returns {{nivel: string, estado: string, grado: number}} Datos normalizados para la UI.
 */
function determinarNivelDiagnostico(porcentajeObtenido, calificacionMinima, diagnostico = {}) {
  let nivel = diagnostico.nivel;
  let estado = diagnostico.estado ?? '—';
  const grado = Number(diagnostico.grado ?? porcentajeObtenido);

  if (!nivel) {
    if (porcentajeObtenido >= calificacionMinima) {
      nivel = 'ok';
      estado = 'Aprobado';
    } else if (porcentajeObtenido < calificacionMinima - 10) {
      nivel = 'fail';
      estado = 'Reprobado';
    } else {
      nivel = 'risk';
      estado = 'En riesgo';
    }
  }

  return { nivel, estado, grado };
}

/**
 * Aplica las clases visuales correspondientes al nivel de diagnóstico
 * en el círculo y en la etiqueta de estado.
 *
 * @param {HTMLElement} diagCircle Elemento que muestra el círculo de diagnóstico.
 * @param {HTMLElement} diagStatus Elemento que muestra el texto del estado.
 * @param {string} nivel Nivel final: "ok" | "risk" | "fail".
 */
function aplicarClasesDiagnostico(diagCircle, diagStatus, nivel) {
  diagCircle.classList.remove(
    'diagnosis-circle--ok',
    'diagnosis-circle--warn',
    'diagnosis-circle--fail'
  );
  diagStatus.classList.remove(
    'diag-status--ok',
    'diag-status--risk',
    'diag-status--fail'
  );

  if (nivel === 'ok') {
    diagCircle.classList.add('diagnosis-circle--ok');
    diagStatus.classList.add('diag-status--ok');
  } else if (nivel === 'fail') {
    diagCircle.classList.add('diagnosis-circle--fail');
    diagStatus.classList.add('diag-status--fail');
  } else {
    diagCircle.classList.add('diagnosis-circle--warn');
    diagStatus.classList.add('diag-status--risk');
  }
}

/**
 * Script principal para la página mis-calificaciones-detalle
 */
document.addEventListener('DOMContentLoaded', () => {
  // ------------------------------------------------------
  // Obtener id_materia desde la URL
  // ------------------------------------------------------
  const params = new URLSearchParams(globalThis.location.search);
  const idMateria = params.get('id_materia') || params.get('id');

  if (!idMateria) {
    console.error('id_materia no presente en la URL');
    return;
  }

  // ------------------------------------------------------
  // Referencias al DOM
  // ------------------------------------------------------
  const contenedorSecciones = document.getElementById('lista-usuarios');
  const buscadorInput = document.getElementById('buscador-menu');
  const buscadorWrapper = document.querySelector('.search-wrapper');
  const buscadorBtn = document.getElementById('search-toggle');

  const tituloPagina = document.querySelector('.page-title h1');

  // Filas del informe
  const rowPorc = document.querySelector('[data-field="porcentaje-obtenido"] td.right');
  const rowObt = document.querySelector('[data-field="puntos-obtenidos"] td.right');
  const rowPerd = document.querySelector('[data-field="puntos-perdidos"] td.right');
  const rowPosi = document.querySelector('[data-field="puntos-posibles"] td.right');
  const rowNec = document.querySelector('[data-field="puntos-necesarios"] td.right');
  const rowMin = document.querySelector('[data-field="calificacion-minima"] td.right');
  const rowMax = document.querySelector('[data-field="calificacion-maxima"] td.right');

  // Elementos de diagnóstico
  const diagCircle = document.querySelector('.diagnosis-circle');
  const diagGrade = document.querySelector('.diag-grade');
  const diagStatus = document.querySelector('.diag-status');

  /** @type {Array<{id:number, nombre:string, actividades:Array}>} */
  let seccionesOriginal = [];

  // ------------------------------------------------------
  // Helpers UI (acordeón)
  // ------------------------------------------------------

  function crearBloqueSeccion(sec) {
    const wrapper = document.createElement('div');
    wrapper.className = 'accordion-card-wrapper';

    const card = document.createElement('div');
    card.className = 'accordion-card';

    const header = document.createElement('div');
    header.className = 'accordion-card__header';

    // Título con puntos ponderados del tipo, si existen
    let tituloSeccion = sec.nombre;
    if (sec.resumenTipo && typeof sec.resumenTipo.puntos_tipo === 'number') {
      const ganados = Number(
        sec.resumenTipo.puntos_asegurados ??
        sec.resumenTipo.ganados ??
        0
      );
      const totalTipo = Number(sec.resumenTipo.puntos_tipo);
      tituloSeccion = `${sec.nombre} (${ganados.toFixed(2)} / ${totalTipo.toFixed(
        2
      )})`;
    }

    header.innerHTML = `
      <div class="accordion-card__header-main">
        <span class="accordion-card__icon"><i data-feather="layers"></i></span>
        <h3 class="accordion-card__title">${tituloSeccion}</h3>
      </div>
    `;

    const panel = document.createElement('div');
    panel.className = 'accordion-card__panel';
    panel.innerHTML = `
      <div class="item-table-wrapper">
        <table class="item-table">
          <thead>
            <tr><th>Actividad</th><th class="right">Puntuación</th></tr>
          </thead>
          <tbody>${filasTabla(sec.actividades)}</tbody>
        </table>
      </div>
    `;

    card.appendChild(header);
    card.appendChild(panel);
    wrapper.appendChild(card);
    return wrapper;
  }

  function renderSecciones(secciones) {
    if (!contenedorSecciones) {
      return;
    }

    contenedorSecciones.innerHTML = '';

    for (const sec of secciones) {
      contenedorSecciones.appendChild(crearBloqueSeccion(sec));
    }

    if (globalThis.feather) {
      feather.replace();
    }
  }

  // Comportamiento de abrir/cerrar tarjetas del acordeón
  if (contenedorSecciones) {
    contenedorSecciones.addEventListener('click', event => {
      const header = event.target.closest('.accordion-card__header');
      if (!header) return;

      const card = header.closest('.accordion-card');
      if (card) {
        card.classList.toggle('open');
      }
    });
  }

  // ------------------------------------------------------
  // Informe + diagnóstico
  // ------------------------------------------------------

  function actualizarInformeYDiagnostico(progreso) {
    if (!progreso) return;

    const porc = Number(progreso.porcentaje_obtenido ?? 0);
    const obtenidos = Number(progreso.puntos_obtenidos ?? 0);
    const perdidos = Number(progreso.puntos_perdidos ?? 0);
    const posibles = Number(progreso.puntos_posibles_obtener ?? 0);
    const necesarios = Number(progreso.puntos_necesarios_aprobar ?? 0);
    const calMin = Number(progreso.calificacion_minima ?? 70);
    const calMinDinamica = Number(progreso.calificacion_minima_dinamica ?? 0);
    const calMaxPos = Number(progreso.calificacion_maxima_posible ?? 0);

    if (rowPorc) rowPorc.textContent = `${porc.toFixed(1)} / 100`;
    if (rowObt) rowObt.textContent = obtenidos.toFixed(2);
    if (rowPerd) rowPerd.textContent = perdidos.toFixed(2);
    if (rowPosi) rowPosi.textContent = posibles.toFixed(2);
    if (rowNec) rowNec.textContent = necesarios.toFixed(2);
    if (rowMin) rowMin.textContent = calMinDinamica.toFixed(2);
    if (rowMax) rowMax.textContent = calMaxPos.toFixed(2);

    if (!diagCircle || !diagGrade || !diagStatus) {
      return;
    }

    const diagnostico = progreso.diagnostico || {};
    const resultado = determinarNivelDiagnostico(porc, calMin, diagnostico);

    diagGrade.textContent = resultado.grado.toFixed(1);
    diagStatus.textContent = resultado.estado;

    aplicarClasesDiagnostico(diagCircle, diagStatus, resultado.nivel);
  }

  // ------------------------------------------------------
  // Filtro de actividades por texto
  // ------------------------------------------------------

  function filtrarActividades() {
    const term = (buscadorInput?.value || '').toLowerCase().trim();

    const filtradas = seccionesOriginal.map(sec => ({
      ...sec,
      actividades: sec.actividades.filter(actividad =>
        actividad.nombre.toLowerCase().includes(term)
      )
    }));

    renderSecciones(filtradas);
  }

  buscadorInput?.addEventListener('input', filtrarActividades);

  buscadorBtn?.addEventListener('click', () => {
    if (!buscadorWrapper || !buscadorInput) return;

    buscadorWrapper.classList.toggle('active');

    if (buscadorWrapper.classList.contains('active')) {
      buscadorInput.focus();
    } else {
      buscadorInput.value = '';
      renderSecciones(seccionesOriginal);
    }
  });

  // ------------------------------------------------------
  // Cargar datos desde la API
  // ------------------------------------------------------

  async function cargarDetalleMateria() {
    // Igual que en mis-calificaciones.js, usamos BASE_URL tal cual
    const apiBase = (globalThis.BASE_URL || '');
    const url = `${apiBase}api/actividades?id_materia=${encodeURIComponent(idMateria)}`;
    console.log('Llamando a detalle de materia:', url);

    try {
      const resp = await fetch(url, {
        method: 'GET',
        headers: { 'Accept': 'application/json' },
        credentials: 'include'
      });

      const text = await resp.text();
      console.log('Respuesta cruda detalle:', text);

      if (!resp.ok) {
        console.error('Error HTTP al cargar detalles:', resp.status);
        return;
      }

      /** @type {{status:string,data?:object,message?:string}|null} */
      let json = null;
      try {
        json = JSON.parse(text);
      } catch {
        console.error('La respuesta de detalle NO es JSON válido.');
        return;
      }

      if (!json || json.status !== 'success' || !json.data) {
        console.error('Error API detalle:', json?.message);
        return;
      }

      const data = json.data;

      if (tituloPagina && data.materia?.nombre_materia) {
        tituloPagina.textContent =
          `Mis calificaciones - detalles (${data.materia.nombre_materia})`;
      }

      const seccionesAPI = Array.isArray(data.secciones) ? data.secciones : [];

      const resumenPorTipo = {};
      const listaResumenTipo = Array.isArray(data.progreso?.por_tipo)
        ? data.progreso.por_tipo
        : [];
      for (const t of listaResumenTipo) {
        if (t && typeof t.id_tipo === 'number') {
          resumenPorTipo[t.id_tipo] = t;
        }
      }

      seccionesOriginal = seccionesAPI.map(sec => {
        const resumenTipo = resumenPorTipo[sec.id_tipo] || null;

        return {
          id: sec.id_tipo,
          nombre: sec.nombre_tipo,
          resumenTipo,
          actividades: (sec.actividades || []).map(a => ({
            id_actividad: a.id_actividad,
            nombre: a.nombre,
            fecha_entrega: a.fecha_entrega,
            estado: a.estado,
            obtenido: a.obtenido === null ? null : Number(a.obtenido),
            maximo: a.maximo === null ? null : Number(a.maximo)
          }))
        };
      });

      renderSecciones(seccionesOriginal);
      actualizarInformeYDiagnostico(data.progreso);

    } catch (error) {
      console.error('Error de red al cargar detalle de materia:', error);
    }
  }

  // ------------------------------------------------------
  // Inicialización
  // ------------------------------------------------------
  cargarDetalleMateria();
});
