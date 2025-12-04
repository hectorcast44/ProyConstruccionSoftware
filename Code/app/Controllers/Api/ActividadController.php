<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\Actividad;
use App\Models\Materia;
use App\Models\Calculadora;
use App\Controllers\AuthController;

/**
 * Controlador para gestionar las actividades de las materias.
 */
class ActividadController extends Controller
{
    private $actividadModel;
    private $materiaModel;
    private $calculadoraModel;

    /**
     * Constructor del controlador.
     * Inicializa los modelos de Actividad, Materia y Calculadora.
     */
    public function __construct()
    {
        $this->actividadModel = new Actividad();
        $this->materiaModel = new Materia();
        $this->calculadoraModel = new Calculadora();
    }

    /**
     * Obtiene la lista de actividades de una materia, agrupadas por tipo.
     * También devuelve el progreso actual de la materia.
     *
     * Requiere el parámetro 'id_materia' o 'id' en la query string.
     *
     * @return void Envía una respuesta JSON con las actividades y el progreso.
     */
    public function index()
    {
        $idUsuario = AuthController::getUserId();
        $idMateria = $_GET['id_materia'] ?? $_GET['id'] ?? 0;

        if ($idMateria <= 0) {
            $this->json(['status' => 'error', 'message' => 'ID Materia requerido'], 400);
            return;
        }

        try {
            // Get Materia progress
            $resultadoMateria = $this->calculadoraModel->obtenerMateriaConProgreso($idMateria, $idUsuario);

            // Get Activities
            $actividades = $this->actividadModel->obtenerPorMateria($idMateria, $idUsuario);

            // Group by type
            $secciones = [];
            foreach ($actividades as $act) {
                $idTipo = $act['id_tipo_actividad'];
                if (!isset($secciones[$idTipo])) {
                    $secciones[$idTipo] = [
                        'id_tipo' => $idTipo,
                        'nombre_tipo' => $act['nombre_tipo'],
                        'actividades' => []
                    ];
                }
                $secciones[$idTipo]['actividades'][] = [
                    'id_actividad' => (int) $act['id_actividad'],
                    'nombre' => $act['nombre_actividad'],
                    'fecha_entrega' => $act['fecha_entrega'],
                    'estado' => $act['estado'],
                    'obtenido' => $act['puntos_obtenidos'] !== null ? (float) $act['puntos_obtenidos'] : null,
                    'maximo' => $act['puntos_posibles'] !== null ? (float) $act['puntos_posibles'] : 0.0
                ];
            }

            $response = [
                'status' => 'success',
                'data' => [
                    'materia' => $resultadoMateria['materia'],
                    'progreso' => $resultadoMateria['progreso'],
                    'secciones' => array_values($secciones)
                ]
            ];

            // si la calculadora devolvió debug, exponerlo dentro de data.debug
            if (isset($resultadoMateria['debug'])) {
                $response['data']['debug'] = $resultadoMateria['debug'];
            }

            // Modo debug: si se pide explícitamente, agregar información útil para depuración
            if (isset($_GET['__dbg']) && $_GET['__dbg']) {
                $response['debug'] = [
                    'id_usuario' => $idUsuario,
                    'id_materia' => $idMateria,
                    'actividades_encontradas' => count($actividades)
                ];
            }

            $this->json($response);

        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Crear o actualizar una actividad.
     *
     * Payload esperado (JSON):
     * - id_actividad (opcional): Si se envía, se actualiza la actividad.
     * - id_materia (required): ID de la materia.
     * - id_tipo_actividad (required): ID del tipo de actividad.
     * - nombre_actividad (required): Nombre de la actividad.
     * - puntos_posibles (opcional): Puntos máximos de la actividad.
     * - puntos_obtenidos (opcional): Puntos obtenidos por el alumno.
     *
     * Validaciones:
     * - Puntos obtenidos <= Puntos posibles.
     * - Puntos posibles <= Puntos restantes disponibles para ese tipo en la materia.
     *
     * @return void JSON response.
     */
    public function store()
    {
        try {
            $idUsuario = AuthController::getUserId();
            $data = json_decode(file_get_contents('php://input'), true);

            $this->validateStoreInput($data);

            // Preparar datos
            $puntosPosibles = isset($data['puntos_posibles']) && $data['puntos_posibles'] !== '' ? (float) $data['puntos_posibles'] : 0.0;
            $puntosObtenidos = isset($data['puntos_obtenidos']) && $data['puntos_obtenidos'] !== '' ? (float) $data['puntos_obtenidos'] : null;

            $this->validateStoreLogic($data, $puntosPosibles, $puntosObtenidos);

            $actividadData = [
                'id_materia' => $data['id_materia'],
                'id_tipo_actividad' => $data['id_tipo_actividad'],
                'id_usuario' => $idUsuario,
                'nombre_actividad' => trim($data['nombre_actividad']),
                'fecha_entrega' => $data['fecha_entrega'] ?? date('Y-m-d'),
                'estado' => $data['estado'] ?? 'pendiente',
                'puntos_posibles' => $puntosPosibles > 0 ? $puntosPosibles : null,
                'puntos_obtenidos' => $puntosObtenidos
            ];

            $message = $this->saveActividad($data, $actividadData, $idUsuario);

            $resp = ['status' => 'success', 'message' => $message];
            if (isset($_GET['__dbg']) && $_GET['__dbg']) {
                $resp['debug'] = [
                    'id_usuario' => $idUsuario,
                    'payload' => $actividadData
                ];
            }
            $this->json($resp);

        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Valida que los campos obligatorios estén presentes en el payload.
     *
     * @param array $data Datos de la petición.
     * @throws \Exception Si faltan campos obligatorios.
     */
    private function validateStoreInput($data)
    {
        if (empty($data['id_materia']) || empty($data['id_tipo_actividad']) || empty($data['nombre_actividad'])) {
            throw new \Exception('Faltan campos obligatorios');
        }
    }

    /**
     * Valida la lógica de negocio para crear/actualizar una actividad.
     *
     * Verifica que los puntos obtenidos no superen los posibles y que
     * los puntos posibles no excedan el porcentaje restante del tipo de actividad.
     *
     * @param array $data Datos de la petición.
     * @param float $puntosPosibles Puntos posibles de la actividad.
     * @param float|null $puntosObtenidos Puntos obtenidos (opcional).
     * @throws \Exception Si alguna validación falla.
     */
    private function validateStoreLogic($data, $puntosPosibles, $puntosObtenidos)
    {
        // Validación 1: Puntos obtenidos no pueden ser mayores a los posibles
        // Solo validamos si hay puntos posibles definidos (> 0)
        if ($puntosPosibles > 0 && $puntosObtenidos !== null && $puntosObtenidos > $puntosPosibles) {
            throw new \Exception('Los puntos obtenidos no pueden ser mayores a los puntos posibles.');
        }

        // Validación 2: Puntos posibles no pueden exceder lo restante del tipo
        if ($puntosPosibles > 0) {
            $this->validarPuntosRestantes(
                $data['id_materia'],
                $data['id_tipo_actividad'],
                $puntosPosibles,
                $data['id_actividad'] ?? null
            );
        }
    }

    /**
     * Guarda la actividad en la base de datos (crear o actualizar) y recalcula la materia.
     *
     * @param array $data Datos originales de la petición (para ID).
     * @param array $actividadData Datos preparados para el modelo.
     * @param int $idUsuario ID del usuario autenticado.
     * @return string Mensaje de éxito.
     */
    private function saveActividad($data, $actividadData, $idUsuario)
    {
        if (isset($data['id_actividad']) && $data['id_actividad'] > 0) {
            // Actualizar
            $this->actividadModel->actualizar($data['id_actividad'], $actividadData);
            $message = 'Actividad actualizada correctamente';
        } else {
            // Crear
            $this->actividadModel->crear($actividadData);
            $message = 'Actividad creada correctamente';
        }

        // Recalcular materia
        $this->calculadoraModel->recalcularMateria($data['id_materia'], $idUsuario);

        return $message;
    }

    /**
     * Elimina una actividad.
     *
     * Requiere el parámetro 'id' en la query string.
     * Recalcula el promedio de la materia tras la eliminación.
     *
     * @return void Envía una respuesta JSON.
     */
    public function delete()
    {
        $idUsuario = AuthController::getUserId();
        $idActividad = $_GET['id'] ?? 0;

        if ($idActividad <= 0) {
            $this->json(['status' => 'error', 'message' => 'ID Actividad requerido'], 400);
            return;
        }

        try {
            // Eliminar y obtener ID de materia para recalcular
            $idMateria = $this->actividadModel->eliminar($idActividad, $idUsuario);

            // Recalcular materia
            $this->calculadoraModel->recalcularMateria($idMateria, $idUsuario);

            $this->json(['status' => 'success', 'message' => 'Actividad eliminada correctamente']);

        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Valida si los puntos de la nueva actividad caben en el porcentaje restante del tipo.
     *
     * @param int $idMateria ID de la materia.
     * @param int $idTipo ID del tipo de actividad.
     * @param float $puntosNuevos Puntos posibles de la nueva actividad.
     * @param int|null $idActividadExcluir ID de actividad a excluir (en caso de edición).
     * @throws \Exception Si los puntos exceden lo disponible.
     */
    private function validarPuntosRestantes($idMateria, $idTipo, $puntosNuevos, $idActividadExcluir = null)
    {
        // 1. Obtener porcentaje del tipo
        $tipos = $this->materiaModel->obtenerTipos($idMateria);
        $porcentajeTipo = 0.0;
        $encontrado = false;
        foreach ($tipos as $t) {
            if ($t['id_tipo_actividad'] == $idTipo) {
                $porcentajeTipo = (float) $t['porcentaje'];
                $encontrado = true;
                break;
            }
        }

        if (!$encontrado) {
            throw new \Exception("El tipo de actividad seleccionado no pertenece a esta materia.");
        }

        // 2. Obtener suma de puntos de actividades existentes
        $actividades = $this->actividadModel->obtenerPorMateria($idMateria, AuthController::getUserId());
        $sumaExistente = 0.0;
        foreach ($actividades as $act) {
            if ($act['id_tipo_actividad'] == $idTipo) {
                // Si estamos editando, excluir la actividad actual de la suma
                if ($idActividadExcluir && $act['id_actividad'] == $idActividadExcluir) {
                    continue;
                }
                $sumaExistente += (float) ($act['puntos_posibles'] ?? 0);
            }
        }

        $disponible = $porcentajeTipo - $sumaExistente;

        // Permitir un pequeño margen de error por flotantes
        if (($puntosNuevos - $disponible) > 0.01) {
            throw new \Exception("Los puntos de la actividad ($puntosNuevos) exceden los puntos disponibles para este tipo ($disponible de $porcentajeTipo).");
        }
    }
}
