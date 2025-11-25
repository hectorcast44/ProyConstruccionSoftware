<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\TipoActividad;
use App\Controllers\AuthController;

class TipoActividadController extends Controller
{
    private $tipoModel;

    public function __construct()
    {
        $this->tipoModel = new TipoActividad();
    }

    public function index()
    {
        $idUsuario = AuthController::getUserId();
        try {
            $tipos = $this->tipoModel->obtenerTodos($idUsuario);
            $this->json(['status' => 'success', 'data' => $tipos]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function store()
    {
        $idUsuario = AuthController::getUserId();
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['nombre_tipo']) || empty(trim($data['nombre_tipo']))) {
            $this->json(['status' => 'error', 'message' => 'El nombre del tipo es requerido'], 400);
            return;
        }

        try {
            $id = $this->tipoModel->crear(trim($data['nombre_tipo']), $idUsuario);
            $this->json(['status' => 'success', 'message' => 'Tipo creado correctamente', 'id' => $id], 201);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function update()
    {
        $idUsuario = AuthController::getUserId();
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $_GET['id'] ?? null;

        if (!$id) {
            $this->json(['status' => 'error', 'message' => 'ID requerido'], 400);
            return;
        }

        if (!isset($data['nombre_tipo']) || empty(trim($data['nombre_tipo']))) {
            $this->json(['status' => 'error', 'message' => 'El nombre del tipo es requerido'], 400);
            return;
        }

        try {
            $this->tipoModel->actualizar($id, trim($data['nombre_tipo']), $idUsuario);
            $this->json(['status' => 'success', 'message' => 'Tipo actualizado correctamente']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function delete()
    {
        $idUsuario = AuthController::getUserId();
        $id = $_GET['id'] ?? null;

        if (!$id) {
            $this->json(['status' => 'error', 'message' => 'ID requerido'], 400);
            return;
        }

        try {
            $this->tipoModel->eliminar($id, $idUsuario);
            $this->json(['status' => 'success', 'message' => 'Tipo eliminado correctamente']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
