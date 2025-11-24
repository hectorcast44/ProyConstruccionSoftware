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

            $this->json([
                'status' => 'success',
                'data' => [
                    'materia' => $resultadoMateria['materia'],
                    'progreso' => $resultadoMateria['progreso'],
                    'secciones' => array_values($secciones)
                ]
            ]);

        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function store()
    {
        // Implement create/update logic similar to MateriaController
        // ...
    }

    public function delete()
    {
        // Implement delete logic
        // ...
    }
}
