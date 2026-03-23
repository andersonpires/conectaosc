<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class LegacyPageController
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $basePath
    ) {
    }

    public function home(): void
    {
        $this->redirect('/dashboard/');
    }

    public function dashboard(): void
    {
        require $this->basePath . '/index.php';
    }

    public function login(): void
    {
        require $this->basePath . '/login.php';
    }

    public function logout(): void
    {
        require $this->basePath . '/logout.php';
    }

    public function legacy(array $params): void
    {
        $path = trim((string)($params['path'] ?? ''), '/');
        if ($path === '') {
            $this->home();
            return;
        }

        $candidate = $this->basePath . '/' . $path;
        if (is_file($candidate) && str_ends_with(strtolower($candidate), '.php')) {
            require $candidate;
            return;
        }

        $notFound = $this->basePath . '/404.php';
        if (is_file($notFound)) {
            http_response_code(404);
            require $notFound;
            return;
        }

        http_response_code(404);
        echo 'Pagina nao encontrada';
    }

    private function redirect(string $path): void
    {
        header('Location: ' . rtrim($this->baseUrl, '/') . $path);
        exit;
    }
}
