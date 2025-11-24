<?php
/**
 * API Endpoint: Resumen de calificaciones por materia.
 *
 * Responsabilidad:
 *  - Listar las materias del usuario actual.
 *  - Para cada materia, devolver la suma de puntos obtenidos y posibles
 *    agrupados por tipo de actividad (solo de actividades calificables).
 *
 * NOTA:
 *  - Aquí solo se hace un resumen simple, no se calculan calificaciones finales.
 *  - La lógica “fina” de mínimos, máximos, etc. vive en CalculadoraService
 *    y se usa en calificaciones_detalle.php.
 */

require_once '../src/db.php';

session_start();

try {
    // =========================================================
    // Obtener id_usuario actual
    // =========================================================
    $idUsuario = obtenerIdUsuarioActual();
    if (!$idUsuario) {
        throw new Exception('Usuario no autenticado.', 401);
    }

    global $pdo;

    // =========================================================
    // Consultar materias del usuario
    // =========================================================
    $sqlMaterias = "
        SELECT
            m.id_materia,
            m.nombre_materia,
            m.calif_minima
        FROM materia m
        WHERE m.id_usuario = :id_usuario
        ORDER BY m.nombre_materia
    ";

    $stmtMaterias = $pdo->prepare($sqlMaterias);
    $stmtMaterias->execute([':id_usuario' => $idUsuario]);

    $materias = [];
    while ($fila = $stmtMaterias->fetch(PDO::FETCH_ASSOC)) {
        $idMateria = (int) $fila['id_materia'];

        $materias[$idMateria] = [
            'id_materia'      => $idMateria,
            'nombre_materia'  => $fila['nombre_materia'],
            'calif_minima'    => (int) $fila['calif_minima'],
            // Se llenará en el siguiente paso
            'tipos'           => []
        ];
    }

    if (empty($materias)) {
        enviarRespuesta(200, [
            'status' => 'success',
            'data'   => []
        ]);
        exit;
    }

    // =========================================================
    // Resumen por tipo de actividad
    //    (consistente con el detalle)
    // =========================================================
    $idsMateria = array_keys($materias);
    $placeholders = implode(',', array_fill(0, count($idsMateria), '?'));

    /**
     * Reglas:
     *  - De ACTIVIDAD tomamos SOLO las actividades que pertenecen al usuario
     *    actual (id_usuario).
     *  - Un tipo aparece si tiene AL MENOS una actividad registrada
     *    (calificable o no). Esto lo hace consistente con el detalle,
     *    que también agrupa por ACTIVIDAD.
     *  - Para el resumen:
     *      * puntos_posibles_calificables = SUM de puntos_posibles > 0 (NULL o 0 no suman)
     *      * puntos_obtenidos_calificables = SUM de puntos_obtenidos de esas actividades
     */
    $sqlTipos = "
        SELECT
            a.id_materia,
            ta.id_tipo_actividad     AS id_tipo,
            ta.nombre_tipo           AS nombre_tipo,
            SUM(
                CASE
                    WHEN a.puntos_posibles IS NULL OR a.puntos_posibles <= 0
                        THEN 0
                    ELSE a.puntos_posibles
                END
            ) AS puntos_posibles,
            SUM(
                CASE
                    WHEN a.puntos_posibles IS NULL OR a.puntos_posibles <= 0
                        THEN 0
                    WHEN a.puntos_obtenidos IS NULL
                        THEN 0
                    ELSE a.puntos_obtenidos
                END
            ) AS puntos_obtenidos
        FROM actividad a
        INNER JOIN tipo_actividad ta
            ON ta.id_tipo_actividad = a.id_tipo_actividad
        WHERE
            a.id_usuario = ?               -- usuario actual
            AND a.id_materia IN ($placeholders)
        GROUP BY
            a.id_materia,
            ta.id_tipo_actividad,
            ta.nombre_tipo
        ORDER BY
            a.id_materia,
            ta.nombre_tipo
    ";

    $params = array_merge([$idUsuario], $idsMateria);
    $stmtTipos = $pdo->prepare($sqlTipos);
    $stmtTipos->execute($params);

    while ($fila = $stmtTipos->fetch(PDO::FETCH_ASSOC)) {
        $idMateria = (int) $fila['id_materia'];

        if (!isset($materias[$idMateria])) {
            continue;
        }

        $materias[$idMateria]['tipos'][] = [
            'id_tipo'          => (int) $fila['id_tipo'],
            'nombre'           => $fila['nombre_tipo'],
            // El frontend mapea: obtenido <- (obtenido || puntos_obtenidos)
            //                    maximo   <- (maximo   || puntos_posibles)
            'puntos_obtenidos' => (float) $fila['puntos_obtenidos'],
            'puntos_posibles'  => (float) $fila['puntos_posibles']
        ];
    }

    // =========================================================
    // Responder
    // =========================================================
    enviarRespuesta(200, [
        'status' => 'success',
        'data'   => array_values($materias)
    ]);

} catch (Exception $excepcion) {
    error_log('Error en calificaciones_resumen.php: ' . $excepcion->getMessage());

    $codigo = ($excepcion->getCode() >= 400 && $excepcion->getCode() < 600)
        ? $excepcion->getCode()
        : 500;

    enviarRespuesta($codigo, [
        'status'  => 'error',
        'message' => 'Error al obtener el resumen de calificaciones.',
        'detail'  => $excepcion->getMessage()
    ]);
}
