<?php
/**
 * API: Información del usuario actual.
 *
 * Responsabilidad:
 *  - Obtener de la base de datos los datos básicos del usuario autenticado.
 *  - En modo desarrollo, el id de usuario proviene de USUARIO_PRUEBAS (db.php).
 *
 * Tabla USUARIO (según captura):
 *  - id_usuario INT PK
 *  - correo VARCHAR
 *  - password VARCHAR  (NO se expone)
 *  - nombre_usuario VARCHAR
 */

require_once '../src/db.php';

session_start();

/**
 * Id de usuario actual.
 * En modo desarrollo será USUARIO_PRUEBAS (por ejemplo, 1).
 * Cuando exista login real, se tomará de $_SESSION['id_usuario'].
 */
$idUsuario = obtenerIdUsuarioActual();

try {
    /**
     * Consulta de usuario.
     * Se mapea nombre_usuario como nombre para simplificar en el frontend.
     */
    $sql = '
        SELECT
            id_usuario,
            correo,
            nombre_usuario
        FROM USUARIO
        WHERE id_usuario = :id_usuario
        LIMIT 1
    ';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_usuario' => $idUsuario
    ]);

    $fila = $stmt->fetch();

    if (!$fila) {
        // Si no se encuentra el usuario, devolver algo genérico
        $usuario = [
            'id_usuario' => $idUsuario,
            'nombre'     => 'Usuario',
            'correo'     => '',
            'avatar'     => 'https://ui-avatars.com/api/?name=Usuario&background=random'
        ];
    } else {
        // Nombre del usuario
        $nombre = $fila['nombre_usuario'] ?? 'Usuario';

        // Mapear datos desde la BD
        $usuario = [
            'id_usuario' => (int) $fila['id_usuario'],
            'nombre'     => $nombre,
            'correo'     => $fila['correo'] ?? '',
            // Avatar dinámico con nombre real y fondo aleatorio
            'avatar'     => 'https://ui-avatars.com/api/?name=' . urlencode($nombre) . '&background=random'
        ];
    }


    enviarRespuesta(200, [
        'status' => 'success',
        'data' => $usuario
    ]);

} catch (PDOException $e) {
    error_log('Error en usuario_info.php: ' . $e->getMessage());

    enviarRespuesta(500, [
        'status' => 'error',
        'message' => 'Error al obtener la información del usuario.'
    ]);
}
