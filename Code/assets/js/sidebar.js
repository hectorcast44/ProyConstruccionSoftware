// -----------------------------------------------------------------------------
// Carga dinámica del sidebar + activación automática de enlaces + colapso
// -----------------------------------------------------------------------------

document.addEventListener('DOMContentLoaded', () => {
  // Punto donde se insertará el sidebar
  const mount = document.getElementById('sidebar-mount');
  if (!mount) return; // Si la página no requiere sidebar, terminamos

  // Cargar el HTML del sidebar desde /partials/
  fetch('../partials/sidebar.html')
    .then(resp => resp.text())
    .then(html => {
      // Inyectar sidebar en el contenedor
      mount.innerHTML = html;

      // Reemplazar íconos Feather dentro del sidebar
      if (window.feather) feather.replace();

      // -----------------------------------------------------------------------
      // 1. Determinar la página actual desde la URL
      //    Ejemplo: "/proyectoCS_v1/pages/mis-calificaciones.html"
      //    Resultado: "mis-calificaciones.html"
      // -----------------------------------------------------------------------
      const fullPath = window.location.pathname;
      const currentPage = fullPath.substring(fullPath.lastIndexOf('/') + 1);

      // -----------------------------------------------------------------------
      // 2. Activar el elemento del sidebar cuyo href coincide con la página actual
      // -----------------------------------------------------------------------
      document.querySelectorAll('.sidebar .nav-item[href]').forEach(link => {
        const href = link.getAttribute('href'); // Ej: "mis-calificaciones.html"
        if (href === currentPage) {
          link.classList.add('active');
        } else {
          link.classList.remove('active');
        }
      });

      // -----------------------------------------------------------------------
      // 3. Habilitar botón de colapso del sidebar
      // -----------------------------------------------------------------------
      const sidebar = document.getElementById('sidebar');
      const toggleBtn = document.getElementById('sidebarToggle');

      if (sidebar && toggleBtn) {
        toggleBtn.addEventListener('click', () => {
          // Alternar estado visual del sidebar
          const collapsed = sidebar.classList.toggle('collapsed');

          // Aplicar estado colapsado al <body> para ajustar el layout global
          document.body.classList.toggle('sidebar-collapsed', collapsed);
        });
      }
    })
    .catch(err => {
      console.error('No se pudo cargar el sidebar:', err);
    });
});
