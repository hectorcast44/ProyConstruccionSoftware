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
        // Ahora soportamos eliminación forzada de tipo junto con actividades y ponderaciones relacionadas
        // $id_tipo: id del tipo
        // $id_usuario: usuario que solicita la eliminación (se verifica propiedad)
        // $force: si true, eliminar actividades y ponderaciones relacionadas antes de borrar el tipo
        $force = false;
        if (func_num_args() >= 3) {
            $force = (bool) func_get_arg(2);
        }

        // 1. Verificar propiedad
        if (!$this->esPropietario($id_tipo, $id_usuario)) {
            throw new Exception("No tienes permiso para eliminar este tipo de actividad o es un tipo predeterminado.");
        }

        // 2. Contar referencias (solo del usuario actual)
        $sqlRefActividad = "SELECT COUNT(*) FROM ACTIVIDAD WHERE id_tipo_actividad = ? AND id_usuario = ?";
        $stmt = $this->pdo->prepare($sqlRefActividad);
        $stmt->execute([$id_tipo, $id_usuario]);
        $countAct = (int) $stmt->fetchColumn();

        $sqlRefPonderacion = "SELECT COUNT(*) FROM PONDERACION p INNER JOIN MATERIA m ON p.id_materia = m.id_materia WHERE p.id_tipo_actividad = ? AND m.id_usuario = ?";
        $stmt = $this->pdo->prepare($sqlRefPonderacion);
        $stmt->execute([$id_tipo, $id_usuario]);
        $countPond = (int) $stmt->fetchColumn();

        if (($countAct > 0 || $countPond > 0) && !$force) {
            throw new Exception("El tipo tiene referencias: actividades={$countAct}, ponderaciones={$countPond}");
        }

        // 3. Si force=true, eliminar dependencias (en transacción)
        try {
            $this->pdo->beginTransaction();

            if ($countAct > 0) {
                $delAct = $this->pdo->prepare("DELETE FROM ACTIVIDAD WHERE id_tipo_actividad = ? AND id_usuario = ?");
                $delAct->execute([$id_tipo, $id_usuario]);
            }

            if ($countPond > 0) {
                $delP = $this->pdo->prepare("DELETE p FROM PONDERACION p INNER JOIN MATERIA m ON p.id_materia = m.id_materia WHERE p.id_tipo_actividad = ? AND m.id_usuario = ?");
                $delP->execute([$id_tipo, $id_usuario]);
            }

            $sql = "DELETE FROM TIPO_ACTIVIDAD WHERE id_tipo_actividad = ?";
            $stmtDel = $this->pdo->prepare($sql);
            $stmtDel->execute([$id_tipo]);

            $this->pdo->commit();
            return ['deleted_activities' => $countAct, 'deleted_ponderaciones' => $countPond];
        } catch (\Exception $e) {
            try { $this->pdo->rollBack(); } catch (\Exception $__) {}
            throw $e;
        }
    }

    /**
     * Devuelve conteos de referencias a un tipo (actividades y ponderaciones)
     */
    public function contarReferencias($id_tipo, $id_usuario)
    {
        $sqlRefActividad = "SELECT COUNT(*) FROM ACTIVIDAD WHERE id_tipo_actividad = ? AND id_usuario = ?";
        $stmt = $this->pdo->prepare($sqlRefActividad);
        $stmt->execute([$id_tipo, $id_usuario]);
        $countAct = (int) $stmt->fetchColumn();

        $sqlRefPonderacion = "SELECT COUNT(*) FROM PONDERACION p INNER JOIN MATERIA m ON p.id_materia = m.id_materia WHERE p.id_tipo_actividad = ? AND m.id_usuario = ?";
        $stmt = $this->pdo->prepare($sqlRefPonderacion);
        $stmt->execute([$id_tipo, $id_usuario]);
        $countPond = (int) $stmt->fetchColumn();

        return ['actividades' => $countAct, 'ponderaciones' => $countPond];
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
