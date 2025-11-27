/**
 * Sidebar dinámico de la aplicación.
 *
 * Este módulo se encarga de:
 *  - Cargar el HTML del sidebar desde un partial externo.
 *  - Activar automáticamente el enlace del menú correspondiente a la página actual.
 *  - Permitir colapsar/expandir el sidebar.
 *  - Cargar el nombre, correo y avatar del usuario desde la API.
 */

document.addEventListener('DOMContentLoaded', () => {
  const mount = document.getElementById('sidebar-mount');
  if (!mount) return;

  // Detectar base path para fetch correcto en subdirectorios
  const basePath = globalThis.BASE_URL || '';

  // Lista de rutas a intentar
  const pathsToTry = [
    basePath + 'partials/sidebar.html',
    '../partials/sidebar.html',
    '../../partials/sidebar.html',
    '/partials/sidebar.html',
    'partials/sidebar.html'
  ];

  // Función recursiva para intentar cargar el sidebar
  const tryLoadSidebar = (index) => {
    if (index >= pathsToTry.length) {
      console.error('No se pudo cargar el sidebar después de varios intentos.');
      mount.innerHTML = `<div style="padding: 20px; color: red; border: 1px solid red;">
        <strong>Error cargando sidebar.</strong><br>
        No se encontró el archivo en ninguna de las rutas esperadas.
      </div>`;
      return;
    }

    const url = pathsToTry[index];
    fetch(url)
      .then(resp => {
        if (!resp.ok) throw new Error('Status ' + resp.status);
        return resp.text();
      })
      .then(html => {
        if (html.includes('<title>404') || html.includes('Not Found')) {
          throw new Error('Contenido 404');
        }
        mount.innerHTML = html;
        if (globalThis.feather) feather.replace();
        // Usamos la URL exitosa para deducir la ruta base correcta para la API
        cargarUsuarioEnSidebar(url);
        activarEnlaceActual();
        inicializarColapsoSidebar();
      })
      .catch(err => {
        console.warn(`Fallo al cargar sidebar desde ${url}:`, err);
        tryLoadSidebar(index + 1);
      });
  };

  tryLoadSidebar(0);
});

/**
 * Obtiene los datos del usuario desde la API y los inserta
 * en los elementos del sidebar (.user-name, .user-label, .user-avatar).
 *
 * @async
 * @returns {Promise<void>}
 */
async function cargarUsuarioEnSidebar(basePath = '') {
  // Si basePath viene de una ruta relativa exitosa (ej: ../partials/sidebar.html),
  // debemos ajustar la ruta de la API para que sea consistente (ej: ../auth/me).
  // Quitamos 'partials/sidebar.html' del final para obtener el prefijo real.
  let apiBase = basePath;
  if (apiBase.endsWith('partials/sidebar.html')) {
    apiBase = apiBase.replace('partials/sidebar.html', '');
  }

  try {
    const resp = await fetch(apiBase + 'auth/me', {
      credentials: 'include'
    });

    const json = await resp.json();
    if (json?.status !== 'success' || !json.data) return;

    const user = json.data;

    const userName = document.querySelector('.user-name');
    const userLabel = document.querySelector('.user-label');
    const avatar = document.querySelector('.user-avatar');

    if (userName) userName.textContent = user.nombre || 'Usuario';
    if (userLabel) userLabel.textContent = user.correo || 'USUARIO';
    if (avatar && user.avatar) avatar.src = user.avatar;

  } catch (error) {
    console.error('Error cargando usuario en sidebar:', error);
  }
}

/**
 * Determina la página actual a partir de la URL del navegador
 * y activa el enlace correspondiente dentro del sidebar.
 */
function activarEnlaceActual() {
  const fullPath = globalThis.location.pathname;
  // Obtener el último segmento de la URL (ej: dashboard, mis-calificaciones)
  const currentPage = fullPath.split('/').pop() || 'dashboard';

  const links = document.querySelectorAll('.sidebar .nav-item[href]');

  for (const link of links) {
    const href = link.getAttribute('href');
    if (href === currentPage) {
      link.classList.add('active');
    } else {
      link.classList.remove('active');
    }
  }
}

/**
 * Inicializa el comportamiento de colapso/expansión del sidebar.
 */
function inicializarColapsoSidebar() {
  const sidebar = document.getElementById('sidebar');
  const toggleBtn = document.getElementById('sidebarToggle');

  if (!sidebar || !toggleBtn) return;

  toggleBtn.addEventListener('click', () => {
    const collapsed = sidebar.classList.toggle('collapsed');
    document.body.classList.toggle('sidebar-collapsed', collapsed);
  });
}
