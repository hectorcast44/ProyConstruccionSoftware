<?php
/**
 * ============================================================================
 * ARCHIVO DE CONEXIÓN Y CONFIGURACIÓN GLOBAL
 * ============================================================================
 * Fusiona la conexión a BD (PDO) y funciones auxiliares para la API.
 * Este archivo se incluye al inicio de cada endpoint de la API.
 */

// --- Funciones auxiliares para la API ---

/**
 * Envía una respuesta JSON estandarizada y finaliza el script.
 * @param int $statusCode - El código de estado HTTP (ej. 200, 400, 500)
 * @param array $data - El payload de datos a enviar
 */
function enviarRespuesta($statusCode, $data) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

/**
 * Obtiene el cuerpo de la solicitud JSON (ej. de un POST o PUT).
 * @return object|null - Los datos decodificados o null si hay error
 */
function obtenerDatosJSON() {
    $json = file_get_contents('php://input');
    return json_decode($json);
}


// --- Configuración de la base de datos (tomado de conexion.php) ---
define('DB_HOST', 'localhost');
define('DB_USER', 'joseph');           // Usuario de MySQL
define('DB_PASS', 'garavi1619');       // Contraseña
define('DB_NAME', 'agenda_escolar'); // Nombre de la base de datos
define('DB_CHARSET', 'utf8mb4');       // Charset recomendado


// --- Lógica de Conexión PDO (El estándar moderno) ---

// 1. DSN (Data Source Name)
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

// 2. Opciones de PDO
$options = [

    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    
    // Traer resultados como arrays asociativos
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    
    // Desactivar emulación de preparados para usar preparados reales
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// 3. Crear la conexión
$pdo = null;

try {
    // Intentar conectar
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (\PDOException $e) {
    
    error_log("Error de conexión a la BD: " . $e->getMessage());
    
    enviarRespuesta(500, [
        'status' => 'error', 
        'message' => 'Error interno del servidor: No se pudo conectar a la base de datos.'
    ]);
}

?>