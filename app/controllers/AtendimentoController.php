<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class AtendimentoController
{
    public function __construct(
        private readonly string $basePath
    ) {
    }

    public function index(): void
    {
        $candidate = $this->basePath . '/app/views/atendimento/index.php';
        if (is_file($candidate)) {
            require $candidate;
            return;
        }
        http_response_code(404);
        echo 'Página não encontrada';
    }

    public function salvar(): void
    {
        $runtime = require $this->basePath . '/bootstrap/runtime.php';
        $baseUrl = (string) ($runtime['base_para_url'] ?? '');
        $flow = new AtendimentoActionFlow($this->basePath, $baseUrl);
        $flow->handle();
    }
}

