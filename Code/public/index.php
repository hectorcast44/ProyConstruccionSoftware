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

// Web Routes
$router->get('/', [App\Controllers\DashboardController::class, 'index']);
$router->get('/dashboard', [App\Controllers\DashboardController::class, 'index']);
$router->get('/auth/me', [App\Controllers\AuthController::class, 'me']);

$router->get('/mis-calificaciones', [App\Controllers\CalificacionesController::class, 'index']);
$router->get('/mis-calificaciones/detalle', [App\Controllers\CalificacionesController::class, 'detalle']);

$router->get('/mis-materias', [App\Controllers\MisMateriasController::class, 'index']);

// API Routes
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
