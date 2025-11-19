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

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

$metodoHttp = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Seguridad: para cualquier método que no sea OPTIONS, se requiere usuario
if ($metodoHttp !== 'OPTIONS') {
    $idUsuario = obtenerIdUsuarioActual();
} else {
    $idUsuario = 0;
}

// Servicios
$actividadService = new ActividadService($pdo);
$calculadora = new CalculadoraService($pdo);

try {
    $pdo->beginTransaction();

    switch ($metodoHttp) {
        
        case 'POST':
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
            $pdo->rollBack();
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
