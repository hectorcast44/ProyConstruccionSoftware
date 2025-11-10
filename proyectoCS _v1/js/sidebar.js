// assets/js/sidebar.js
document.addEventListener('DOMContentLoaded', () => {
  const mount = document.getElementById('sidebar-mount');
  if (!mount) return;

  // carga el HTML del sidebar
  fetch('../partials/sidebar.html')
    .then(resp => resp.text())
    .then(html => {
      mount.innerHTML = html;

      // iconos de feather
      if (window.feather) {
        feather.replace();
      }

      const sidebar = document.getElementById('sidebar');
      const toggleBtn = document.getElementById('sidebarToggle');

      // por si algún día cambias el id en el html
      if (!sidebar || !toggleBtn) return;

      toggleBtn.addEventListener('click', () => {
        // colapsa el aside
        sidebar.classList.toggle('collapsed');

        // ¿quedó colapsado?
        const isCollapsed = sidebar.classList.contains('collapsed');

        // se lo contamos al body
        document.body.classList.toggle('sidebar-collapsed', isCollapsed);
      });
    })
    .catch(err => {
      console.error('No se pudo cargar el sidebar:', err);
    });
});
