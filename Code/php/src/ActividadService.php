<?php
/**
 * API Endpoint: Detalle de calificaciones por materia.
 *
 * Responsabilidad:
 *  - Obtener la información de la materia y su progreso usando CalculadoraService.
 *  - Obtener las actividades mediante ActividadService.
 *  - Entregar al frontend la data ya organizada.
 */

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

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    enviarRespuesta(405, [
        'status' => 'error',
        'message' => 'Método no permitido. Use GET.'
    ]);
}

// Usuario actual desde helper
$idUsuario = obtenerIdUsuarioActual();

// Leer id_materia desde ?id_materia= o ?id=
$idMateria = 0;

if (isset($_GET['id_materia'])) {
    $idMateria = (int) $_GET['id_materia'];
} elseif (isset($_GET['id'])) {
    $idMateria = (int) $_GET['id'];
}

if ($idMateria <= 0) {
    enviarRespuesta(400, [
        'status' => 'error',
        'message' => 'El parámetro "id_materia" es obligatorio y debe ser numérico.'
    ]);
}

try {

    // Servicios
    $calculadora = new CalculadoraService($pdo);
    $actividadService = new ActividadService($pdo);

    //Obtener materia + progreso (se recalcula internamente)
    $resultadoMateria = $calculadora->obtenerMateriaConProgreso($idMateria, $idUsuario);
    $filaMateria = $resultadoMateria['materia'];
    $progreso = $resultadoMateria['progreso'];

    //Obtener actividades con sus tipos usando ActividadService
    $filasActividades = $actividadService->obtenerPorMateria($idMateria, $idUsuario);
    $secciones = [];

    foreach ($filasActividades as $actividad) {
        $idTipoActividad = (int) $actividad['id_tipo_actividad'];

        if (!isset($secciones[$idTipoActividad])) {
            $secciones[$idTipoActividad] = [
                'id_tipo' => $idTipoActividad,
                'nombre_tipo' => $actividad['nombre_tipo'],
                'actividades' => []
            ];
        }

        $secciones[$idTipoActividad]['actividades'][] = [
            'id_actividad' => (int) $actividad['id_actividad'],
            'nombre' => $actividad['nombre_actividad'],
            'fecha_entrega' => $actividad['fecha_entrega'],
            'estado' => $actividad['estado'],
            'obtenido'  => $actividad['puntos_obtenidos'] !== null
                ? (float) $actividad['puntos_obtenidos']: null,
            'maximo' => $actividad['puntos_posibles'] !== null
                ? (float) $actividad['puntos_posibles']: 0.0,
        ];

    }

    enviarRespuesta(200, [
        'status' => 'success',
        'data' => [
            'materia' => $filaMateria,
            'progreso' => $progreso,
            'secciones' => array_values($secciones)
        ]
    ]);

} catch (Exception $e) {

    error_log('Error en calificaciones_detalle.php: ' . $e->getMessage());

    $codigo = ($e->getCode() >= 400 && $e->getCode() < 600)
        ? $e->getCode()
        : 500;

    enviarRespuesta($codigo, [
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
