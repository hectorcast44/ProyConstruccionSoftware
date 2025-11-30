<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

class Actividad
{
    private $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance()->getConnection();
    }

    public function crear(array $datos)
    {
        $sql = "INSERT INTO ACTIVIDAD (
                    id_materia, id_tipo_actividad, id_usuario,
                    nombre_actividad, fecha_entrega, estado,
                    puntos_posibles, puntos_obtenidos
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $datos['id_materia'],
            $datos['id_tipo_actividad'],
            $datos['id_usuario'],
            $datos['nombre_actividad'],
            $datos['fecha_entrega'],
            $datos['estado'],
            $datos['puntos_posibles'],
            $datos['puntos_obtenidos']
        ]);

        return $this->pdo->lastInsertId();
    }

    public function actualizar($id_actividad, array $datos)
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

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
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

        return $stmt->rowCount() > 0;
    }

    public function eliminar($id_actividad, $id_usuario)
    {
        // Verificar si es calificable (RF-003)
        $sqlCheck = "SELECT id_materia, puntos_posibles FROM ACTIVIDAD WHERE id_actividad = ? AND id_usuario = ?";
        $stmtCheck = $this->pdo->prepare($sqlCheck);
        $stmtCheck->execute([$id_actividad, $id_usuario]);
        $actividad = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$actividad) {
            throw new Exception('Actividad no encontrada.');
        }

        if ($actividad['puntos_posibles'] !== null && $actividad['puntos_posibles'] > 0) {
            throw new Exception('RF-003: No se puede eliminar una actividad calificable (con puntos posibles).', 400);
        }

        $sql = "DELETE FROM ACTIVIDAD WHERE id_actividad = ? AND id_usuario = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_actividad, $id_usuario]);

        return $actividad['id_materia']; // Return id_materia to allow recalculation if needed
    }

    public function obtenerPorMateria($id_materia, $id_usuario)
    {
        $sql = "SELECT 
                    a.*, 
                    t.nombre_tipo 
                FROM ACTIVIDAD a
                JOIN TIPO_ACTIVIDAD t ON a.id_tipo_actividad = t.id_tipo_actividad
                WHERE a.id_materia = ? AND a.id_usuario = ?
                ORDER BY t.nombre_tipo, a.fecha_entrega, a.nombre_actividad ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_materia, $id_usuario]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id_actividad, $id_usuario)
    {
        $sql = "SELECT * FROM ACTIVIDAD WHERE id_actividad = ? AND id_usuario = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_actividad, $id_usuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
