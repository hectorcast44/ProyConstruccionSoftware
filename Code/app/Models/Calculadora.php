<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

class Calculadora
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function recalcularMateria(int $id_materia, int $id_usuario): bool
    {
        try {
            $stmtPond = $this->pdo->prepare(
                "SELECT id_tipo_actividad, porcentaje FROM PONDERACION WHERE id_materia = ?"
            );
            $stmtPond->execute([$id_materia]);
            $ponderaciones = $stmtPond->fetchAll(PDO::FETCH_KEY_PAIR);

            $stmtAct = $this->pdo->prepare(
                "SELECT id_tipo_actividad, puntos_posibles, puntos_obtenidos
                 FROM ACTIVIDAD
                 WHERE id_materia = ? AND id_usuario = ? AND puntos_posibles IS NOT NULL"
            );
            $stmtAct->execute([$id_materia, $id_usuario]);
            $actividades = $stmtAct->fetchAll(PDO::FETCH_ASSOC);

            $puntosGanados = 0.0;
            $puntosPerdidos = 0.0;
            $puntosPendientes = 0.0;
            $porTipo = [];

            foreach ($actividades as $act) {
                $tipoId = (int) $act['id_tipo_actividad'];
                $puntosPosible = (float) $act['puntos_posibles'];
                $puntosObt = $act['puntos_obtenidos'] !== null ? (float) $act['puntos_obtenidos'] : null;

                if (!isset($porTipo[$tipoId])) {
                    $porTipo[$tipoId] = ['suma_obtenidos' => 0.0, 'suma_posibles' => 0.0];
                }

                if ($puntosObt !== null) {
                    $puntosGanados += $puntosObt;
                    $puntosPerdidos += ($puntosPosible - $puntosObt);
                    $porTipo[$tipoId]['suma_obtenidos'] += $puntosObt;
                    $porTipo[$tipoId]['suma_posibles'] += $puntosPosible;
                } else {
                    $puntosPendientes += $puntosPosible;
                }
            }

            $calificacionFinal = 0.0;
            foreach ($ponderaciones as $tipoId => $porcentaje) {
                if (isset($porTipo[$tipoId]) && $porTipo[$tipoId]['suma_posibles'] > 0) {
                    $sumaObt = $porTipo[$tipoId]['suma_obtenidos'];
                    $sumaPos = $porTipo[$tipoId]['suma_posibles'];
                    $contribucion = ($sumaObt / $sumaPos) * (float) $porcentaje;
                    $calificacionFinal += $contribucion;
                }
            }

            $stmtGuardar = $this->pdo->prepare(
                "UPDATE MATERIA
                 SET calificacion_actual = ?, puntos_ganados = ?, puntos_perdidos = ?, puntos_pendientes = ?
                 WHERE id_materia = ? AND id_usuario = ?"
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
        } catch (Exception $e) {
            error_log("Error en Calculadora::recalcularMateria: " . $e->getMessage());
            throw $e;
        }
    }

    public function obtenerMateriaConProgreso(int $id_materia, int $id_usuario): array
    {
        $sql = "SELECT * FROM MATERIA WHERE id_materia = :id_materia AND id_usuario = :id_usuario LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_materia' => $id_materia, ':id_usuario' => $id_usuario]);
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
            $porcentajeObtenido = ($puntosGanados / $totalPuntos) * 100.0;
            $porcentajeMaxPosible = (($puntosGanados + $puntosPendientes) / $totalPuntos) * 100.0;
            $puntosRequeridosAprobar = ($califMinima / 100.0) * $totalPuntos;
            $puntosNecesarios = max(0.0, $puntosRequeridosAprobar - $puntosGanados);
        } else {
            $porcentajeObtenido = 0.0;
            $porcentajeMaxPosible = 0.0;
            $puntosNecesarios = 0.0;
        }

        $estado = 'En riesgo';
        $nivel = 'risk';
        if ($porcentajeObtenido >= $califMinima) {
            $estado = 'Aprobado';
            $nivel = 'ok';
        } elseif ($porcentajeObtenido < $califMinima - 10.0) {
            $estado = 'Reprobado';
            $nivel = 'fail';
        }

        return [
            'materia' => $fila,
            'progreso' => [
                'porcentaje_obtenido' => round($porcentajeObtenido, 2),
                'porcentaje_total' => 100,
                'puntos_obtenidos' => $puntosGanados,
                'puntos_perdidos' => $puntosPerdidos,
                'puntos_posibles_obtener' => $puntosPendientes,
                'puntos_necesarios_aprobar' => round($puntosNecesarios, 2),
                'calificacion_minima' => $califMinima,
                'calificacion_actual' => $califActual,
                'calificacion_maxima_posible' => round($porcentajeMaxPosible, 2),
                'diagnostico' => [
                    'grado' => round($porcentajeObtenido),
                    'estado' => $estado,
                    'nivel' => $nivel
                ]
            ]
        ];
    }
}
