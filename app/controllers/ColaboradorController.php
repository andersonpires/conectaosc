<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class ColaboradorController
{
    public function __construct(
        private readonly string $basePath
    ) {
    }

    public function index(): void
    {
        $runtime = require $this->basePath . '/bootstrap/runtime.php';
        $baseUrl = (string) ($runtime['base_para_url'] ?? '');
        $flow = new ColaboradorFlow($this->basePath, $baseUrl);
        $flow->handle();
    }

    public function resetSenha(): void
    {
        $runtime = require $this->basePath . '/bootstrap/runtime.php';
        $baseUrl = (string) ($runtime['base_para_url'] ?? '');
        $flow = new ColaboradorResetSenhaFlow($this->basePath, $baseUrl);
        $flow->handle();
    }

    private function render(string $relativePath): void
    {
        $candidate = $this->basePath . $relativePath;
        if (is_file($candidate)) {
            require $candidate;
            return;
        }
        http_response_code(404);
        echo 'Página não encontrada';
    }
}


