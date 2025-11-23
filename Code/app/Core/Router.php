<?php

namespace App\Core;

class Router
{
    private $routes = [];

    public function get($path, $callback)
    {
        $this->routes['GET'][$path] = $callback;
    }

    public function post($path, $callback)
    {
        $this->routes['POST'][$path] = $callback;
    }

    public function delete($path, $callback)
    {
        $this->routes['DELETE'][$path] = $callback;
    }

    public function resolve()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Remove base path if project is in a subdirectory
        // Assuming project is at /ProyConstruccionSoftware/Code/public
        // Adjust this logic if needed based on server config
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        if (strpos($path, $scriptName) === 0) {
            $path = substr($path, strlen($scriptName));
        }
        $path = '/' . trim($path, '/');

        $callback = $this->routes[$method][$path] ?? false;

        if ($callback === false) {
            // Try regex matching for dynamic routes
            foreach ($this->routes[$method] as $route => $handler) {
                $pattern = "@^" . preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_]+)', $route) . "$@";
                if (preg_match($pattern, $path, $matches)) {
                    $callback = $handler;
                    // Filter out numeric keys
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    return call_user_func($callback, $params);
                }
            }

            http_response_code(404);
            echo "Not Found";
            return;
        }

        if (is_array($callback)) {
            $controller = new $callback[0]();
            $method = $callback[1];
            return call_user_func([$controller, $method]);
        }

        return call_user_func($callback);
    }
}
