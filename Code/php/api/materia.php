<?php
/**
 * API Endpoint para Gestión de Materias
 *
 * Funcionalidades:
 *  - GET/materia.php -> Lista todas las materias del usuario
 *  - GET/materia.php?id_materia=ID -> Obtiene una materia específica
 *  - POST/materia.php -> Crea o actualiza una materia (según si trae id_materia)
 *  - DELETE/materia.php?id_materia=ID -> Elimina una materia
 */

require_once '../src/db.php';
require_once '../src/MateriaService.php';

session_start();

// Configuración CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

$metodoHttp = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Para OPTIONS no exigimos usuario
if ($metodoHttp !== 'OPTIONS') {
    $idUsuario = obtenerIdUsuarioActual();
} else {
    $idUsuario = 0;
}

$materiaService = new MateriaService($pdo);

/**
 * Obtiene el id de materia desde la query string.
 *
 * @return int|null Id de materia o null si no viene en la URL.
 */
function obtenerIdMateriaDesdeQuery(): ?int
{
    if (isset($_GET['id_materia'])) {
        return (int) $_GET['id_materia'];
    }

    if (isset($_GET['id'])) {
        return (int) $_GET['id'];
    }

    return null;
}

/**
 * Normaliza y valida los datos recibidos para crear/editar materia.
 *
 * @param object|null $data Datos crudos del cuerpo JSON.
 *
 * @return array Arreglo con nombre_materia y calif_minima.
 *
 * @throws Exception Si los datos son inválidos.
 */
function validarDatosMateria(?object $data): array
{
    if (!$data) {
        throw new Exception('No se recibieron datos para la materia.', 400);
    }

    if (empty($data->nombre_materia)) {
        throw new Exception('El campo "nombre_materia" es obligatorio.', 400);
    }

    $nombreMateria = trim((string) $data->nombre_materia);

    if (strlen($nombreMateria) > 100) {
        throw new Exception('El nombre de la materia no puede exceder 100 caracteres.', 400);
    }

    $califMinima = isset($data->calif_minima)
        ? (int) $data->calif_minima
        : 70;

    if ($califMinima < 0 || $califMinima > 100) {
        throw new Exception('La calificación mínima debe estar entre 0 y 100.', 400);
    }

    return [
        'nombre_materia' => $nombreMateria,
        'calif_minima' => $califMinima
    ];
}

try {
    switch ($metodoHttp) {

        // Listar materias o traer una específica
        case 'GET':
            $idMateria = obtenerIdMateriaDesdeQuery();

            if ($idMateria !== null && $idMateria > 0) {
                $materia = $materiaService->obtenerPorId($idMateria, $idUsuario);

                if (!$materia) {
                    enviarRespuesta(404, [
                        'status' => 'error',
                        'message' => 'No se encontró la materia solicitada.'
                    ]);
                }

                enviarRespuesta(200, [
                    'status' => 'success',
                    'data' => $materia
                ]);
            }

            $materias = $materiaService->obtenerPorUsuario($idUsuario);

            enviarRespuesta(200, [
                'status' => 'success',
                'data' => $materias
            ]);
            break;

        // Crear o actualizar materia
        case 'POST':
            $data = obtenerDatosJSON();
            $datosValidados = validarDatosMateria($data);

            $idMateria = isset($data->id_materia) ? (int) $data->id_materia : 0;

            $pdo->beginTransaction();

            if ($idMateria > 0) {
                // Actualizar materia existente
                $materiaService->actualizar(
                    $idMateria,
                    $idUsuario,
                    $datosValidados['nombre_materia'],
                    $datosValidados['calif_minima']
                );

                $pdo->commit();

                enviarRespuesta(200, [
                    'status' => 'success',
                    'message' => 'Materia actualizada correctamente.',
                    'id_materia' => $idMateria
                ]);
            } else {
                // Crear nueva materia
                $idMateriaNueva = $materiaService->crear(
                    $idUsuario,
                    $datosValidados['nombre_materia'],
                    $datosValidados['calif_minima']
                );

                $pdo->commit();

                enviarRespuesta(201, [
                    'status' => 'success',
                    'message' => 'Materia creada correctamente.',
                    'id_materia' => $idMateriaNueva
                ]);
            }
            break;

        // Eliminar materia
        case 'DELETE':
            $idMateria = obtenerIdMateriaDesdeQuery();

            if (!$idMateria || $idMateria <= 0) {
                enviarRespuesta(400, [
                    'status' => 'error',
                    'message' => 'Se requiere un "id_materia" válido para eliminar.'
                ]);
            }

            $pdo->beginTransaction();

            // MateriaService valida propiedad y restricciones (actividades asociadas, etc.)
            $materiaService->eliminar($idMateria, $idUsuario);

            $pdo->commit();

            enviarRespuesta(200, [
                'status' => 'success',
                'message' => 'Materia eliminada correctamente.'
            ]);
            break;

        // Preflight CORS
        case 'OPTIONS':
            enviarRespuesta(204, []);
            break;

        default:
            enviarRespuesta(405, [
                'status' => 'error',
                'message' => 'Método no permitido.'
            ]);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Error de BD en materia.php: ' . $e->getMessage());

    enviarRespuesta(500, [
        'status' => 'error',
        'message' => 'Error en la base de datos.'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $codigo = $e->getCode();
    if ($codigo < 400 || $codigo >= 600) {
        $codigo = 400;
    }

    enviarRespuesta($codigo, [
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
