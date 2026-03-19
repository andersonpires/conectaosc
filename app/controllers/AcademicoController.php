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
        $this->render('/app/views/curso/formCurso.php');
    }

    public function cursosCadastrar(): void
    {
        $this->render('/app/views/curso/cadCurso.php');
    }

    public function cursosAlterar(): void
    {
        $this->render('/app/views/curso/alteraCurso.php');
    }

    public function cursosExcluir(): void
    {
        $this->render('/app/views/curso/excluirCurso.php');
    }

    public function cursosPlanejamento(): void
    {
        $this->render('/app/views/curso/planejamentoCurso.php');
    }

    public function planoCursos(): void
    {
        $this->render('/app/views/curso/planoCursos.php');
    }

    public function turmas(): void
    {
        $this->render('/app/views/turma/formTurma.php');
    }

    public function turmasCronograma(): void
    {
        $this->render('/app/views/turma/cronogramaTurma.php');
    }

    public function turmasCadastrar(): void
    {
        $this->render('/app/views/turma/cadTurma.php');
    }

    public function turmasAlterar(): void
    {
        $this->render('/app/views/turma/alteraTurma.php');
    }

    public function turmasExcluir(): void
    {
        $this->render('/app/views/turma/excluirTurma.php');
    }

    public function matriculasListagem(): void
    {
        $this->render('/app/views/matricula/listagemMatriculas.php');
    }

    public function matriculasRealizar(): void
    {
        if (
            ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
            && isset($_POST['checkbox'])
            && is_array($_POST['checkbox'])
            && count($_POST['checkbox']) > 0
        ) {
            $this->render('/app/views/matricula/formMatricula.php');
            return;
        }

        $target = rtrim($this->baseUrl, '/')
            . '/beneficiarios/lista?msg='
            . urlencode("'Selecione as pessoas e clique em Matricular Aluno'")
            . '&matricula=1';
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
