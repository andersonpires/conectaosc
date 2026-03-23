<?php
declare(strict_types=1);

namespace Front\Core;

final class FrontRouter
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$this->normalize($path)] = $handler;
    }

    public function dispatch(string $path): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $normalizedPath = $this->normalize($path);
        $handler = $this->routes[$method][$normalizedPath] ?? null;

        if ($handler === null) {
            http_response_code(404);
            echo 'Route not found';
            return;
        }

        call_user_func($handler);
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

