<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class AcademicoController
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $basePath
    ) {
    }

    public function cursos(): void
    {
        $this->render('/app/curso/formCurso.php');
    }

    public function cursosCadastrar(): void
    {
        $this->render('/app/curso/cadCurso.php');
    }

    public function cursosAlterar(): void
    {
        $this->render('/app/curso/alteraCurso.php');
    }

    public function cursosExcluir(): void
    {
        $this->render('/app/curso/excluirCurso.php');
    }

    public function turmas(): void
    {
        $this->render('/app/turma/formTurma.php');
    }

    public function turmasCadastrar(): void
    {
        $this->render('/app/turma/cadTurma.php');
    }

    public function turmasAlterar(): void
    {
        $this->render('/app/turma/alteraTurma.php');
    }

    public function turmasExcluir(): void
    {
        $this->render('/app/turma/excluirTurma.php');
    }

    public function matriculasListagem(): void
    {
        $this->render('/app/matricula/listagemMatriculas.php');
    }

    public function matriculasRealizar(): void
    {
        $target = rtrim($this->baseUrl, '/')
            . "/beneficiarios/lista?msg='Selecione%20as%20pessoas%20e%20clique%20em%20Matricular%20Aluno'&matricula=1";
        header('Location: ' . $target);
        exit;
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
