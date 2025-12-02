<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Excepción de dominio para errores relacionados con tipos de actividad.
 */
class TipoActividadException extends \Exception {
    /**
     * Construir una excepción de tipo de actividad.
     *
     * @param string $mensaje Mensaje descriptivo del error.
     * @param int $codigo Código de error
     * @param \Throwable|null $anterior Excepción previa encadenada.
     */
    public function __construct(string $mensaje = 'Error en tipo de actividad.', int $codigo = 0, ?\Throwable $anterior = null) {
        parent::__construct($mensaje, $codigo, $anterior);
    }
}

/**
 * Modelo de dominio para la entidad TIPO_ACTIVIDAD.
 *
 * Responsabilidades:
 *  - Gestionar tipos de actividad propios del usuario y predeterminados.
 *  - Validar propiedad y evitar duplicados por nombre.
 *  - Soportar eliminación opcional (forzada) con limpieza de dependencias.
 */
class TipoActividad {
    /**
     * Conexión PDO hacia la base de datos.
     *
     * @var PDO
     */
    private PDO $pdo;

    /** Identificador del usuario “sistema” que define tipos predeterminados. */
    private const USUARIO_SISTEMA_ID = 1;

    /**
     * Construir el modelo TipoActividad con una conexión PDO.
     *
     * @param PDO|null $pdo Conexión opcional. Si es null, obtener desde Database.
     *
     * @return void
     */
    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? Database::getInstance()->getConnection();
    }

    /**
     * Obtener todos los tipos visibles para un usuario.
     *
     * Incluye:
     *  - Tipos propios del usuario.
     *  - Tipos predeterminados (usuario sistema).
     * Agrega un flag lógico es_propio para indicar editabilidad.
     *
     * @param int $idUsuario Identificador del usuario.
     *
     * @return array<int,array<string,mixed>> Lista de tipos de actividad.
     */
    public function obtenerTodos(int $idUsuario): array {
        $sql = "SELECT
                    *,
                    (id_usuario = :id_usuario) AS es_propio
                FROM TIPO_ACTIVIDAD
                WHERE id_usuario = :id_usuario
                   OR id_usuario = :id_sistema
                ORDER BY nombre_tipo ASC";

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute([
            ':id_usuario' => $idUsuario,
            ':id_sistema' => self::USUARIO_SISTEMA_ID,
        ]);

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener un tipo de actividad por identificador, validando visibilidad.
     *
     * @param int $idTipo Identificador del tipo de actividad.
     * @param int $idUsuario Identificador del usuario.
     *
     * @return array<string,mixed>|false Fila del tipo de actividad o false si no existe.
     */
    public function obtenerPorId(int $idTipo, int $idUsuario) {
        $sql = "SELECT *
                FROM TIPO_ACTIVIDAD
                WHERE id_tipo_actividad = :id_tipo
                  AND (id_usuario = :id_usuario OR id_usuario = :id_sistema)";

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute([
            ':id_tipo' => $idTipo,
            ':id_usuario' => $idUsuario,
            ':id_sistema' => self::USUARIO_SISTEMA_ID,
        ]);

        return $sentencia->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crear un nuevo tipo de actividad para un usuario.
     *
     * @param string $nombre Nombre del tipo de actividad.
     * @param int $idUsuario Identificador del usuario propietario.
     *
     * @throws TipoActividadException Si el nombre ya existe.
     *
     * @return int Identificador del tipo de actividad creado.
     */
    public function crear(string $nombre, int $idUsuario): int {
        if ($this->existeNombre($nombre, $idUsuario)) {
            throw new TipoActividadException("El tipo de actividad '{$nombre}' ya existe.");
        }

        $sql = "INSERT INTO TIPO_ACTIVIDAD (id_usuario, nombre_tipo)
                VALUES (:id_usuario, :nombre)";

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute([
            ':id_usuario' => $idUsuario,
            ':nombre' => $nombre,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualizar el nombre de un tipo de actividad propio del usuario.
     *
     * @param int $idTipo Identificador del tipo de actividad.
     * @param string $nombre Nombre nuevo.
     * @param int $idUsuario Identificador del usuario propietario.
     *
     * @throws TipoActividadException Si no es propietario o el nombre está duplicado.
     *
     * @return bool Verdadero si se modificó al menos un registro.
     */
    public function actualizar(int $idTipo, string $nombre, int $idUsuario): bool {
        if (!$this->esPropietario($idTipo, $idUsuario)) {
            throw new TipoActividadException(
                'No tienes permiso para editar este tipo de actividad o es un tipo predeterminado.'
            );
        }

        if ($this->existeNombre($nombre, $idUsuario, $idTipo)) {
            throw new TipoActividadException("El tipo de actividad '{$nombre}' ya existe.");
        }

        $sql = "UPDATE TIPO_ACTIVIDAD
                SET nombre_tipo = :nombre
                WHERE id_tipo_actividad = :id_tipo";

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute([
            ':nombre' => $nombre,
            ':id_tipo' => $idTipo,
        ]);

        return $sentencia->rowCount() > 0;
    }

    /**
     * Eliminar un tipo de actividad, con opción de eliminación forzada.
     *
     * Si $forzar es falso:
     *  - Solo permite eliminar tipos sin referencias (actividades ni ponderaciones).
     * Si $forzar es verdadero:
     *  - Elimina actividades y ponderaciones relacionadas del usuario antes de borrar el tipo.
     *
     * @param int $idTipo Identificador del tipo de actividad.
     * @param int $idUsuario Identificador del usuario propietario.
     * @param bool $forzar Indica si se deben eliminar dependencias relacionadas.
     *
     * @throws TipoActividadException Si no es propietario o la operación falla.
     *
     * @return array<string,int> Resumen de eliminaciones: ['deleted_activities'=>..., 'deleted_ponderaciones'=>...].
     */
    public function eliminar(int $idTipo, int $idUsuario, bool $forzar = false): array {
        if (!$this->esPropietario($idTipo, $idUsuario)) {
            throw new TipoActividadException(
                'No tienes permiso para eliminar este tipo de actividad o es un tipo predeterminado.'
            );
        }

        $referencias = $this->contarReferencias($idTipo, $idUsuario);
        $cantidadActividades = $referencias['actividades'];
        $cantidadPonderaciones = $referencias['ponderaciones'];

        if (($cantidadActividades > 0 || $cantidadPonderaciones > 0) && !$forzar) {
            $mensaje = "El tipo tiene referencias: actividades={$cantidadActividades}, ponderaciones={$cantidadPonderaciones}";
            throw new TipoActividadException($mensaje);
        }

        try {
            $this->pdo->beginTransaction();

            if ($forzar && $cantidadActividades > 0) {
                $sqlEliminarActividades = "DELETE FROM ACTIVIDAD
                                           WHERE id_tipo_actividad = :id_tipo
                                             AND id_usuario = :id_usuario";
                $sentenciaEliminarActividades = $this->pdo->prepare($sqlEliminarActividades);
                $sentenciaEliminarActividades->execute([
                    ':id_tipo' => $idTipo,
                    ':id_usuario' => $idUsuario,
                ]);
            }

            if ($forzar && $cantidadPonderaciones > 0) {
                $sqlEliminarPonderaciones = "DELETE p
                                             FROM PONDERACION p
                                             INNER JOIN MATERIA m
                                                 ON p.id_materia = m.id_materia
                                             WHERE p.id_tipo_actividad = :id_tipo
                                               AND m.id_usuario = :id_usuario";
                $sentenciaEliminarPonderaciones = $this->pdo->prepare($sqlEliminarPonderaciones);
                $sentenciaEliminarPonderaciones->execute([
                    ':id_tipo' => $idTipo,
                    ':id_usuario' => $idUsuario,
                ]);
            }

            $sqlEliminarTipo = "DELETE FROM TIPO_ACTIVIDAD
                                WHERE id_tipo_actividad = :id_tipo";
            $sentenciaEliminarTipo = $this->pdo->prepare($sqlEliminarTipo);
            $sentenciaEliminarTipo->execute([
                ':id_tipo' => $idTipo,
            ]);

            $this->pdo->commit();

            return [
                'deleted_activities' => $cantidadActividades,
                'deleted_ponderaciones' => $cantidadPonderaciones,
            ];
        } catch (\Throwable $excepcion) {
            try {
                $this->pdo->rollBack();
            } catch (\Throwable $ignored) {
                // Ignorar errores de rollback para no ocultar la excepción original.
            }

            throw new TipoActividadException(
                'Error al eliminar el tipo de actividad.',
                0,
                $excepcion
            );
        }
    }

    /**
     * Contar referencias de un tipo de actividad en ACTIVIDAD y PONDERACION.
     *
     * @param int $idTipo Identificador del tipo de actividad.
     * @param int $idUsuario Identificador del usuario.
     *
     * @return array<string,int> Arreglo con llaves 'actividades' y 'ponderaciones'.
     */
    public function contarReferencias(int $idTipo, int $idUsuario): array {
        $sqlRefActividad = "SELECT COUNT(*)
                            FROM ACTIVIDAD
                            WHERE id_tipo_actividad = :id_tipo
                              AND id_usuario = :id_usuario";

        $sentencia = $this->pdo->prepare($sqlRefActividad);
        $sentencia->execute([
            ':id_tipo' => $idTipo,
            ':id_usuario' => $idUsuario,
        ]);
        $cantidadActividades = (int) $sentencia->fetchColumn();

        $sqlRefPonderacion = "SELECT COUNT(*)
                              FROM PONDERACION p
                              INNER JOIN MATERIA m
                                  ON p.id_materia = m.id_materia
                              WHERE p.id_tipo_actividad = :id_tipo
                                AND m.id_usuario = :id_usuario";

        $sentencia = $this->pdo->prepare($sqlRefPonderacion);
        $sentencia->execute([
            ':id_tipo' => $idTipo,
            ':id_usuario' => $idUsuario,
        ]);
        $cantidadPonderaciones = (int) $sentencia->fetchColumn();

        return [
            'actividades' => $cantidadActividades,
            'ponderaciones' => $cantidadPonderaciones,
        ];
    }

    /**
     * Verificar si un tipo de actividad pertenece al usuario.
     *
     * No se consideran propios los tipos del usuario sistema (predeterminados).
     *
     * @param int $idTipo Identificador del tipo de actividad.
     * @param int $idUsuario Identificador del usuario.
     *
     * @return bool Verdadero si el tipo pertenece al usuario.
     */
    private function esPropietario(int $idTipo, int $idUsuario): bool {
        $sql = "SELECT id_usuario
                FROM TIPO_ACTIVIDAD
                WHERE id_tipo_actividad = :id_tipo";

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute([
            ':id_tipo' => $idTipo,
        ]);

        $idOwner = $sentencia->fetchColumn();

        if ($idOwner === false) {
            return false;
        }

        return (int) $idOwner === $idUsuario;
    }

    /**
     * Verificar si ya existe un tipo con el mismo nombre para el usuario o como predeterminado.
     *
     * @param string $nombre Nombre del tipo de actividad.
     * @param int $idUsuario Identificador del usuario.
     * @param int|null $excluirId Identificador de tipo a excluir de la búsqueda (para updates).
     *
     * @return bool Verdadero si el nombre ya está en uso.
     */
    private function existeNombre(string $nombre, int $idUsuario, ?int $excluirId = null): bool {
        $sql = "SELECT COUNT(*)
                FROM TIPO_ACTIVIDAD
                WHERE nombre_tipo = :nombre
                  AND (id_usuario = :id_usuario OR id_usuario = :id_sistema)";

        $parametros = [
            ':nombre' => $nombre,
            ':id_usuario' => $idUsuario,
            ':id_sistema' => self::USUARIO_SISTEMA_ID,
        ];

        if ($excluirId !== null) {
            $sql .= " AND id_tipo_actividad != :excluir_id";
            $parametros[':excluir_id'] = $excluirId;
        }

        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute($parametros);

        return (int) $sentencia->fetchColumn() > 0;
    }
}
