<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mis calificaciones</title>
  <script>
    const BASE_URL = "<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/";
  </script>

  <!-- estilos globales -->
  <link rel="stylesheet" href="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/assets/css/sidebar.css" />
  <link rel="stylesheet" href="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/assets/css/layout.css" />
  <link rel="stylesheet"
    href="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/assets/css/search-bar.css" />
  <link rel="stylesheet"
    href="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/assets/css/table-theme.css" />
  <link rel="stylesheet"
    href="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/assets/css/card-accordion.css" />

  <!-- estilos específicos de esta página -->
  <link rel="stylesheet"
    href="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/styles/mis-calificaciones.css" />
</head>

<body class="has-sidebar page-calificaciones">
  <div id="sidebar-mount"></div>

  <main>
    <!-- buscador flotante -->
    <div class="search-wrapper">
      <div class="input-holder">
        <input type="text" class="search-input" placeholder="Buscar materia..." id="buscador-menu" />
        <button class="search-icon" aria-label="Buscar / Cerrar" id="search-toggle">
          <i data-feather="search"></i>
        </button>
      </div>
    </div>

    <div class="page-title">
      <i data-feather="bar-chart-2"></i>
      <h1>Mis calificaciones</h1>
    </div>

    <!-- grid de cards de materias/calificaciones -->
    <section id="lista-calificaciones" class="accordion-card-grid"></section>
  </main>

  <script src="https://unpkg.com/feather-icons"></script>
  <script src="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/assets/js/sidebar.js"></script>
  <script src="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/assets/js/ui-helpers.js"></script>
  <script src="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/js/mis-calificaciones.js"></script>

</body>

</html>
```