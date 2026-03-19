<?php
declare(strict_types=1);

namespace FrontEnd\Core;

final class FrontRouter
{
    /** @var array<string, array<int, array{pattern:string, handler:callable|array}>> */
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, callable|array $handler): void
    {
        $regex = '#^' . preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $this->normalize($path)) . '$#';
        $this->routes[$method][] = [
            'pattern' => $regex,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $path): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'HEAD') {
            $method = 'GET';
        }
        $normalizedPath = $this->normalize($path);

        foreach ($this->routes[$method] ?? [] as $route) {
            if (!preg_match($route['pattern'], $normalizedPath, $matches)) {
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }

            call_user_func($route['handler'], $params);
            return;
        }

        $projectSlug = '/' . basename(dirname(__DIR__, 2));
        $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
        $slugPos = stripos($scriptName, $projectSlug . '/');
        if ($slugPos !== false) {
            $scriptName = substr($scriptName, $slugPos);
        }

        $basePrefix = rtrim(dirname($scriptName), '/');
        if ($basePrefix === '' || $basePrefix === '.' || $basePrefix === '/' || $basePrefix === '\\') {
            $basePrefix = $projectSlug;
        }

        $target = ($basePrefix !== '' ? $basePrefix : '')
            . '/login/?logout=1&erro=Rota+n%C3%A3o+encontrada.+Fa%C3%A7a+o+login+novamente.';
        header('Location: ' . $target);
        exit;
    }

    private function normalize(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '' || $trimmed === '/') {
            return '/';
        }

        return '/' . trim($trimmed, '/');
    }
}
