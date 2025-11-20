<?php

class CalculadoraService {
    /** @var PDO */
    private $pdo;

    /**
     * @param PDO $pdo Conexión a la base de datos
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Recalcula todas las métricas de una materia en base a sus actividades
     * y guarda los totales en la tabla MATERIA.
     *
     * - Usa la tabla PONDERACION para obtener el porcentaje de cada tipo.
     * - Considera "calificable" una actividad con puntos_posibles NOT NULL.
     *
     * @param int $id_materia ID de la materia
     * @param int $id_usuario ID del usuario (seguridad)
     * @return bool true si se actualizó la fila de MATERIA
     * @throws PDOException si falla alguna consulta
     */
    public function recalcularMateria(int $id_materia, int $id_usuario): bool
    {
        try {
            // Ponderaciones por tipo (RF-038)
            $stmtPond = $this->pdo->prepare(
                "SELECT id_tipo_actividad, porcentaje
                 FROM PONDERACION
                 WHERE id_materia = ?"
            );
            $stmtPond->execute([$id_materia]);

            // Mapa id_tipo_actividad => porcentaje
            $ponderaciones = $stmtPond->fetchAll(PDO::FETCH_KEY_PAIR);

            // Actividades calificables (puntos_posibles NOT NULL)
            $stmtAct = $this->pdo->prepare(
                "SELECT id_tipo_actividad, puntos_posibles, puntos_obtenidos
                 FROM ACTIVIDAD
                 WHERE id_materia = ?
                   AND id_usuario = ?
                   AND puntos_posibles IS NOT NULL"
            );
            $stmtAct->execute([$id_materia, $id_usuario]);
            $actividades = $stmtAct->fetchAll(PDO::FETCH_ASSOC);

            // Totales globales + totales por tipo
            $puntosGanados = 0.0;
            $puntosPerdidos = 0.0;
            $puntosPendientes = 0.0;

            // id_tipo => ['suma_obtenidos' => float, 'suma_posibles' => float]
            $porTipo = [];

            foreach ($actividades as $act) {
                $tipoId = (int) $act['id_tipo_actividad'];
                $puntosPosible = (float) $act['puntos_posibles'];
                $puntosObt     = $act['puntos_obtenidos'] !== null
                    ? (float) $act['puntos_obtenidos']
                    : null;

                if (!isset($porTipo[$tipoId])) {
                    $porTipo[$tipoId] = [
                        'suma_obtenidos' => 0.0,
                        'suma_posibles' => 0.0
                    ];
                }

                if ($puntosObt !== null) {
                    // Actividad calificada
                    $puntosGanados  += $puntosObt;
                    $puntosPerdidos += ($puntosPosible - $puntosObt);

                    $porTipo[$tipoId]['suma_obtenidos'] += $puntosObt;
                    $porTipo[$ipoId]['suma_posibles']  += $puntosPosible;
                } else {
                    // Actividad pendiente
                    $puntosPendientes += $puntosPosible;
                }
            }

            // Calificación final ponderada
            $calificacionFinal = 0.0;

            foreach ($ponderaciones as $tipoId => $porcentaje) {
                if (
                    isset($porTipo[$tipoId]) &&
                    $porTipo[$tipoId]['suma_posibles'] > 0
                ) {
                    $sumaObt = $porTipo[$tipoId]['suma_obtenidos'];
                    $sumaPos = $porTipo[$tipoId]['suma_posibles'];

                    $contribucion = ($sumaObt / $sumaPos) * (float) $porcentaje;
                    $calificacionFinal += $contribucion;
                }
            }

            // Guardar resultados en la tabla MATERIA
            $stmtGuardar = $this->pdo->prepare(
                "UPDATE MATERIA
                 SET calificacion_actual = ?,
                     puntos_ganados = ?,
                     puntos_perdidos  = ?,
                     puntos_pendientes = ?
                 WHERE id_materia = ?
                   AND id_usuario = ?"
            );

            $stmtGuardar->execute([
                $calificacionFinal,
                $puntosGanados,
                $puntosPerdidos,
                $puntosPendientes,
                $id_materia,
                $id_usuario
            ]);

            return $stmtGuardar->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error en CalculadoraService::recalcularMateria: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene la fila de MATERIA para un usuario y calcula las métricas de
     * progreso que usa el frontend (progreso de puntos, máximos posibles,
     * puntos necesarios para aprobar y diagnóstico).
     *
     * NO recalcula a partir de ACTIVIDAD; usa los campos ya guardados en MATERIA.
     *
     * @param int $id_materia ID de la materia
     * @param int $id_usuario ID del usuario
     * @return array {
     *   materia: array Fila original de la tabla MATERIA
     *   progreso: array Métricas listas para la UI
     * }
     * @throws Exception si la materia no pertenece al usuario actual
     */
    public function obtenerMateriaConProgreso(int $id_materia, int $id_usuario): array
    {
        $sql = "
            SELECT
                id_materia,
                id_usuario,
                nombre_materia,
                calif_minima,
                calificacion_actual,
                puntos_ganados,
                puntos_perdidos,
                puntos_pendientes
            FROM MATERIA
            WHERE id_materia = :id_materia
              AND id_usuario = :id_usuario
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_materia' => $id_materia,
            ':id_usuario' => $id_usuario
        ]);

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            throw new Exception('No se encontró la materia para el usuario actual.', 404);
        }

        $puntosGanados = (float) $fila['puntos_ganados'];
        $puntosPerdidos = (float) $fila['puntos_perdidos'];
        $puntosPendientes = (float) $fila['puntos_pendientes'];
        $califMinima = (float) $fila['calif_minima'];
        $califActual = (float) $fila['calificacion_actual'];

        $totalPuntos = $puntosGanados + $puntosPerdidos + $puntosPendientes;

        if ($totalPuntos > 0.0) {
            // % obtenido sólo por puntos (independiente de la ponderación)
            $porcentajeObtenido = ($puntosGanados / $totalPuntos) * 100.0;
            $porcentajeMaxPosible = (($puntosGanados + $puntosPendientes) / $totalPuntos) * 100.0;

            $puntosRequeridosAprobar = ($califMinima / 100.0) * $totalPuntos;
            $puntosNecesarios = max(0.0, $puntosRequeridosAprobar - $puntosGanados);
        } else {
            $porcentajeObtenido = 0.0;
            $porcentajeMaxPosible = 0.0;
            $puntosNecesarios = 0.0;
        }

        $porcentajeObtenido = round($porcentajeObtenido, 2);
        $porcentajeMaxPosible = round($porcentajeMaxPosible, 2);
        $puntosNecesarios = round($puntosNecesarios, 2);

        // Diagnóstico básico 
        $estado = 'En riesgo';
        $nivel = 'risk';

        if ($porcentajeObtenido >= $califMinima) {
            $estado = 'Aprobado';
            $nivel = 'ok';
        } elseif ($porcentajeObtenido < $califMinima - 10.0) {
            $estado = 'Reprobado';
            $nivel = 'fail';
        }

        $progreso = [
            'porcentaje_obtenido' => $porcentajeObtenido,
            'porcentaje_total' => 100,
            'puntos_obtenidos'=> $puntosGanados,
            'puntos_perdidos' => $puntosPerdidos,
            'puntos_posibles_obtener' => $puntosPendientes,
            'puntos_necesarios_aprobar' => $puntosNecesarios,
            'calificacion_minima' => $califMinima,
            'calificacion_actual' => $califActual,
            'calificacion_maxima_posible' => $porcentajeMaxPosible,
            'diagnostico' => [
                'grado' => round($porcentajeObtenido),
                'estado' => $estado,
                'nivel' => $nivel
            ]
        ];

        return [
            'materia' => $fila,
            'progreso' => $progreso
        ];
    }
}
