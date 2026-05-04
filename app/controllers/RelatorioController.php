<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class RelatorioController
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $basePath
    ) {
    }

    public function frequencia(): void
    {
        $this->render('/app/views/relatorio/relFrequencia.php');
    }

    public function frequenciaIntervaloPdf(): void
    {
        $this->render('/app/views/relatorio/getPDFInterval.php');
    }

    public function personalizado(): void
    {
        $this->render('/app/views/relatorio/relCustomize.php');
    }

    public function presencaCursoTurma(): void
    {
        $this->render('/app/views/relatorio/relatorioPresencaCursoTurma.php');
    }

    public function matriculados(): void
    {
        $this->render('/app/views/relatorio/relMatriculados.php');
    }

    public function swot(): void
    {
        $this->render('/app/views/relatorio/swotRel.php');
    }

    public function fichaCadastroPdf(): void
    {
        $this->render('/api/get/getPDF_ficha_cadastro.php');
    }

    public function ipaiAnamnesePdf(): void
    {
        $this->render('/api/get/getPDF_ipai_anamnese_lote.php');
    }

    private function render(string $legacyPath): void
    {
        $candidate = $this->basePath . $legacyPath;
        if (is_file($candidate)) {
            require $candidate;
            return;
        }
        http_response_code(404);
        echo 'Página não encontrada';
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
