<?php
/**
 * API Endpoint para Tipos de Actividades
 */

// 1. Dependencias
require_once '../src/db.php'; 
require_once '../src/TipoActividadService.php';

// 2. Iniciar Sesión
session_start();

// 3. Configuración de CORS
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS"); 
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

// 4. Seguridad: Verificar Sesión de Usuario
if ($_SERVER['REQUEST_METHOD'] != 'OPTIONS' && !isset($_SESSION['id_usuario'])) {
    enviarRespuesta(401, [
        'status' => 'error', 
        'message' => 'No autorizado. Por favor, inicie sesión.'
    ]);
}
$id_usuario = $_SESSION['id_usuario'] ?? 0;

// 5. Instanciar Servicio
$tipoActividadService = new TipoActividadService($pdo);

// -------------------------------------------------
// --- Enrutador Principal
// -------------------------------------------------

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        
        // --- OBTENER (GET) ---
        case 'GET':
            // Si viene un ID en la URL, obtener uno específico
            if (!empty($_GET['id'])) {
                $id_tipo_actividad = (int)$_GET['id'];
                $tipo = $tipoActividadService->obtenerTipoActividadPorId($id_tipo_actividad, $id_usuario);
                
                if ($tipo === null) {
                    throw new Exception("Tipo de actividad no encontrado.", 404);
                }
                
                enviarRespuesta(200, [
                    'status' => 'success',
                    'data' => $tipo
                ]);
            } else {
                // Obtener todos los tipos
                $tipos = $tipoActividadService->obtenerTiposActividad($id_usuario);
                
                enviarRespuesta(200, [
                    'status' => 'success',
                    'data' => $tipos
                ]);
            }
            break;

        // --- CREAR (POST) ---
        case 'POST':
            $pdo->beginTransaction();
            
            $data = obtenerDatosJSON();
            
            // Validar datos
            if (empty($data->nombre_tipo) || trim($data->nombre_tipo) === '') {
                throw new Exception("El campo 'nombre_tipo' es obligatorio.", 400);
            }

            $datos = [
                'nombre_tipo' => trim($data->nombre_tipo),
                'id_usuario' => $id_usuario
            ];

            $id_tipo_creado = $tipoActividadService->crearTipoActividad($datos);
            
            $pdo->commit();
            enviarRespuesta(201, [
                'status' => 'success',
                'message' => 'Tipo de actividad creado exitosamente.',
                'id_tipo_actividad' => $id_tipo_creado
            ]);
            break;

        // --- EDITAR (PUT) ---
        case 'PUT':
            $pdo->beginTransaction();
            
            $data = obtenerDatosJSON();
            
            if (empty($data->id_tipo_actividad)) {
                throw new Exception("Se requiere 'id_tipo_actividad' para editar.", 400);
            }
            
            if (empty($data->nombre_tipo) || trim($data->nombre_tipo) === '') {
                throw new Exception("El campo 'nombre_tipo' es obligatorio.", 400);
            }

            $id_tipo_actividad = (int)$data->id_tipo_actividad;
            $datos = [
                'nombre_tipo' => trim($data->nombre_tipo),
                'id_usuario' => $id_usuario
            ];

            $tipoActividadService->editarTipoActividad($id_tipo_actividad, $datos);
            
            $pdo->commit();
            enviarRespuesta(200, [
                'status' => 'success',
                'message' => 'Tipo de actividad actualizado exitosamente.'
            ]);
            break;

        // --- ELIMINAR (DELETE) ---
        case 'DELETE':
            $pdo->beginTransaction();
            
            if (empty($_GET['id'])) {
                throw new Exception("Se requiere 'id' en la URL para eliminar.", 400);
            }
            
            $id_tipo_actividad = (int)$_GET['id'];
            $tipoActividadService->eliminarTipoActividad($id_tipo_actividad, $id_usuario);
            
            $pdo->commit();
            enviarRespuesta(200, [
                'status' => 'success',
                'message' => 'Tipo de actividad eliminado exitosamente.'
            ]);
            break;

        // --- Pre-flight (OPTIONS) ---
        case 'OPTIONS':
            enviarRespuesta(204, []);
            break;

        // --- Otros métodos ---
        default:
            throw new Exception("Método no permitido.", 405);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    
    $codigo = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
    
    if ($codigo == 500) error_log("Error en tipo_actividad.php: " . $e->getMessage());
    
    enviarRespuesta($codigo, ['status' => 'error', 'message' => $e->getMessage()]);
}
?>
