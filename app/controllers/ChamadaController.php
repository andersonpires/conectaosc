<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class ChamadaController
{
    public function __construct(
        private readonly string $basePath
    ) {
    }

    public function tipo(): void
    {
        $this->render('/app/views/chamada/listTipoChamada.php');
    }

    public function lista(): void
    {
        $this->render('/app/views/chamada/listChamada.php');
    }

    public function faltas(): void
    {
        $this->render('/app/views/chamada/listTotalChamada.php');
    }

    public function salvar(): void
    {
        $this->render('/app/views/chamada/savebanco.php');
    }

    public function fotos(): void
    {
        $this->render('/app/views/chamada/downloadFotos.php');
    }

    public function resumoWhatsapp(): void
    {
        $this->render('/app/views/chamada/resumoWhatsapp.php');
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


