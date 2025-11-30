/**
 * Sidebar dinámico de la aplicación.
 */

document.addEventListener('DOMContentLoaded', () => {
  const mount = document.getElementById('sidebar-mount');
  if (!mount) return;

  // Como tenemos <base href>, podemos usar rutas relativas a /public/
  const sidebarUrl = (globalThis.BASE_URL || '') + 'partials/sidebar.html';

  fetch(sidebarUrl)
    .then(resp => {
      if (!resp.ok) throw new Error('Status ' + resp.status);
      return resp.text();
    })
    .then(html => {
      if (html.includes('<title>404') || html.includes('Not Found')) {
        throw new Error('Contenido 404');
      }

      mount.innerHTML = html;

      if (globalThis.feather) {
        feather.replace();
      }

      activarEnlaceActual();
      inicializarColapsoSidebar();
      cargarUsuarioEnSidebar();
      inicializarLogoutSidebar(); 
    })
    .catch(err => {
      console.error('Error cargando sidebar:', err);
      mount.innerHTML = `<div style="padding: 20px; color: red; border: 1px solid red;">
        <strong>Error cargando sidebar.</strong><br>
        No se encontró el archivo en la ruta esperada.
      </div>`;
    });
});


async function cargarUsuarioEnSidebar() {
  try {
    const resp = await fetch((globalThis.BASE_URL || '') + 'auth/me', {
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

function normalizarRuta(path) {
  if (!path) return '';
  path = path.split('?')[0];
  const segments = path.split('/');
  path = segments[segments.length - 1] || '';
  return path.replace(/\.php$/i, '');
}

function activarEnlaceActual() {
  const currentPage = normalizarRuta(globalThis.location.pathname);
  const links = document.querySelectorAll('#sidebar a[href]');

  for (const link of links) {
    const href = link.getAttribute('href');
    const hrefNorm = normalizarRuta(href);

    if (hrefNorm === currentPage) {
      link.classList.add('active');
    } else {
      link.classList.remove('active');
    }
  }
}

function inicializarColapsoSidebar() {
  const sidebar = document.getElementById('sidebar');
  const toggleBtn = document.getElementById('sidebarToggle');

  if (!sidebar || !toggleBtn) return;

  toggleBtn.addEventListener('click', () => {
    const collapsed = sidebar.classList.toggle('collapsed');
    document.body.classList.toggle('sidebar-collapsed', collapsed);
  });
}

function inicializarLogoutSidebar() {
  const btnLogout = document.getElementById('sidebarLogout');
  if (!btnLogout) return;

  btnLogout.addEventListener('click', (event) => {
    event.preventDefault();
    const base = globalThis.BASE_URL || '';
    globalThis.location.href = base + 'logout';
  });
}

