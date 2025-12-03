const { initMisCalificacionesDetalle, filasTabla, determinarNivelDiagnostico, aplicarClasesDiagnostico, crearBloqueSeccion, renderSecciones, actualizarInformeYDiagnostico, filtrarActividades, cargarDetalleMateria } = require('../../public/js/mis-calificaciones-detalle');

globalThis.fetch = jest.fn(() =>
    Promise.resolve({
        ok: true,
        text: () => Promise.resolve(JSON.stringify({ status: 'success', data: { materia: { nombre_materia: 'Test' }, secciones: [], progreso: {} } })),
        json: () => Promise.resolve({ status: 'success', data: { materia: { nombre_materia: 'Test' }, secciones: [], progreso: {} } })
    })
);

globalThis.feather = { replace: jest.fn() };
globalThis.UIHelpers = {
    initAccordionGrid: jest.fn(),
    initSearchBar: jest.fn()
};

describe('mis-calificaciones-detalle.js', () => {
    beforeEach(() => {
        document.body.innerHTML = `
      <div id="lista-usuarios"></div>
      <input id="buscador-menu" />
      <div class="search-wrapper"></div>
      <button id="search-toggle"></button>
      <div class="page-title"><h1></h1></div>
      <div data-field="porcentaje-obtenido"><td class="right"></td></div>
      <div data-field="puntos-obtenidos"><td class="right"></td></div>
      <div data-field="puntos-perdidos"><td class="right"></td></div>
      <div data-field="puntos-posibles"><td class="right"></td></div>
      <div data-field="puntos-necesarios"><td class="right"></td></div>
      <div data-field="calificacion-minima"><td class="right"></td></div>
      <div data-field="calificacion-maxima"><td class="right"></td></div>
      <div class="diagnosis-circle"></div>
      <div class="diag-grade"></div>
      <div class="diag-status"></div>
    `;
        jest.clearAllMocks();
        // Mock URLSearchParams directly
        globalThis.URLSearchParams = jest.fn(() => ({
            get: jest.fn((key) => '1')
        }));
    });

    test('filasTabla should return HTML', () => {
        const html = filasTabla([{ nombre: 'Act 1', obtenido: 10, maximo: 20 }]);
        expect(html).toContain('Act 1');
        expect(html).toContain('10 / 20');
    });

    test('determinarNivelDiagnostico should return correct level', () => {
        expect(determinarNivelDiagnostico(80, 70).nivel).toBe('ok');
        expect(determinarNivelDiagnostico(50, 70).nivel).toBe('fail');
        expect(determinarNivelDiagnostico(65, 70).nivel).toBe('risk');
    });

    test('aplicarClasesDiagnostico should update classes', () => {
        const circle = document.createElement('div');
        const status = document.createElement('div');
        aplicarClasesDiagnostico(circle, status, 'ok');
        expect(circle.classList.contains('diagnosis-circle--ok')).toBe(true);
        expect(status.classList.contains('diag-status--ok')).toBe(true);
    });

    test('crearBloqueSeccion should return DOM element', () => {
        const sec = { nombre: 'Seccion 1', actividades: [] };
        const block = crearBloqueSeccion(sec);
        expect(block.classList.contains('accordion-card-wrapper')).toBe(true);
        expect(block.innerHTML).toContain('Seccion 1');
    });

    test('renderSecciones should populate container', () => {
        const secciones = [{ nombre: 'Seccion 1', actividades: [] }];
        renderSecciones(secciones);
        const container = document.getElementById('lista-usuarios');
        expect(container.children.length).toBe(1);
    });

    test('initMisCalificacionesDetalle should call API', async () => {
        initMisCalificacionesDetalle();
        await new Promise(resolve => setTimeout(resolve, 100));
        expect(fetch).toHaveBeenCalled();
    });
});
