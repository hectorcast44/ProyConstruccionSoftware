<?php
/**
 * Clase de Servicio para Tipos de Actividades (Lógica de Negocio)
 */
class TipoActividadService
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene todos los tipos de actividades del usuario.
     *
     * @param int $id_usuario - El ID del usuario.
     * @return array - Array de tipos de actividades.
     */
    public function obtenerTiposActividad(int $id_usuario): array
    {
        $sql = "SELECT id_tipo_actividad, nombre_tipo 
                FROM TIPO_ACTIVIDAD 
                WHERE id_usuario = ? 
                ORDER BY nombre_tipo ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_usuario]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un tipo de actividad específico por su ID.
     *
     * @param int $id_tipo_actividad - El ID del tipo de actividad.
     * @param int $id_usuario - El ID del usuario (para seguridad).
     * @return array|null - Los datos del tipo de actividad o null si no existe.
     */
    public function obtenerTipoActividadPorId(int $id_tipo_actividad, int $id_usuario): ?array
    {
        $sql = "SELECT id_tipo_actividad, nombre_tipo 
                FROM TIPO_ACTIVIDAD 
                WHERE id_tipo_actividad = ? AND id_usuario = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_tipo_actividad, $id_usuario]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Crea un nuevo tipo de actividad.
     *
     * @param array $datos - Array con los datos (nombre_tipo, id_usuario).
     * @return int - El ID del tipo de actividad creado.
     * @throws Exception si el nombre ya existe.
     */
    public function crearTipoActividad(array $datos): int
    {
        // Verificar que el nombre no exista para este usuario
        if ($this->existeNombreTipo($datos['nombre_tipo'], $datos['id_usuario'])) {
            throw new Exception("Ya existe un tipo de actividad con ese nombre.", 400);
        }

        $sql = "INSERT INTO TIPO_ACTIVIDAD (id_usuario, nombre_tipo) 
                VALUES (?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $datos['id_usuario'],
            $datos['nombre_tipo']
        ]);
        
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Edita un tipo de actividad existente.
     *
     * @param int $id_tipo_actividad - El ID del tipo de actividad a editar.
     * @param array $datos - Array con los nuevos datos.
     * @return bool - true si la actualización fue exitosa.
     * @throws Exception si el tipo no existe o el nombre ya está en uso.
     */
    public function editarTipoActividad(int $id_tipo_actividad, array $datos): bool
    {
        // Verificar que el tipo existe y pertenece al usuario
        $tipoActual = $this->obtenerTipoActividadPorId($id_tipo_actividad, $datos['id_usuario']);
        if (!$tipoActual) {
            throw new Exception("Tipo de actividad no encontrado o no pertenece al usuario.", 404);
        }

        // Verificar que el nuevo nombre no exista (excepto el actual)
        if ($this->existeNombreTipo($datos['nombre_tipo'], $datos['id_usuario'], $id_tipo_actividad)) {
            throw new Exception("Ya existe otro tipo de actividad con ese nombre.", 400);
        }

        $sql = "UPDATE TIPO_ACTIVIDAD 
                SET nombre_tipo = ? 
                WHERE id_tipo_actividad = ? AND id_usuario = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $datos['nombre_tipo'],
            $id_tipo_actividad,
            $datos['id_usuario']
        ]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Elimina un tipo de actividad.
     *
     * @param int $id_tipo_actividad - El ID del tipo de actividad a eliminar.
     * @param int $id_usuario - El ID del usuario (para seguridad).
     * @return bool - true si la eliminación fue exitosa.
     * @throws Exception si el tipo está siendo usado en actividades o ponderaciones.
     */
    public function eliminarTipoActividad(int $id_tipo_actividad, int $id_usuario): bool
    {
        // Verificar que el tipo existe
        $tipo = $this->obtenerTipoActividadPorId($id_tipo_actividad, $id_usuario);
        if (!$tipo) {
            throw new Exception("Tipo de actividad no encontrado o no pertenece al usuario.", 404);
        }

        // Verificar si está siendo usado en actividades
        $stmt_actividades = $this->pdo->prepare(
            "SELECT COUNT(*) as total FROM ACTIVIDAD 
             WHERE id_tipo_actividad = ? AND id_usuario = ?"
        );
        $stmt_actividades->execute([$id_tipo_actividad, $id_usuario]);
        $result_actividades = $stmt_actividades->fetch(PDO::FETCH_ASSOC);

        if ($result_actividades['total'] > 0) {
            throw new Exception(
                "No se puede eliminar este tipo de actividad porque está siendo usado en " . 
                $result_actividades['total'] . " actividad(es).", 
                400
            );
        }

        // Verificar si está siendo usado en ponderaciones
        $stmt_ponderaciones = $this->pdo->prepare(
            "SELECT COUNT(*) as total FROM PONDERACION 
             WHERE id_tipo_actividad = ?"
        );
        $stmt_ponderaciones->execute([$id_tipo_actividad]);
        $result_ponderaciones = $stmt_ponderaciones->fetch(PDO::FETCH_ASSOC);

        if ($result_ponderaciones['total'] > 0) {
            throw new Exception(
                "No se puede eliminar este tipo de actividad porque está siendo usado en " . 
                $result_ponderaciones['total'] . " ponderación(es).", 
                400
            );
        }

        // Proceder con la eliminación
        $sql = "DELETE FROM TIPO_ACTIVIDAD 
                WHERE id_tipo_actividad = ? AND id_usuario = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_tipo_actividad, $id_usuario]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Verifica si existe un tipo de actividad con el mismo nombre.
     *
     * @param string $nombre_tipo - El nombre a verificar.
     * @param int $id_usuario - El ID del usuario.
     * @param int|null $excluir_id - ID a excluir de la búsqueda (para edición).
     * @return bool - true si existe un nombre duplicado.
     */
    private function existeNombreTipo(string $nombre_tipo, int $id_usuario, ?int $excluir_id = null): bool
    {
        if ($excluir_id === null) {
            $sql = "SELECT COUNT(*) as total FROM TIPO_ACTIVIDAD 
                    WHERE nombre_tipo = ? AND id_usuario = ?";
            $params = [$nombre_tipo, $id_usuario];
        } else {
            $sql = "SELECT COUNT(*) as total FROM TIPO_ACTIVIDAD 
                    WHERE nombre_tipo = ? AND id_usuario = ? AND id_tipo_actividad != ?";
            $params = [$nombre_tipo, $id_usuario, $excluir_id];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['total'] > 0;
    }
}
?>
