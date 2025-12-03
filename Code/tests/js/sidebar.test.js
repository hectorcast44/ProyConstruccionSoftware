const { initSidebar, normalizarRuta } = require('../../public/assets/js/sidebar');

global.fetch = jest.fn(() =>
    Promise.resolve({
        ok: true,
        text: () => Promise.resolve('<div>sidebar content</div>'),
        json: () => Promise.resolve({ status: 'success', data: { nombre: 'User' } })
    })
);

global.feather = { replace: jest.fn() };

describe('sidebar.js', () => {
    beforeEach(() => {
        document.body.innerHTML = '<div id="sidebar-mount"></div>';
        jest.clearAllMocks();
    });

    test('initSidebar should fetch sidebar content', async () => {
        initSidebar();
        // initSidebar calls fetch but doesn't return promise, so we wait
        await new Promise(resolve => setTimeout(resolve, 100));

        expect(fetch).toHaveBeenCalledWith(expect.stringContaining('partials/sidebar.html'));
        expect(document.getElementById('sidebar-mount').innerHTML).toBe('<div>sidebar content</div>');
    });

    test('normalizarRuta should clean path', () => {
        expect(normalizarRuta('/path/to/file.php')).toBe('file');
        expect(normalizarRuta('/file')).toBe('file');
    });
});
