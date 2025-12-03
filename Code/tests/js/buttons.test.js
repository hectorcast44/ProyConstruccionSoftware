const { cargarPartial, initButtons } = require('../../public/assets/js/buttons');

global.fetch = jest.fn(() =>
    Promise.resolve({
        text: () => Promise.resolve('<div>partial content</div>')
    })
);

global.feather = { replace: jest.fn() };

describe('buttons.js', () => {
    beforeEach(() => {
        document.body.innerHTML = '<div id="container"></div><button id="btn"></button>';
        jest.clearAllMocks();
    });

    test('cargarPartial should load content', async () => {
        await cargarPartial('path/to/partial', 'container', 'btn', jest.fn());
        expect(fetch).toHaveBeenCalledWith('path/to/partial');
        expect(document.getElementById('container').innerHTML).toBe('<div>partial content</div>');
    });
});
