<?php
namespace App\Core;

class Router
{
    private array $routes = [];
    private string $basePath;

    public function __construct(string $basePath = '/clinica/api')
    {
        $this->basePath = $basePath;
    }

    public function get(string $path, callable $handler): self
    {
        $this->routes['GET'][$this->normalize($path)] = $handler;
        return $this;
    }

    public function post(string $path, callable $handler): self
    {
        $this->routes['POST'][$this->normalize($path)] = $handler;
        return $this;
    }

    public function put(string $path, callable $handler): self
    {
        $this->routes['PUT'][$this->normalize($path)] = $handler;
        return $this;
    }

    public function delete(string $path, callable $handler): self
    {
        $this->routes['DELETE'][$this->normalize($path)] = $handler;
        return $this;
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '' : $path;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $routeOverride = isset($_GET['__route__']) ? (string) $_GET['__route__'] : '';
        if ($routeOverride !== '') {
            $uri = '/' . trim($routeOverride, '/');
            if ($uri === '/') {
                $uri = '';
            }
        } else {
            $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
            if (strpos($uri, $this->basePath) === 0) {
                $uri = substr($uri, strlen($this->basePath));
            }
            $uri = ($uri === '' || $uri === false) ? '' : ('/' . trim($uri, '/'));
            if ($uri === '/') {
                $uri = '';
            }
        }

        $routes = $this->routes[$method] ?? [];
        $matched = null;
        $params = [];

        foreach ($routes as $pattern => $handler) {
            $regex = $this->patternToRegex($pattern);
            if (preg_match($regex, $uri, $m)) {
                $matched = $handler;
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                break;
            }
        }

        if ($matched === null) {
            JsonResponse::error('Rota não encontrada', [], 404);
        }

        call_user_func_array($matched, array_values($params));
    }

    private function patternToRegex(string $pattern): string
    {
        $pattern = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern);
        return '#^' . $pattern . '$#';
    }
}
