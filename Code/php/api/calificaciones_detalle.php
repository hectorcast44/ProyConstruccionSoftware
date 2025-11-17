<?php
/**
 * API: Detalle de calificaciones por materia
 *
 * Devuelve:
 *  - info de la materia
 *  - actividades agrupadas por tipo
 *  - resumen de progreso (para la card y el diagnóstico)
 */

header('Content-Type: application/json; charset=utf-8');

// ---------------------------
// Helper de respuesta JSON
// ---------------------------
function send_json(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// ---------------------------
// CORS básico
// ---------------------------
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    send_json(204, []);
}

// Solo GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_json(405, [
        'status'  => 'error',
        'message' => 'Método no permitido. Use GET.'
    ]);
}

// ===========================
// 1) Validar id_materia
// ===========================
$id_materia = isset($_GET['id_materia']) ? (int) $_GET['id_materia'] : 0;
if ($id_materia <= 0) {
    send_json(400, [
        'status'  => 'error',
        'message' => 'Parámetro id_materia es requerido y debe ser numérico.'
    ]);
}

// ===========================
// 2) Conexión a la BD
// ===========================
$host    = 'localhost';
$db      = 'agenda_escolar';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    send_json(500, [
        'status'  => 'error',
        'message' => 'No se pudo conectar a la base de datos.',
        'detail'  => $e->getMessage()
    ]);
}

// ===========================
// 3) Usuario (por ahora fijo)
// ===========================
$id_usuario = 1;

// ===========================
// 4) Consultar info base de la materia
// ===========================
try {
    $sqlMateria = "
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
    ";

    $stmtMateria = $pdo->prepare($sqlMateria);
    $stmtMateria->execute([
        ':id_usuario' => $id_usuario,
        ':id_materia' => $id_materia
    ]);

    $materiaRow = $stmtMateria->fetch();

    if (!$materiaRow) {
        send_json(404, [
            'status'  => 'error',
            'message' => 'No se encontró la materia solicitada para este usuario.'
        ]);
    }

    // Normalizar info base
    $p_ganados    = (float) $materiaRow['puntos_ganados'];
    $p_perdidos   = (float) $materiaRow['puntos_perdidos'];
    $p_pendientes = (float) $materiaRow['puntos_pendientes'];
    $total_puntos = $p_ganados + $p_perdidos + $p_pendientes;

    $calif_min    = (float) $materiaRow['calif_minima'];
    $calif_actual = (float) $materiaRow['calificacion_actual'];

    // ===============================
    // Calcular progreso
    // ===============================
    if ($total_puntos > 0) {
        $porcentaje_obtenido = ($p_ganados / $total_puntos) * 100;
        $porcentaje_max_posible = (($p_ganados + $p_pendientes) / $total_puntos) * 100;

        $puntos_requeridos_para_aprobar = ($calif_min / 100) * $total_puntos;
        $puntos_necesarios = max(0, $puntos_requeridos_para_aprobar - $p_ganados);
    } else {
        $porcentaje_obtenido = 0;
        $porcentaje_max_posible = 0;
        $puntos_necesarios = 0;
    }

    // Redondeos para UI
    $porcentaje_obtenido    = round($porcentaje_obtenido, 2);
    $porcentaje_max_posible = round($porcentaje_max_posible, 2);
    $puntos_necesarios      = round($puntos_necesarios, 2);

    // ===============================
    // Diagnóstico
    // ===============================
    $estado = 'En riesgo';
    $nivel  = 'risk';

    if ($porcentaje_obtenido >= $calif_min) {
        $estado = 'Aprobado';
        $nivel  = 'ok';
    } elseif ($porcentaje_obtenido < $calif_min - 10) {
        $estado = 'Reprobado';
        $nivel  = 'fail';
    }

    // ===============================
    // Construir bloque progreso
    // ===============================
    $progreso = [
        'porcentaje_obtenido' => $porcentaje_obtenido,
        'porcentaje_total'    => 100,

        'puntos_obtenidos'          => $p_ganados,
        'puntos_perdidos'           => $p_perdidos,
        'puntos_posibles_obtener'   => $p_pendientes,
        'puntos_necesarios_aprobar' => $puntos_necesarios,

        'calificacion_minima'         => $calif_min,
        'calificacion_actual'         => $calif_actual,
        'calificacion_maxima_posible' => $porcentaje_max_posible,

        // datos del círculo
        'diagnostico' => [
            'grado'  => round($porcentaje_obtenido),
            'estado' => $estado,
            'nivel'  => $nivel
        ]
    ];

    // ===============================
    // 5) Actividades agrupadas por tipo
    // ===============================
    $sqlActividades = "
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
    ";

    $stmtAct = $pdo->prepare($sqlActividades);
    $stmtAct->execute([
        ':id_usuario' => $id_usuario,
        ':id_materia' => $id_materia
    ]);

    $rows = $stmtAct->fetchAll();
    $secciones = [];

    foreach ($rows as $r) {
        $id_tipo = (int) $r['id_tipo_actividad'];

        if (!isset($secciones[$id_tipo])) {
            $secciones[$id_tipo] = [
                'id_tipo'     => $id_tipo,
                'nombre_tipo' => $r['nombre_tipo'],
                'actividades' => []
            ];
        }

        $secciones[$id_tipo]['actividades'][] = [
            'id_actividad'  => (int) $r['id_actividad'],
            'nombre'        => $r['nombre_actividad'],
            'fecha_entrega' => $r['fecha_entrega'],
            'estado'        => $r['estado'],
            'obtenido'      => (float) ($r['puntos_obtenidos'] ?? 0),
            'maximo'        => (float) ($r['puntos_posibles'] ?? 0)
        ];
    }

    // ===============================
    // 6) Respuesta final
    // ===============================
    send_json(200, [
        'status' => 'success',
        'data'   => [
            'materia'   => $materiaRow,
            'progreso'  => $progreso,
            'secciones' => array_values($secciones)
        ]
    ]);

} catch (Exception $e) {
    send_json(500, [
        'status'  => 'error',
        'message' => 'Error al obtener el detalle de la materia.',
        'detail'  => $e->getMessage()
    ]);
}
