<?php
/**
 * API Endpoint para Configurar Ponderaciones de una Materia
 *
 * Cumple con:
 * - RF-038 a RF-041 (Validación de suma 100%)
 * - RF-048 (Disparar recálculo automático)
 */

// 1. Dependencias y Configuración
require_once '../src/db.php'; 
require_once '../src/CalculadoraService.php';

// 2. Iniciar Sesión
session_start();

// 3. Configuración de CORS
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

// 4. Manejar solicitud OPTIONS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    enviarRespuesta(204, []);
}

// 5. Seguridad: Verificar Sesión de Usuario
if (!isset($_SESSION['id_usuario'])) {
    enviarRespuesta(401, [
        'status' => 'error', 
        'message' => 'No autorizado. Por favor, inicie sesión.'
    ]);
}
$id_usuario = $_SESSION['id_usuario'];

// 6. Restringir Método a POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    enviarRespuesta(405, ['status' => 'error', 'message' => 'Método no permitido']);
}

// -------------------------------------------------
// --- Inicia Lógica Principal (dentro de try/catch)
// -------------------------------------------------

try {
    // 7. Obtener Datos del Frontend
    $data = obtenerDatosJSON();

    // 8. Validación Básica de Entrada
    if (!$data || empty($data->id_materia) || !isset($data->ponderaciones) || !is_array($data->ponderaciones)) {
        enviarRespuesta(400, ['status' => 'error', 'message' => 'Datos incompletos. Se requiere id_materia y un array de ponderaciones.']);
    }

    $id_materia = $data->id_materia;

    // 9. Validación de Lógica (RF-039 / RF-040)
    $suma_total = 0;
    if (empty($data->ponderaciones)) {
         enviarRespuesta(400, ['status' => 'error', 'message' => 'El array de ponderaciones no puede estar vacío.']);
    }

    foreach ($data->ponderaciones as $pond) {
        if (!isset($pond->id_tipo_actividad) || !isset($pond->porcentaje)) {
            enviarRespuesta(400, ['status' => 'error', 'message' => 'Cada ponderación debe tener "id_tipo_actividad" y "porcentaje".']);
        }
        if (!is_numeric($pond->porcentaje) || $pond->porcentaje < 0) {
            enviarRespuesta(400, ['status' => 'error', 'message' => 'El porcentaje debe ser un número positivo (o 0).']);
        }
        // RF-041 (Permitir 0%) se cumple implícitamente
        $suma_total += (float)$pond->porcentaje;
    }

    // RF-039: La suma DEBE ser exactamente 100
    // Usamos una pequeña tolerancia (epsilon) para comparaciones de floats
    if (abs($suma_total - 100.0) > 0.001) {
        enviarRespuesta(400, [
            'status' => 'error', 
            'message' => "RF-039: La suma de porcentajes debe ser exactamente 100%. Suma actual: $suma_total %."
        ]);
    }

    // 10. Lógica de Base de Datos (Transacción)
    $pdo->beginTransaction();

    // Seguridad: Validar que la materia pertenece al usuario
    $stmt_check = $pdo->prepare("SELECT 1 FROM MATERIA WHERE id_materia = ? AND id_usuario = ?");
    $stmt_check->execute([$id_materia, $id_usuario]);
    if ($stmt_check->rowCount() == 0) {
        enviarRespuesta(403, ['status' => 'error', 'message' => 'Acceso denegado. Esta materia no le pertenece.']);
    }

    // a. Borrar ponderaciones antiguas para esta materia
    $stmt_delete = $pdo->prepare("DELETE FROM PONDERACION WHERE id_materia = ?");
    $stmt_delete->execute([$id_materia]);

    // b. Insertar las nuevas ponderaciones
    $sql_insert = "INSERT INTO PONDERACION (id_materia, id_tipo_actividad, porcentaje) VALUES (?, ?, ?)";
    $stmt_insert = $pdo->prepare($sql_insert);
    
    foreach ($data->ponderaciones as $pond) {
        $stmt_insert->execute([
            $id_materia,
            $pond->id_tipo_actividad,
            (float)$pond->porcentaje
        ]);
    }

    // 11. Disparar Recálculo Automático (RF-048)
    $calculadora = new CalculadoraService($pdo);
    $calculadora->recalcularMateria($id_materia, $id_usuario);

    // 12. Confirmar Transacción
    $pdo->commit();

    // 13. Respuesta Exitosa
    enviarRespuesta(200, [
        'status' => 'success',
        'message' => 'Ponderaciones guardadas y calificaciones recalculadas.'
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error de BD en ponderaciones.php: " + $e->getMessage());
    enviarRespuesta(500, ['status' => 'error', 'message' => 'Error en la base de datos: ' . $e->getMessage()]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error general en ponderaciones.php: " + $e->getMessage());
    enviarRespuesta(500, ['status' => 'error', 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
}