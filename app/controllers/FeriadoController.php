<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class FeriadoController
{
    public function __construct(
        private readonly string $basePath
    ) {
    }

    public function index(): void
    {
        $runtime = require $this->basePath . '/bootstrap/runtime.php';
        $baseUrl = (string) ($runtime['base_para_url'] ?? '');
        $flow = new FeriadoFlow($this->basePath, $baseUrl);
        $flow->handle();
    }
}

