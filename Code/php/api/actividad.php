<?php
/**
 * API Endpoint RESTful para Actividades.
 *
 * Responsabilidades:
 *  - Crear actividades (POST)
 *  - Editar actividades (PUT)
 *  - Eliminar actividades (DELETE)
 *  - Recalcular la materia asociada después de cambios
 */

require_once '../src/db.php';
require_once '../src/CalculadoraService.php';
require_once '../src/ActividadService.php';

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

// Servicios
$actividadService = new ActividadService($pdo);
$calculadora = new CalculadoraService($pdo);

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
            $datos = validarYPrepararDatos($data, $idUsuario);
            
            $idActividad = $actividadService->crearActividad($datos);
            $calculadora->recalcularMateria($datos['id_materia'], $idUsuario);

            $pdo->commit();
            enviarRespuesta(201, [
                'status' => 'success',
                'message' => 'Actividad creada.',
                'id_actividad' => $idActividad
            ]);
            break;

        case 'PUT':
            $pdo->beginTransaction();
            $data = obtenerDatosJSON();
            if (empty($data->id_actividad)) {
                throw new Exception("Se requiere 'id_actividad' para editar.", 400);
            }

            $idActividad = (int) $data->id_actividad;
            $datos = validarYPrepararDatos($data, $idUsuario);

            $actividadService->editarActividad($idActividad, $datos);
            $calculadora->recalcularMateria($datos['id_materia'], $idUsuario);

            $pdo->commit();
            enviarRespuesta(200, [
                'status' => 'success',
                'message' => 'Actividad actualizada.'
            ]);
            break;

        case 'DELETE':
            $pdo->beginTransaction();
            // El ID vendrá por la URL (ej. ?id=15)
            if (empty($_GET['id'])) {
                throw new Exception("Se requiere 'id' en la URL para eliminar.", 400);
            }

            $idActividad = (int) $_GET['id'];
            $idMateria = $actividadService->eliminarActividad($idActividad, $idUsuario);

            $calculadora->recalcularMateria($idMateria, $idUsuario);

            $pdo->commit();
            enviarRespuesta(200, [
                'status' => 'success',
                'message' => 'Actividad eliminada.'
            ]);
            break;

        case 'OPTIONS':
            enviarRespuesta(204, []);
            break;

        default:
            throw new Exception("Método no permitido.", 405);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $codigo = ($e->getCode() >= 400 && $e->getCode() < 600)
        ? $e->getCode()
        : 500;

    if ($codigo === 500) {
        error_log("Error en actividad.php: " . $e->getMessage());
    }

    enviarRespuesta($codigo, [
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

/**
 * Valida y prepara los datos de entrada para crear o editar una actividad.
 *
 * Reglas:
 *  - Campos obligatorios
 *  - Coherencia entre puntos posibles y puntos obtenidos
 *
 * @return array Datos listos para el servicio
 */
function validarYPrepararDatos(object $data, int $idUsuario): array
{
    $camposRequeridos = ['id_materia', 'id_tipo_actividad', 'nombre_actividad', 'fecha_entrega', 'estado'];

    foreach ($camposRequeridos as $campo) {
        if (empty($data->{$campo})) {
            throw new Exception("Datos incompletos. El campo '$campo' es obligatorio.", 400);
        }
    }

    $puntosPosibles = null;
    $puntosObtenidos = null;

    if (isset($data->puntos_posibles) && is_numeric($data->puntos_posibles) && $data->puntos_posibles > 0) {
        $puntosPosibles = (float) $data->puntos_posibles;

        if (isset($data->puntos_obtenidos) && $data->puntos_obtenidos !== null) {
            if (!is_numeric($data->puntos_obtenidos) || $data->puntos_obtenidos < 0) {
                throw new Exception("RF-037: 'Puntos obtenidos' debe ser un número (0 o más).", 400);
            }

            if ($data->puntos_obtenidos > $puntosPosibles) {
                throw new Exception("RF-036: 'Puntos obtenidos' no pueden ser mayores a 'Puntos posibles'.", 400);
            }

            $puntosObtenidos = (float) $data->puntos_obtenidos;
        }

    } else {
        if (isset($data->puntos_obtenidos) && $data->puntos_obtenidos !== null) {
            throw new Exception(
                "RF-034: No se pueden asignar 'Puntos obtenidos' si la actividad no tiene 'Puntos posibles' (o si son 0).",
                400
            );
        }
    }

    return [
        'id_materia' => (int) $data->id_materia,
        'id_tipo_actividad' => (int) $data->id_tipo_actividad,
        'id_usuario' => $idUsuario,
        'nombre_actividad' => $data->nombre_actividad,
        'fecha_entrega' => $data->fecha_entrega,
        'estado' => $data->estado,
        'puntos_posibles' => $puntosPosibles,
        'puntos_obtenidos' => $puntosObtenidos
    ];
}
