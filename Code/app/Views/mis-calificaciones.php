<?php
  if (session_status() === PHP_SESSION_NONE) {
      session_start();
  }

  if (empty($_SESSION['id_usuario'])) {
      header('Location: /auth/login');
      exit;
  }

  $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
  $publicPos  = strpos($scriptName, '/public/');
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
  <title>Mis calificaciones</title>
  <base href="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES); ?>">
  <script>
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
    <div class="main-bar">
      <div class="page-title">
        <i data-feather="bar-chart-2"></i>
        <h1>Mis calificaciones</h1>
      </div>
    </div>

    <div id="content-group" class="content-group">
      <div class="search-box" id="search-box">
        <i data-feather="search" class="d-search-icon"></i>
        <input
          type="text"
          id="d-search-input"
          class="d-search-input"
          placeholder="Buscar materia..."
        >
      </div>
    </div>

    <section id="lista-calificaciones" class="accordion-card-grid"></section>
  </main>


  <script src="https://unpkg.com/feather-icons"></script>
  <script src="<?php echo $baseUrl; ?>assets/js/sidebar.js?v=<?php echo time(); ?>"></script>
  <script src="<?php echo $baseUrl; ?>assets/js/ui-helpers.js"></script>
  <script src="<?php echo $baseUrl; ?>js/mis-calificaciones.js?v=<?php echo time(); ?>"></script>
</body>
</html>
