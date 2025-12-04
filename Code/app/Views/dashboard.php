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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <base href="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES); ?>">
    <script>
        const BASE_URL = "<?php echo $baseUrl; ?>";
    </script>
    <!-- estilos globales -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/sidebar.css" />
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/layout.css" />
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/search-bar.css" />
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/table-theme.css" />
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/buttons.css" />
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/modal.css">
    <!-- estilos específicos -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>styles/dashboard.css">

    <!-- FullCalendar -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <style>
        /* Estilos para la vista de calendario */
        #calendar-view {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-top: 20px;
            display: none;
            /* Oculto por defecto */
        }

        .fc-event {
            cursor: pointer;
        }

        .fc-toolbar-title {
            font-size: 1.25em !important;
        }

        .btn-icon-text {
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>

<body class="has-sidebar" data-page="dashboard">
    <!-- aquí se inyecta el sidebar -->
    <div id="sidebar-mount"></div>
    <main>
        <div class="main-bar">
            <div class="page-title">
                <i data-feather="layout"></i>
                <h1>Dashboard</h1>
            </div>

            <!-- Botones -->
            <div class="contenedor-botones">
                <div id="contenedor-boton-nueva"></div>
                <div id="contenedor-boton-editar"></div>
                <div id="contenedor-boton-eliminar"></div>
            </div>
        </div>

        <!-- Search Bar y Filtro -->
        <div id="content-group" class="content-group">
            <div class="search-box" id="search-box">
                <i data-feather="search" class="d-search-icon"></i>
                <input type="text" id="d-search-input" class="d-search-input" placeholder="Buscar...">
            </div>
            <button id="btn-toggle-view" class="btn-secondary btn-icon-text" title="Cambiar vista">
                <i data-feather="calendar"></i>
                <span>Calendario</span>
            </button>
            <div id="contenedor-boton-filtro"></div>
        </div>

        <!-- Contenido de tabla -->
        <table id="tabla" class="dashboard-table-wrapper">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Actividad</th>
                    <th>Materia</th>
                    <th>Tipo</th>
                    <th>Progreso</th>
                </tr>
            </thead>
            <tbody id="tabla-body">
                <!-- Filas de actividades generadas dinámicamente -->
            </tbody>
        </table>

        <!-- Contenedor del Calendario -->
        <div id="calendar-view"></div>

        <p id="tabla-vacia" class="oculto">No se han encontrado actividades que coincidan con la búsqueda.</p>
        <div id="mensaje-vacio" class="oculto">
            <h3>No se han registrado actividades.</h3>
            <p>Presiona el botón "Nueva" para agregar una.</p>
        </div>
    </main>
    <!-- feather icons -->
    <script src="https://unpkg.com/feather-icons"></script>
    <!-- sidebar dinámico -->
    <script src="<?php echo $baseUrl; ?>assets/js/sidebar.js?v=<?php echo time(); ?>"></script>
    <!-- ui-helpers -->
    <script src="<?php echo $baseUrl; ?>assets/js/ui-helpers.js"></script>
    <!-- Modal nueva -->
    <script src="<?php echo $baseUrl; ?>assets/js/modal-nueva.js?v=<?php echo time(); ?>"></script>
    <!-- Modal filtro (define abrirModalFiltro) -->
    <script src="<?php echo $baseUrl; ?>assets/js/modal-filtro.js?v=<?php echo time(); ?>"></script>
    <!-- Lógica de botones -->
    <script src="<?php echo $baseUrl; ?>assets/js/buttons.js"></script>
    <!-- Lógica de la página Dashboard -->
    <script src="<?php echo $baseUrl; ?>js/dashboard.js"></script>
</body>

</html>