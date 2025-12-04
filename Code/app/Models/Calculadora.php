<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

/**
 * Excepción lanzada cuando no se encuentra la materia para el usuario.
 */
class MateriaNotFoundException extends Exception {
    /**
     * Construir la excepción de materia no encontrada.
     *
     * @param string $message Mensaje de error.
     * @param int $code Código de error.
     */
    public function __construct(string $message = "No se encontró la materia para el usuario actual.", int $code = 404) {
        parent::__construct($message, $code);
    }
}

/**
 * Servicio de cálculo de calificaciones por materia.
 *
 * Gestionar:
 *  - Recalcular calificación actual de una materia.
 *  - Calcular puntos ganados, perdidos y pendientes.
 *  - Proveer resumen de progreso para el frontend.
 */
class Calculadora {
    /**
     * Conexión PDO hacia la base de datos.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Resultados ponderados por tipo de actividad en el último recálculo.
     *
     * Clave: id_tipo_actividad
     * Valor:
     *  [
     *    'id_tipo' => int,
     *    'puntos_tipo' => float,
     *    'puntos_asegurados' => float,
     *    'puntos_perdidos' => float,
     *    'puntos_pendientes' => float,
     *  ]
     *
     * @var array<int,array<string,mixed>>
     */
    private array $resultadosPorTipo = [];

    /**
     * Información de depuración opcional del último recálculo.
     *
     * @var array<string,mixed>|null
     */
    private ?array $debugInfo = null;
    private const ESCALA_PORCENTAJE = 100.0;
    private const APROBATORIA_POR_DEFECTO = 70.0;
    private const RIESGO_MARGEN = 10.0;

    /**
     * Construir la calculadora con una conexión PDO.
     *
     * @param PDO|null $pdo Conexión opcional. Si es null, obtener desde Database.
     *
     * @return void
     */
    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? Database::getInstance()->getConnection();
    }

    /**
     * Recalcular las métricas de una materia.
     *
     * Modelo:
     *  - La suma de las ponderaciones de la materia representa el "total" de puntos
     *    de la calificación (normalmente 100).
     *  - Cada tipo de actividad tiene un valor en puntos dentro de la calificación
     *    (PONDERACION.porcentaje).
     *  - Las actividades internas se expresan en puntos_posibles.
     *  - Si la suma de puntos_posibles de un tipo es menor que puntos_tipo:
     *    -> la diferencia se considera puntos futuros.
     *  - Si la suma de puntos_posibles de un tipo es mayor que puntos_tipo:
     *    -> se escalan todos los puntos_posibles para caber en puntos_tipo.
     *
     * @param int $idMateria Identificador de la materia.
     * @param int $idUsuario Identificador del usuario.
     *
     * @throws Exception En caso de error interno.
     *
     * @return bool Verdadero si el recálculo se completó sin errores.
     */
    public function recalcularMateria(int $idMateria, int $idUsuario): bool {
        $this->inicializarEstado();

        try {
            $ponderaciones = $this->obtenerPonderacionesMateria($idMateria);
            $puntosEscalaTotal = $this->calcularSumaPonderaciones($ponderaciones);

            $actividades = $this->obtenerActividadesCalificables($idMateria, $idUsuario);
            $datosPorTipo = $this->agruparActividadesPorTipo($actividades);

            if (empty($ponderaciones)) {
                [$ponderaciones, $puntosEscalaTotal] = $this->construirPonderacionesFallback($datosPorTipo);
            }

            if (isset($_GET['__dbg']) && $_GET['__dbg']) {
                $this->debugInfo = [
                    'ponderaciones' => $ponderaciones,
                    'puntosEscalaTotal' => $puntosEscalaTotal,
                    'datosPorTipo' => $datosPorTipo,
                ];
            }

            [$puntosGanados, $puntosPerdidos, $puntosPendientes, $sumaCalifPorTipo] =
                $this->calcularResultadosPorTipo($ponderaciones, $datosPorTipo);

            // La calificación actual ES lo que llevo asegurado hoy en la escala total
            // (normalmente 0–100), es decir, los puntos ganados ya ponderados.
            $calificacionActual = $puntosGanados;


            [$puntosGanados, $puntosPerdidos, $puntosPendientes, $calificacionActual] =
                $this->redondearResultadosGlobales(
                    $puntosGanados,
                    $puntosPerdidos,
                    $puntosPendientes,
                    $calificacionActual
                );

            $this->guardarResultadosEnMateria(
                $idMateria,
                $idUsuario,
                $calificacionActual,
                $puntosGanados,
                $puntosPerdidos,
                $puntosPendientes
            );

            $this->completarDebugFinal(
                $calificacionActual,
                $puntosGanados,
                $puntosPerdidos,
                $puntosPendientes,
                $sumaCalifPorTipo
            );

            return true;
        } catch (Exception $excepcion) {
            error_log('Error en Calculadora::recalcularMateria: ' . $excepcion->getMessage());
            throw $excepcion;
        }
    }

    /**
     * Obtener datos de la materia y su progreso listo para el frontend.
     *
     * @param int $idMateria Identificador de la materia.
     * @param int $idUsuario Identificador del usuario.
     *
     * @throws MateriaNotFoundException Si no se encuentra la materia.
     * @throws Exception Si ocurre un error inesperado.
     *
     * @return array{
     *   materia: array<string,mixed>,
     *   progreso: array<string,mixed>,
     *   debug?: array<string,mixed>
     * }
     */
    public function obtenerMateriaConProgreso(int $idMateria, int $idUsuario): array {
        $this->recalcularMateria($idMateria, $idUsuario);

        $filaMateria = $this->obtenerFilaMateria($idMateria, $idUsuario);

        $puntosGanados = (float) $filaMateria['puntos_ganados'];
        $puntosPerdidos = (float) $filaMateria['puntos_perdidos'];
        $puntosPendientes = (float) $filaMateria['puntos_pendientes'];
        $califMinimaAprob = (float) ($filaMateria['calif_minima'] ?? self::APROBATORIA_POR_DEFECTO);
        $califActual = (float) $filaMateria['calificacion_actual'];

        $totalEscala = $this->calcularTotalEscala(
            $puntosGanados,
            $puntosPerdidos,
            $puntosPendientes
        );

        [$califMinDinamica, $califMaxDinamica] = $this->calcularRangoDinamico(
            $puntosGanados,
            $puntosPendientes,
            $totalEscala
        );

        $porcentajeObtenido = $this->calcularPorcentajeObtenido(
            $califActual,
            $totalEscala
        );

        $puntosNecesarios = $this->calcularPuntosNecesariosAprobar(
            $califMinimaAprob,
            $califMinDinamica,
            $puntosPendientes
        );

        $porcentajeObtenido = round($porcentajeObtenido, 2);
        $califMinDinamica = round($califMinDinamica, 2);
        $califMaxDinamica = round($califMaxDinamica, 2);
        $puntosNecesarios = round($puntosNecesarios, 2);

        [$estado, $nivel] = $this->diagnosticarEstado(
            $porcentajeObtenido,
            $califMinimaAprob
        );

        $progreso = [
            'porcentaje_obtenido' => $porcentajeObtenido,
            'porcentaje_total' => $totalEscala,
            'puntos_obtenidos' => $puntosGanados,
            'puntos_perdidos' => $puntosPerdidos,
            'puntos_posibles_obtener' => $puntosPendientes,
            'puntos_necesarios_aprobar' => $puntosNecesarios,
            'calificacion_minima' => $califMinimaAprob,
            'calificacion_actual' => $califActual,
            'calificacion_maxima_posible' => $califMaxDinamica,
            'calificacion_minima_dinamica' => $califMinDinamica,
            'por_tipo' => array_values($this->resultadosPorTipo),
            'diagnostico' => [
                'grado' => round($porcentajeObtenido),
                'estado' => $estado,
                'nivel' => $nivel,
            ],
        ];

        $salida = [
            'materia' => $filaMateria,
            'progreso' => $progreso,
        ];

        if (isset($_GET['__dbg']) && $_GET['__dbg']) {
            $salida['debug'] = $this->debugInfo;
        }

        return $salida;
    }

    /* ==========================================================
       HELPERS PRIVADOS: REINICIO Y CARGA DE DATOS
       ========================================================== */

    /**
     * Inicializar estado interno para un nuevo recálculo.
     *
     * @return void
     */
    private function inicializarEstado(): void {
        $this->resultadosPorTipo = [];
        $this->debugInfo = null;
    }

    /**
     * Obtener ponderaciones por tipo de actividad para una materia.
     *
     * @param int $idMateria Identificador de la materia.
     *
     * @return array<int,float> Mapa id_tipo_actividad => porcentaje.
     */
    private function obtenerPonderacionesMateria(int $idMateria): array {
        $sql = "SELECT id_tipo_actividad, porcentaje
                FROM PONDERACION
                WHERE id_materia = ?";

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute([$idMateria]);

        $ponderaciones = $sentencia->fetchAll(PDO::FETCH_KEY_PAIR);
        if (!$ponderaciones) {
            return [];
        }

        $resultado = [];
        foreach ($ponderaciones as $idTipo => $valor) {
            $resultado[(int) $idTipo] = (float) $valor;
        }

        return $resultado;
    }

    /**
     * Calcular la suma de todas las ponderaciones de una materia.
     *
     * @param array<int,float> $ponderaciones Ponderaciones por tipo.
     *
     * @return float Suma total de ponderaciones.
     */
    private function calcularSumaPonderaciones(array $ponderaciones): float {
        $suma = 0.0;
        foreach ($ponderaciones as $valor) {
            $suma += (float) $valor;
        }
        return $suma;
    }

    /**
     * Obtener actividades calificables de una materia para un usuario.
     *
     * Solo incluye actividades con puntos_posibles > 0.
     *
     * @param int $idMateria Identificador de la materia.
     * @param int $idUsuario Identificador del usuario.
     *
     * @return array<int,array<string,mixed>> Lista de actividades.
     */
    private function obtenerActividadesCalificables(int $idMateria, int $idUsuario): array {
        $sql = "SELECT id_tipo_actividad, puntos_posibles, puntos_obtenidos
                FROM ACTIVIDAD
                WHERE id_materia = ?
                  AND id_usuario = ?
                  AND puntos_posibles IS NOT NULL
                  AND puntos_posibles > 0";

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute([$idMateria, $idUsuario]);

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Agrupar actividades por tipo para facilitar el cálculo posterior.
     *
     * @param array<int,array<string,mixed>> $actividades Lista de actividades.
     *
     * @return array<int,array<string,float>> Datos agrupados por tipo.
     */
    private function agruparActividadesPorTipo(array $actividades): array {
        $datosPorTipo = [];

        foreach ($actividades as $actividad) {
            $idTipo = (int) $actividad['id_tipo_actividad'];

            if (!isset($datosPorTipo[$idTipo])) {
                $datosPorTipo[$idTipo] = [
                    'sum_pos' => 0.0,
                    'sum_calif_pos' => 0.0,
                    'sum_pend_pos' => 0.0,
                    'sum_obt' => 0.0,
                ];
            }

            $puntosPosibles = (float) $actividad['puntos_posibles'];
            $puntosObtenidos = $actividad['puntos_obtenidos'];

            $datosPorTipo[$idTipo]['sum_pos'] += $puntosPosibles;

            if ($puntosObtenidos !== null) {
                $datosPorTipo[$idTipo]['sum_calif_pos'] += $puntosPosibles;
                $datosPorTipo[$idTipo]['sum_obt'] += (float) $puntosObtenidos;
            } else {
                $datosPorTipo[$idTipo]['sum_pend_pos'] += $puntosPosibles;
            }
        }

        return $datosPorTipo;
    }

    /**
     * Construir ponderaciones de respaldo cuando no existen en PONDERACION.
     *
     * @param array<int,array<string,float>> $datosPorTipo Datos agrupados por tipo.
     *
     * @return array{0:array<int,float>,1:float} Ponderaciones y suma total.
     */
    private function construirPonderacionesFallback(array $datosPorTipo): array {
        $ponderaciones = [];
        foreach ($datosPorTipo as $idTipo => $info) {
            $sumaPosibles = (float) ($info['sum_pos'] ?? 0.0);
            if ($sumaPosibles > 0.0) {
                $ponderaciones[$idTipo] = $sumaPosibles;
            }
        }

        $sumaTotal = $this->calcularSumaPonderaciones($ponderaciones);

        return [$ponderaciones, $sumaTotal];
    }

    /**
     * Calcular resultados globales por tipo de actividad.
     *
     * @param array<int,float> $ponderaciones Ponderaciones por tipo.
     * @param array<int,array<string,float>> $datosPorTipo Datos agrupados por tipo.
     *
     * @return array{0:float,1:float,2:float,3:float}
     */
    private function calcularResultadosPorTipo(array $ponderaciones, array $datosPorTipo): array {
        $puntosGanados = 0.0;
        $puntosPerdidos = 0.0;
        $puntosPendientes = 0.0;
        $sumaCalifPorTipo = 0.0;

        foreach ($ponderaciones as $idTipo => $puntosTipo) {
            $puntosTipo = (float) $puntosTipo;
            if ($puntosTipo <= 0.0) {
                continue;
            }

            $info = $datosPorTipo[$idTipo] ?? [
                'sum_pos' => 0.0,
                'sum_calif_pos' => 0.0,
                'sum_pend_pos' => 0.0,
                'sum_obt' => 0.0,
            ];

            $sumPos = (float) $info['sum_pos'];
            $sumCalifPos = (float) $info['sum_calif_pos'];
            $sumPendPos = (float) $info['sum_pend_pos'];
            $sumObt = (float) $info['sum_obt'];

            if ($sumCalifPos > 0.0) {
                $promedioTipo = $sumObt / $sumCalifPos;
                $sumaCalifPorTipo += $promedioTipo * $puntosTipo;
            }

            $totalInternoFinal = max($puntosTipo, $sumPos);

            if ($totalInternoFinal <= 0.0) {
                $ganadosTipo = 0.0;
                $perdidosTipo = 0.0;
                $pendientesTipo = 0.0;
            } else {
                $factor = $puntosTipo / $totalInternoFinal;

                $ganadosTipo = $sumObt * $factor;
                $perdidosTipo = max(0.0, ($sumCalifPos - $sumObt) * $factor);

                $pendDefinidos = $sumPendPos * $factor;
                $pendFuturos = max(0.0, $totalInternoFinal - $sumPos) * $factor;

                $pendientesTipo = $pendDefinidos + $pendFuturos;
            }

            $puntosGanados += $ganadosTipo;
            $puntosPerdidos += $perdidosTipo;
            $puntosPendientes += $pendientesTipo;

            $this->resultadosPorTipo[(int) $idTipo] = [
                'id_tipo' => (int) $idTipo,
                'puntos_tipo' => round($puntosTipo, 2),
                'puntos_asegurados' => round($ganadosTipo, 2),
                'puntos_perdidos' => round($perdidosTipo, 2),
                'puntos_pendientes' => round($pendientesTipo, 2),
            ];
        }

        return [$puntosGanados, $puntosPerdidos, $puntosPendientes, $sumaCalifPorTipo];
    }


    /**
     * Redondear los resultados globales a dos decimales.
     *
     * @param float $puntosGanados Puntos ya ganados.
     * @param float $puntosPerdidos Puntos ya perdidos.
     * @param float $puntosPendientes Puntos en juego.
     * @param float $calificacionActual Calificación actual normalizada.
     *
     * @return array{0:float,1:float,2:float,3:float}
     */
    private function redondearResultadosGlobales(
        float $puntosGanados,
        float $puntosPerdidos,
        float $puntosPendientes,
        float $calificacionActual
    ): array {
        $puntosGanados = round($puntosGanados, 2);
        $puntosPerdidos = round($puntosPerdidos, 2);
        $puntosPendientes = round($puntosPendientes, 2);
        $calificacionActual = round($calificacionActual, 2);

        return [$puntosGanados, $puntosPerdidos, $puntosPendientes, $calificacionActual];
    }

    /**
     * Guardar los resultados de cálculo en la tabla MATERIA.
     *
     * @param int $idMateria Identificador de la materia.
     * @param int $idUsuario Identificador del usuario.
     * @param float $calificacionActual Calificación actual.
     * @param float $puntosGanados Puntos ganados.
     * @param float $puntosPerdidos Puntos perdidos.
     * @param float $puntosPendientes Puntos pendientes.
     *
     * @return void
     */
    private function guardarResultadosEnMateria(
        int $idMateria,
        int $idUsuario,
        float $calificacionActual,
        float $puntosGanados,
        float $puntosPerdidos,
        float $puntosPendientes
    ): void {
        $sql = "UPDATE MATERIA
                SET calificacion_actual = ?,
                    puntos_ganados = ?,
                    puntos_perdidos = ?,
                    puntos_pendientes = ?
                WHERE id_materia = ?
                  AND id_usuario = ?";

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute([
            $calificacionActual,
            $puntosGanados,
            $puntosPerdidos,
            $puntosPendientes,
            $idMateria,
            $idUsuario,
        ]);
    }

    /**
     * Completar la información de depuración al final del recálculo.
     *
     * @param float $calificacionActual Calificación actual.
     * @param float $puntosGanados Puntos ganados.
     * @param float $puntosPerdidos Puntos perdidos.
     * @param float $puntosPendientes Puntos pendientes.
     * @param float $sumaCalifPorTipo Suma de contribuciones por tipo.
     *
     * @return void
     */
    private function completarDebugFinal(
        float $calificacionActual,
        float $puntosGanados,
        float $puntosPerdidos,
        float $puntosPendientes,
        float $sumaCalifPorTipo
    ): void {
        if (isset($_GET['__dbg']) && $_GET['__dbg']) {
            $this->debugInfo = array_merge($this->debugInfo ?? [], [
                'calificacionActual' => $calificacionActual,
                'puntosGanados' => $puntosGanados,
                'puntosPerdidos' => $puntosPerdidos,
                'puntosPendientes' => $puntosPendientes,
                'sumaCalificacionPorTipo' => $sumaCalifPorTipo,
            ]);
        }
    }

    /* ==========================================================
       HELPERS PRIVADOS: PROGRESO Y DIAGNÓSTICO
       ========================================================== */

    /**
     * Obtener la fila de la tabla MATERIA para un usuario.
     *
     * @param int $idMateria Identificador de la materia.
     * @param int $idUsuario Identificador del usuario.
     *
     * @throws MateriaNotFoundException Si no se encuentra la materia.
     *
     * @return array<string,mixed> Fila de la tabla MATERIA.
     */
    private function obtenerFilaMateria(int $idMateria, int $idUsuario): array {
        $sql = "SELECT
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
                LIMIT 1";

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute([
            ':id_materia' => $idMateria,
            ':id_usuario' => $idUsuario,
        ]);

        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            throw new MateriaNotFoundException();
        }

        return $fila;
    }

    /**
     * Calcular el total de la escala de puntos de la materia.
     *
     * @param float $puntosGanados Puntos ganados.
     * @param float $puntosPerdidos Puntos perdidos.
     * @param float $puntosPendientes Puntos pendientes.
     *
     * @return float Total de la escala (≈ suma de ponderaciones).
     */
    private function calcularTotalEscala(
        float $puntosGanados,
        float $puntosPerdidos,
        float $puntosPendientes
    ): float {
        $total = $puntosGanados + $puntosPerdidos + $puntosPendientes;

        if ($total <= 0.0) {
            return self::ESCALA_PORCENTAJE;
        }

        return $total;
    }

    /**
     * Calcular las calificaciones mínima y máxima dinámicas posibles.
     *
     * @param float $puntosGanados Puntos ya ganados.
     * @param float $puntosPendientes Puntos en juego.
     * @param float $totalEscala Total de la escala.
     *
     * @return array{0:float,1:float} [min_dinamica, max_dinamica]
     */
    private function calcularRangoDinamico(
        float $puntosGanados,
        float $puntosPendientes,
        float $totalEscala
    ): array {
        $califMinDinamica = $puntosGanados;
        $califMaxDinamica = $puntosGanados + $puntosPendientes;

        if ($califMaxDinamica > $totalEscala) {
            $califMaxDinamica = $totalEscala;
        }

        return [$califMinDinamica, $califMaxDinamica];
    }

    /**
     * Calcular el porcentaje obtenido por el alumno.
     *
     * @param float $califActual Calificación actual.
     * @param float $totalEscala Total de la escala.
     *
     * @return float Porcentaje obtenido (0–100).
     */
    private function calcularPorcentajeObtenido(float $califActual, float $totalEscala): float {
        if ($totalEscala != self::ESCALA_PORCENTAJE) {
            return ($califActual / $totalEscala) * self::ESCALA_PORCENTAJE;
        }

        return $califActual;
    }

    /**
     * Calcular los puntos necesarios para aprobar.
     *
     * @param float $califMinimaAprob Calificación mínima aprobatoria.
     * @param float $califMinDinamica Calificación mínima dinámica.
     * @param float $puntosPendientes Puntos en juego.
     *
     * @return float Puntos necesarios para aprobar.
     */
    private function calcularPuntosNecesariosAprobar(
        float $califMinimaAprob,
        float $califMinDinamica,
        float $puntosPendientes
    ): float {
        if ($califMinimaAprob <= $califMinDinamica) {
            return 0.0;
        }

        $puntosNecesarios = $califMinimaAprob - $califMinDinamica;

        if ($puntosNecesarios < 0.0) {
            $puntosNecesarios = 0.0;
        }

        if ($puntosNecesarios > $puntosPendientes) {
            $puntosNecesarios = $puntosPendientes;
        }

        return $puntosNecesarios;
    }

    /**
     * Determinar el diagnóstico de estado (Aprobado, En riesgo, Reprobado).
     *
     * @param float $porcentajeObtenido Porcentaje actual obtenido.
     * @param float $califMinimaAprob Calificación mínima aprobatoria.
     *
     * @return array{0:string,1:string} [estado, nivel].
     */
    private function diagnosticarEstado(float $porcentajeObtenido, float $califMinimaAprob): array {
        $estado = 'En riesgo';
        $nivel = 'risk';

        if ($porcentajeObtenido >= $califMinimaAprob) {
            $estado = 'Aprobado';
            $nivel = 'ok';
        } elseif ($porcentajeObtenido < ($califMinimaAprob - self::RIESGO_MARGEN)) {
            $estado = 'Reprobado';
            $nivel = 'fail';
        }

        return [$estado, $nivel];
    }
}
