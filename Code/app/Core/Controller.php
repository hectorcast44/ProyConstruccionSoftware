<?php

namespace App\Core;

/**
 * Controlador base del sistema.
 *
 * Proporcionar métodos comunes para:
 *  - Renderizar vistas.
 *  - Devolver respuestas JSON.
 */
class Controller{
    /**
     * Renderizar una vista PHP ubicada en la carpeta /Views/.
     *
     * Este método:
     *  - Extrae los datos enviados como variables para la vista.
     *  - Construye la ruta física de la vista solicitada.
     *  - Incluye el archivo PHP correspondiente si existe.
     *  - En caso de no existir la vista, termina la ejecución mostrando un mensaje de error.
     *
     * @param string $view Nombre lógico de la vista sin extensión (por ejemplo: "dashboard").
     * @param array<string,mixed> $data Datos que se pasarán como variables a la vista.
     *
     * @return void
     */
    protected function view($view, $data = []): void {
        extract($data);
        $viewPath = __DIR__ . "/../Views/{$view}.php";

        if (file_exists($viewPath)) {
            require_once $viewPath; // Se incluye como plantilla de vista
        } else {
            die("View {$view} not found");
        }
    }

    /**
     * Enviar una respuesta JSON estandarizada.
     *
     * Este método:
     *  - Establece el encabezado Content-Type como JSON.
     *  - Define el código de estado HTTP.
     *  - Convierte los datos proporcionados en formato JSON.
     *  - Finaliza la ejecución después de enviar la respuesta.
     *
     * @param mixed $data Datos a convertir y enviar en formato JSON.
     * @param int $status Código de estado HTTP (por defecto 200).
     *
     * @return void
     */
    protected function json($data, $status = 200): void {
        header("Content-Type: application/json");
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
}
