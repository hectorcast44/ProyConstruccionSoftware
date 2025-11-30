<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

class TipoActividad
{
    private $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance()->getConnection();
    }

    public function obtenerTodos($id_usuario)
    {
        // Obtener tipos del usuario Y tipos predeterminados (usuario 1)
        // Asumimos que el usuario 1 es el administrador/sistema
        // Agregamos flag es_propio para saber si el usuario puede editar/eliminar
        $sql = "SELECT *, (id_usuario = ?) as es_propio FROM TIPO_ACTIVIDAD 
                WHERE id_usuario = ? OR id_usuario = 1 
                ORDER BY nombre_tipo ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_usuario, $id_usuario]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id_tipo, $id_usuario)
    {
        $sql = "SELECT * FROM TIPO_ACTIVIDAD WHERE id_tipo_actividad = ? AND (id_usuario = ? OR id_usuario = 1)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_tipo, $id_usuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crear($nombre, $id_usuario)
    {
        // Verificar si ya existe un tipo con ese nombre para el usuario o predeterminado
        if ($this->existeNombre($nombre, $id_usuario)) {
            throw new Exception("El tipo de actividad '$nombre' ya existe.");
        }

        $sql = "INSERT INTO TIPO_ACTIVIDAD (id_usuario, nombre_tipo) VALUES (?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_usuario, $nombre]);

        return $this->pdo->lastInsertId();
    }

    public function actualizar($id_tipo, $nombre, $id_usuario)
    {
        // Verificar que el tipo pertenezca al usuario (no sea predeterminado)
        if (!$this->esPropietario($id_tipo, $id_usuario)) {
            throw new Exception("No tienes permiso para editar este tipo de actividad o es un tipo predeterminado.");
        }

        // Verificar duplicados
        if ($this->existeNombre($nombre, $id_usuario, $id_tipo)) {
            throw new Exception("El tipo de actividad '$nombre' ya existe.");
        }

        $sql = "UPDATE TIPO_ACTIVIDAD SET nombre_tipo = ? WHERE id_tipo_actividad = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$nombre, $id_tipo]);

        return $stmt->rowCount() > 0;
    }

    public function eliminar($id_tipo, $id_usuario)
    {
        // RF-016: Eliminar tipos sin referencias
        // RF-017: Mensaje al eliminar tipo con referencias

        // 1. Verificar propiedad
        if (!$this->esPropietario($id_tipo, $id_usuario)) {
            throw new Exception("No tienes permiso para eliminar este tipo de actividad o es un tipo predeterminado.");
        }

        // 2. Verificar referencias en ACTIVIDAD
        $sqlRefActividad = "SELECT COUNT(*) FROM ACTIVIDAD WHERE id_tipo_actividad = ?";
        $stmt = $this->pdo->prepare($sqlRefActividad);
        $stmt->execute([$id_tipo]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("No se puede eliminar el tipo porque hay actividades asociadas a él.");
        }

        // 3. Verificar referencias en PONDERACION
        $sqlRefPonderacion = "SELECT COUNT(*) FROM PONDERACION WHERE id_tipo_actividad = ?";
        $stmt = $this->pdo->prepare($sqlRefPonderacion);
        $stmt->execute([$id_tipo]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("No se puede eliminar el tipo porque está siendo utilizado en ponderaciones de materias.");
        }

        // 4. Eliminar
        $sql = "DELETE FROM TIPO_ACTIVIDAD WHERE id_tipo_actividad = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_tipo]);

        return true;
    }

    private function esPropietario($id_tipo, $id_usuario)
    {
        $sql = "SELECT id_usuario FROM TIPO_ACTIVIDAD WHERE id_tipo_actividad = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_tipo]);
        $owner = $stmt->fetchColumn();

        return $owner == $id_usuario;
    }

    private function existeNombre($nombre, $id_usuario, $excluirId = null)
    {
        $sql = "SELECT COUNT(*) FROM TIPO_ACTIVIDAD 
                WHERE nombre_tipo = ? AND (id_usuario = ? OR id_usuario = 1)";

        $params = [$nombre, $id_usuario];

        if ($excluirId) {
            $sql .= " AND id_tipo_actividad != ?";
            $params[] = $excluirId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() > 0;
    }
}
