<?php
declare(strict_types=1);

namespace FrontEnd\Core;

final class LegacyPageRenderer
{
    public function __construct(private readonly string $basePath)
    {
    }

    public function render(string $relativePhpPath): void
    {
        $relativePhpPath = ltrim(str_replace('\\', '/', $relativePhpPath), '/');
        $fullPath = $this->basePath . '/' . $relativePhpPath;

        if (!is_file($fullPath) || !str_ends_with(strtolower($fullPath), '.php')) {
            $notFound = $this->basePath . '/app/views/pages/404.php';
            http_response_code(404);
            if (is_file($notFound)) {
                require $notFound;
                return;
            }
            echo 'Pagina nao encontrada';
            return;
        }

        require $fullPath;
    }
}
