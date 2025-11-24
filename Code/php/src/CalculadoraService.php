<?php

class CalculadoraService {
    /** @var PDO */
    private $pdo;

    /**
     * Resultados ponderados por tipo de actividad en el último recálculo.
     * Clave: id_tipo_actividad
     * Valor: [
     *   'id_tipo'=> int,
     *   'puntos_tipo' => float, // cuántos puntos de la calificación vale este tipo (ponderación)
     *   'puntos_asegurados' => float, // puntos ya ganados y que no se pueden perder
     *   'puntos_perdidos' => float, // puntos que ya no se pueden recuperar
     *   'puntos_pendientes' => float, // puntos que siguen en juego (pendientes+futuros)
     * ]
     *
     * @var array<int,array<string,mixed>>
     */
    private array $resultadosPorTipo = [];

    /**
     * @param PDO
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Recalcula las métricas de una materia utilizando:
     *  - Las actividades registradas (tabla ACTIVIDAD)
     *  - Las ponderaciones por tipo (tabla PONDERACION)
     *
     * Modelo:
     *  - La suma de las ponderaciones de la materia representa el "total" de puntos
     *    de la calificación (normalmente 100).
     *  - Cada tipo de actividad t tiene un valor en puntos dentro de la calificación
     *    (columnas PONDERACION.porcentaje), aquí lo llamamos puntos_tipo.
     *  - Las actividades dentro de cada tipo se expresan en puntos_posibles internos.
     *  - Si la suma de puntos_posibles de un tipo es menor que puntos_tipo:
     *    -> la diferencia se considera puntos FUTUROS que todavía no se han
     *           definido, pero que el alumno podrá ganar o perder.
     *  - Si la suma de puntos_posibles de un tipo es mayor que puntos_tipo:
     *    -> todos los puntos_posibles creados se escalan para caber en puntos_tipo.
     *
     * Con esto garantizamos que, para la materia:
     * puntos_ganados + puntos_perdidos + puntos_pendientes = suma de puntos_tipo (≈100)
     *
     * Donde:
     *  - puntos_ganados  = "calificación mínima dinámica" (lo que ya no puede perder).
     *  - puntos_perdidos = puntos que ya no podrá recuperar.
     *  - puntos_pendientes = puntos que siguen en juego (pendientes + futuros).
     *
     * Además se calcula una calificación_actual 0–100 usando sólo actividades
     * calificadas de cada tipo.
     *
     * Los totales se guardan en la tabla MATERIA.
     *
     * @param int $id_materia
     * @param int $id_usuario
     * @return bool true si la actualización se ejecutó sin error
     * @throws PDOException
     */
    public function recalcularMateria(int $id_materia, int $id_usuario): bool
    {
        $this->resultadosPorTipo = [];

        // Obtener ponderaciones por tipo para la materia
        $stmtPond = $this->pdo->prepare(
            "SELECT id_tipo_actividad, porcentaje
             FROM PONDERACION
             WHERE id_materia = ?"
        );
        $stmtPond->execute([$id_materia]);

        // Mapa id_tipo_actividad => puntos_tipo (ponderación)
        $ponderaciones = $stmtPond->fetchAll(PDO::FETCH_KEY_PAIR);
        if (!$ponderaciones) {
            $ponderaciones = [];
        }

        $puntosEscalaTotal = 0.0;
        foreach ($ponderaciones as $valor) {
            $puntosEscalaTotal += (float) $valor;
        }

        // Obtener actividades con puntos_posibles > 0
        $stmtAct = $this->pdo->prepare(
            "SELECT id_tipo_actividad, puntos_posibles, puntos_obtenidos
             FROM ACTIVIDAD
             WHERE id_materia = ?
               AND id_usuario = ?
               AND puntos_posibles IS NOT NULL
               AND puntos_posibles > 0"
        );
        $stmtAct->execute([$id_materia, $id_usuario]);
        $actividades = $stmtAct->fetchAll(PDO::FETCH_ASSOC);

        // Estructura por tipo:
        //   id_tipo => [
        //       'sum_pos' => total puntos_posibles (calificables),
        //       'sum_calif_pos'=> puntos_posibles de actividades ya calificadas,
        //       'sum_pend_pos' => puntos_posibles de actividades pendientes,
        //       'sum_obt'=> suma de puntos_obtenidos
        //   ]
        $datosTipo = [];

        foreach ($actividades as $act) {
            $tipoId = (int) $act['id_tipo_actividad'];

            if (!isset($datosTipo[$tipoId])) {
                $datosTipo[$tipoId] = [
                    'sum_pos' => 0.0,
                    'sum_calif_pos' => 0.0,
                    'sum_pend_pos' => 0.0,
                    'sum_obt' => 0.0,
                ];
            }

            $puntosPosibles  = (float) $act['puntos_posibles'];
            $puntosObtenidos = $act['puntos_obtenidos'];

            $datosTipo[$tipoId]['sum_pos'] += $puntosPosibles;

            if ($puntosObtenidos !== null) {
                $datosTipo[$tipoId]['sum_calif_pos'] += $puntosPosibles;
                $datosTipo[$tipoId]['sum_obt'] += (float) $puntosObtenidos;
            } else {
                $datosTipo[$tipoId]['sum_pend_pos'] += $puntosPosibles;
            }
        }

        // Recorrer cada tipo ponderado para calcular:
        // - puntos_ganados / perdidos / pendientes (escala 0–puntosEscalaTotal)
        // - contribución a la calificación actual (0–100)
        $puntosGanados = 0.0;
        $puntosPerdidos  = 0.0;
        $puntosPendientes  = 0.0;
        $sumaCalificacionPorTipo = 0.0;

        foreach ($ponderaciones as $tipoId => $puntosTipo) {
            $puntosTipo = (float) $puntosTipo;
            if ($puntosTipo <= 0.0) {
                continue;
            }
        
           
            $info = $datosTipo[$tipoId] ?? [
                'sum_pos' => 0.0,
                'sum_calif_pos' => 0.0,
                'sum_pend_pos' => 0.0,
                'sum_obt' => 0.0,
            ];

            $sumPos = (float) $info['sum_pos'];
            $sumCalifPos = (float) $info['sum_calif_pos'];
            $sumPendPos = (float) $info['sum_pend_pos'];
            $sumObt = (float) $info['sum_obt'];

            // ---------------------------
            // Calificación actual (promedio ponderado)
            // ---------------------------
            if ($sumCalifPos > 0.0) {
                // Promedio (0–1) de este tipo usando sólo actividades calificadas
                $promedioTipo = $sumObt / $sumCalifPos;
                // Aporta promedioTipo * puntosTipo a la calificación total
                $sumaCalificacionPorTipo += $promedioTipo * $puntosTipo;
            }

            // ---------------------------
            // Puntos ganados / perdidos / pendientes (ponderados)
            // ---------------------------
            // "Total interno final" para este tipo:
            //  - Si sumPos < puntosTipo → todavía hay puntos futuros a crear (puntosTipo - sumPos)
            //  - Si sumPos >= puntosTipo → todos los puntos ya están definidos y se escalan a puntosTipo.
            $totalInternoFinal = max($puntosTipo, $sumPos);

            if ($totalInternoFinal <= 0.0) {
                $factor = 0.0;
                $ganadosTipo = 0.0;
                $perdidosTipo = 0.0;
                $pendientesTipo = 0.0;
            } else {
                $factor = $puntosTipo / $totalInternoFinal;

                // Puntos asegurados en este tipo (ya ganados y no se pueden perder)
                $ganadosTipo  = $sumObt * $factor;

                // Puntos perdidos: lo que ya no podrá recuperar en actividades calificadas
                $perdidosTipo = max(0.0, ($sumCalifPos - $sumObt) * $factor);

                // Puntos pendientes de actividades definidas pero sin calificar
                $pendDefinidos = $sumPendPos * $factor;

                // Puntos futuros de actividades que aún no existen (puntosTipo - sumPos) si sumPos < puntosTipo
                $pendFuturos = max(0.0, $totalInternoFinal - $sumPos) * $factor;

                $pendientesTipo = $pendDefinidos + $pendFuturos;
            }

            $puntosGanados += $ganadosTipo;
            $puntosPerdidos += $perdidosTipo;
            $puntosPendientes += $pendientesTipo;

            // Guardar detalle por tipo para el frontend
            $this->resultadosPorTipo[$tipoId] = [
                'id_tipo' => (int) $tipoId,
                'puntos_tipo' => round($puntosTipo, 2),
                'puntos_asegurados' => round($ganadosTipo, 2),
                'puntos_perdidos' => round($perdidosTipo, 2),
                'puntos_pendientes' => round($pendientesTipo, 2),
            ];
        }

        // Normalizar calificación actual a escala 0–100,
        // por si la suma de ponderaciones != 100.
        if ($puntosEscalaTotal > 0.0) {
            $calificacionActual = ($sumaCalificacionPorTipo / $puntosEscalaTotal) * 100.0;
        } else {
            $calificacionActual = 0.0;
        }

        // Redondeos suaves para almacenar
        $puntosGanados = round($puntosGanados, 2);
        $puntosPerdidos = round($puntosPerdidos, 2);
        $puntosPendientes = round($puntosPendientes, 2);
        $calificacionActual = round($calificacionActual, 2);

        // 4) Guardar resultados en la tabla MATERIA
        $stmtGuardar = $this->pdo->prepare(
            "UPDATE MATERIA
            SET calificacion_actual = ?,
                puntos_ganados = ?,
                puntos_perdidos = ?,
                puntos_pendientes = ?
            WHERE id_materia = ?
            AND id_usuario = ?"
        );

        $stmtGuardar->execute([
            $calificacionActual,
            $puntosGanados,
            $puntosPerdidos,
            $puntosPendientes,
            $id_materia,
            $id_usuario,
        ]);

        return $stmtGuardar->rowCount() >= 0;
    }

    /**
     * Devuelve los datos de la materia y un resumen de progreso
     * listo para ser consumido por la API calificaciones_detalle.php
     *
     * @param int $id_materia
     * @param int $id_usuario
     * @return array
     * @throws Exception
     */
    public function obtenerMateriaConProgreso(int $id_materia, int $id_usuario): array
    {
        // Recalcular siempre antes de leer, para tener la materia al día
        $this->recalcularMateria($id_materia, $id_usuario);

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
            ':id_usuario' => $id_usuario,
        ]);

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$fila) {
            throw new Exception('No se encontró la materia para el usuario actual.', 404);
        }

        $puntosGanados = (float) $fila['puntos_ganados'];
        $puntosPerdidos = (float) $fila['puntos_perdidos'];
        $puntosPendientes = (float) $fila['puntos_pendientes'];
        $califMinimaAprob = (float) $fila['calif_minima'];        // mínima aprobatoria (BD)
        $califActual = (float) $fila['calificacion_actual']; // promedio ponderado actual 0–100

        // Total de la escala. Idealmente coincide con la suma de ponderaciones.
        $totalEscala = $puntosGanados + $puntosPerdidos + $puntosPendientes;
        if ($totalEscala <= 0.0) {
            $totalEscala = 100.0;
        }

        // Calificación mínima/máxima DINÁMICAS:
        //  - mín. dinámica: lo que ya tiene asegurado aunque falle lo pendiente
        //  - máx. dinámica: lo que podría lograr si gana todo lo pendiente
        $califMinDinamica = $puntosGanados;
        $califMaxDinamica = $puntosGanados + $puntosPendientes;
        if ($califMaxDinamica > $totalEscala) {
            $califMaxDinamica = $totalEscala;
        }

        // Porcentaje obtenido: usamos la calificación actual (0–100), normalizada
        // por si la escala no es exactamente 100.
        if ($totalEscala != 100.0) {
            $porcentajeObtenido = ($califActual / $totalEscala) * 100.0;
        } else {
            $porcentajeObtenido = $califActual;
        }

        // Puntos necesarios para aprobar (respecto a la mínima aprobatoria)
        if ($califMinimaAprob <= $califMinDinamica) {
            $puntosNecesarios = 0.0;
        } else {
            $puntosNecesarios = $califMinimaAprob - $califMinDinamica;
            if ($puntosNecesarios < 0.0) {
                $puntosNecesarios = 0.0;
            }
            // No podemos necesitar más puntos de los que quedan en juego
            if ($puntosNecesarios > $puntosPendientes) {
                $puntosNecesarios = $puntosPendientes;
            }
        }

        $porcentajeObtenido = round($porcentajeObtenido, 2);
        $califMinDinamica   = round($califMinDinamica, 2);
        $califMaxDinamica   = round($califMaxDinamica, 2);
        $puntosNecesarios   = round($puntosNecesarios, 2);

        // Diagnóstico básico (puede ser refinado en el frontend)
        $estado = 'En riesgo';
        $nivel  = 'risk';

        if ($porcentajeObtenido >= $califMinimaAprob) {
            $estado = 'Aprobado';
            $nivel  = 'ok';
        } elseif ($porcentajeObtenido < $califMinimaAprob - 10.0) {
            $estado = 'Reprobado';
            $nivel  = 'fail';
        }

        $progreso = [
            'porcentaje_obtenido'          => $porcentajeObtenido,
            'porcentaje_total'             => $totalEscala,
            'puntos_obtenidos'             => $puntosGanados,
            'puntos_perdidos'              => $puntosPerdidos,
            'puntos_posibles_obtener'      => $puntosPendientes,
            'puntos_necesarios_aprobar'    => $puntosNecesarios,
            'calificacion_minima'          => $califMinimaAprob,   // mínima aprobatoria (BD)
            'calificacion_actual'          => $califActual,        // promedio ponderado actual
            'calificacion_maxima_posible'  => $califMaxDinamica,   // máx. dinámica (si gana todo)
            'calificacion_minima_dinamica' => $califMinDinamica,   // mín. dinámica (si pierde todo)
            'por_tipo'                     => array_values($this->resultadosPorTipo),
            'diagnostico' => [
                'grado'  => round($porcentajeObtenido),
                'estado' => $estado,
                'nivel'  => $nivel,
            ],
        ];

        return [
            'materia'  => $fila,
            'progreso' => $progreso,
        ];
    }
}
