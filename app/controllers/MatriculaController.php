<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class MatriculaController
{
    public function __construct(
        private readonly string $basePath
    ) {
    }

    public function turma(): void
    {
        $this->render('/app/views/matricula/listTurmasMat.php');
    }

    public function resultado(): void
    {
        $this->render('/app/views/matricula/resultadoMatriculas.php');
    }

    public function cadastrar(): void
    {
        $this->render('/app/views/matricula/cadMatricula.php');
    }

    public function alterar(): void
    {
        $this->render('/app/views/matricula/alteraMatricula.php');
    }

    public function excluir(): void
    {
        $this->render('/app/views/matricula/excluirMatricula.php');
    }

    public function contrato(): void
    {
        $this->render('/app/views/matricula/gerarContrato.php');
    }

    public function contratosTurma(): void
    {
        $this->render('/app/views/matricula/gerarContratosTurma.php');
    }

    public function contratosTurmaProcessar(): void
    {
        $this->render('/app/views/matricula/processarContratoTurmaItem.php');
    }

    public function contratosTurmaSolicitar(): void
    {
        $this->render('/app/views/matricula/solicitarContratoTurma.php');
    }

    public function contratosCurso(): void
    {
        $this->render('/app/views/matricula/gerarContratosCurso.php');
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


