<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\Actividad;
use App\Models\Calculadora;
use App\Controllers\AuthController;

class ActividadController extends Controller
{
    private $actividadModel;
    private $calculadoraModel;

    public function __construct()
    {
        $this->actividadModel = new Actividad();
        $this->calculadoraModel = new Calculadora();
    }

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

    public function store()
    {
        $idUsuario = AuthController::getUserId();
        $data = json_decode(file_get_contents('php://input'), true);

        // Validaciones básicas
        if (empty($data['id_materia']) || empty($data['id_tipo_actividad']) || empty($data['nombre_actividad'])) {
            $this->json(['status' => 'error', 'message' => 'Faltan campos obligatorios'], 400);
            return;
        }

        // Preparar datos
        $actividadData = [
            'id_materia' => $data['id_materia'],
            'id_tipo_actividad' => $data['id_tipo_actividad'],
            'id_usuario' => $idUsuario,
            'nombre_actividad' => trim($data['nombre_actividad']),
            'fecha_entrega' => $data['fecha_entrega'] ?? date('Y-m-d'),
            'estado' => $data['estado'] ?? 'pendiente',
            'puntos_posibles' => isset($data['puntos_posibles']) && $data['puntos_posibles'] !== '' ? (float)$data['puntos_posibles'] : null,
            'puntos_obtenidos' => isset($data['puntos_obtenidos']) && $data['puntos_obtenidos'] !== '' ? (float)$data['puntos_obtenidos'] : null
        ];

        try {
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
}
