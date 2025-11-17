<?php
/**
 * Clase de Servicio para Actividades (Lógica de Negocio)
 */
class ActividadService
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Crea una nueva actividad en la BD.
     *
     * @param array $datos - Array asociativo con los datos.
     * @return int - El ID de la actividad creada.
     */
    public function crearActividad(array $datos): int
    {
        $sql = "INSERT INTO ACTIVIDAD 
                  (id_materia, id_tipo_actividad, id_usuario, nombre_actividad, 
                   fecha_entrega, estado, puntos_posibles, puntos_obtenidos) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $datos['id_materia'],
            $datos['id_tipo_actividad'],
            $datos['id_usuario'], // Seguridad
            $datos['nombre_actividad'],
            $datos['fecha_entrega'],
            $datos['estado'],
            $datos['puntos_posibles'],
            $datos['puntos_obtenidos']
        ]);
        
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Edita una actividad existente en la BD.
     *
     * @param int $id_actividad - El ID de la actividad a editar.
     * @param array $datos - Array asociativo con los nuevos datos.
     * @return bool - true si la actualización fue exitosa.
     */
    public function editarActividad(int $id_actividad, array $datos): bool
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
            $datos['id_usuario'] // Seguridad
        ]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Elimina una actividad, si cumple con las reglas de negocio (RF-003).
     *
     * @param int $id_actividad - El ID de la actividad a eliminar.
     * @param int $id_usuario - El ID del usuario (por seguridad).
     * @return int - El id_materia de la actividad eliminada (para recálculo).
     * @throws Exception si la actividad es calificable (RF-003) o no se encuentra.
     */
    public function eliminarActividad(int $id_actividad, int $id_usuario): int
    {
        // 1. Verificar la actividad y obtener id_materia (para recálculo)
        $stmt_check = $this->pdo->prepare(
            "SELECT id_materia, puntos_posibles 
             FROM ACTIVIDAD 
             WHERE id_actividad = ? AND id_usuario = ?"
        );
        $stmt_check->execute([$id_actividad, $id_usuario]);
        $actividad = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$actividad) {
            throw new Exception("Actividad no encontrada o no pertenece al usuario.", 404);
        }

        // 2. Aplicar regla de negocio RF-003
        // (Nuestra lógica: "calificable" = puntos_posibles NO es NULL)
        if ($actividad['puntos_posibles'] !== null) {
            throw new Exception("RF-003: No se puede eliminar una actividad que es calificable.", 400);
        }

        // 3. Proceder con la eliminación
        $stmt_delete = $this->pdo->prepare(
            "DELETE FROM ACTIVIDAD 
             WHERE id_actividad = ? AND id_usuario = ?"
        );
        $stmt_delete->execute([$id_actividad, $id_usuario]);

        // 4. Devolver el id_materia para que el controlador pueda recalcular
        return (int)$actividad['id_materia'];
    }
}
?>