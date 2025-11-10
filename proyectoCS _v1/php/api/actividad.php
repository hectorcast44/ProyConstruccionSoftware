<?php
/**
 * API Endpoint para Crear y Actualizar Actividades
 *
 * Lógica actualizada:
 * - Una actividad es "Calificable" si 'puntos_posibles' es numérico y > 0.
 * - Si es 0, null, o no numérico, se guarda como NULL y no es calificable.
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
// --- Inicia Lógica Principal 
// -------------------------------------------------

try {
    // 7. Obtener Datos del Frontend
    $data = obtenerDatosJSON();

    // 8. Validación Básica (Campos obligatorios del SQL)
    $campos_requeridos = ['id_materia', 'id_tipo_actividad', 'nombre_actividad', 'fecha_entrega', 'estado'];
    foreach ($campos_requeridos as $campo) {
        if (empty($data->{$campo})) {
            enviarRespuesta(400, ['status' => 'error', 'message' => "Datos incompletos. El campo '$campo' es obligatorio."]);
        }
    }

    // =======================================================================
    // --- 9. Validación de Calificación---
    // =======================================================================
    
    $puntos_posibles = null;
    $puntos_obtenidos = null;

    // ¿Es Calificable? (puntos_posibles > 0)
    if (isset($data->puntos_posibles) && is_numeric($data->puntos_posibles) && $data->puntos_posibles > 0) {
        
        // --- SÍ ES CALIFICABLE (RF-033) ---
        $puntos_posibles = (float)$data->puntos_posibles;

        // Validar "Puntos obtenidos" si existen
        if (isset($data->puntos_obtenidos) && $data->puntos_obtenidos !== null) {
            
            // RF-037: Debe ser un número positivo (o 0)
            if (!is_numeric($data->puntos_obtenidos) || $data->puntos_obtenidos < 0) {
                enviarRespuesta(400, ['status' => 'error', 'message' => 'RF-037: "Puntos obtenidos" debe ser un número (0 o más).']);
            }
            
            // RF-036: No puede exceder los puntos posibles
            if ($data->puntos_obtenidos > $puntos_posibles) {
                enviarRespuesta(400, ['status' => 'error', 'message' => 'RF-036: "Puntos obtenidos" no pueden ser mayores a "Puntos posibles".']);
            }
            $puntos_obtenidos = (float)$data->puntos_obtenidos;
        }
        // Si $data->puntos_obtenidos es null, $puntos_obtenidos se queda como null (actividad pendiente)

    } else {
        
        // --- NO ES CALIFICABLE (RF-034) ---
        // (Porque $data->puntos_posibles es null, 0, no numérico, o no está seteado)
        
        // $puntos_posibles se queda como null (así se guardará en la BD)
        
        if (isset($data->puntos_obtenidos) && $data->puntos_obtenidos !== null) {
            enviarRespuesta(400, ['status' => 'error', 'message' => 'RF-034: No se pueden asignar "Puntos obtenidos" si la actividad no tiene "Puntos posibles" (o si son 0).']);
        }
    }
    // =======================================================================
    // --- Fin de la Lógica de Validación ---
    // =======================================================================


    // 10. Lógica de Base de Datos (INSERT o UPDATE)
    
    // Iniciar transacción
    $pdo->beginTransaction();

    $id_actividad = $data->id_actividad ?? null;
    $id_actividad_guardada = null;

    if ($id_actividad) {
        $sql = "UPDATE ACTIVIDAD SET 
                  id_materia = ?, 
                  id_tipo_actividad = ?, 
                  nombre_actividad = ?, 
                  fecha_entrega = ?, 
                  estado = ?, 
                  puntos_posibles = ?, 
                  puntos_obtenidos = ?
                WHERE id_actividad = ? AND id_usuario = ?"; 
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data->id_materia,
            $data->id_tipo_actividad,
            $data->nombre_actividad,
            $data->fecha_entrega,
            $data->estado,
            $puntos_posibles, // Se guarda 10.0 o null
            $puntos_obtenidos, // Se guarda 8.0 o null
            $id_actividad,
            $id_usuario
        ]);
        $id_actividad_guardada = $id_actividad;

    } else {
        // --- INSERT (Crear) ---
        $sql = "INSERT INTO ACTIVIDAD 
                  (id_materia, id_tipo_actividad, id_usuario, nombre_actividad, 
                   fecha_entrega, estado, puntos_posibles, puntos_obtenidos) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data->id_materia,
            $data->id_tipo_actividad,
            $id_usuario, 
            $data->nombre_actividad,
            $data->fecha_entrega,
            $data->estado,
            $puntos_posibles, 
            $puntos_obtenidos 
        ]);
        $id_actividad_guardada = $pdo->lastInsertId();
    }

    // 11. Disparar Recálculo Automático (RF-048)
    $calculadora = new CalculadoraService($pdo);
    $calculadora->recalcularMateria($data->id_materia, $id_usuario);

    // 12. Confirmar Transacción
    $pdo->commit();
    
    // 13. Respuesta Exitosa
    enviarRespuesta(201, [
        'status' => 'success',
        'message' => 'Actividad guardada y calificaciones recalculadas.',
        'id_actividad' => $id_actividad_guardada
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error de BD en actividad.php: " . $e->getMessage());
    enviarRespuesta(500, ['status' => 'error', 'message' => 'Error en la base de datos: ' . $e->getMessage()]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error general en actividad.php: " . $e->getMessage());
    enviarRespuesta(500, ['status' => 'error', 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
}