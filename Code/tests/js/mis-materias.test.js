const { initMisMaterias } = require('../../public/js/mis-materias');

// Mock UIHelpers
global.UIHelpers = {
    TagStyleManager: {
        getClassFor: jest.fn(() => 'tag-mock')
    },
    initAccordionGrid: jest.fn(),
    initSearchBar: jest.fn()
};

// Mock feather
global.feather = {
    replace: jest.fn()
};

// Mock fetch
global.fetch = jest.fn(() =>
    Promise.resolve({
        ok: true,
        text: () => Promise.resolve(JSON.stringify({ data: [] })),
        json: () => Promise.resolve({ data: [] })
    })
);

describe('mis-materias.js', () => {
    beforeEach(() => {
        // Reset DOM
        document.body.innerHTML = `
      <div id="lista-materias"></div>
      <div class="search-wrapper">
        <input id="buscador-materias" />
        <button id="search-toggle"></button>
      </div>
    `;
        jest.clearAllMocks();
    });

    test('should initialize and call UIHelpers', () => {
        initMisMaterias();
        expect(UIHelpers.initAccordionGrid).toHaveBeenCalled();
        expect(UIHelpers.initSearchBar).toHaveBeenCalled();
    });

    test('should load materias from API', async () => {
        const mockData = {
            data: [
                { id: 1, nombre: 'Matemáticas', tipos: [] }
            ]
        };

        fetch.mockImplementationOnce(() =>
            Promise.resolve({
                ok: true,
                text: () => Promise.resolve(JSON.stringify(mockData)),
                json: () => Promise.resolve(mockData)
            })
        );

        initMisMaterias();

        // Wait for async operations (simple way)
        await new Promise(resolve => setTimeout(resolve, 100));

        expect(fetch).toHaveBeenCalledWith(expect.stringContaining('api/materias'), expect.anything());
        // Check if content was rendered
        const lista = document.getElementById('lista-materias');
        expect(lista.innerHTML).toContain('Matemáticas');
    });
});
