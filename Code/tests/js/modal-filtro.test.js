const { abrirModalFiltro, inicializarModalFiltro } = require('../../public/assets/js/modal-filtro');

global.fetch = jest.fn(() =>
    Promise.resolve({
        ok: true,
        text: () => Promise.resolve('<div id="modal-filtro" class="oculto"></div>'),
        json: () => Promise.resolve([])
    })
);

global.feather = { replace: jest.fn() };

describe('modal-filtro.js', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        jest.clearAllMocks();
    });

    test('abrirModalFiltro should fetch partial if modal does not exist', async () => {
        await abrirModalFiltro();
        expect(fetch).toHaveBeenCalledWith(expect.stringContaining('partials/modal-filtro.html'));
    });

    test('inicializarModalFiltro should populate selects', async () => {
        document.body.innerHTML = `
      <div id="modal-filtro">
        <select id="filtro-materia"></select>
        <select id="filtro-tipo"></select>
      </div>
    `;

        // Mock fetch for types and subjects
        fetch.mockImplementation((url) => {
            if (url.includes('api/materias')) return Promise.resolve({ json: () => Promise.resolve([{ nombre: 'Math' }]) });
            if (url.includes('api/tipos-actividad')) return Promise.resolve({ ok: true, json: () => Promise.resolve([{ nombre: 'Exam' }]) });
            return Promise.resolve({ ok: true });
        });

        inicializarModalFiltro();

        await new Promise(resolve => setTimeout(resolve, 100));

        expect(fetch).toHaveBeenCalledWith(expect.stringContaining('api/materias'), expect.anything());
        expect(fetch).toHaveBeenCalledWith(expect.stringContaining('api/tipos-actividad'), expect.anything());
    });
});
