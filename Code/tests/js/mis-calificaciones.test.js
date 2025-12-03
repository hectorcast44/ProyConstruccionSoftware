const { initMisCalificaciones, obtenerTagClassPorTipo, generarFilasTipos, crearCardMateria, renderizarMaterias, filtrarYRenderizar, cargarMateriasDesdeAPI } = require('../../public/js/mis-calificaciones');

global.fetch = jest.fn(() =>
    Promise.resolve({
        ok: true,
        text: () => Promise.resolve(JSON.stringify({ data: [] })),
        json: () => Promise.resolve({ data: [] })
    })
);

global.feather = { replace: jest.fn() };
global.UIHelpers = {
    TagStyleManager: { getClassFor: jest.fn(() => 'tag-mock') },
    initAccordionGrid: jest.fn(),
    initSearchBar: jest.fn()
};

describe('mis-calificaciones.js', () => {
    beforeEach(() => {
        document.body.innerHTML = `
      <div id="lista-calificaciones"></div>
      <input id="buscador-menu" />
      <div class="search-wrapper"></div>
      <button id="search-toggle"></button>
    `;
        jest.clearAllMocks();
    });

    test('obtenerTagClassPorTipo should return class', () => {
        expect(obtenerTagClassPorTipo({ nombre: 'Test' })).toBe('tag-mock');
    });

    test('generarFilasTipos should return HTML', () => {
        const html = generarFilasTipos([{ nombre: 'Tipo 1', obtenido: 10, maximo: 20 }]);
        expect(html).toContain('Tipo 1');
        expect(html).toContain('10 / 20');
    });

    test('crearCardMateria should return DOM element', () => {
        const materia = { id: 1, nombre: 'Materia 1', tipos: [] };
        const card = crearCardMateria(materia);
        expect(card.classList.contains('accordion-card-wrapper')).toBe(true);
        expect(card.innerHTML).toContain('Materia 1');
    });

    test('renderizarMaterias should populate list', () => {
        const materias = [{ id: 1, nombre: 'Materia 1', tipos: [] }];
        renderizarMaterias(materias);
        const lista = document.getElementById('lista-calificaciones');
        expect(lista.children.length).toBe(1);
    });

    test('initMisCalificaciones should call API and init helpers', async () => {
        initMisCalificaciones();
        expect(UIHelpers.initAccordionGrid).toHaveBeenCalled();
        expect(UIHelpers.initSearchBar).toHaveBeenCalled();
        await new Promise(resolve => setTimeout(resolve, 100));
        expect(fetch).toHaveBeenCalledWith(expect.stringContaining('api/materias'), expect.anything());
    });
});
