<?php
/**
 * API Endpoint: Resumen de calificaciones por materia.
 *
 * Responsabilidad:
 *  - Listar las materias del usuario actual.
 *  - Para cada materia, devolver la suma de puntos obtenidos y posibles
 *    agrupados por tipo de actividad.
 *
 * Este endpoint no calcula calificaciones finales, únicamente resume datos
 * de la tabla ACTIVIDAD.
 */

require_once '../src/db.php';

session_start();

// Configuración CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Manejo de preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Validación de método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    enviarRespuesta(405, [
        'status' => 'error',
        'message' => 'Método no permitido. Use GET.'
    ]);
}

// Obtención del usuario actual (maneja modo desarrollo y sesión)
$idUsuario = obtenerIdUsuarioActual();

try {
    // Consulta de materias del usuario
    $consultaMaterias = '
        SELECT
            id_materia,
            nombre_materia
        FROM MATERIA
        WHERE id_usuario = :id_usuario
        ORDER BY nombre_materia
    ';

    $sentenciaMaterias = $pdo->prepare($consultaMaterias);
    $sentenciaMaterias->execute([
        ':id_usuario' => $idUsuario
    ]);

    $filasMaterias = $sentenciaMaterias->fetchAll();
    $materias = [];

    foreach ($filasMaterias as $fila) {
        $idMateria = (int) $fila['id_materia'];

        $materias[$idMateria] = [
            'id' => $idMateria,
            'nombre' => $fila['nombre_materia'],
            'tipos' => []
        ];
    }

    // Si no hay materias, devolver un arreglo vacío coherente
    if (empty($materias)) {
        enviarRespuesta(200, [
            'status' => 'success',
            'data' => []
        ]);
    }

    // Consulta de resumen por tipo de actividad
    $consultaResumen = '
        SELECT
            a.id_materia,
            ta.nombre_tipo,
            SUM(a.puntos_obtenidos) AS puntos_obtenidos,
            SUM(a.puntos_posibles)  AS puntos_posibles
        FROM ACTIVIDAD a
        INNER JOIN TIPO_ACTIVIDAD ta
            ON ta.id_tipo_actividad = a.id_tipo_actividad
        WHERE
            a.id_usuario = :id_usuario
        GROUP BY
            a.id_materia,
            ta.nombre_tipo
        ORDER BY
            a.id_materia,
            ta.nombre_tipo
    ';

    $sentenciaResumen = $pdo->prepare($consultaResumen);
    $sentenciaResumen->execute([
        ':id_usuario' => $idUsuario
    ]);

    $filasResumen = $sentenciaResumen->fetchAll();

    foreach ($filasResumen as $fila) {
        $idMateria = (int) $fila['id_materia'];

        if (!isset($materias[$idMateria])) {
            continue;
        }

        $materias[$idMateria]['tipos'][] = [
            'nombre' => $fila['nombre_tipo'],
            'obtenido' => $fila['puntos_obtenidos'] !== null ? (float) $fila['puntos_obtenidos'] : 0.0,
            'maximo' => $fila['puntos_posibles']  !== null ? (float) $fila['puntos_posibles']  : 0.0,
        ];
    }

    enviarRespuesta(200, [
        'status' => 'success',
        'data' => array_values($materias)
    ]);

} catch (Exception $excepcion) {
    error_log('Error en calificaciones_resumen.php: ' . $excepcion->getMessage());

    enviarRespuesta(500, [
        'status' => 'error',
        'message' => 'Error al obtener el resumen de calificaciones.',
        'detail' => $excepcion->getMessage()
    ]);
}
