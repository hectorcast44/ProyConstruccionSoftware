// Página: mis-calificaciones-detalle.html
// Lee ?id_materia= o ?id= de la URL, consulta php/api/calificaciones_detalle.php
// y rellena acordeón, informe y diagnóstico.

document.addEventListener('DOMContentLoaded', () => {
  // ------------------------------------------------------
  // 1) Obtener id_materia
  // ------------------------------------------------------
  const params = new URLSearchParams(window.location.search);
  const idMateria = params.get('id_materia') || params.get('id');

  if (!idMateria) {
    console.error('id_materia no presente en la URL');
    return;
  }

  // ------------------------------------------------------
  // 2) Referencias al DOM
  // ------------------------------------------------------
  const contenedorSecciones = document.getElementById('lista-usuarios');
  const buscadorInput       = document.getElementById('buscador-menu');
  const buscadorWrapper     = document.querySelector('.search-wrapper');
  const buscadorBtn         = document.getElementById('search-toggle');

  const tituloPagina = document.querySelector('.page-title h1'); 

  // filas informe
  const rowPorc = document.querySelector('[data-field="porcentaje-obtenido"] td.right');
  const rowObt  = document.querySelector('[data-field="puntos-obtenidos"] td.right');
  const rowPerd = document.querySelector('[data-field="puntos-perdidos"] td.right');
  const rowPosi = document.querySelector('[data-field="puntos-posibles"] td.right');
  const rowNec  = document.querySelector('[data-field="puntos-necesarios"] td.right');
  const rowMin  = document.querySelector('[data-field="calificacion-minima"] td.right');
  const rowMax  = document.querySelector('[data-field="calificacion-maxima"] td.right');

  // diagnóstico
  const diagCircle = document.querySelector('.diagnosis-circle');
  const diagGrade  = document.querySelector('.diag-grade');
  const diagStatus = document.querySelector('.diag-status');

  let seccionesOriginal = [];

  // ------------------------------------------------------
  // 3) Helpers UI (acordeón)
  // ------------------------------------------------------
  function filasTabla(actividades = []) {
    if (!actividades.length) {
      return `
        <tr><td colspan="2" class="right">Sin registros</td></tr>
      `;
    }

    return actividades.map(a => `
      <tr>
        <td>${a.nombre}</td>
        <td class="right">${a.obtenido} / ${a.maximo}</td>
      </tr>
    `).join('');
  }

  function crearBloqueSeccion(sec) {
    const wrapper = document.createElement('div');
    wrapper.className = 'accordion-card-wrapper';

    const card = document.createElement('div');
    card.className = 'accordion-card';

    const header = document.createElement('div');
    header.className = 'accordion-card__header';
    header.innerHTML = `
      <div class="accordion-card__header-main">
        <span class="accordion-card__icon"><i data-feather="layers"></i></span>
        <h3 class="accordion-card__title">${sec.nombre}</h3>
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
    contenedorSecciones.innerHTML = '';

    secciones.forEach(sec => {
      contenedorSecciones.appendChild(crearBloqueSeccion(sec));
    });

    if (window.feather) feather.replace();
  }

  contenedorSecciones.addEventListener('click', e => {
    const header = e.target.closest('.accordion-card__header');
    if (!header) return;

    const card = header.closest('.accordion-card');
    card.classList.toggle('open');
  });

  // ------------------------------------------------------
  // 4) Informe + diagnóstico
  // ------------------------------------------------------
  function actualizarInformeYDiagnostico(progreso) {
    if (!progreso) return;

    const porc       = Number(progreso.porcentaje_obtenido ?? 0);
    const obtenidos  = Number(progreso.puntos_obtenidos ?? 0);
    const perdidos   = Number(progreso.puntos_perdidos ?? 0);
    const posibles   = Number(progreso.puntos_posibles_obtener ?? 0);
    const necesarios = Number(progreso.puntos_necesarios_aprobar ?? 0);
    const calMin     = Number(progreso.calificacion_minima ?? 70);
    const calMaxPos  = Number(progreso.calificacion_maxima_posible ?? 0);

    // ----- rellenar tabla -----
    if (rowPorc) rowPorc.textContent = `${porc.toFixed(1)} / 100`;
    if (rowObt)  rowObt.textContent  = obtenidos.toFixed(2);
    if (rowPerd) rowPerd.textContent = perdidos.toFixed(2);
    if (rowPosi) rowPosi.textContent = posibles.toFixed(2);
    if (rowNec)  rowNec.textContent  = necesarios.toFixed(2);
    if (rowMin)  rowMin.textContent  = calMin.toFixed(2);
    if (rowMax)  rowMax.textContent  = calMaxPos.toFixed(2);

    if (!diagCircle || !diagGrade || !diagStatus) return;

    const diag   = progreso.diagnostico || {};
    const grado  = Number(diag.grado ?? porc);
    let   estado = diag.estado ?? '—';
    let   nivel  = diag.nivel; // ok / risk / fail (si viene del backend)

    // 🔁 Recalcular nivel por si no viene bien del backend
    if (!nivel) {
      if (porc >= calMin) {
        nivel = 'ok';
        estado = 'Aprobado';
      } else if (porc < calMin - 10) {
        nivel = 'fail';
        estado = 'Reprobado';
      } else {
        nivel = 'risk';
        estado = 'En riesgo';
      }
    }

    // mostrar grado y estado
    diagGrade.textContent  = grado.toFixed(1);
    diagStatus.textContent = estado;

    // limpiar clases anteriores
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

    // aplicar color según nivel FINAL
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

  // ------------------------------------------------------
  // 5) Filtro actividades
  // ------------------------------------------------------
  function filtrarActividades() {
    const term = (buscadorInput?.value || '').toLowerCase().trim();

    const filtradas = seccionesOriginal.map(sec => ({
      ...sec,
      actividades: sec.actividades.filter(a =>
        a.nombre.toLowerCase().includes(term)
      )
    }));

    renderSecciones(filtradas);
  }

  buscadorInput?.addEventListener('input', filtrarActividades);

  buscadorBtn?.addEventListener('click', () => {
    buscadorWrapper.classList.toggle('active');
    if (buscadorWrapper.classList.contains('active')) {
      buscadorInput?.focus();
    } else {
      buscadorInput.value = '';
      renderSecciones(seccionesOriginal);
    }
  });

  // ------------------------------------------------------
  // 6) Cargar data de API
  // ------------------------------------------------------
  async function cargarDetalleMateria() {
    const url = `../php/api/calificaciones_detalle.php?id_materia=${encodeURIComponent(idMateria)}`;

    try {
      const resp = await fetch(url);
      const raw  = await resp.text();

      let json;
      try {
        json = JSON.parse(raw);
      } catch {
        console.error('JSON inválido');
        return;
      }

      if (!resp.ok || json.status !== 'success') {
        console.error('Error API detalle:', json?.message);
        return;
      }

      const data = json.data || {};

      // Actualizar título con el nombre de la materia
      if (tituloPagina && data.materia?.nombre_materia) {
        tituloPagina.textContent =
          `Mis calificaciones - detalles (${data.materia.nombre_materia})`;
      }

      const seccionesAPI = Array.isArray(data.secciones) ? data.secciones : [];

      seccionesOriginal = seccionesAPI.map(sec => ({
        id: sec.id_tipo,
        nombre: sec.nombre_tipo,
        actividades: (sec.actividades || []).map(a => ({
          id_actividad: a.id_actividad,
          nombre: a.nombre,
          fecha_entrega: a.fecha_entrega,
          estado: a.estado,
          obtenido: Number(a.obtenido ?? 0),
          maximo: Number(a.maximo ?? 0)
        }))
      }));

      renderSecciones(seccionesOriginal);
      actualizarInformeYDiagnostico(data.progreso);

    } catch (e) {
      console.error('Error de red:', e);
    }
  }

  // ------------------------------------------------------
  // 7) Iniciar
  // ------------------------------------------------------
  cargarDetalleMateria();
});
