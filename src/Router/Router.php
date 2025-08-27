<?php
namespace src\Router;
class Router {
    private $routes = [];
    private $basePath;

    public function __construct($basePath = '') {
        $this->basePath = rtrim($basePath, '/');
    }

    public function addRoute($method, $path, $handler) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $this->basePath . $path,
            'handler' => $handler
        ];
    }

    public function get($path, $handler) {
        $this->addRoute('GET', $path, $handler);
    }

    public function post($path, $handler) {
        $this->addRoute('POST', $path, $handler);
    }

    public function put($path, $handler) {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete($path, $handler) {
        $this->addRoute('DELETE', $path, $handler);
    }

    public function run() {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod && $this->matchPath($route['path'], $requestPath)) {
                $params = $this->extractParams($route['path'], $requestPath);

                if (is_callable($route['handler'])) {
                    call_user_func($route['handler'], $params);
                } elseif (is_array($route['handler'])) {
                    [$controller, $method] = $route['handler'];
                    if (class_exists($controller)) {
                        $instance = new $controller();
                        if (method_exists($instance, $method)) {
                            $instance->$method($params);
                        } else {
                            $this->sendError(500, "Method $method not found in $controller");
                        }
                    } else {
                        $this->sendError(500, "Controller $controller not found");
                    }
                }
                return;
            }
        }

        $this->sendError(404, 'Route not found');
    }

    private function matchPath($routePath, $requestPath) {
        $routePattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
        $routePattern = str_replace('/', '\/', $routePattern);
        return preg_match('/^' . $routePattern . '$/', $requestPath);
    }

    private function extractParams($routePath, $requestPath) {
        $params = [];
        $routeParts = explode('/', $routePath);
        $requestParts = explode('/', $requestPath);

        foreach ($routeParts as $index => $part) {
            if (preg_match('/\{([^}]+)\}/', $part, $matches)) {
                $paramName = $matches[1];
                $params[$paramName] = $requestParts[$index] ?? null;
            }
        }

        return $params;
    }

    private function sendError($code, $message) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message, 'code' => $code]);
        exit;
    }

    public static function sendJson($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public static function getInput() {
        $input = file_get_contents('php://input');
        return json_decode($input, true);
    }
}