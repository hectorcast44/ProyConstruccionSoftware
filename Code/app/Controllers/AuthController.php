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

    /**
     * Construye la URL base hasta /public/ y redirige a una ruta relativa.
     * Ej: redirectTo('dashboard') -> /ProyConstruccionSoftware/Code/public/dashboard
     */
    private function redirectTo(string $relativePath): void
    {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        // Ej: /ProyConstruccionSoftware/Code/public/index.php
        $baseDir = rtrim(dirname($scriptName), '/');
        $baseUrl = $baseDir . '/';

        $relativePath = ltrim($relativePath, '/');

        header('Location: ' . $baseUrl . $relativePath);
        exit;
    }

    public function showLogin()
    {
        if (!empty($_SESSION['id_usuario'])) {
            $this->redirectTo('dashboard');
        }

        $this->view('login');
    }

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->showLogin();
        }

        $correo = trim($_POST['correo'] ?? '');
        $pass = trim($_POST['password'] ?? '');

        if ($correo === '' || $pass === '') {
            return $this->view('login', [
                'error' => 'Debes ingresar correo y contraseña.'
            ]);
        }

        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("
            SELECT id_usuario, correo, password, nombre_usuario
            FROM USUARIO
            WHERE correo = ?
            LIMIT 1
        ");
        $stmt->execute([$correo]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            !$user ||
            !password_verify($pass, $user['password'])
        ) {
            return $this->view('login', [
                'error' => 'Correo o contraseña incorrectos.'
            ]);
        }

        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            $nuevoHash = password_hash($pass, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE USUARIO SET password = ? WHERE id_usuario = ?");
            $upd->execute([$nuevoHash, $user['id_usuario']]);
        }

        $_SESSION['id_usuario'] = (int) $user['id_usuario'];
        $_SESSION['nombre_usuario'] = $user['nombre_usuario'];

        $this->redirectTo('dashboard');
    }

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->showLogin();
        }

        $nombre = trim($_POST['nombre'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $pass   = trim($_POST['password'] ?? '');

        if ($nombre === '' || $correo === '' || $pass === '') {
            return $this->view('login', [
                'signup_error' => 'Todos los campos son obligatorios.',
                'signup_open' => true
            ]);
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return $this->view('login', [
                'signup_error' => 'El correo electrónico no es válido.',
                'signup_open' => true
            ]);
        }

        if (strlen($pass) < 6) {
            return $this->view('login', [
                'signup_error' => 'La contraseña debe tener al menos 6 caracteres.',
                'signup_open' => true
            ]);
        }

        $pdo = Database::getInstance()->getConnection();

        // ¿Ya existe ese correo?
        $stmt = $pdo->prepare("
            SELECT id_usuario
            FROM USUARIO
            WHERE correo = ?
            LIMIT 1
        ");
        $stmt->execute([$correo]);
        $existe = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existe) {
            return $this->view('login', [
                'signup_error' => 'Ya existe una cuenta registrada con ese correo.',
                'signup_open' => true
            ]);
        }

        $hash = password_hash($pass, PASSWORD_DEFAULT);

        $insert = $pdo->prepare("
            INSERT INTO USUARIO (nombre_usuario, correo, password)
            VALUES (?, ?, ?)
        ");
        $ok = $insert->execute([$nombre, $correo, $hash]);

        if (!$ok) {
            return $this->view('login', [
                'signup_error' => 'Ocurrió un error al crear la cuenta. Inténtalo de nuevo.',
                'signup_open' => true
            ]);
        }

        // Auto-login tras registro
        $idNuevo = (int) $pdo->lastInsertId();
        $_SESSION['id_usuario'] = $idNuevo;
        $_SESSION['nombre_usuario'] = $nombre;

        $this->redirectTo('dashboard');
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();

        $this->redirectTo('auth/login');
    }

    public function me()
    {
        $idUsuario = self::getUserId();
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("
            SELECT id_usuario, correo, nombre_usuario
            FROM USUARIO
            WHERE id_usuario = ?
        ");
        $stmt->execute([$idUsuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return $this->json([
                'status' => 'error',
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $this->json([
            'status' => 'success',
            'data'   => [
                'id_usuario' => (int) $user['id_usuario'],
                'nombre' => $user['nombre_usuario'],
                'correo' => $user['correo'],
                'avatar' => 'https://ui-avatars.com/api/?name='
                    . urlencode($user['nombre_usuario'])
                    . '&background=random'
            ]
        ]);
    }

    public static function getUserId(): int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['id_usuario'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        return (int) $_SESSION['id_usuario'];
    }
}
