<?php

namespace App\Core;

/**
 * Enrutador principal del sistema.
 *
 * Gestionar:
 *  - Registro de rutas GET, POST y DELETE.
 *  - Resolución de rutas dinámicas con parámetros.
 *  - Ejecución del callback correspondiente.
 */
class Router
{
    /**
     * Contenedor de rutas asociadas por método HTTP.
     *
     * @var array<string,array<string,mixed>>
     */
    private $rutas = [];

    /**
     * Registrar una ruta con el método GET.
     *
     * @param string $ruta Ruta a registrar.
     * @param callable|array $callback Acción a ejecutar.
     *
     * @return void
     */
    public function get(string $ruta, $callback): void {
        $this->rutas['GET'][$ruta] = $callback;
    }

    /**
     * Registrar una ruta con el método POST.
     *
     * @param string $ruta Ruta a registrar.
     * @param callable|array $callback Acción a ejecutar.
     *
     * @return void
     */
    public function post(string $ruta, $callback): void {
        $this->rutas['POST'][$ruta] = $callback;
    }

    /**
     * Registrar una ruta con el método DELETE.
     *
     * @param string $ruta Ruta a registrar.
     * @param callable|array $callback Acción a ejecutar.
     *
     * @return void
     */
    public function delete(string $ruta, $callback): void {
        $this->rutas['DELETE'][$ruta] = $callback;
    }

    /**
     * Resolver la ruta solicitada.
     *
     * Este método:
     *  - Obtiene la ruta y método actual.
     *  - Normaliza la ruta solicitada.
     *  - Busca coincidencia exacta.
     *  - Busca coincidencia con parámetros dinámicos.
     *  - Ejecuta el callback correspondiente si existe.
     *  - Devuelve 404 si no se encuentra coincidencia.
     *
     * @return void
     */
    public function resolve(): void {
        $metodoHttp = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $rutaSolicitada = $this->normalizarRuta($_SERVER['REQUEST_URI'] ?? '/');
        $callbackCoincidente = null;
        $parametros = [];

        if (isset($this->rutas[$metodoHttp][$rutaSolicitada])) {
            $callbackCoincidente = $this->rutas[$metodoHttp][$rutaSolicitada];
        } else {
            [$callbackCoincidente, $parametros] = $this->buscarRutaDinamica($metodoHttp, $rutaSolicitada);
        }

        if ($callbackCoincidente === null) {
            $this->mostrar404();
        } else {
            $this->ejecutarCallback($callbackCoincidente, $parametros);
        }
    }

    /**
     * Normalizar la ruta de la solicitud.
     *
     * @param string $uri URI completa solicitada.
     *
     * @return string Ruta normalizada comenzando con "/".
     */
    private function normalizarRuta(string $uri): string {
        $ruta = parse_url($uri, PHP_URL_PATH) ?? '/';
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

        if (strpos($ruta, $scriptDir) === 0) {
            $ruta = substr($ruta, strlen($scriptDir));
        }

        return '/' . trim($ruta, '/');
    }

    /**
     * Buscar coincidencias con rutas dinámicas tales como:
     *   /usuarios/{id}
     *
     * @param string $metodoHttp Método HTTP actual.
     * @param string $rutaSolicitada Ruta normalizada.
     *
     * @return array{0:callable|array|null,1:array<string,string>} Callback y parámetros extraídos.
     */
    private function buscarRutaDinamica(string $metodoHttp, string $rutaSolicitada): array {
        if (!isset($this->rutas[$metodoHttp])) {
            return [null, []];
        }

        foreach ($this->rutas[$metodoHttp] as $ruta => $callback) {
            $patron = "@^" . preg_replace('/\{(\w+)\}/', '(?P<$1>\w+)', $ruta) . "$@";

            if (preg_match($patron, $rutaSolicitada, $coincidencias)) {
                $parametros = array_filter($coincidencias, 'is_string', ARRAY_FILTER_USE_KEY);
                return [$callback, $parametros];
            }
        }

        return [null, []];
    }

    /**
     * Ejecutar el callback asociado a la ruta encontrada.
     *
     * @param callable|array $callback Acción a ejecutar.
     * @param array<string,string> $parametros Parámetros dinámicos extraídos.
     *
     * @return void
     */
    private function ejecutarCallback($callback, array $parametros): void {
        if (is_array($callback)) {
            $controlador = new $callback[0]();
            $metodo = $callback[1];
            call_user_func([$controlador, $metodo], $parametros);
        } else {
            call_user_func($callback, $parametros);
        }

        // Ignorar el resultado: resolve() siempre retorna void
    }

    /**
     * Mostrar una respuesta HTTP 404 estándar.
     *
     * @return void
     */
    private function mostrar404(): void {
        http_response_code(404);
        echo "Not Found";
    }
}
