<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class BackupController
{
    public function __construct(
        private readonly string $basePath
    ) {
    }

    public function configuracao(): void
    {
        $this->render('/app/views/backup/configBackup.php');
    }

    public function dados(): void
    {
        $this->render('/app/views/backup/backup.php');
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


