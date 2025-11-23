<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script>
        const BASE_URL = "<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/";
    </script>
    <!-- estilos globales -->
    <link rel="stylesheet"
        href="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/assets/css/sidebar.css" />
    <link rel="stylesheet" href="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/assets/css/layout.css" />
    <link rel="stylesheet"
        href="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/assets/css/search-bar.css" />
    <link rel="stylesheet"
        href="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/assets/css/table-theme.css" />
    <link rel="stylesheet"
        href="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/assets/css/buttons.css" />
    <link rel="stylesheet" href="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/assets/css/modal.css">
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
        </div>



        <!-- Contenido de tabla -->
        <table id="tabla" style="margin-top: 20px; width:100%">
            <thead>
                <tr>
                    <th>Actividad</th>
                    <th>Materia</th>
                    <th>Tipo</th>
                    <th>Puntaje Máx</th>
                    <th>Puntaje</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody id="tabla-body"></tbody>
        </table>

    </main>
    <!-- feather icons -->
    <script src="https://unpkg.com/feather-icons"></script>
    <!-- sidebar dinámico -->
    <script src="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/assets/js/sidebar.js"></script>
    <!-- Lógica del modal -->
    <script src="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/assets/js/modal-nueva.js"></script>
    <!-- Lógica de boton nueva calificación -->
    <script src="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/assets/js/buttons.js"></script>
    <!-- Lógica de la página Dashboard -->
    <script src="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/js/dashboard.js"></script>
</body>

</html>