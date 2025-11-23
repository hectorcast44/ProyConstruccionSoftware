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

  fetch(basePath + 'partials/sidebar.html')
    .then(resp => {
      if (!resp.ok) throw new Error(`HTTP ${resp.status} ${resp.statusText}`);
      return resp.text();
    })
    .then(html => {
      // Verificar si lo que llegó parece HTML de sidebar y no una página de error 404 genérica
      if (html.includes('<title>404') || html.includes('Not Found')) {
        throw new Error('El servidor devolvió una página de error 404');
      }

      mount.innerHTML = html;

      if (globalThis.feather) {
        feather.replace();
      }

      cargarUsuarioEnSidebar(basePath);
      activarEnlaceActual();
      inicializarColapsoSidebar();
    })
    .catch(err => {
      console.error('No se pudo cargar el sidebar:', err);
      // Mostrar error visible para depuración
      mount.innerHTML = `<div style="padding: 20px; color: red; background: #fff; border: 1px solid red;">
        <strong>Error cargando sidebar:</strong><br>
        Ruta intentada: <code>${basePath}partials/sidebar.html</code><br>
        Detalle: ${err.message}
      </div>`;
    });
});

/**
 * Obtiene los datos del usuario desde la API y los inserta
 * en los elementos del sidebar (.user-name, .user-label, .user-avatar).
 *
 * @async
 * @returns {Promise<void>}
 */
async function cargarUsuarioEnSidebar(basePath = '') {
  try {
    const resp = await fetch(basePath + 'auth/me', {
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
