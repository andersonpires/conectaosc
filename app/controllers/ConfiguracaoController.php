<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class ConfiguracaoController
{
    public function __construct(
        private readonly string $basePath
    ) {
    }

    public function index(): void
    {
        $runtime = require $this->basePath . '/bootstrap/runtime.php';
        $baseUrl = (string) ($runtime['base_para_url'] ?? '');
        $flow = new ConfiguracaoFlow($this->basePath, $baseUrl);
        $flow->handle();
    }
}

