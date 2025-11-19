<?php
/**
 * API Endpoint para Configurar Ponderaciones de una Materia.
 *
 * Responsabilidades:
 *  - Validar que la suma de ponderaciones llegue a 100%.
 *  - Guardar las ponderaciones en la tabla PONDERACION.
 *  - Disparar recálculo automático de la materia (CalculadoraService).
 *
 * Cumple con:
 *  - RF-038 a RF-041 (validación de suma 100%)
 *  - RF-048 (disparar recálculo automático)
 */

require_once '../src/db.php';
require_once '../src/CalculadoraService.php';

session_start();

// Configuración CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

$metodoHttp = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($metodoHttp === 'OPTIONS') {
    enviarRespuesta(204, []);
}

// Requiere usuario autenticado para POST
$idUsuario = obtenerIdUsuarioActual();

/**
 * Valida y normaliza el payload de ponderaciones.
 *
 * @param object|null $data Datos crudos del cuerpo JSON.
 *
 * @return array Arreglo con id_materia y lista de ponderaciones normalizadas.
 *
 * @throws Exception Si los datos son inválidos.
 */
function validarPayloadPonderaciones(?object $data): array
{
    if (!$data) {
        throw new Exception('No se recibieron datos de ponderaciones.', 400);
    }

    if (empty($data->id_materia)) {
        throw new Exception('El campo "id_materia" es obligatorio.', 400);
    }

    if (!isset($data->ponderaciones) || !is_array($data->ponderaciones)) {
        throw new Exception('El campo "ponderaciones" es obligatorio y debe ser un arreglo.', 400);
    }

    $idMateria = (int) $data->id_materia;
    if ($idMateria <= 0) {
        throw new Exception('El "id_materia" debe ser un entero positivo.', 400);
    }

    $ponderaciones = [];
    $suma = 0.0;

    foreach ($data->ponderaciones as $index => $pond) {
        if (!isset($pond->id_tipo_actividad) || !isset($pond->porcentaje)) {
            throw new Exception("Cada ponderación debe incluir 'id_tipo_actividad' y 'porcentaje'.", 400);
        }

        $idTipo = (int) $pond->id_tipo_actividad;
        $porcentaje = (float) $pond->porcentaje;

        if ($idTipo <= 0) {
            throw new Exception("El 'id_tipo_actividad' en la posición {$index} no es válido.", 400);
        }

        if ($porcentaje <= 0) {
            throw new Exception("El 'porcentaje' en la posición {$index} debe ser mayor que 0.", 400);
        }

        $suma += $porcentaje;

        $ponderaciones[] = [
            'id_tipo_actividad' => $idTipo,
            'porcentaje' => $porcentaje
        ];
    }

    // Validar que la suma sea 100% (permitiendo pequeño margen por redondeo)
    $diferencia = abs($suma - 100.0);
    if ($diferencia > 0.01) {
        throw new Exception("La suma de las ponderaciones debe ser 100%. Actualmente es {$suma}.", 400);
    }

    return [
        'id_materia' => $idMateria,
        'ponderaciones' => $ponderaciones
    ];
}

/**
 * Verifica que la materia pertenezca al usuario actual.
 *
 * @param PDO $pdo Conexión a la base de datos.
 * @param int $idMateria Id de la materia.
 * @param int $idUsuario Id del usuario.
 *
 * @return void
 *
 * @throws Exception Si la materia no existe o no pertenece al usuario.
 */
function verificarMateriaUsuario(PDO $pdo, int $idMateria, int $idUsuario): void
{
    $sql = 'SELECT COUNT(*) AS total FROM MATERIA WHERE id_materia = :id_materia AND id_usuario = :id_usuario';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_materia' => $idMateria,
        ':id_usuario' => $idUsuario
    ]);

    $fila = $stmt->fetch();
    $total = $fila ? (int) $fila['total'] : 0;

    if ($total === 0) {
        throw new Exception('La materia no existe o no pertenece al usuario actual.', 403);
    }
}

// Solo se permite POST (ya manejamos OPTIONS arriba)
if ($metodoHttp !== 'POST') {
    enviarRespuesta(405, [
        'status' => 'error',
        'message' => 'Método no permitido. Use POST.'
    ]);
}

try {
    $data = obtenerDatosJSON();
    $datosValidados = validarPayloadPonderaciones($data);

    $idMateria = $datosValidados['id_materia'];
    $listaPonderaciones = $datosValidados['ponderaciones'];

    $pdo->beginTransaction();

    // Verificar propiedad de la materia
    verificarMateriaUsuario($pdo, $idMateria, $idUsuario);

    // Eliminar ponderaciones previas de la materia
    $sqlDelete = 'DELETE FROM PONDERACION WHERE id_materia = :id_materia';
    $stmtDelete = $pdo->prepare($sqlDelete);
    $stmtDelete->execute([
        ':id_materia' => $idMateria
    ]);

    // Insertar nuevas ponderaciones
    $sqlInsert = '
        INSERT INTO PONDERACION (id_materia, id_tipo_actividad, porcentaje)
        VALUES (:id_materia, :id_tipo_actividad, :porcentaje)
    ';
    $stmtInsert = $pdo->prepare($sqlInsert);

    foreach ($listaPonderaciones as $pond) {
        $stmtInsert->execute([
            ':id_materia' => $idMateria,
            ':id_tipo_actividad' => $pond['id_tipo_actividad'],
            ':porcentaje' => $pond['porcentaje']
        ]);
    }

    // Recalcular la materia (RF-048)
    $calculadora = new CalculadoraService($pdo);
    $calculadora->recalcularMateria($idMateria, $idUsuario);

    $pdo->commit();

    enviarRespuesta(200, [
        'status' => 'success',
        'message' => 'Ponderaciones guardadas y materia recalculada correctamente.'
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Error de BD en materias_ponderaciones.php: ' . $e->getMessage());

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
