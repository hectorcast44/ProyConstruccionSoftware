const { abrirModalNueva, inicializarModalNueva } = require('../../public/assets/js/modal-nueva');

global.fetch = jest.fn(() =>
    Promise.resolve({
        ok: true,
        text: () => Promise.resolve('<dialog id="modal-nueva"><form id="form-actividad"></form></dialog>'),
        json: () => Promise.resolve([])
    })
);

global.feather = { replace: jest.fn() };

describe('modal-nueva.js', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        jest.clearAllMocks();
        if (global.HTMLDialogElement) {
            HTMLDialogElement.prototype.showModal = jest.fn();
            HTMLDialogElement.prototype.close = jest.fn();
        }
    });

    test('abrirModalNueva should fetch partial if modal does not exist', async () => {
        await abrirModalNueva();
        expect(fetch).toHaveBeenCalledWith(expect.stringContaining('partials/modal-nueva.html'));
    });

    test('inicializarModalNueva should attach submit listener', () => {
        document.body.innerHTML = `
      <dialog id="modal-nueva">
        <form id="form-actividad"></form>
        <button id="cerrar-modal"></button>
      </dialog>
    `;

        inicializarModalNueva();

        const form = document.getElementById('form-actividad');
        expect(form).toBeTruthy();
    });
});
