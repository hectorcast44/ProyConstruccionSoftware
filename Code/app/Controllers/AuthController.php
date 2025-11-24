<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

class AuthController extends Controller
{

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login()
    {
        // Placeholder for login logic
        // For now, we rely on the dev mode or manual session setting
        // In a real app, this would handle POST credentials
    }

    public function logout()
    {
        session_destroy();
        header('Location: /');
        exit;
    }

    public function me()
    {
        $idUsuario = $this->getUserId();
        $pdo = Database::getInstance()->getConnection();

        $stmt = $pdo->prepare("SELECT id_usuario, correo, nombre_usuario FROM USUARIO WHERE id_usuario = ?");
        $stmt->execute([$idUsuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $this->json([
                'status' => 'success',
                'data' => [
                    'id_usuario' => $idUsuario,
                    'nombre' => 'Usuario',
                    'avatar' => 'https://ui-avatars.com/api/?name=Usuario&background=random'
                ]
            ]);
        } else {
            $this->json([
                'status' => 'success',
                'data' => [
                    'id_usuario' => (int) $user['id_usuario'],
                    'nombre' => $user['nombre_usuario'],
                    'correo' => $user['correo'],
                    'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($user['nombre_usuario']) . '&background=random'
                ]
            ]);
        }
    }

    public static function getUserId()
    {
        // Hardcoded for dev mode as per original db.php
        // In production, check config or env
        if (true) { // MODO_DESARROLLO
            return 1;
        }

        if (!isset($_SESSION['id_usuario'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        return $_SESSION['id_usuario'];
    }
}
