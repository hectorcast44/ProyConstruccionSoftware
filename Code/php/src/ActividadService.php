<?php
/**
 * Clase de Servicio para Actividades (Lógica de Negocio)
 */
class ActividadService
{
    private $pdo;

    // Estados válidos para las actividades
    const ESTADOS_VALIDOS = ['pendiente', 'en proceso', 'completado'];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Valida que el estado sea válido.
     *
     * @param string $estado - El estado a validar.
     * @return bool - true si es válido.
     * @throws Exception si el estado no es válido.
     */
    public function validarEstado(string $estado): bool
    {
        $estado_lower = strtolower(trim($estado));
        if (!in_array($estado_lower, self::ESTADOS_VALIDOS)) {
            throw new Exception(
                "Estado inválido. Los estados válidos son: " . implode(', ', self::ESTADOS_VALIDOS) . ".", 
                400
            );
        }
        return true;
    }

    /**
     * Valida transiciones de estado según reglas de negocio.
     *
     * @param string $estado_actual - El estado actual de la actividad.
     * @param string $nuevo_estado - El nuevo estado propuesto.
     * @return bool - true si la transición es válida.
     * @throws Exception si la transición no es válida.
     */
    public function validarTransicionEstado(string $estado_actual, string $nuevo_estado): bool
    {
        $estado_actual = strtolower(trim($estado_actual));
        $nuevo_estado = strtolower(trim($nuevo_estado));

        // Validar que ambos estados sean válidos primero
        $this->validarEstado($estado_actual);
        $this->validarEstado($nuevo_estado);

        // Todas las transiciones de estado son permitidas
        return true;
    }

    /**
     * Obtiene actividades con filtros opcionales.
     *
     * @param int $id_usuario - El ID del usuario.
     * @param array $filtros - Array asociativo con filtros opcionales:
     *                         - id_materia: Filtrar por materia
     *                         - id_tipo_actividad: Filtrar por tipo
     *                         - estado: Filtrar por estado
     *                         - fecha_desde: Filtrar actividades desde esta fecha
     *                         - fecha_hasta: Filtrar actividades hasta esta fecha
     *                         - buscar: Buscar en nombre_actividad
     * @return array - Array de actividades.
     */
    public function obtenerActividades(int $id_usuario, array $filtros = []): array
    {
        $sql = "SELECT a.*, t.nombre_tipo, m.nombre_materia 
                FROM ACTIVIDAD a 
                LEFT JOIN TIPO_ACTIVIDAD t ON a.id_tipo_actividad = t.id_tipo_actividad
                LEFT JOIN MATERIA m ON a.id_materia = m.id_materia
                WHERE a.id_usuario = ?";
        
        $params = [$id_usuario];

        // Aplicar filtros dinámicamente
        if (!empty($filtros['id_materia'])) {
            $sql .= " AND a.id_materia = ?";
            $params[] = (int)$filtros['id_materia'];
        }

        if (!empty($filtros['id_tipo_actividad'])) {
            $sql .= " AND a.id_tipo_actividad = ?";
            $params[] = (int)$filtros['id_tipo_actividad'];
        }

        if (!empty($filtros['estado'])) {
            $sql .= " AND LOWER(a.estado) = LOWER(?)";
            $params[] = $filtros['estado'];
        }

        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND a.fecha_entrega >= ?";
            $params[] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND a.fecha_entrega <= ?";
            $params[] = $filtros['fecha_hasta'];
        }

        if (!empty($filtros['buscar'])) {
            $sql .= " AND a.nombre_actividad LIKE ?";
            $params[] = '%' . $filtros['buscar'] . '%';
        }

        // Ordenar por fecha de entrega
        $sql .= " ORDER BY a.fecha_entrega DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea una nueva actividad en la BD.
     *
     * @param array $datos - Array asociativo con los datos.
     * @return int - El ID de la actividad creada.
     */
    public function crearActividad(array $datos): int
    {
        // Validar el estado
        $this->validarEstado($datos['estado']);

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
            strtolower(trim($datos['estado'])),
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
        // Obtener el estado actual de la actividad
        $stmt_check = $this->pdo->prepare(
            "SELECT estado FROM ACTIVIDAD WHERE id_actividad = ? AND id_usuario = ?"
        );
        $stmt_check->execute([$id_actividad, $datos['id_usuario']]);
        $actividad_actual = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$actividad_actual) {
            throw new Exception("Actividad no encontrada o no pertenece al usuario.", 404);
        }

        // Validar la transición de estado
        $this->validarTransicionEstado($actividad_actual['estado'], $datos['estado']);

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
            strtolower(trim($datos['estado'])),
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