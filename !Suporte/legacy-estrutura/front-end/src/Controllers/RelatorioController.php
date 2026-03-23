<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class RelatorioController
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $basePath
    )
    {
    }

    public function frequencia(): void
    {
        $this->render('/app/relatorio/relFrequencia.php');
    }

    public function personalizado(): void
    {
        $this->render('/app/relatorio/relCustomize.php');
    }

    public function presencaCursoTurma(): void
    {
        $this->render('/app/relatorio/relatorioPresencaCursoTurma.php');
    }

    public function matriculados(): void
    {
        $this->render('/app/relatorio/relMatriculados.php');
    }

    public function swot(): void
    {
        $this->render('/app/relatorio/swotRel.php');
    }

    private function render(string $legacyPath): void
    {
        $candidate = $this->basePath . $legacyPath;
        if (is_file($candidate)) {
            require $candidate;
            return;
        }
        http_response_code(404);
        echo 'Pagina nao encontrada';
    }

    private function redirect(string $path): void
    {
        $location = rtrim($this->baseUrl, '/') . $path;
        $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
        if ($query !== '') {
            $location .= str_contains($location, '?') ? '&' . $query : '?' . $query;
        }
        header('Location: ' . $location);
        exit;
    }
}
