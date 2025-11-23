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
                $this->json(['status' => 'success', 'data' => $materia]);
            } else {
                $this->json(['status' => 'error', 'message' => 'Materia no encontrada'], 404);
            }
        } else {
            $materias = $this->materiaModel->obtenerPorUsuario($idUsuario);
            $this->json(['status' => 'success', 'data' => $materias]);
        }
    }

    public function store()
    {
        $idUsuario = AuthController::getUserId();
        $data = json_decode(file_get_contents('php://input'), true);

        try {
            if (isset($data['id_materia']) && $data['id_materia'] > 0) {
                // Update
                $this->materiaModel->actualizar(
                    $data['id_materia'],
                    $idUsuario,
                    $data['nombre_materia'],
                    $data['calif_minima'] ?? 70
                );
                $this->json(['status' => 'success', 'message' => 'Materia actualizada']);
            } else {
                // Create
                $id = $this->materiaModel->crear(
                    $idUsuario,
                    $data['nombre_materia'],
                    $data['calif_minima'] ?? 70
                );
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
