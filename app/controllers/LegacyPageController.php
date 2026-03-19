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
        $legacyDashboard = $this->basePath . '/app/views/pages/dashboard.php';
        if (is_file($legacyDashboard)) {
            require $legacyDashboard;
            return;
        }

        require $this->basePath . '/app/index.php';
    }

    public function login(): void
    {
        require $this->basePath . '/app/views/pages/login.php';
    }

    public function esqueciSenha(): void
    {
        $flow = new EsqueciSenhaFlow($this->basePath, $this->baseUrl);
        $flow->handle();
    }

    public function logout(): void
    {
        require $this->basePath . '/app/views/pages/logout.php';
    }

    public function confirma(): void
    {
        require $this->basePath . '/app/views/pages/confirma.php';
    }

    public function assinaturaDigital(): void
    {
        require $this->basePath . '/app/views/pages/assinaturadigital.php';
    }

    public function paginasFooter(): void
    {
        require $this->basePath . '/app/views/partials/paginasFooter.php';
    }

    public function beberibe2025(): void
    {
        require $this->basePath . '/app/views/pages/beberibe2025.php';
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

        $notFound = $this->basePath . '/app/views/pages/404.php';
        if (is_file($notFound)) {
            http_response_code(404);
            require $notFound;
            return;
        }

        http_response_code(404);
        echo 'Página não encontrada';
    }

    private function redirect(string $path): void
    {
        header('Location: ' . rtrim($this->baseUrl, '/') . $path);
        exit;
    }
}

