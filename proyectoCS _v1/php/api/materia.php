<?php
/**
 * API Endpoint para Gestión de Materias
 * 
 * Funcionalidades:
 * - Crear materia (POST)
 * - Actualizar materia (POST con id_materia)
 * - Eliminar materia (DELETE)
 * - Listar materias del usuario (GET)
 * - Obtener materia específica (GET con id_materia)
 */

// 1. Dependencias y Configuración
require_once '../src/db.php'; 
require_once '../src/MateriaService.php';

// 2. Iniciar Sesión
session_start();

// 3. Configuración de CORS
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

// 4. Manejar solicitud OPTIONS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(204);
    exit();
}

// 5. Seguridad: Verificar Sesión de Usuario
if (!isset($_SESSION['id_usuario'])) {
    enviarRespuesta(401, [
        'status' => 'error', 
        'message' => 'No autorizado. Por favor, inicie sesión.'
    ]);
}
$id_usuario = $_SESSION['id_usuario'];

// -------------------------------------------------
// --- Inicia Lógica Principal 
// -------------------------------------------------

try {
    $materiaService = new MateriaService($pdo);
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        
        // ========================================
        // GET: Obtener materias
        // ========================================
        case 'GET':
            if (isset($_GET['id_materia'])) {
                // Obtener una materia específica
                $resultado = $materiaService->obtenerPorId($_GET['id_materia'], $id_usuario);
                
                if ($resultado) {
                    enviarRespuesta(200, [
                        'status' => 'success',
                        'data' => $resultado
                    ]);
                } else {
                    enviarRespuesta(404, [
                        'status' => 'error',
                        'message' => 'Materia no encontrada.'
                    ]);
                }
            } else {
                // Obtener todas las materias del usuario
                $materias = $materiaService->obtenerPorUsuario($id_usuario);
                
                enviarRespuesta(200, [
                    'status' => 'success',
                    'data' => $materias
                ]);
            }
            break;

        // ========================================
        // POST: Crear o Actualizar materia
        // ========================================
        case 'POST':
            $data = obtenerDatosJSON();

            // Validación de campos obligatorios
            if (empty($data->nombre_materia)) {
                enviarRespuesta(400, [
                    'status' => 'error',
                    'message' => 'El campo "nombre_materia" es obligatorio.'
                ]);
            }

            // Validar longitud del nombre (máximo 100 caracteres según BD)
            if (strlen($data->nombre_materia) > 100) {
                enviarRespuesta(400, [
                    'status' => 'error',
                    'message' => 'El nombre de la materia no puede exceder 100 caracteres.'
                ]);
            }

            // Calificación mínima por defecto
            $calif_minima = isset($data->calif_minima) ? (int)$data->calif_minima : 70;

            // Iniciar transacción
            $pdo->beginTransaction();

            if (isset($data->id_materia) && !empty($data->id_materia)) {
                // --- ACTUALIZAR ---
                $materiaService->actualizar(
                    $data->id_materia,
                    $id_usuario,
                    $data->nombre_materia,
                    $calif_minima
                );

                $pdo->commit();

                enviarRespuesta(200, [
                    'status' => 'success',
                    'message' => 'Materia actualizada correctamente.',
                    'id_materia' => $data->id_materia
                ]);

            } else {
                // --- CREAR ---
                $id_materia_nueva = $materiaService->crear(
                    $id_usuario,
                    $data->nombre_materia,
                    $calif_minima
                );

                $pdo->commit();

                enviarRespuesta(201, [
                    'status' => 'success',
                    'message' => 'Materia creada correctamente.',
                    'id_materia' => $id_materia_nueva
                ]);
            }
            break;

        // ========================================
        // DELETE: Eliminar materia
        // ========================================
        case 'DELETE':
            if (isset($_GET['id_materia'])) {
                $id_materia = $_GET['id_materia'];
            } else {
                $data = obtenerDatosJSON();
                $id_materia = $data->id_materia ?? null;
            }

            if (!$id_materia) {
                enviarRespuesta(400, [
                    'status' => 'error',
                    'message' => 'Se requiere el ID de la materia.'
                ]);
            }

            // Iniciar transacción
            $pdo->beginTransaction();

            $resultado = $materiaService->eliminar($id_materia, $id_usuario);

            if ($resultado) {
                $pdo->commit();
                
                enviarRespuesta(200, [
                    'status' => 'success',
                    'message' => 'Materia eliminada correctamente.'
                ]);
            } else {
                $pdo->rollBack();
                
                enviarRespuesta(404, [
                    'status' => 'error',
                    'message' => 'Materia no encontrada o ya fue eliminada.'
                ]);
            }
            break;

        // ========================================
        // Método no permitido
        // ========================================
        default:
            enviarRespuesta(405, [
                'status' => 'error',
                'message' => 'Método no permitido'
            ]);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error de BD en materia.php: " . $e->getMessage());
    enviarRespuesta(500, [
        'status' => 'error',
        'message' => 'Error en la base de datos: ' . $e->getMessage()
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error general en materia.php: " . $e->getMessage());
    enviarRespuesta(500, [
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

// -------------------------------------------------
// --- Funciones Auxiliares 
// -------------------------------------------------

/**
 * Obtener y decodificar datos JSON del request
 */
function obtenerDatosJSON() {
    $json = file_get_contents('php://input');
    $data = json_decode($json);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        enviarRespuesta(400, [
            'status' => 'error',
            'message' => 'JSON inválido: ' . json_last_error_msg()
        ]);
    }
    
    return $data;
}

/**
 * Enviar respuesta JSON y terminar ejecución
 */
function enviarRespuesta($codigo, $datos) {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit();
}
?>
