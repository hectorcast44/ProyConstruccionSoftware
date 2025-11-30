// Carga materias desde la API y renderiza tarjetas
document.addEventListener('DOMContentLoaded', () => {
  const lista = document.getElementById('lista-materias');
  if (!lista) return;

  const base = globalThis.BASE_URL || '';
  const url = base + 'api/materias';

  lista.innerHTML = '<div class="card"><div class="card-body">Cargando...</div></div>';

  fetch(url, { credentials: 'same-origin' })
    .then(async res => {
      const text = await res.text();
      let json = null;
      try { json = JSON.parse(text); } catch (e) { throw new Error('Respuesta inválida de la API'); }
      if (!res.ok) throw new Error(json.message || ('HTTP ' + res.status));
      return json;
    })
    .then(payload => {
      const materias = payload.data || [];
      if (!materias.length) {
        lista.innerHTML = '<div class="card"><div class="card-header">Sin materias</div><div class="card-body">No se encontraron materias para este usuario.</div></div>';
        return;
      }

      lista.innerHTML = '';
      materias.forEach(m => {
        const tiposHtml = (m.tipos || []).map(t => {
          const obtenido = Number(t.obtenido || 0);
          const maximo = Number(t.maximo || 0);
          const pct = (maximo > 0) ? Math.round((obtenido / maximo) * 100) : '--';
          return `
            <div class="tipo-row">
              <div class="tipo-nombre">${escapeHtml(t.nombre)}</div>
              <div class="tipo-meta">${obtenido}/${maximo} (${pct !== '--' ? pct + '%' : '--'})</div>
            </div>
          `;
        }).join('');

        const card = document.createElement('div');
        card.className = 'card';
        card.innerHTML = `
          <div class="card-header">${escapeHtml(m.nombre)}</div>
          <div class="card-body">
            ${tiposHtml || '<div class="muted">No hay actividades registradas</div>'}
          </div>
        `;

        lista.appendChild(card);
      });
    })
    .catch(err => {
      console.error('Error cargando materias:', err);
      lista.innerHTML = `<div class="card"><div class="card-header">Error</div><div class="card-body">${escapeHtml(err.message)}</div></div>`;
    });

  // Helper
  function escapeHtml(s){
    return String(s || '').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#39;');
  }
});
