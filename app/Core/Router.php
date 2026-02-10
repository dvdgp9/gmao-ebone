<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, string $controller, string $method): void
    {
        $this->addRoute('GET', $path, $controller, $method);
    }

    public function post(string $path, string $controller, string $method): void
    {
        $this->addRoute('POST', $path, $controller, $method);
    }

    private function addRoute(string $httpMethod, string $path, string $controller, string $method): void
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'httpMethod' => $httpMethod,
            'pattern' => $pattern,
            'controller' => $controller,
            'method' => $method,
        ];
    }

    public function dispatch(string $url): void
    {
        $url = trim($url, '/');
        $httpMethod = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $route) {
            if ($route['httpMethod'] !== $httpMethod) {
                continue;
            }

            if (preg_match($route['pattern'], $url, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $controllerClass = $route['controller'];
                $method = $route['method'];

                if (!class_exists($controllerClass)) {
                    $this->error404("Controller not found: {$controllerClass}");
                    return;
                }

                $controller = new $controllerClass();

                if (!method_exists($controller, $method)) {
                    $this->error404("Method not found: {$method}");
                    return;
                }

                call_user_func_array([$controller, $method], $params);
                return;
            }
        }

        $this->error404();
    }

    private function error404(string $message = ''): void
    {
        http_response_code(404);
        if ($_ENV['APP_DEBUG'] === 'true' && $message) {
            echo "<h1>404</h1><p>{$message}</p>";
        } else {
            require_once __DIR__ . '/../views/errors/404.php';
        }
    }
}
