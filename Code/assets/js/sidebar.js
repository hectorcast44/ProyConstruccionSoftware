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

  fetch('../partials/sidebar.html')
    .then(resp => resp.text())
    .then(html => {
      mount.innerHTML = html;

      if (globalThis.feather) {
        feather.replace();
      }

      cargarUsuarioEnSidebar();
      activarEnlaceActual();
      inicializarColapsoSidebar();
    })
    .catch(err => {
      console.error('No se pudo cargar el sidebar:', err);
    });
});

/**
 * Obtiene los datos del usuario desde la API y los inserta
 * en los elementos del sidebar (.user-name, .user-label, .user-avatar).
 *
 * @async
 * @returns {Promise<void>}
 */
async function cargarUsuarioEnSidebar() {
  try {
    const resp = await fetch('../php/api/usuario_info.php', {
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
  const currentPage = fullPath.substring(fullPath.lastIndexOf('/') + 1);

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
