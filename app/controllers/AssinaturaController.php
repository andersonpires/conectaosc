<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class AssinaturaController
{
    public function __construct(
        private readonly string $basePath
    ) {
    }

    public function upload(): void
    {
        $this->render('/app/views/assinatura/PDFupload.php');
    }

    public function preview(): void
    {
        $this->render('/app/views/assinatura/PDFpreview.php');
    }

    public function finalizar(): void
    {
        $this->render('/app/views/assinatura/PDFfinaliza.php');
    }

    public function validar(): void
    {
        $this->render('/app/views/assinatura/verPDF.php');
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


