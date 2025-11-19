<?php
/**
 * API Endpoint: Detalle de calificaciones por materia.
 *
 * Responsabilidad:
 *  - Obtener la información general de una materia del usuario.
 *  - Calcular métricas de progreso (porcentaje obtenido, máximo posible,
 *    puntos necesarios para aprobar, diagnóstico).
 *  - Listar las actividades agrupadas por tipo de actividad.
 *
 * Entrada:
 *  - Parámetro GET: id_materia (o id) con el identificador de la materia.
 *
 * Salida (JSON):
 *  - data.materia   -> fila de la tabla MATERIA.
 *  - data.progreso  -> métricas calculadas para la UI.
 *  - data.secciones -> actividades agrupadas por tipo.
 */

require_once '../src/db.php';

session_start();

// Configuración de CORS
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
        'message'=> 'Método no permitido. Use GET.'
    ]);
}

/**
 * Calcula métricas de progreso para una materia a partir de sus totales.
 *
 * @param float $puntosGanados       Puntos obtenidos por el estudiante.
 * @param float $puntosPerdidos      Puntos perdidos (posibles - obtenidos).
 * @param float $puntosPendientes    Puntos de actividades aún no calificadas.
 * @param float $calificacionMinima  Calificación mínima para aprobar (0-100).
 * @param float $calificacionActual  Calificación actual calculada y guardada.
 *
 * @return array Estructura con porcentajes, puntos y diagnóstico.
 */
function calcularProgresoMateria(
    float $puntosGanados,
    float $puntosPerdidos,
    float $puntosPendientes,
    float $calificacionMinima,
    float $calificacionActual
): array {
    $totalPuntos = $puntosGanados + $puntosPerdidos + $puntosPendientes;

    if ($totalPuntos > 0) {
        $porcentajeObtenido = ($puntosGanados / $totalPuntos) * 100.0;
        $porcentajeMaxPosible = (($puntosGanados + $puntosPendientes) / $totalPuntos) * 100.0;

        $puntosRequeridosAprobar = ($calificacionMinima / 100.0) * $totalPuntos;
        $puntosNecesarios = max(0.0, $puntosRequeridosAprobar - $puntosGanados);
    } else {
        $porcentajeObtenido = 0.0;
        $porcentajeMaxPosible = 0.0;
        $puntosNecesarios = 0.0;
    }

    $porcentajeObtenido = round($porcentajeObtenido, 2);
    $porcentajeMaxPosible = round($porcentajeMaxPosible, 2);
    $puntosNecesarios = round($puntosNecesarios, 2);

    $estado = 'En riesgo';
    $nivel = 'risk';

    if ($porcentajeObtenido >= $calificacionMinima) {
        $estado = 'Aprobado';
        $nivel = 'ok';
    } elseif ($porcentajeObtenido < $calificacionMinima - 10) {
        $estado = 'Reprobado';
        $nivel = 'fail';
    }

    return [
        'porcentaje_obtenido' => $porcentajeObtenido,
        'porcentaje_total' => 100,
        'puntos_obtenidos' => $puntosGanados,
        'puntos_perdidos' => $puntosPerdidos,
        'puntos_posibles_obtener' => $puntosPendientes,
        'puntos_necesarios_aprobar' => $puntosNecesarios,
        'calificacion_minima' => $calificacionMinima,
        'calificacion_actual' => $calificacionActual,
        'calificacion_maxima_posible' => $porcentajeMaxPosible,
        'diagnostico' => [
            'grado' => round($porcentajeObtenido),
            'estado' => $estado,
            'nivel' => $nivel
        ]
    ];
}

// Id de usuario desde helper centralizado
$idUsuario = obtenerIdUsuarioActual();

// Obtener id_materia desde la URL (?id_materia= o ?id=)
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
    // Consulta de información base de la materia
    $consultaMateria = '
        SELECT
            id_materia,
            nombre_materia,
            calif_minima,
            calificacion_actual,
            puntos_ganados,
            puntos_perdidos,
            puntos_pendientes
        FROM MATERIA
        WHERE id_usuario = :id_usuario
          AND id_materia = :id_materia
        LIMIT 1
    ';

    $sentenciaMateria = $pdo->prepare($consultaMateria);
    $sentenciaMateria->execute([
        ':id_usuario' => $idUsuario,
        ':id_materia' => $idMateria
    ]);

    $filaMateria = $sentenciaMateria->fetch();

    if (!$filaMateria) {
        enviarRespuesta(404, [
            'status' => 'error',
            'message' => 'No se encontró la materia solicitada para el usuario actual.'
        ]);
    }

    $puntosGanados = (float) $filaMateria['puntos_ganados'];
    $puntosPerdidos = (float) $filaMateria['puntos_perdidos'];
    $puntosPendientes = (float) $filaMateria['puntos_pendientes'];
    $calificacionMin = (float) $filaMateria['calif_minima'];
    $calificacionAct = (float) $filaMateria['calificacion_actual'];

    $progreso = calcularProgresoMateria(
        $puntosGanados,
        $puntosPerdidos,
        $puntosPendientes,
        $calificacionMin,
        $calificacionAct
    );

    // Consulta de actividades agrupadas por tipo
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
        'data' => [
            'materia' => $filaMateria,
            'progreso' => $progreso,
            'secciones' => array_values($secciones)
        ]
    ]);

} catch (Exception $excepcion) {
    error_log('Error en calificaciones_detalle.php: ' . $excepcion->getMessage());

    enviarRespuesta(500, [
        'status' => 'error',
        'message' => 'Error al obtener el detalle de la materia.',
        'detail' => $excepcion->getMessage()
    ]);
}
