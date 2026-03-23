<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class QuizzController
{
    public function __construct(
        private readonly string $basePath
    ) {
    }

    public function formulario(): void
    {
        $this->render('/app/quizz/formAvaliaprofessor.php');
    }

    public function enviar(): void
    {
        $this->render('/app/quizz/cadAvaliaprofessor.php');
    }

    public function sucesso(): void
    {
        $this->render('/app/quizz/professoressucess.php');
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
}

