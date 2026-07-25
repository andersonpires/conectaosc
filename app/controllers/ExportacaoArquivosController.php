<?php

declare(strict_types=1);

namespace FrontEnd\Controllers;

/**
 * Exportacao dos arquivos fisicos (fotos e PDFs) para a migracao da plataforma.
 * Rota protegida por slug secreto + sessao de administrador.
 */
final class ExportacaoArquivosController
{
    public function __construct(
        private readonly string $basePath
    ) {
    }

    public function pagina(): void
    {
        $this->render('/app/views/exportacao/exportacao.php');
    }

    public function gerar(): void
    {
        $this->render('/app/views/exportacao/gerarZip.php');
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
