<?php

use App\Core\Router;

session_start();

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

$router = new Router();

/* ============================
 * RUTAS DE AUTENTICACIÓN
 * ============================ */

$router->get('/auth/login', [App\Controllers\AuthController::class, 'showLogin']);
$router->post('/auth/login', [App\Controllers\AuthController::class, 'login']);
$router->post('/auth/register', [App\Controllers\AuthController::class, 'register']);
$router->get('/logout', [App\Controllers\AuthController::class, 'logout']);
$router->get('/auth/me', [App\Controllers\AuthController::class, 'me']);

/* ============================
 * RUTAS WEB
 * ============================ */

$router->get('/', function () {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $baseDir = rtrim(dirname($scriptName), '/');
    $baseUrl = $baseDir . '/'; 

    if (empty($_SESSION['id_usuario'])) {
        header('Location: ' . $baseUrl . 'auth/login');
    } else {
        header('Location: ' . $baseUrl . 'dashboard');
    }
    exit;
});

// Dashboard (protegida por el middleware "manual" en la vista)
$router->get('/dashboard', [App\Controllers\DashboardController::class, 'index']);
$router->get('/mis-calificaciones', [App\Controllers\CalificacionesController::class, 'index']);
$router->get('/mis-calificaciones/detalle', [App\Controllers\CalificacionesController::class, 'detalle']);

/* ============================
 * RUTAS API
 * ============================ */

$router->get('/api/materias', [App\Controllers\Api\MateriaController::class, 'index']);
$router->post('/api/materias', [App\Controllers\Api\MateriaController::class, 'store']);
$router->delete('/api/materias', [App\Controllers\Api\MateriaController::class, 'delete']);
$router->get('/api/actividades', [App\Controllers\Api\ActividadController::class, 'index']);
$router->post('/api/actividades', [App\Controllers\Api\ActividadController::class, 'store']);

// Tipo Actividad Routes
$router->get('/api/tipos-actividad', [App\Controllers\Api\TipoActividadController::class, 'index']);
$router->post('/api/tipos-actividad', [App\Controllers\Api\TipoActividadController::class, 'store']);
$router->post('/api/tipos-actividad/update', [App\Controllers\Api\TipoActividadController::class, 'update']);
$router->delete('/api/tipos-actividad', [App\Controllers\Api\TipoActividadController::class, 'delete']);

$router->resolve();
