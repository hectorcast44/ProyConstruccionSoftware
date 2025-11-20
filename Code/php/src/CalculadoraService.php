<?php

class CalculadoraService {
    /** @var PDO */
    private $pdo;

    /**
     * @param PDO $pdo Conexión a la base de datos.
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Recalcula todas las métricas numéricas de una materia y
     * actualiza la fila en la tabla MATERIA.
     *
     * @param int $id_materia Id de la materia a recalcular.
     * @param int $id_usuario Id del usuario (seguridad de datos).
     * @return bool true si se actualizó la fila.
     *
     * @throws PDOException En caso de error de base de datos.
     */
    public function recalcularMateria(int $id_materia, int $id_usuario): bool {

        try {
            // Ponderaciones configuradas para la materia
            $stmtPond = $this->pdo->prepare(
                'SELECT id_tipo_actividad, porcentaje
                FROM PONDERACION
                WHERE id_materia = ?'
            );
            $stmtPond->execute([$id_materia]);
            $ponderaciones = $stmtPond->fetchAll(PDO::FETCH_KEY_PAIR);

            // Actividades calificables (con puntos_posibles)
            $stmtAct = $this->pdo->prepare(
                'SELECT id_tipo_actividad, puntos_posibles, puntos_obtenidos
                FROM ACTIVIDAD
                WHERE id_materia = ?
                AND id_usuario = ?
                AND puntos_posibles IS NOT NULL'
            );
            $stmtAct->execute([$id_materia, $id_usuario]);
            $actividades = $stmtAct->fetchAll(PDO::FETCH_ASSOC);

            $puntosGanados = 0.0;
            $puntosPerdidos = 0.0;
            $puntosPendientes = 0.0;

            $dataPorTipo = [];

            foreach ($actividades as $act) {
                $tipoId = (int) $act['id_tipo_actividad'];

                if (!isset($dataPorTipo[$tipoId])) {
                    $dataPorTipo[$tipoId] = [
                        'suma_obtenidos' => 0.0,
                        'suma_posibles' => 0.0
                    ];
                }

                $puntosPosibles = (float) $act['puntos_posibles'];

                if ($act['puntos_obtenidos'] !== null) {
                    $puntosObtenidos = (float) $act['puntos_obtenidos'];

                    $puntosGanados += $puntosObtenidos;
                    $puntosPerdidos += ($puntosPosibles - $puntosObtenidos);

                    $dataPorTipo[$tipoId]['suma_obtenidos'] += $puntosObtenidos;
                    $dataPorTipo[$tipoId]['suma_posibles'] += $puntosPosibles;

                } else {
                    $puntosPendientes += $puntosPosibles;
                }
            }

            // Calificación final ponderada
            $calificacionFinal = 0.0;

            foreach ($ponderaciones as $tipoId => $porcentaje) {
                if (!isset($dataPorTipo[$tipoId])) {
                    continue;
                }

                $sumaObtenidos = $dataPorTipo[$tipoId]['suma_obtenidos'];
                $sumaPosibles = $dataPorTipo[$tipoId]['suma_posibles'];

                if ($sumaPosibles <= 0) {
                    continue;
                }

                $contribucion = ($sumaObtenidos / $sumaPosibles) * (float) $porcentaje;
                $calificacionFinal += $contribucion;
            }

            // Guardar resultados en MATERIA
            $stmtGuardar = $this->pdo->prepare(
                'UPDATE MATERIA
                SET calificacion_actual = ?,
                    puntos_ganados = ?,
                    puntos_perdidos = ?,
                    puntos_pendientes = ?
                WHERE id_materia = ?
                AND id_usuario = ?'
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
            error_log('Error en CalculadoraService::recalcularMateria: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene la fila de MATERIA para un usuario y construye
     * la estructura de progreso (porcentajes, puntos y diagnóstico).
     *
     * @param int $idMateria Id de la materia.
     * @param int $idUsuario Id del usuario propietario.
     * @return array Arreglo con claves:
     * - materia  (array de la tabla MATERIA)
     * - progreso (array con totales y diagnóstico)
     *
     * @throws Exception Materia no encontrada o error interno.
     */
    public function obtenerMateriaConProgreso(int $idMateria, int $idUsuario): array {
        // Asegurar que los valores numéricos estén actualizados
        $this->recalcularMateria($idMateria, $idUsuario);

        $sql = '
            SELECT
                id_materia,
                nombre_materia,
                calif_minima,
                calificacion_actual,
                puntos_ganados,
                puntos_perdidos,
                puntos_pendientes
            FROM MATERIA
            WHERE id_usuario = :id_usuario
              AND id_materia = :id_materia
            LIMIT 1
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_usuario' => $idUsuario,
            ':id_materia' => $idMateria
        ]);

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            throw new Exception('No se encontró la materia solicitada para el usuario actual.', 404);
        }

        $progreso = $this->calcularProgresoDesdeFila($fila);

        return [
            'materia'  => $fila,
            'progreso' => $progreso
        ];
    }

    /**
     * Construye la estructura de progreso a partir de una fila de MATERIA.
     *
     * @param array $fila Fila de la tabla MATERIA con totales y calificación.
     * @return array Estructura con porcentajes, puntos y diagnóstico.
     */
    private function calcularProgresoDesdeFila(array $fila): array {
        $puntosGanados    = (float) ($fila['puntos_ganados']     ?? 0);
        $puntosPerdidos   = (float) ($fila['puntos_perdidos']    ?? 0);
        $puntosPendientes = (float) ($fila['puntos_pendientes']  ?? 0);
        $calMinima        = (float) ($fila['calif_minima']       ?? 0);
        $calActual        = (float) ($fila['calificacion_actual']?? 0);

        $totalPuntos = $puntosGanados + $puntosPerdidos + $puntosPendientes;

        if ($totalPuntos > 0) {
            $porcentajeObtenido   = ($puntosGanados / $totalPuntos) * 100.0;
            $porcentajeMaxPosible = (($puntosGanados + $puntosPendientes) / $totalPuntos) * 100.0;

            $puntosRequeridosAprobar = ($calMinima / 100.0) * $totalPuntos;
            $puntosNecesarios        = max(0.0, $puntosRequeridosAprobar - $puntosGanados);
        } else {
            $porcentajeObtenido   = 0.0;
            $porcentajeMaxPosible = 0.0;
            $puntosNecesarios     = 0.0;
        }

        $porcentajeObtenido = round($porcentajeObtenido, 2);
        $porcentajeMaxPosible = round($porcentajeMaxPosible, 2);
        $puntosNecesarios = round($puntosNecesarios, 2);

        $estado = 'En riesgo';
        $nivel = 'risk';

        if ($porcentajeObtenido >= $calMinima) {
            $estado = 'Aprobado';
            $nivel  = 'ok';
        } elseif ($porcentajeObtenido < $calMinima - 10) {
            $estado = 'Reprobado';
            $nivel = 'fail';
        }

        return [
            'porcentaje_obtenido' => $porcentajeObtenido,
            'porcentaje_total' => 100,
            'puntos_obtenidos' => $puntosGanados,
            'puntos_perdidos' => $puntosPerdidos,
            'puntos_posibles_obtener' => $puntosPendientes,
            'puntos_necesarios_aprobar' => $puntosNecesarios,
            'calificacion_minima' => $calMinima,
            'calificacion_actual' => $calActual,
            'calificacion_maxima_posible'=> $porcentajeMaxPosible,
            'diagnostico' => [
                'grado' => round($porcentajeObtenido),
                'estado' => $estado,
                'nivel' => $nivel
            ]
        ];
    }
}
