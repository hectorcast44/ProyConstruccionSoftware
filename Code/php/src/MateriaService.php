<?php
/**
 * Clase MateriaService
 * Maneja la lógica de negocio para las materias del usuario
 */

class MateriaService {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Crear una nueva materia
     * @param int $id_usuario ID del usuario
     * @param string $nombre_materia Nombre de la materia
     * @param int $calif_minima Calificación mínima (default: 70)
     * @return int ID de la materia creada
     */
    public function crear($id_usuario, $nombre_materia, $calif_minima = 70) {
        // Validar que no exista una materia con el mismo nombre para este usuario
        if ($this->existeMateria($id_usuario, $nombre_materia)) {
            throw new Exception('Ya existe una materia con ese nombre.');
        }

        // Validar calificación mínima (debe estar entre 0 y 100)
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

    /**
     * Actualizar una materia existente
     * @param int $id_materia ID de la materia
     * @param int $id_usuario ID del usuario (para verificar propiedad)
     * @param string $nombre_materia Nuevo nombre
     * @param int $calif_minima Nueva calificación mínima
     * @return bool True si se actualizó correctamente
     */
    public function actualizar($id_materia, $id_usuario, $nombre_materia, $calif_minima) {
        // Verificar que la materia pertenece al usuario
        if (!$this->verificarPropiedad($id_materia, $id_usuario)) {
            throw new Exception('No tiene permisos para modificar esta materia.');
        }

        // Validar calificación mínima
        if ($calif_minima < 0 || $calif_minima > 100) {
            throw new Exception('La calificación mínima debe estar entre 0 y 100.');
        }

        // Verificar que no exista otra materia con el mismo nombre
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

    /**
     * Actualizar las estadísticas de puntos y calificación de una materia
     * @param int $id_materia ID de la materia
     * @param float $calificacion_actual Calificación actual
     * @param float $puntos_ganados Puntos ganados
     * @param float $puntos_perdidos Puntos perdidos
     * @param float $puntos_pendientes Puntos pendientes
     * @return bool True si se actualizó correctamente
     */
    public function actualizarEstadisticas($id_materia, $calificacion_actual, $puntos_ganados, $puntos_perdidos, $puntos_pendientes) {
        $sql = "UPDATE MATERIA 
                SET calificacion_actual = ?, 
                    puntos_ganados = ?, 
                    puntos_perdidos = ?, 
                    puntos_pendientes = ? 
                WHERE id_materia = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $calificacion_actual, 
            $puntos_ganados, 
            $puntos_perdidos, 
            $puntos_pendientes, 
            $id_materia
        ]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Eliminar una materia
     * @param int $id_materia ID de la materia
     * @param int $id_usuario ID del usuario (para verificar propiedad)
     * @return bool True si se eliminó correctamente
     */
    public function eliminar($id_materia, $id_usuario) {
        // Verificar que la materia pertenece al usuario
        if (!$this->verificarPropiedad($id_materia, $id_usuario)) {
            throw new Exception('No tiene permisos para eliminar esta materia.');
        }

        // Verificar que no tenga actividades asociadas
        if ($this->tieneActividades($id_materia)) {
            throw new Exception('No se puede eliminar una materia que tiene actividades asociadas.');
        }

        $sql = "DELETE FROM MATERIA WHERE id_materia = ? AND id_usuario = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_materia, $id_usuario]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Obtener todas las materias de un usuario
     * @param int $id_usuario ID del usuario
     * @return array Lista de materias con todas sus estadísticas
     */
    public function obtenerPorUsuario($id_usuario) {
        $sql = "SELECT 
                    id_materia,
                    id_usuario,
                    nombre_materia,
                    calif_minima,
                    calificacion_actual,
                    puntos_ganados,
                    puntos_perdidos,
                    puntos_pendientes
                FROM MATERIA 
                WHERE id_usuario = ? 
                ORDER BY nombre_materia";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_usuario]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener una materia específica
     * @param int $id_materia ID de la materia
     * @param int $id_usuario ID del usuario
     * @return array|false Datos de la materia o false si no existe
     */
    public function obtenerPorId($id_materia, $id_usuario) {
        $sql = "SELECT 
                    id_materia,
                    id_usuario,
                    nombre_materia,
                    calif_minima,
                    calificacion_actual,
                    puntos_ganados,
                    puntos_perdidos,
                    puntos_pendientes
                FROM MATERIA 
                WHERE id_materia = ? AND id_usuario = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_materia, $id_usuario]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Verificar si existe una materia con el mismo nombre para el usuario
     * @param int $id_usuario ID del usuario
     * @param string $nombre_materia Nombre a verificar
     * @return bool True si existe
     */
    private function existeMateria($id_usuario, $nombre_materia) {
        $sql = "SELECT COUNT(*) FROM MATERIA 
                WHERE id_usuario = ? AND nombre_materia = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_usuario, $nombre_materia]);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Verificar si existe otra materia con el mismo nombre (excepto la actual)
     * @param int $id_usuario ID del usuario
     * @param string $nombre_materia Nombre a verificar
     * @param int $id_materia_actual ID de la materia que se está editando
     * @return bool True si existe otra materia con ese nombre
     */
    private function existeMateriaExcepto($id_usuario, $nombre_materia, $id_materia_actual) {
        $sql = "SELECT COUNT(*) FROM MATERIA 
                WHERE id_usuario = ? AND nombre_materia = ? AND id_materia != ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_usuario, $nombre_materia, $id_materia_actual]);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Verificar que la materia pertenece al usuario
     * @param int $id_materia ID de la materia
     * @param int $id_usuario ID del usuario
     * @return bool True si pertenece al usuario
     */
    private function verificarPropiedad($id_materia, $id_usuario) {
        $sql = "SELECT COUNT(*) FROM MATERIA 
                WHERE id_materia = ? AND id_usuario = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_materia, $id_usuario]);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Verificar si la materia tiene actividades asociadas
     * @param int $id_materia ID de la materia
     * @return bool True si tiene actividades
     */
    private function tieneActividades($id_materia) {
        $sql = "SELECT COUNT(*) FROM ACTIVIDAD WHERE id_materia = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_materia]);
        
        return $stmt->fetchColumn() > 0;
    }
}
?>
