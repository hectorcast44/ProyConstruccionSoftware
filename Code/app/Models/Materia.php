<?php

namespace App\Models;

use App\Core\Database;
use App\Exceptions\MateriaException;
use PDO;

/**
 * Modelo de dominio para la entidad MATERIA.
 *
 * Responsabilidades:
 *  - Gestionar creación, actualización y eliminación de materias.
 *  - Consultar materias por usuario o por identificador.
 *  - Obtener resúmenes de actividades por materia.
 *  - Gestionar las ponderaciones (tipos) de una materia.
 */
class Materia {
    /**
     * Conexión PDO hacia la base de datos.
     *
     * @var PDO
     */
    private PDO $pdo;
    private const CALIFICACION_MINIMA_POR_DEFECTO = 70.0;
    private const CALIFICACION_MINIMA_LIMITE_INFERIOR = 0.0;
    private const CALIFICACION_MINIMA_LIMITE_SUPERIOR = 100.0;
    private const PUNTOS_INICIALES = 0.0;

    /**
     * Construir el modelo Materia con una conexión PDO.
     *
     * @param PDO|null $pdo Conexión opcional. Si es null, obtener desde Database.
     *
     * @return void
     */
    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? Database::getInstance()->getConnection();
    }

    /**
     * Crear una nueva materia para un usuario.
     *
     * @param int $idUsuario Identificador del usuario propietario.
     * @param string $nombreMateria Nombre de la materia.
     * @param float $calificacionMinima Calificación mínima aprobatoria.
     *
     * @throws MateriaException Si la materia ya existe o la calificación mínima es inválida.
     *
     * @return int Identificador de la materia creada.
     */
    public function crear(int $idUsuario, string $nombreMateria, float $calificacionMinima = self::CALIFICACION_MINIMA_POR_DEFECTO): int {
        $this->validarCalificacionMinima($calificacionMinima);

        if ($this->existeMateria($idUsuario, $nombreMateria)) {
            throw new MateriaException('Ya existe una materia con ese nombre.');
        }

        $sql = "INSERT INTO MATERIA (
                    id_usuario,
                    nombre_materia,
                    calif_minima,
                    calificacion_actual,
                    puntos_ganados,
                    puntos_perdidos,
                    puntos_pendientes
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute([
            $idUsuario,
            $nombreMateria,
            $calificacionMinima,
            self::PUNTOS_INICIALES,
            self::PUNTOS_INICIALES,
            self::PUNTOS_INICIALES,
            self::PUNTOS_INICIALES,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualizar los datos básicos de una materia.
     *
     * @param int $idMateria Identificador de la materia.
     * @param int $idUsuario Identificador del usuario propietario.
     * @param string $nombreMateria Nombre de la materia.
     * @param float $calificacionMinima Calificación mínima aprobatoria.
     *
     * @throws MateriaException Si no pertenece al usuario, la calificación mínima es inválida o el nombre ya existe.
     *
     * @return bool Verdadero si se actualizó al menos un registro.
     */
    public function actualizar(int $idMateria, int $idUsuario, string $nombreMateria, float $calificacionMinima): bool {
        if (!$this->verificarPropiedad($idMateria, $idUsuario)) {
            throw new MateriaException('No tiene permisos para modificar esta materia.');
        }

        $this->validarCalificacionMinima($calificacionMinima);

        if ($this->existeMateriaExcepto($idUsuario, $nombreMateria, $idMateria)) {
            throw new MateriaException('Ya existe otra materia con ese nombre.');
        }

        $sql = "UPDATE MATERIA
                SET nombre_materia = ?, calif_minima = ?
                WHERE id_materia = ? AND id_usuario = ?";

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute([
            $nombreMateria,
            $calificacionMinima,
            $idMateria,
            $idUsuario,
        ]);

        return $sentencia->rowCount() > 0;
    }

    /**
     * Eliminar una materia del usuario.
     *
     * @param int $idMateria Identificador de la materia.
     * @param int $idUsuario Identificador del usuario propietario.
     *
     * @throws MateriaException Si no pertenece al usuario o tiene actividades asociadas.
     *
     * @return bool Verdadero si se eliminó al menos un registro.
     */
    public function eliminar(int $idMateria, int $idUsuario): bool {
        if (!$this->verificarPropiedad($idMateria, $idUsuario)) {
            throw new MateriaException('No tiene permisos para eliminar esta materia.');
        }

        if ($this->tieneActividades($idMateria)) {
            throw new MateriaException('No se puede eliminar una materia que tiene actividades asociadas.');
        }

        $sql = "DELETE FROM MATERIA WHERE id_materia = ? AND id_usuario = ?";
        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute([$idMateria, $idUsuario]);

        return $sentencia->rowCount() > 0;
    }

    /**
     * Obtener todas las materias de un usuario.
     *
     * @param int $idUsuario Identificador del usuario.
     *
     * @return array<int,array<string,mixed>> Lista de materias del usuario.
     */
    public function obtenerPorUsuario(int $idUsuario): array {
        $sql = "SELECT *
                FROM MATERIA
                WHERE id_usuario = ?
                ORDER BY nombre_materia";

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute([$idUsuario]);

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener una materia por identificador para un usuario.
     *
     * @param int $idMateria Identificador de la materia.
     * @param int $idUsuario Identificador del usuario.
     *
     * @return array<string,mixed>|false Fila de la materia o false si no existe.
     */
    public function obtenerPorId(int $idMateria, int $idUsuario) {
        $sql = "SELECT *
                FROM MATERIA
                WHERE id_materia = ?
                  AND id_usuario = ?";

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute([$idMateria, $idUsuario]);

        return $sentencia->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Verificar si ya existe una materia con el mismo nombre para el usuario.
     *
     * @param int $idUsuario Identificador del usuario.
     * @param string $nombreMateria Nombre de la materia.
     *
     * @return bool Verdadero si ya existe una materia con ese nombre.
     */
    private function existeMateria(int $idUsuario, string $nombreMateria): bool {
        $sql = "SELECT COUNT(*)
                FROM MATERIA
                WHERE id_usuario = ?
                  AND nombre_materia = ?";

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute([$idUsuario, $nombreMateria]);

        return (int) $sentencia->fetchColumn() > 0;
    }

    /**
     * Verificar si existe otra materia con el mismo nombre, excluyendo una materia actual.
     *
     * @param int $idUsuario Identificador del usuario.
     * @param string $nombreMateria Nombre de la materia.
     * @param int $idMateriaActual Identificador de la materia a excluir.
     *
     * @return bool Verdadero si existe otra materia con ese nombre.
     */
    private function existeMateriaExcepto(int $idUsuario, string $nombreMateria, int $idMateriaActual): bool {
        $sql = "SELECT COUNT(*)
                FROM MATERIA
                WHERE id_usuario = ?
                  AND nombre_materia = ?
                  AND id_materia != ?";

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute([$idUsuario, $nombreMateria, $idMateriaActual]);

        return (int) $sentencia->fetchColumn() > 0;
    }

    /**
     * Verificar si una materia pertenece a un usuario.
     *
     * @param int $idMateria Identificador de la materia.
     * @param int $idUsuario Identificador del usuario.
     *
     * @return bool Verdadero si la materia pertenece al usuario.
     */
    private function verificarPropiedad(int $idMateria, int $idUsuario): bool {
        $sql = "SELECT COUNT(*)
                FROM MATERIA
                WHERE id_materia = ?
                  AND id_usuario = ?";

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute([$idMateria, $idUsuario]);

        return (int) $sentencia->fetchColumn() > 0;
    }

    /**
     * Verificar si una materia tiene actividades asociadas.
     *
     * @param int $idMateria Identificador de la materia.
     *
     * @return bool Verdadero si existen actividades relacionadas.
     */
    private function tieneActividades(int $idMateria): bool {
        $sql = "SELECT COUNT(*)
                FROM ACTIVIDAD
                WHERE id_materia = ?";

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute([$idMateria]);

        return (int) $sentencia->fetchColumn() > 0;
    }

    /**
     * Obtener un resumen de actividades por materia y tipo para un usuario.
     *
     * @param int $idUsuario Identificador del usuario.
     *
     * @return array<int,array<string,mixed>> Lista de resúmenes por materia y tipo.
     */
    public function obtenerResumenActividades(int $idUsuario): array {
        $sql = "SELECT
                    a.id_materia,
                    ta.nombre_tipo,
                    SUM(a.puntos_obtenidos) AS puntos_obtenidos,
                    SUM(a.puntos_posibles)  AS puntos_posibles
                FROM ACTIVIDAD a
                INNER JOIN TIPO_ACTIVIDAD ta
                    ON ta.id_tipo_actividad = a.id_tipo_actividad
                WHERE a.id_usuario = ?
                GROUP BY
                    a.id_materia,
                    ta.nombre_tipo
                ORDER BY
                    a.id_materia,
                    ta.nombre_tipo";

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute([$idUsuario]);

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener los tipos (ponderaciones) asignados a una materia.
     *
     * @param int $idMateria Identificador de la materia.
     *
     * @return array<int,array<string,mixed>> Lista de tipos con porcentaje.
     */
    public function obtenerTipos(int $idMateria): array {
        $sql = "SELECT
                    p.id_tipo_actividad,
                    ta.nombre_tipo,
                    p.porcentaje
                FROM PONDERACION p
                INNER JOIN TIPO_ACTIVIDAD ta
                    ON ta.id_tipo_actividad = p.id_tipo_actividad
                WHERE p.id_materia = ?";

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute([$idMateria]);

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Asignar las ponderaciones (tipos de actividad) de una materia.
     *
     * Reemplaza las ponderaciones existentes solo si el arreglo no está vacío.
     *
     * @param int $idMateria Identificador de la materia.
     * @param array<int,mixed> $tipos Arreglo de ids o de arrays con ['id'|'id_tipo'|'id_tipo_actividad','porcentaje'].
     *
     * @return bool Verdadero si la operación se considera exitosa.
     */
    public function setPonderaciones(int $idMateria, array $tipos): bool {
        if (empty($tipos)) {
            // No eliminar ponderaciones existentes para evitar borrados accidentales.
            return true;
        }

        $sqlEliminar = "DELETE FROM PONDERACION WHERE id_materia = ?";
        $sentenciaEliminar = $this->pdo->prepare($sqlEliminar);
        $sentenciaEliminar->execute([$idMateria]);

        $sqlInsertar = "INSERT INTO PONDERACION (id_materia, id_tipo_actividad, porcentaje)
                        VALUES (?, ?, ?)";

        $sentenciaInsertar = $this->pdo->prepare($sqlInsertar);

        foreach ($tipos as $tipo) {
            $idTipo = null;
            $porcentaje = 0.0;

            if (is_array($tipo)) {
                $idTipo = $tipo['id'] ?? $tipo['id_tipo'] ?? $tipo['id_tipo_actividad'] ?? null;
                $porcentaje = isset($tipo['porcentaje']) ? (float) $tipo['porcentaje'] : 0.0;
            } else {
                $idTipo = (int) $tipo;
            }

            $idTipo = (int) $idTipo;

            if ($idTipo <= 0) {
                continue;
            }

            $sentenciaInsertar->execute([
                $idMateria,
                $idTipo,
                $porcentaje,
            ]);
        }

        return true;
    }

    /**
     * Validar que la calificación mínima esté dentro del rango permitido.
     *
     * @param float $calificacionMinima Calificación a validar.
     *
     * @throws MateriaException Si está fuera del rango permitido.
     *
     * @return void
     */
    private function validarCalificacionMinima(float $calificacionMinima): void {
        if (
            $calificacionMinima < self::CALIFICACION_MINIMA_LIMITE_INFERIOR
            || $calificacionMinima > self::CALIFICACION_MINIMA_LIMITE_SUPERIOR
        ) {
            throw new MateriaException('La calificación mínima debe estar entre 0 y 100.');
        }
    }
}
