<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class AlertaController
{
    public function __construct(
        private readonly string $basePath
    ) {
    }

    public function index(): void
    {
        $this->render('/app/views/alerta/alerta.php');
    }

    public function salvar(): void
    {
        $this->render('/app/views/alerta/cadAlerta.php');
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


