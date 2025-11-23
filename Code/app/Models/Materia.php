<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

class Materia
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function crear($id_usuario, $nombre_materia, $calif_minima = 70)
    {
        if ($this->existeMateria($id_usuario, $nombre_materia)) {
            throw new Exception('Ya existe una materia con ese nombre.');
        }

        if ($calif_minima < 0 || $calif_minima > 100) {
            throw new Exception('La calificación mínima debe estar entre 0 y 100.');
        }

        $sql = "INSERT INTO MATERIA (
                    id_usuario,
                    nombre_materia,
                    calif_minima,
                    calificacion_actual,
                    puntos_ganados,
                    puntos_perdidos,
                    puntos_pendientes
                ) VALUES (?, ?, ?, 0.00, 0.00, 0.00, 0.00)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_usuario, $nombre_materia, $calif_minima]);

        return $this->pdo->lastInsertId();
    }

    public function actualizar($id_materia, $id_usuario, $nombre_materia, $calif_minima)
    {
        if (!$this->verificarPropiedad($id_materia, $id_usuario)) {
            throw new Exception('No tiene permisos para modificar esta materia.');
        }

        if ($calif_minima < 0 || $calif_minima > 100) {
            throw new Exception('La calificación mínima debe estar entre 0 y 100.');
        }

        if ($this->existeMateriaExcepto($id_usuario, $nombre_materia, $id_materia)) {
            throw new Exception('Ya existe otra materia con ese nombre.');
        }

        $sql = "UPDATE MATERIA
                SET nombre_materia = ?, calif_minima = ?
                WHERE id_materia = ? AND id_usuario = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$nombre_materia, $calif_minima, $id_materia, $id_usuario]);

        return $stmt->rowCount() > 0;
    }

    public function eliminar($id_materia, $id_usuario)
    {
        if (!$this->verificarPropiedad($id_materia, $id_usuario)) {
            throw new Exception('No tiene permisos para eliminar esta materia.');
        }

        if ($this->tieneActividades($id_materia)) {
            throw new Exception('No se puede eliminar una materia que tiene actividades asociadas.');
        }

        $sql = "DELETE FROM MATERIA WHERE id_materia = ? AND id_usuario = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_materia, $id_usuario]);

        return $stmt->rowCount() > 0;
    }

    public function obtenerPorUsuario($id_usuario)
    {
        $sql = "SELECT * FROM MATERIA WHERE id_usuario = ? ORDER BY nombre_materia";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id_materia, $id_usuario)
    {
        $sql = "SELECT * FROM MATERIA WHERE id_materia = ? AND id_usuario = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_materia, $id_usuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function existeMateria($id_usuario, $nombre_materia)
    {
        $sql = "SELECT COUNT(*) FROM MATERIA WHERE id_usuario = ? AND nombre_materia = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_usuario, $nombre_materia]);
        return $stmt->fetchColumn() > 0;
    }

    private function existeMateriaExcepto($id_usuario, $nombre_materia, $id_materia_actual)
    {
        $sql = "SELECT COUNT(*) FROM MATERIA WHERE id_usuario = ? AND nombre_materia = ? AND id_materia != ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_usuario, $nombre_materia, $id_materia_actual]);
        return $stmt->fetchColumn() > 0;
    }

    private function verificarPropiedad($id_materia, $id_usuario)
    {
        $sql = "SELECT COUNT(*) FROM MATERIA WHERE id_materia = ? AND id_usuario = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_materia, $id_usuario]);
        return $stmt->fetchColumn() > 0;
    }

    private function tieneActividades($id_materia)
    {
        $sql = "SELECT COUNT(*) FROM ACTIVIDAD WHERE id_materia = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_materia]);
        return $stmt->fetchColumn() > 0;
    }
    public function obtenerResumenActividades($id_usuario)
    {
        $sql = "SELECT
                    a.id_materia,
                    ta.nombre_tipo,
                    SUM(a.puntos_obtenidos) AS puntos_obtenidos,
                    SUM(a.puntos_posibles)  AS puntos_posibles
                FROM ACTIVIDAD a
                INNER JOIN TIPO_ACTIVIDAD ta
                    ON ta.id_tipo_actividad = a.id_tipo_actividad
                WHERE
                    a.id_usuario = ?
                GROUP BY
                    a.id_materia,
                    ta.nombre_tipo
                ORDER BY
                    a.id_materia,
                    ta.nombre_tipo";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
