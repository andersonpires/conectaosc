<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class BeneficiarioController
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $basePath
    )
    {
    }

    public function cadastro(): void
    {
        $this->render('/app/beneficiario/beneficiarioController.php');
    }

    public function lista(): void
    {
        $this->render('/app/beneficiario/listagemSBenef.php');
    }

    public function dados(): void
    {
        $this->render('/app/beneficiario/listagemBeneficiarios.php');
    }

    public function aniversariantes(): void
    {
        $this->render('/app/aniversariantes/aniversariantes.php');
    }

    public function aniversariantesDados(): void
    {
        $this->render('/app/aniversariantes/aniversariantesController.php');
    }

    public function consultaCpf(): void
    {
        $this->render('/app/beneficiario/consulta-cpf.php');
    }

    public function avaliarVulnerabilidadeStream(): void
    {
        $this->render('/app/beneficiario/avaliarVulnerabilidadeStream.php');
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
