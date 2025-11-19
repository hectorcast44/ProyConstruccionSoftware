<?php
/**
 * API Endpoint (Controlador RESTful) para Actividades

 */

// 1. Dependencias
require_once '../src/db.php'; 
require_once '../src/CalculadoraService.php';
require_once '../src/ActividadService.php'; 

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
$id_usuario = $_SESSION['id_usuario'] ?? 0; // Asignar 0 si es OPTIONS

// 5. Instanciar Servicios
$actividadService = new ActividadService($pdo);
$calculadora = new CalculadoraService($pdo);

// -------------------------------------------------
// --- Enrutador Principal (Manejo de Métodos)
// -------------------------------------------------

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        
        // --- OBTENER (GET) ---
        case 'GET':
            // Construir filtros desde los parámetros de consulta
            $filtros = [];
            
            if (!empty($_GET['id_materia'])) {
                $filtros['id_materia'] = (int)$_GET['id_materia'];
            }
            
            if (!empty($_GET['id_tipo_actividad'])) {
                $filtros['id_tipo_actividad'] = (int)$_GET['id_tipo_actividad'];
            }
            
            if (!empty($_GET['estado'])) {
                $filtros['estado'] = $_GET['estado'];
            }
            
            if (!empty($_GET['fecha_desde'])) {
                $filtros['fecha_desde'] = $_GET['fecha_desde'];
            }
            
            if (!empty($_GET['fecha_hasta'])) {
                $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
            }
            
            if (!empty($_GET['buscar'])) {
                $filtros['buscar'] = $_GET['buscar'];
            }
            
            // Obtener actividades con filtros
            $actividades = $actividadService->obtenerActividades($id_usuario, $filtros);
            
            enviarRespuesta(200, [
                'status' => 'success',
                'data' => $actividades,
                'total' => count($actividades)
            ]);
            break;
        
        // --- CREAR (POST) ---
        case 'POST':
            $pdo->beginTransaction();
            $data = obtenerDatosJSON();
            // Validar y preparar datos
            $datosParaGuardar = validarYPrepararDatos($data, $id_usuario);
            
            // Llamar al servicio
            $id_actividad_guardada = $actividadService->crearActividad($datosParaGuardar);
            
            // Recalcular
            $calculadora->recalcularMateria($datosParaGuardar['id_materia'], $id_usuario);
            
            // Enviar respuesta
            $pdo->commit();
            enviarRespuesta(201, [
                'status' => 'success',
                'message' => 'Actividad creada.',
                'id_actividad' => $id_actividad_guardada
            ]);
            break;

        // --- EDITAR (PUT) ---
        case 'PUT':
            $pdo->beginTransaction();
            $data = obtenerDatosJSON();
            
            // PUT requiere un ID
            if (empty($data->id_actividad)) {
                throw new Exception("Se requiere 'id_actividad' para editar.", 400);
            }
            $id_actividad = (int)$data->id_actividad;
            
            // Validar y preparar datos
            $datosParaGuardar = validarYPrepararDatos($data, $id_usuario);
            
            // Llamar al servicio
            $actividadService->editarActividad($id_actividad, $datosParaGuardar);
            
            // Recalcular
            $calculadora->recalcularMateria($datosParaGuardar['id_materia'], $id_usuario);
            
            // Enviar respuesta
            $pdo->commit();
            enviarRespuesta(200, [
                'status' => 'success',
                'message' => 'Actividad actualizada.'
            ]);
            break;

        // --- ELIMINAR (DELETE) ---
        case 'DELETE':
            $pdo->beginTransaction();
            // El ID vendrá por la URL (ej. ?id=15)
            if (empty($_GET['id'])) {
                throw new Exception("Se requiere 'id' en la URL para eliminar.", 400);
            }
            $id_actividad = (int)$_GET['id'];
            
            // Llamar al servicio (devuelve id_materia para recalcular)
            $id_materia = $actividadService->eliminarActividad($id_actividad, $id_usuario);
            
            $calculadora->recalcularMateria($id_materia, $id_usuario);
            
            // Enviar respuesta
            $pdo->commit();
            enviarRespuesta(200, [
                'status' => 'success',
                'message' => 'Actividad eliminada.'
            ]);
            break;
            
        // --- Pre-flight (OPTIONS) ---
        case 'OPTIONS':
            enviarRespuesta(204, []);
            break;

        // --- Otros métodos (GET, etc.) ---
        default:
            throw new Exception("Método no permitido.", 405);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    
    // Usar el código de la excepción si está disponible (ej. 400, 404), si no, 500
    $codigo = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
    
    // Loguear el error real
    if ($codigo == 500) error_log("Error en actividad.php: " . $e->getMessage());
    
    // Enviar mensaje amigable
    enviarRespuesta($codigo, ['status' => 'error', 'message' => $e->getMessage()]);
}


/**
 * Función Auxiliar de Validación
 * Reúne la lógica de validación que es común para CREAR y EDITAR.
 */
function validarYPrepararDatos($data, $id_usuario) {
    $campos_requeridos = ['id_materia', 'id_tipo_actividad', 'nombre_actividad', 'fecha_entrega', 'estado'];
    foreach ($campos_requeridos as $campo) {
        if (empty($data->{$campo})) {
            throw new Exception("Datos incompletos. El campo '$campo' es obligatorio.", 400);
        }
    }

    $puntos_posibles = null;
    $puntos_obtenidos = null;
    
    if (isset($data->puntos_posibles) && is_numeric($data->puntos_posibles) && $data->puntos_posibles > 0) {
        $puntos_posibles = (float)$data->puntos_posibles;
        if (isset($data->puntos_obtenidos) && $data->puntos_obtenidos !== null) {
            if (!is_numeric($data->puntos_obtenidos) || $data->puntos_obtenidos < 0) {
                throw new Exception("RF-037: 'Puntos obtenidos' debe ser un número (0 o más).", 400);
            }
            if ($data->puntos_obtenidos > $puntos_posibles) {
                throw new Exception("RF-036: 'Puntos obtenidos' no pueden ser mayores a 'Puntos posibles'.", 400);
            }
            $puntos_obtenidos = (float)$data->puntos_obtenidos;
        }
    } else {
        if (isset($data->puntos_obtenidos) && $data->puntos_obtenidos !== null) {
            throw new Exception("RF-034: No se pueden asignar 'Puntos obtenidos' si la actividad no tiene 'Puntos posibles' (o si son 0).", 400);
        }
    }

    // Devolver un array limpio para el servicio
    return [
        'id_materia'        => (int)$data->id_materia,
        'id_tipo_actividad' => (int)$data->id_tipo_actividad,
        'id_usuario'        => (int)$id_usuario,
        'nombre_actividad'  => $data->nombre_actividad,
        'fecha_entrega'     => $data->fecha_entrega,
        'estado'            => $data->estado,
        'puntos_posibles'   => $puntos_posibles,
        'puntos_obtenidos'  => $puntos_obtenidos
    ];
}