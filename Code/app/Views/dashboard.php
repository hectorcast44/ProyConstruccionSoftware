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
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/dashboard-table.css">
</head>

<body class="has-sidebar" data-page="dashboard">
    <!-- aquí se inyecta el sidebar -->
    <div id="sidebar-mount"></div>
    <main>
        <div class="main-bar">
            <div class="page-title">
                <i data-feather="home"></i>
                <h1>Dashboard</h1>
            </div>

            <!-- Botones -->
            <div class="contenedor-botones">
                <div id="contenedor-boton-nueva"></div>
                <div id="contenedor-boton-editar"></div>
                <div id="contenedor-boton-eliminar"></div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="content-group">
            <div class="search-content">
                <input type="text" id="search-input" class="search-input" placeholder="Buscar...">
            </div>
            <div id="contenedor-boton-filtro"></div>
        </div>



        <!-- Contenido de tabla -->
        <!-- Contenido de tabla -->
            <table id="tabla" class="dashboard-table-wrapper">
                <thead >
                    <tr>
                        <th>Fecha</th>
                        <th>Actividad</th>
                        <th>Materia</th>
                        <th>Tipo</th>
                        <th>Progreso</th>
                    </tr>
                </thead>
                <tbody id="tabla-body">
                    <tr>
                        <td>2025-11-21</td>
                        <td>Actividad 1	</td>
                        <td>Inferencia</td>
                        <td>Tarea</td>
                        <td>
                            <span class="progress-badge progress-encurso" data-progreso="en curso">
                                En curso
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>2025-12-25</td>
                        <td>Proyecto 1</td>
                        <td>Bases de datos</td>
                        <td>Proyecto</td>
                        <td>
                            <span class="progress-badge progress-completado" data-progreso="listo">
                                Listo
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>2025-11-19</td>
                        <td>Examen</td>
                        <td>Construcción de Software</td>
                        <td>Examen</td>
                        <td>
                            <span class="progress-badge progress-sininiciar" data-progreso="sin iniciar">
                                Sin iniciar
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p id="tabla-vacia" class="oculto">No se han encontrado actividades que coincidan con la búsqueda.</p>
            <div id="mensaje-vacio" class="oculto">
                <h3>No se han registrado actividades.</h3>
                <p>Presiona el botón "Nueva" para agregar una.</p>
                <img src="<?php echo $baseUrl; ?>/assets/img/empty-dashboard.png" alt="Lista vacía" height="250px">
            </div>
        </main>
    <!-- feather icons -->
    <script src="https://unpkg.com/feather-icons"></script>
    <!-- sidebar dinámico -->
    <script src="<?php echo $baseUrl; ?>assets/js/sidebar.js?v=<?php echo time(); ?>"></script>
     <!-- Modal nueva -->
    <script src="<?php echo $baseUrl; ?>assets/js/modal-nueva.js"></script>
    <!-- Lógica de botones -->
    <script src="<?php echo $baseUrl; ?>assets/js/buttons.js"></script>
    <!-- Lógica de la página Dashboard -->
    <script src="<?php echo $baseUrl; ?>js/dashboard.js"></script>
</body>

</html>