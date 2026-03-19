<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class EventoController
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $basePath
    )
    {
    }

    public function inscricao(): void
    {
        $this->render('/app/evento/formInscricao.php');
    }

    public function inscritos(): void
    {
        $this->render('/app/evento/listagemInscritos.php');
    }

    public function cadastrarInscrito(): void
    {
        $this->render('/app/evento/cadInscrito.php');
    }

    public function excluirInscrito(): void
    {
        $this->render('/app/evento/excluirInscrito.php');
    }

    public function verificarEmail(): void
    {
        $this->render('/app/evento/verifica_email.php');
    }

    public function inscricaoSucesso(): void
    {
        $this->render('/app/evento/inscricaosucess.php');
    }

    public function editarInscrito(): void
    {
        $this->render('/app/evento/alteraInscrito.php');
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
