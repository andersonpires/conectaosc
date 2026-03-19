<?php
declare(strict_types=1);

namespace BackEnd\Core;

final class Router
{
    private Request $request;
    private array $routes = [];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function get(string $pattern, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $pattern, $handler, $middlewares);
    }

    public function post(string $pattern, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $pattern, $handler, $middlewares);
    }

    public function put(string $pattern, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $pattern, $handler, $middlewares);
    }

    public function patch(string $pattern, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('PATCH', $pattern, $handler, $middlewares);
    }

    public function delete(string $pattern, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $pattern, $handler, $middlewares);
    }

    public function dispatch(): void
    {
        $method = $this->request->method();
        $path = $this->normalizePath($this->request->path());

        $methodRoutes = $this->routes[$method] ?? [];
        foreach ($methodRoutes as $route) {
            $params = $this->match($route['pattern'], $path);
            if ($params === null) {
                continue;
            }

            foreach ($route['middlewares'] as $middleware) {
                $middleware($this->request);
            }

            call_user_func_array($route['handler'], $params);
            return;
        }

        Response::json(
            [
                'success' => false,
                'message' => 'Route not found',
                'data' => (object) [],
                'errors' => [],
            ],
            404
        );
    }

    private function addRoute(string $method, string $pattern, callable|array $handler, array $middlewares): void
    {
        $this->routes[$method][] = [
            'pattern' => $this->normalizePath($pattern),
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }

    private function normalizePath(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '' || $trimmed === '/') {
            return '/';
        }

        return '/' . trim($trimmed, '/');
    }

    private function match(string $pattern, string $path): ?array
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        if (!is_string($regex)) {
            return null;
        }

        $regex = '#^' . $regex . '$#';
        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[] = $value;
            }
        }

        return $params;
    }
}

