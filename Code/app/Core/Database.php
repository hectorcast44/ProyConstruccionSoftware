<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Clase encargada de gestionar la conexión a la base de datos.
 *
 * Implementar:
 *  - Patrón Singleton para crear una única instancia de conexión.
 *  - Configurar PDO con manejo de errores y modo de obtención por defecto.
 *  - Proveer acceso centralizado a la conexión mediante getConnection().
 */
class Database {
    /**
     * Instancia única de la clase Database (patrón Singleton).
     *
     * @var Database|null
     */
    private static $instance = null;

    /**
     * Instancia PDO activa.
     *
     * @var PDO
     */
    private $pdo;

    /**
     * Construir la conexión a la base de datos usando parámetros del archivo config.php.
     *
     * Este constructor:
     *  - Carga la configuración desde Config/config.php.
     *  - Construye el DSN para MySQL.
     *  - Inicializa un objeto PDO con la configuración establecida.
     *  - Establece atributos recomendados (modo de errores, fetch por defecto).
     *  - Maneja excepciones de conexión mediante PDOException.
     *
     * @return void
     */
    private function __construct() {
        $config = require_once __DIR__ . '/../Config/config.php';
        $dbConfig = $config['db'];

        try {
            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset={$dbConfig['charset']}";

            $this->pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }

    /**
     * Obtener la instancia única del gestor de base de datos.
     *
     * Si no existe una instancia previa, crearla. Siempre devolver
     *
     * @return Database Instancia única del manejador de base de datos.
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtener la conexión PDO activa.
     *
     * @return PDO Objeto PDO configurado para realizar operaciones en la base de datos.
     */
    public function getConnection(): PDO {
        return $this->pdo;
    }
}
