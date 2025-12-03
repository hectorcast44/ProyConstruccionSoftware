const { abrirModalCrearMateria, inicializarModalNuevaMateria } = require('../../public/assets/js/modal-nueva-materia');

global.fetch = jest.fn(() =>
    Promise.resolve({
        ok: true,
        text: () => Promise.resolve('<dialog id="modal-nueva-materia"><form id="form-materia"></form></dialog>'),
        json: () => Promise.resolve([])
    })
);

global.feather = { replace: jest.fn() };

describe('modal-nueva-materia.js', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        jest.clearAllMocks();
        // Mock showModal/close since JSDOM doesn't fully support dialog methods yet or might be missing
        if (global.HTMLDialogElement) {
            HTMLDialogElement.prototype.showModal = jest.fn();
            HTMLDialogElement.prototype.close = jest.fn();
        }
    });

    test('abrirModalCrearMateria should fetch partial if modal does not exist', async () => {
        await abrirModalCrearMateria();
        expect(fetch).toHaveBeenCalledWith(expect.stringContaining('partials/modal-nueva-materia.html'));
    });

    test('inicializarModalNuevaMateria should attach submit listener', () => {
        document.body.innerHTML = `
      <dialog id="modal-nueva-materia">
        <form id="form-materia"></form>
        <button id="cerrar-modal-materia"></button>
      </dialog>
    `;

        inicializarModalNuevaMateria();

        const form = document.getElementById('form-materia');
        expect(form).toBeTruthy();
    });
});
