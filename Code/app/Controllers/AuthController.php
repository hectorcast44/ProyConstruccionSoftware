<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

/**
 * Controlador de autenticación de usuarios.
 *
 * Gestionar:
 *  - Iniciar sesión.
 *  - Registrar nuevos usuarios.
 *  - Cerrar sesión.
 *  - Obtener datos del usuario autenticado.
 */
class AuthController extends Controller {

    private const SEGUNDOS_INVALIDAR_COOKIE = 42000;
    private const ESTADO_HTTP_NO_AUTORIZADO = 401;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Redirigir a una ruta relativa dentro de /public/ calculando la URL base.
     *
     * Ejemplo:
     *   redirectTo('dashboard') -> /ProyConstruccionSoftware/Code/public/dashboard
     *
     * @param string $relativePath Ruta relativa destino (sin dominio).
     *
     * @return void
     */
    private function redirectTo(string $relativePath): void {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $baseDir = rtrim(dirname($scriptName), '/');
        $baseUrl = $baseDir . '/';
        $relativePath = ltrim($relativePath, '/');

        header('Location: ' . $baseUrl . $relativePath);
        exit;
    }

    /**
     * Mostrar el formulario de inicio de sesión.
     *
     * Si el usuario ya tiene sesión activa, redirigir al dashboard.
     *
     * @return void
     */
    public function showLogin(): void {
        if (!empty($_SESSION['id_usuario'])) {
            $this->redirectTo('dashboard');
        }

        $this->view('login');
    }

    /**
     * Procesar la petición de inicio de sesión.
     *
     * Orquestar:
     *  - Validar que la petición sea POST.
     *  - Leer y validar las credenciales.
     *  - Autenticar al usuario.
     *  - Actualizar el hash de contraseña si es necesario.
     *  - Crear la sesión y redirigir al dashboard.
     *
     * @return void
     */
    public function login(): void {
        if ($this->esPeticionPost()) {
            [$correo, $passwordPlano] = $this->obtenerCredencialesLoginDesdePost();
            $pdo = Database::getInstance()->getConnection();

            $entradaValida = $this->esEntradaLoginValida($correo, $passwordPlano);

            if ($entradaValida) {
                $usuario = $this->buscarUsuarioPorCorreo($pdo, $correo);
                $usuarioAutenticado = $this->esUsuarioAutenticado($usuario, $passwordPlano);

                if ($usuarioAutenticado && $usuario !== null) {
                    $this->actualizarHashPasswordSiEsNecesario($pdo, $usuario, $passwordPlano);
                    $this->iniciarSesionUsuario($usuario);
                    $this->redirectTo('dashboard');
                }
            }
        } else {
            $this->showLogin();
        }
    }

    /**
     * Procesar el registro de un nuevo usuario.
     *
     * Orquestar:
     *  - Validar que la petición sea POST.
     *  - Leer y validar los datos del formulario.
     *  - Verificar disponibilidad del correo.
     *  - Crear el usuario.
     *  - Iniciar sesión y redirigir al dashboard.
     *
     * @return void
     */
    public function register(): void {
        if ($this->esPeticionPost()) {
            [$nombre, $correo, $passwordPlano] = $this->obtenerDatosRegistroDesdePost();
            $pdo = Database::getInstance()->getConnection();

            $entradaValida = $this->esEntradaRegistroValida($nombre, $correo, $passwordPlano);

            if ($entradaValida) {
                $correoDisponible = $this->esCorreoDisponible($pdo, $correo);

                if ($correoDisponible) {
                    $idNuevoUsuario = $this->crearUsuarioRegistro($pdo, $nombre, $correo, $passwordPlano);

                    if ($idNuevoUsuario !== null) {
                        $this->iniciarSesionUsuario([
                            'id_usuario' => $idNuevoUsuario,
                            'nombre_usuario' => $nombre
                        ]);
                        $this->redirectTo('dashboard');
                    }
                }
            }
        } else {
            $this->showLogin();
        }
    }

    /**
     * Cerrar la sesión del usuario actual y limpiar la cookie de sesión.
     *
     * @return void
     */
    public function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - self::SEGUNDOS_INVALIDAR_COOKIE,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }

        session_destroy();

        $this->redirectTo('auth/login');
    }

    /**
     * Devolver la información básica del usuario autenticado en formato JSON.
     *
     * Estructura de respuesta exitosa:
     *  {
     *    "status": "success",
     *    "data": {
     *      "id_usuario": int,
     *      "nombre": string,
     *      "correo": string,
     *      "avatar": string
     *    }
     *  }
     *
     * @return void
     */
    public function me(): void {
        $idUsuario = self::getUserId();

        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare(
            'SELECT id_usuario, correo, nombre_usuario
             FROM USUARIO
             WHERE id_usuario = ?'
        );

        if ($stmt === false) {
            $this->json([
                'status' => 'error',
                'message' => 'No se pudo preparar la consulta de usuario.'
            ], 500);
        } else {
            $stmt->execute([$idUsuario]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Usuario no encontrado'
                ], 404);
            } else {
                $this->json([
                    'status' => 'success',
                    'data' => [
                        'id_usuario' => (int) $usuario['id_usuario'],
                        'nombre' => $usuario['nombre_usuario'],
                        'correo' => $usuario['correo'],
                        'avatar' => 'https://ui-avatars.com/api/?name='
                            . urlencode($usuario['nombre_usuario'])
                            . '&background=random'
                    ]
                ]);
            }
        }
    }

    /**
     * Obtener el identificador del usuario autenticado desde la sesión.
     *
     * Si no hay usuario autenticado, enviar respuesta 401 y finalizar ejecución.
     *
     * @return int Identificador del usuario autenticado.
     */
    public static function getUserId(): int {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['id_usuario'])) {
            http_response_code(self::ESTADO_HTTP_NO_AUTORIZADO);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        return (int) $_SESSION['id_usuario'];
    }

    /* ==========================================================
       HELPERS GENERALES DE PETICIÓN Y SESIÓN
       ========================================================== */

    /**
     * Verificar si la petición actual se hizo con el método HTTP POST.
     *
     * @return bool Verdadero si la petición es POST, falso en otro caso.
     */
    private function esPeticionPost(): bool {
        $metodo = $_SERVER['REQUEST_METHOD'] ?? '';
        return $metodo === 'POST';
    }

    /**
     * Crear la sesión para el usuario autenticado.
     *
     * @param array<string,mixed> $usuario Datos del usuario autenticado.
     *
     * @return void
     */
    private function iniciarSesionUsuario(array $usuario): void {
        $_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
        $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
    }

    /* ==========================================================
       HELPERS ESPECÍFICOS PARA LOGIN
       ========================================================== */

    /**
     * Obtener las credenciales de inicio de sesión desde $_POST.
     *
     * @return array{0:string,1:string} Arreglo con correo y contraseña en texto plano.
     */
    private function obtenerCredencialesLoginDesdePost(): array {
        $correo = trim($_POST['correo'] ?? '');
        $passwordPlano = trim($_POST['password'] ?? '');
        return [$correo, $passwordPlano];
    }

    /**
     * Validar los datos de entrada para el inicio de sesión.
     *
     * Mostrar mensaje de error si faltan campos obligatorios.
     *
     * @param string $correo Correo electrónico proporcionado por el usuario.
     * @param string $passwordPlano Contraseña en texto plano.
     *
     * @return bool Verdadero si los datos son válidos, falso si se debe detener el flujo.
     */
    private function esEntradaLoginValida(string $correo, string $passwordPlano): bool {
        $entradaValida = true;

        if ($correo === '' || $passwordPlano === '') {
            $this->view('login', [
                'error' => 'Debes ingresar correo y contraseña.'
            ]);
            $entradaValida = false;
        }

        return $entradaValida;
    }

    /**
     * Buscar un usuario por correo electrónico.
     *
     * @param PDO $pdo Conexión PDO activa.
     * @param string $correo Correo electrónico a buscar.
     *
     * @return array<string,mixed>|null Arreglo asociativo del usuario o null si no se encontró.
     */
    private function buscarUsuarioPorCorreo(PDO $pdo, string $correo): ?array {
        $usuario = null;

        $stmt = $pdo->prepare(
            'SELECT id_usuario, correo, password, nombre_usuario
             FROM USUARIO
             WHERE correo = ?
             LIMIT 1'
        );

        if ($stmt === false) {
            $this->view('login', [
                'error' => 'No se pudo preparar la consulta de autenticación.'
            ]);
        } else {
            $stmt->execute([$correo]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($fila !== false) {
                $usuario = $fila;
            }
        }

        return $usuario;
    }

    /**
     * Verificar si el usuario existe y la contraseña es correcta.
     *
     * Mostrar mensaje de error en caso de credenciales inválidas.
     *
     * @param array<string,mixed>|null $usuario Datos del usuario o null si no existe.
     * @param string $passwordPlano Contraseña en texto plano.
     *
     * @return bool Verdadero si el usuario está autenticado, falso en caso contrario.
     */
    private function esUsuarioAutenticado(?array $usuario, string $passwordPlano): bool {
        $usuarioAutenticado = true;

        if ($usuario === null || !password_verify($passwordPlano, $usuario['password'])) {
            $this->view('login', [
                'error' => 'Correo o contraseña incorrectos.'
            ]);
            $usuarioAutenticado = false;
        }

        return $usuarioAutenticado;
    }

    /**
     * Actualizar el hash de contraseña si el algoritmo actual lo requiere.
     *
     * Esta rutina no detiene el flujo de autenticación si la actualización falla.
     *
     * @param PDO $pdo Conexión PDO activa.
     * @param array<string,mixed> $usuario Datos del usuario autenticado.
     * @param string $passwordPlano Contraseña en texto plano.
     *
     * @return void
     */
    private function actualizarHashPasswordSiEsNecesario(PDO $pdo, array $usuario, string $passwordPlano): void {
        if (!password_needs_rehash($usuario['password'], PASSWORD_DEFAULT)) {
            return;
        }

        $nuevoHash = password_hash($passwordPlano, PASSWORD_DEFAULT);

        $stmtActualizar = $pdo->prepare(
            'UPDATE USUARIO
             SET password = ?
             WHERE id_usuario = ?'
        );

        if ($stmtActualizar !== false) {
            $stmtActualizar->execute([
                $nuevoHash,
                (int) $usuario['id_usuario']
            ]);
        }
    }

    /* ==========================================================
       HELPERS ESPECÍFICOS PARA REGISTER
       ========================================================== */

    /**
     * Obtener los datos de registro desde $_POST.
     *
     * @return array{0:string,1:string,2:string} Arreglo con nombre, correo y contraseña en texto plano.
     */
    private function obtenerDatosRegistroDesdePost(): array {
        $nombre = trim($_POST['nombre'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $passwordPlano = trim($_POST['password'] ?? '');
        return [$nombre, $correo, $passwordPlano];
    }

    /**
     * Validar los datos de entrada para el registro.
     *
     * Mostrar mensaje de error si faltan campos, el correo es inválido
     * o la contraseña no cumple la longitud mínima.
     *
     * @param string $nombre Nombre del usuario.
     * @param string $correo Correo electrónico.
     * @param string $passwordPlano Contraseña en texto plano.
     *
     * @return bool Verdadero si los datos son válidos, falso en otro caso.
     */
    private function esEntradaRegistroValida(string $nombre, string $correo, string $passwordPlano): bool {
        $entradaValida = true;
        $mensajeError = '';

        if ($nombre === '' || $correo === '' || $passwordPlano === '') {
            $mensajeError = 'Todos los campos son obligatorios.';
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $mensajeError = 'El correo electrónico no es válido.';
        } elseif (strlen($passwordPlano) < 6) {
            $mensajeError = 'La contraseña debe tener al menos 6 caracteres.';
        }

        if ($mensajeError !== '') {
            $this->view('login', [
                'signup_error' => $mensajeError,
                'signup_open' => true
            ]);
            $entradaValida = false;
        }

        return $entradaValida;
    }

    /**
     * Verificar si el correo electrónico está disponible para registro.
     *
     * Mostrar mensaje de error si no se puede preparar la consulta o
     * si ya existe una cuenta registrada con ese correo.
     *
     * @param PDO $pdo Conexión PDO activa.
     * @param string $correo Correo electrónico a validar.
     *
     * @return bool Verdadero si el correo está disponible, falso en otro caso.
     */
    private function esCorreoDisponible(PDO $pdo, string $correo): bool {
        $correoDisponible = true;

        $stmtExiste = $pdo->prepare(
            'SELECT id_usuario
             FROM USUARIO
             WHERE correo = ?
             LIMIT 1'
        );

        if ($stmtExiste === false) {
            $this->view('login', [
                'signup_error' => 'No se pudo validar la cuenta existente.',
                'signup_open' => true
            ]);
            $correoDisponible = false;
        } else {
            $stmtExiste->execute([$correo]);
            $registroExistente = $stmtExiste->fetch(PDO::FETCH_ASSOC);

            if ($registroExistente) {
                $this->view('login', [
                    'signup_error' => 'Ya existe una cuenta registrada con ese correo.',
                    'signup_open' => true
                ]);
                $correoDisponible = false;
            }
        }

        return $correoDisponible;
    }

    /**
     * Crear un nuevo usuario en la base de datos.
     *
     * Mostrar mensaje de error si no se puede preparar la consulta
     * o si ocurre un error al insertar el registro.
     *
     * @param PDO $pdo Conexión PDO activa.
     * @param string $nombre Nombre del usuario.
     * @param string $correo Correo electrónico.
     * @param string $passwordPlano Contraseña en texto plano.
     *
     * @return int|null Identificador del nuevo usuario o null si falla la operación.
     */
    private function crearUsuarioRegistro(PDO $pdo, string $nombre, string $correo, string $passwordPlano): ?int {
        $idNuevoUsuario = null;
        $hashPassword = password_hash($passwordPlano, PASSWORD_DEFAULT);

        $stmtInsert = $pdo->prepare(
            'INSERT INTO USUARIO (nombre_usuario, correo, password)
             VALUES (?, ?, ?)'
        );

        if ($stmtInsert === false) {
            $this->view('login', [
                'signup_error' => 'No se pudo preparar la creación de la cuenta.',
                'signup_open' => true
            ]);
        } else {
            $ejecucionCorrecta = $stmtInsert->execute([
                $nombre,
                $correo,
                $hashPassword
            ]);

            if (!$ejecucionCorrecta) {
                $this->view('login', [
                    'signup_error' => 'Ocurrió un error al crear la cuenta. Inténtalo de nuevo.',
                    'signup_open' => true
                ]);
            } else {
                $idNuevoUsuario = (int) $pdo->lastInsertId();
            }
        }

        return $idNuevoUsuario;
    }
}
