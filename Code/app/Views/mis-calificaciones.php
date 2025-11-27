<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mis calificaciones</title>
  <script>
    <?php
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $dirName = dirname($scriptName);
    if ($dirName === '.' || $dirName === '/')
      $dirName = '';
    $baseUrl = rtrim($dirName, '/') . '/';
    ?>
    const BASE_URL = "<?php echo $baseUrl; ?>";
  </script>

  <!-- estilos globales -->
  <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/sidebar.css" />
  <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/layout.css" />
  <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/search-bar.css" />
  <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/table-theme.css" />
  <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/card-accordion.css" />

  <!-- estilos específicos de esta página -->
  <link rel="stylesheet" href="<?php echo $baseUrl; ?>styles/mis-calificaciones.css" />
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
  <script src="<?php echo $baseUrl; ?>assets/js/sidebar.js?v=<?php echo time(); ?>"></script>
  <script src="<?php echo $baseUrl; ?>assets/js/ui-helpers.js"></script>
  <script src="<?php echo $baseUrl; ?>js/mis-calificaciones.js"></script>

</body>

</html>