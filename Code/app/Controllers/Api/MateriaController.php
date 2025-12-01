<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\Materia;
use App\Controllers\AuthController;

class MateriaController extends Controller
{
    private $materiaModel;

    public function __construct()
    {
        $this->materiaModel = new Materia();
    }

    public function index()
    {
        $idUsuario = AuthController::getUserId();

        if (isset($_GET['id'])) {
            $materia = $this->materiaModel->obtenerPorId($_GET['id'], $idUsuario);
            if ($materia) {
                // adjuntar tipos/ponderaciones si existen
                try {
                    $tipos = $this->materiaModel->obtenerTipos($materia['id_materia']);
                } catch (\Exception $e) { $tipos = []; }
                $materia['tipos'] = $tipos;
                $this->json(['status' => 'success', 'data' => $materia]);
            } else {
                $this->json(['status' => 'error', 'message' => 'Materia no encontrada'], 404);
            }
        } else {
            // Obtener materias base
            $materiasDB = $this->materiaModel->obtenerPorUsuario($idUsuario);

            // Estructurar materias
            $materias = [];
            foreach ($materiasDB as $m) {
                $id = $m['id_materia'];
                $materias[$id] = [
                    'id' => $id,
                    'nombre' => $m['nombre_materia'],
                    'tipos' => []
                ];
            }

            if (empty($materias)) {
                $this->json(['status' => 'success', 'data' => []]);
                return;
            }

            // Obtener resumen de puntos por tipo de actividad (Lógica portada de calificaciones_resumen.php)
            // Nota: Idealmente esto iría en un método del modelo, pero por simplicidad lo integramos aquí 
            // o lo movemos a MateriaModel->obtenerResumenPorUsuario($idUsuario).
            // Para mantener MVC puro, lo moveremos al modelo en un paso posterior si es necesario, 
            // pero por ahora usaremos el modelo para obtener los datos crudos si es posible, 
            // o ejecutaremos la query aquí si el modelo no tiene el método.

            // Mejor enfoque: Agregar método al modelo Materia para obtener el resumen.
            $resumen = $this->materiaModel->obtenerResumenActividades($idUsuario);

            foreach ($resumen as $fila) {
                $idMateria = $fila['id_materia'];
                if (isset($materias[$idMateria])) {
                    $materias[$idMateria]['tipos'][] = [
                        'nombre' => $fila['nombre_tipo'],
                        'obtenido' => (float) $fila['puntos_obtenidos'],
                        'maximo' => (float) $fila['puntos_posibles']
                    ];
                }
            }

            $this->json(['status' => 'success', 'data' => array_values($materias)]);
        }
    }

    /**
     * Obtener tipos (ponderaciones) asociados a una materia
     * GET /api/materias/tipos?id=123
     */
    public function tipos()
    {
        $idUsuario = AuthController::getUserId();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $this->json(['status' => 'error', 'message' => 'Se requiere id de materia'], 400);
            return;
        }

        try {
            $tipos = $this->materiaModel->obtenerTipos($id);
            $this->json(['status' => 'success', 'data' => $tipos]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function store()
    {
        $idUsuario = AuthController::getUserId();
        $data = json_decode(file_get_contents('php://input'), true);

        try {
            if (isset($data['id_materia']) && $data['id_materia'] > 0) {
                // Update: solo actualizar campos de materia si vienen explícitamente en el payload.
                if (isset($data['nombre_materia']) || isset($data['calif_minima'])) {
                    $this->materiaModel->actualizar(
                        $data['id_materia'],
                        $idUsuario,
                        $data['nombre_materia'] ?? null,
                        $data['calif_minima'] ?? 70
                    );
                }

                // si vienen tipos, actualizar ponderaciones (no tocar otros campos)
                if (isset($data['tipos']) && is_array($data['tipos'])) {
                    $this->materiaModel->setPonderaciones($data['id_materia'], $data['tipos']);
                }

                $this->json(['status' => 'success', 'message' => 'Materia actualizada']);
            } else {
                // Create
                $id = $this->materiaModel->crear(
                    $idUsuario,
                    $data['nombre_materia'],
                    $data['calif_minima'] ?? 70
                );
                // si vienen tipos, insertarlas como ponderaciones
                if (isset($data['tipos']) && is_array($data['tipos'])) {
                    $this->materiaModel->setPonderaciones($id, $data['tipos']);
                }
                $this->json(['status' => 'success', 'message' => 'Materia creada', 'id_materia' => $id], 201);
            }
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function delete()
    {
        $idUsuario = AuthController::getUserId();
        $idMateria = $_GET['id'] ?? 0;

        try {
            $this->materiaModel->eliminar($idMateria, $idUsuario);
            $this->json(['status' => 'success', 'message' => 'Materia eliminada']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
