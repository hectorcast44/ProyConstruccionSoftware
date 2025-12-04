<?php

namespace App\Models;

use App\Core\Database;
use App\Exceptions\ActividadException;
use PDO;

/**
 * Modelo de ACTIVIDAD.
 *
 * Gestionar:
 *  - Crear actividades.
 *  - Actualizar actividades.
 *  - Eliminar actividades.
 *  - Consultar actividades por materia o por identificador.
 */
class Actividad
{
    /**
     * Conexión PDO hacia la base de datos.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Construir el modelo de Actividad con una conexión PDO.
     *
     * Si no se proporciona una conexión, obtenerla desde Database::getInstance().
     *
     * @param PDO|null $pdo Conexión PDO opcional a reutilizar.
     *
     * @return void
     */
    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance()->getConnection();
    }

    /**
     * Crear una nueva actividad en la base de datos.
     *
     * Esperar en $datos las claves:
     *  - id_materia
     *  - id_tipo_actividad
     *  - id_usuario
     *  - nombre_actividad
     *  - fecha_entrega
     *  - estado
     *  - puntos_posibles
     *  - puntos_obtenidos
     *
     * @param array<string,mixed> $datos Datos de la actividad a crear.
     *
     * @return int Identificador de la nueva actividad.
     */
    public function crear(array $datos): int
    {
        $sql = "INSERT INTO ACTIVIDAD (
                    id_materia, id_tipo_actividad, id_usuario,
                    nombre_actividad, fecha_entrega, estado,
                    puntos_posibles, puntos_obtenidos
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute([
            $datos['id_materia'],
            $datos['id_tipo_actividad'],
            $datos['id_usuario'],
            $datos['nombre_actividad'],
            $datos['fecha_entrega'],
            $datos['estado'],
            $datos['puntos_posibles'],
            $datos['puntos_obtenidos']
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualizar una actividad existente.
     *
     * Solo actualizar actividades que pertenezcan al usuario indicado.
     *
     * Esperar en $datos las claves:
     *  - id_materia
     *  - id_tipo_actividad
     *  - nombre_actividad
     *  - fecha_entrega
     *  - estado
     *  - puntos_posibles
     *  - puntos_obtenidos
     *  - id_usuario
     *
     * @param int $id_actividad Identificador de la actividad a actualizar.
     * @param array<string,mixed> $datos Datos nuevos de la actividad.
     *
     * @return bool Verdadero si se actualizó al menos un registro, falso en otro caso.
     */
    public function actualizar(int $id_actividad, array $datos): bool
    {
        $sql = "UPDATE ACTIVIDAD SET
                    id_materia = ?,
                    id_tipo_actividad = ?,
                    nombre_actividad = ?,
                    fecha_entrega = ?,
                    estado = ?,
                    puntos_posibles = ?,
                    puntos_obtenidos = ?
                WHERE id_actividad = ? AND id_usuario = ?";

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute([
            $datos['id_materia'],
            $datos['id_tipo_actividad'],
            $datos['nombre_actividad'],
            $datos['fecha_entrega'],
            $datos['estado'],
            $datos['puntos_posibles'],
            $datos['puntos_obtenidos'],
            $id_actividad,
            $datos['id_usuario']
        ]);

        return $sentencia->rowCount() > 0;
    }

    /**
     * Eliminar una actividad.
     *
     * Verificar primero que la actividad exista y pertenezca al usuario.
     * @param int $id_actividad Identificador de la actividad a eliminar.
     * @param int $id_usuario Identificador del usuario propietario.
     *
     * @throws ActividadException Si la actividad no existe.
     *
     * @return int Identificador de la materia a la que pertenecía la actividad.
     */
    public function eliminar(int $id_actividad, int $id_usuario): int
    {
        $sqlVerificar = "SELECT id_materia, puntos_posibles
                         FROM ACTIVIDAD
                         WHERE id_actividad = ? AND id_usuario = ?";

        $sentenciaVerificar = $this->pdo->prepare($sqlVerificar);
        $sentenciaVerificar->execute([$id_actividad, $id_usuario]);
        $actividad = $sentenciaVerificar->fetch(PDO::FETCH_ASSOC);

        if (!$actividad) {
            throw new ActividadException('Actividad no encontrada.');
        }

        $sqlEliminar = "DELETE FROM ACTIVIDAD WHERE id_actividad = ? AND id_usuario = ?";
        $sentenciaEliminar = $this->pdo->prepare($sqlEliminar);
        $sentenciaEliminar->execute([$id_actividad, $id_usuario]);

        // Devolver id_materia para permitir recálculo de calificaciones si es necesario.
        return (int) $actividad['id_materia'];
    }

    /**
     * Obtener todas las actividades de una materia para un usuario.
     *
     * @param int $id_materia Identificador de la materia.
     * @param int $id_usuario Identificador del usuario propietario.
     *
     * @return array<int,array<string,mixed>> Lista de actividades encontradas.
     */
    public function obtenerPorMateria(int $id_materia, int $id_usuario): array
    {
        $sql = "SELECT
                    a.*,
                    t.nombre_tipo
                FROM ACTIVIDAD a
                JOIN TIPO_ACTIVIDAD t ON a.id_tipo_actividad = t.id_tipo_actividad
                WHERE a.id_materia = ? AND a.id_usuario = ?
                ORDER BY t.nombre_tipo, a.fecha_entrega, a.nombre_actividad ASC";

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute([$id_materia, $id_usuario]);

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener una actividad por su identificador y usuario.
     *
     * @param int $id_actividad Identificador de la actividad.
     * @param int $id_usuario Identificador del usuario propietario.
     *
     * @return array<string,mixed>|null Datos de la actividad o null si no se encuentra.
     */
    public function obtenerPorId(int $id_actividad, int $id_usuario): ?array
    {
        $sql = "SELECT * FROM ACTIVIDAD WHERE id_actividad = ? AND id_usuario = ?";

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute([$id_actividad, $id_usuario]);

        $resultado = $sentencia->fetch(PDO::FETCH_ASSOC);

        if ($resultado === false) {
            return null;
        }

        return $resultado;
    }
}
