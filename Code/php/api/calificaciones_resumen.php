<?php
/**
 * API: Resumen de calificaciones por materia
 * Conexión directa a agenda_escolar usando PDO.
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
// 1) Conexión a la BD
// ===========================
$host = 'localhost';
$db   = 'agenda_escolar';
$user = 'root';
$pass = '';
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
        'message' => 'Error interno del servidor: No se pudo conectar a la base de datos.',
        'detail'  => $e->getMessage()
    ]);
}

// ===========================
// 2) Usuario (por ahora fijo)
// ===========================
$id_usuario = 1;

// ===========================
// 3) Obtener materias
// ===========================
try {
    $sqlMaterias = "
        SELECT
            `id_materia`,
            `nombre_materia`
        FROM `MATERIA`
        WHERE `id_usuario` = :id_usuario
        ORDER BY `nombre_materia`
    ";

    $stmtMaterias = $pdo->prepare($sqlMaterias);
    $stmtMaterias->execute([':id_usuario' => $id_usuario]);
    $materiasBD = $stmtMaterias->fetchAll();

    $materias = [];
    foreach ($materiasBD as $row) {
        $id_materia = (int) $row['id_materia'];

        $materias[$id_materia] = [
            'id'     => $id_materia,
            'nombre' => $row['nombre_materia'],
            'tipos'  => []
        ];
    }

    if (empty($materias)) {
        send_json(200, [
            'status' => 'success',
            'data'   => []
        ]);
    }

    // ===========================
    // 4) Sumar puntos por tipo
    // ===========================
    $sqlResumen = "
        SELECT
            a.`id_materia`,
            ta.`nombre_tipo`,
            SUM(a.`puntos_obtenidos`) AS puntos_obtenidos,
            SUM(a.`puntos_posibles`)  AS puntos_posibles
        FROM `ACTIVIDAD` a
        INNER JOIN `TIPO_ACTIVIDAD` ta
            ON ta.`id_tipo_actividad` = a.`id_tipo_actividad`
        WHERE
            a.`id_usuario` = :id_usuario
        GROUP BY
            a.`id_materia`,
            ta.`nombre_tipo`
        ORDER BY
            a.`id_materia`,
            ta.`nombre_tipo`
    ";

    $stmtResumen = $pdo->prepare($sqlResumen);
    $stmtResumen->execute([':id_usuario' => $id_usuario]);
    $filas = $stmtResumen->fetchAll();

    foreach ($filas as $fila) {
        $id_materia = (int) $fila['id_materia'];
        if (!isset($materias[$id_materia])) {
            continue;
        }

        $materias[$id_materia]['tipos'][] = [
            'nombre'   => $fila['nombre_tipo'],
            'obtenido' => $fila['puntos_obtenidos'] !== null ? (float) $fila['puntos_obtenidos'] : 0,
            'maximo'   => $fila['puntos_posibles']  !== null ? (float) $fila['puntos_posibles']  : 0,
        ];
    }

    // ===========================
    // 5) Respuesta final
    // ===========================
    send_json(200, [
        'status' => 'success',
        'data'   => array_values($materias)
    ]);

} catch (Exception $e) {
    send_json(500, [
        'status'  => 'error',
        'message' => 'Error al obtener el resumen de materias.',
        'detail'  => $e->getMessage()
    ]);
}
