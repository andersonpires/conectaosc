<?php
declare(strict_types=1);

namespace BackEnd\Core;

final class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $body;

    public function __construct(string $path)
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->path = $path;
        $this->query = $_GET ?? [];
        $this->body = $this->resolveBody();
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(): array
    {
        return $this->query;
    }

    public function body(): array
    {
        return $this->body;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->body)) {
            return $this->body[$key];
        }

        if (array_key_exists($key, $this->query)) {
            return $this->query[$key];
        }

        return $default;
    }

    private function resolveBody(): array
    {
        $rawBody = file_get_contents('php://input');
        if ($rawBody === false || $rawBody === '') {
            return $_POST ?? [];
        }

        $decoded = json_decode($rawBody, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        parse_str($rawBody, $parsed);
        if (is_array($parsed) && $parsed !== []) {
            return $parsed;
        }

        return $_POST ?? [];
    }
}

