<?php
/**
 * ============================================================================
 * ARCHIVO DE CONEXIÓN Y CONFIGURACIÓN GLOBAL
 * ============================================================================
 * Proporciona la conexión PDO y funciones auxiliares comunes a la API.
 * Debe incluirse al inicio de cada endpoint.
 */

/**
 * Modo de desarrollo para pruebas sin login.
 * Cuando está activado, se usa un id_usuario fijo definido en USUARIO_PRUEBAS.
 * IMPORTANTE: desactivar en producción.
 */
define('MODO_DESARROLLO', true);
define('USUARIO_PRUEBAS', 1);

/**
 * Envía una respuesta JSON estandarizada y finaliza el script.
 *
 * @param int   $statusCode Código de estado HTTP (ej. 200, 400, 500)
 * @param array $data       Cuerpo de la respuesta
 *
 * @return void
 */
function enviarRespuesta(int $statusCode, array $data): void
{
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Obtiene el cuerpo JSON de la solicitud y lo decodifica.
 *
 * @return object|null Objeto decodificado o null si no hay JSON válido
 */
function obtenerDatosJSON(): ?object
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return null;
    }

    $decoded = json_decode($raw);
    return $decoded instanceof stdClass ? $decoded : null;
}

/**
 * Obtiene el identificador del usuario actual.
 * Considera el modo de desarrollo y la sesión.
 *
 * Requiere que la sesión haya sido iniciada previamente con session_start().
 *
 * @return int Id de usuario válido para usar en consultas
 */
function obtenerIdUsuarioActual(): int
{
    if (defined('MODO_DESARROLLO') && MODO_DESARROLLO === true) {
        return (int) USUARIO_PRUEBAS;
    }

    if (!isset($_SESSION['id_usuario'])) {
        enviarRespuesta(401, [
            'status'  => 'error',
            'message' => 'No autorizado. Inicie sesión.'
        ]);
    }

    return (int) $_SESSION['id_usuario'];
}

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'agenda_escolar');
define('DB_CHARSET', 'utf8mb4');

// Construcción del DSN de PDO
$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

// Opciones recomendadas para PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Creación de la conexión PDO compartida
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log('Error de conexión a la BD: ' . $e->getMessage());

    enviarRespuesta(500, [
        'status'  => 'error',
        'message' => 'Error interno del servidor: no se pudo conectar a la base de datos.'
    ]);
}
