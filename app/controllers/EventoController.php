<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class EventoController
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $basePath
    ) {
    }

    public function inscricao(): void
    {
        $this->render('/app/views/evento/formInscricao.php');
    }

    public function inscritos(): void
    {
        $this->render('/app/views/evento/listagemInscritos.php');
    }

    public function cadastrarInscrito(): void
    {
        $flow = new EventoCadastroFlow($this->basePath, $this->baseUrl);
        $flow->handle();
    }

    public function excluirInscrito(): void
    {
        $flow = new EventoExcluirFlow($this->basePath, $this->baseUrl);
        $flow->handle();
    }

    public function verificarEmail(): void
    {
        $flow = new EventoVerificaEmailFlow($this->basePath);
        $flow->handle();
    }

    public function inscricaoSucesso(): void
    {
        $this->render('/app/views/evento/inscricaosucess.php');
    }

    public function editarInscrito(): void
    {
        $this->render('/app/views/evento/alteraInscrito.php');
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
