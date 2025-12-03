const { initDashboard, obtenerBaseUrl, verificarTablaVacia, normalizeEstado, tipoClase } = require('../../public/js/dashboard');

global.fetch = jest.fn(() =>
    Promise.resolve({
        ok: true,
        text: () => Promise.resolve(JSON.stringify({ data: [] })),
        json: () => Promise.resolve({ data: [] })
    })
);

global.feather = { replace: jest.fn() };

describe('dashboard.js', () => {
    beforeEach(() => {
        document.body.innerHTML = `
      <table id="tabla">
        <tbody id="tabla-body"></tbody>
      </table>
      <div id="mensaje-vacio" class="oculto"></div>
      <div id="tabla-vacia" class="oculto"></div>
      <div id="contenedor-boton-editar"></div>
      <div id="contenedor-boton-eliminar"></div>
      <div id="contenedor-boton-filtro"></div>
      <div id="search-box"></div>
    `;
        jest.clearAllMocks();
    });

    test('obtenerBaseUrl should return string', () => {
        expect(typeof obtenerBaseUrl()).toBe('string');
    });

    test('normalizeEstado should return valid state', () => {
        expect(normalizeEstado('listo')).toBe('listo');
        expect(normalizeEstado('En Curso')).toBe('en curso');
        expect(normalizeEstado(null)).toBe('pendiente');
    });

    test('tipoClase should return valid class', () => {
        expect(tipoClase('Examen')).toBe('tag-azul');
        expect(tipoClase('Proyecto')).toBe('tag-verde');
        expect(tipoClase('Otro')).toBe('tag-agua');
    });

    test('verificarTablaVacia should toggle visibility', () => {
        verificarTablaVacia();
        const msg = document.getElementById('mensaje-vacio');
        expect(msg.classList.contains('oculto')).toBe(false);
    });

    test('initDashboard should call API', async () => {
        initDashboard();
        await new Promise(resolve => setTimeout(resolve, 100));
        expect(fetch).toHaveBeenCalledWith(expect.stringContaining('api/materias'), expect.anything());
    });
});
