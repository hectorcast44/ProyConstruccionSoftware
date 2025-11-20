<?php
/**
 * API Endpoint: Detalle de calificaciones por materia.
 *
 * Responsabilidad:
 *  - Obtener la información general de una materia del usuario.
 *  - Obtener métricas de progreso (porcentaje, puntos, diagnóstico)
 *    a través de CalculadoraService.
 *  - Listar las actividades agrupadas por tipo de actividad.
 */

require_once '../src/db.php';
require_once '../src/CalculadoraService.php';

session_start();

// Configuración de CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Validación de método
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    enviarRespuesta(405, [
        'status'  => 'error',
        'message' => 'Método no permitido. Use GET.'
    ]);
}

// Id de usuario desde helper centralizado (db.php)
$idUsuario = obtenerIdUsuarioActual();

// id_materia desde query string (?id_materia= o ?id=)
$idMateria = 0;

if (isset($_GET['id_materia'])) {
    $idMateria = (int) $_GET['id_materia'];
} elseif (isset($_GET['id'])) {
    $idMateria = (int) $_GET['id'];
}

if ($idMateria <= 0) {
    enviarRespuesta(400, [
        'status'  => 'error',
        'message' => 'El parámetro "id_materia" es obligatorio y debe ser numérico.'
    ]);
}

try {
    $calculadora = new CalculadoraService($pdo);

    // Materia + progreso calculado vía servicio
    $resultadoMateria = $calculadora->obtenerMateriaConProgreso($idMateria, $idUsuario);
    $filaMateria = $resultadoMateria['materia'];
    $progreso    = $resultadoMateria['progreso'];

    // Actividades agrupadas por tipo
    $consultaActividades = '
        SELECT
            a.id_actividad,
            a.nombre_actividad,
            a.fecha_entrega,
            a.estado,
            a.puntos_obtenidos,
            a.puntos_posibles,
            ta.id_tipo_actividad,
            ta.nombre_tipo
        FROM ACTIVIDAD a
        INNER JOIN TIPO_ACTIVIDAD ta
            ON ta.id_tipo_actividad = a.id_tipo_actividad
        WHERE
            a.id_usuario = :id_usuario
            AND a.id_materia = :id_materia
        ORDER BY
            ta.nombre_tipo,
            a.fecha_entrega,
            a.nombre_actividad
    ';

    $sentenciaActividades = $pdo->prepare($consultaActividades);
    $sentenciaActividades->execute([
        ':id_usuario' => $idUsuario,
        ':id_materia' => $idMateria
    ]);

    $filasActividades = $sentenciaActividades->fetchAll();
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
            'obtenido' => (float) ($actividad['puntos_obtenidos'] ?? 0),
            'maximo' => (float) ($actividad['puntos_posibles']  ?? 0)
        ];
    }

    enviarRespuesta(200, [
        'status' => 'success',
        'data' => ['materia' => $filaMateria,'progreso' => $progreso, 'secciones' => array_values($secciones)]
    ]);

} catch (Exception $excepcion) {
    error_log('Error en calificaciones_detalle.php: ' . $excepcion->getMessage());

    $codigo = ($excepcion->getCode() >= 400 && $excepcion->getCode() < 600)
        ? $excepcion->getCode(): 500;

    enviarRespuesta($codigo, [
        'status'  => 'error',
        'message' => $excepcion->getMessage()
    ]);
}
