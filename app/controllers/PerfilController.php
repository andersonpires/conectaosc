<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class PerfilController
{
    public function __construct(
        private readonly string $basePath
    ) {
    }

    public function index(): void
    {
        $runtime = require $this->basePath . '/bootstrap/runtime.php';
        $baseUrl = (string) ($runtime['base_para_url'] ?? '');
        $flow = new PerfilFlow($this->basePath, $baseUrl);
        $flow->handle();
    }
}

