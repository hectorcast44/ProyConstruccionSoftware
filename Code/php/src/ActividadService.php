<?php
/**
 * API Endpoint: Detalle de calificaciones por materia.
 *
 * Responsabilidad:
 *  - Obtener la información de la materia y su progreso usando CalculadoraService.
 *  - Obtener las actividades mediante ActividadService.
 *  - Entregar al frontend la data ya organizada.
 */

require_once '../src/db.php';
require_once '../src/CalculadoraService.php';
require_once '../src/ActividadService.php';

session_start();

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    enviarRespuesta(405, [
        'status' => 'error',
        'message' => 'Método no permitido. Use GET.'
    ]);
}

// Usuario actual desde helper
$idUsuario = obtenerIdUsuarioActual();

// Leer id_materia desde ?id_materia= o ?id=
$idMateria = 0;

if (isset($_GET['id_materia'])) {
    $idMateria = (int) $_GET['id_materia'];
} elseif (isset($_GET['id'])) {
    $idMateria = (int) $_GET['id'];
}

if ($idMateria <= 0) {
    enviarRespuesta(400, [
        'status' => 'error',
        'message' => 'El parámetro "id_materia" es obligatorio y debe ser numérico.'
    ]);
}

try {

    // Servicios
    $calculadora = new CalculadoraService($pdo);
    $actividadService = new ActividadService($pdo);

    //Obtener materia + progreso (se recalcula internamente)
    $resultadoMateria = $calculadora->obtenerMateriaConProgreso($idMateria, $idUsuario);
    $filaMateria = $resultadoMateria['materia'];
    $progreso = $resultadoMateria['progreso'];

    //Obtener actividades con sus tipos usando ActividadService
    $filasActividades = $actividadService->obtenerPorMateria($idMateria, $idUsuario);
    $secciones = [];

    foreach ($filasActividades as $actividad) {
        $idTipoActividad = (int) $actividad['id_tipo_actividad'];

        if (!isset($secciones[$idTipoActividad])) {
            $secciones[$idTipoActividad] = [
                'id_tipo' => $idTipoActividad,
                'nombre_tipo' => $actividad['nombre_tipo'],
                'actividades' => []
            ];
        }

        $secciones[$idTipoActividad]['actividades'][] = [
            'id_actividad' => (int) $actividad['id_actividad'],
            'nombre' => $actividad['nombre_actividad'],
            'fecha_entrega' => $actividad['fecha_entrega'],
            'estado' => $actividad['estado'],
            'obtenido'  => $actividad['puntos_obtenidos'] !== null
                ? (float) $actividad['puntos_obtenidos']: null,
            'maximo' => $actividad['puntos_posibles'] !== null
                ? (float) $actividad['puntos_posibles']: 0.0,
        ];

    }

    enviarRespuesta(200, [
        'status' => 'success',
        'data' => [
            'materia' => $filaMateria,
            'progreso' => $progreso,
            'secciones' => array_values($secciones)
        ]
    ]);

} catch (Exception $e) {

    error_log('Error en calificaciones_detalle.php: ' . $e->getMessage());

    $codigo = ($e->getCode() >= 400 && $e->getCode() < 600)
        ? $e->getCode()
        : 500;

    enviarRespuesta($codigo, [
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
