// assets/js/sidebar.js
document.addEventListener('DOMContentLoaded', () => {
  const mount = document.getElementById('sidebar-mount');
  if (!mount) return;

  fetch('../partials/sidebar.html')
    .then(resp => resp.text())
    .then(html => {
      mount.innerHTML = html;

      if (window.feather) feather.replace();

      // ★ activar nav-item según la página ★
      const currentPage = document.body.dataset.page;

      document.querySelectorAll('.sidebar .nav-item').forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes(currentPage)) {
          link.classList.add('active');        // ← FIX
        } else {
          link.classList.remove('active');     // ← FIX
        }
      });

      const sidebar = document.getElementById('sidebar');
      const toggleBtn = document.getElementById('sidebarToggle');
      if (!sidebar || !toggleBtn) return;

      toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        document.body.classList.toggle(
          'sidebar-collapsed',
          sidebar.classList.contains('collapsed')
        );
      });
    })
    .catch(err => console.error('No se pudo cargar el sidebar:', err));
});