<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['id_usuario'])) {
        header('Location: /auth/login');
        exit;
    }
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $publicPos = strpos($scriptName, '/public/');
    if ($publicPos === false) {
        $baseUrl = '/';
    } else {
        $baseUrl = substr($scriptName, 0, $publicPos + strlen('/public/'));
    }
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mis materias</title>
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
  <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/buttons.css" />
  <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/search-bar.css" />
  <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/modal.css" />
  <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/table-theme.css" />
  <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/card-accordion.css" />

  <!-- estilos específicos -->
  <link rel="stylesheet" href="<?php echo $baseUrl; ?>styles/mis-materias.css" />
</head>

<body class="has-sidebar page-mis-materias" data-page="mis-materias">
  <div id="sidebar-mount"></div>

  <main>
    <div class="main-bar">
      <div class="page-title">
        <i data-feather="book"></i>
        <h1>Mis materias</h1>
      </div>

      <div class="contenedor-botones" style="display:inline-block; margin-left: 12px; vertical-align: middle;">
        <div id="contenedor-boton-nueva"></div>
      </div>
    </div>

    <div class="content-group">
      <div class="search-box">
        <i data-feather="search" class="d-search-icon"></i>
        <input
          type="text"
          id="buscador-materias"
          class="d-search-input"
          placeholder="Buscar materia..."
        >
      </div>
    </div>

    <!-- listado de materias (cards) -->
    <section id="lista-materias" class="accordion-card-grid"></section>
  </main>


  <script src="https://unpkg.com/feather-icons"></script>
  <script src="<?php echo $baseUrl; ?>assets/js/sidebar.js?v=<?php echo time(); ?>"></script>
  <script src="<?php echo $baseUrl; ?>assets/js/ui-helpers.js"></script>
  <script src="<?php echo $baseUrl; ?>assets/js/modal-nueva-materia.js"></script>
  <script src="<?php echo $baseUrl; ?>assets/js/buttons.js"></script>
  <script src="<?php echo $baseUrl; ?>js/mis-materias.js?v=<?php echo time(); ?>"></script>
</body>

</html>
