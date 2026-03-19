<?php
declare(strict_types=1);

namespace BackEnd\Controllers;

use BackEnd\Core\Response;
use BackEnd\Services\RelatorioService;

final class RelatorioController
{
    public function __construct(private readonly RelatorioService $service)
    {
    }

    public function cursosOptions(): void
    {
        $somenteAtivos = isset($_POST['somenteAtivos']) ? (int)$_POST['somenteAtivos'] : 1;
        Response::json([
            'success' => true,
            'message' => 'Cursos carregados',
            'data' => $this->service->cursosOptions($somenteAtivos),
            'errors' => [],
        ]);
    }

    public function turmasOptions(): void
    {
        $idCurso = (int)($_POST['IdCurso'] ?? 0);
        $somenteAtivos = isset($_POST['somenteAtivos']) ? (int)$_POST['somenteAtivos'] : 1;
        $incluirTodas = isset($_POST['todasTurmas']) && (int)$_POST['todasTurmas'] === 0 ? 0 : 1;
        Response::json([
            'success' => true,
            'message' => 'Turmas carregadas',
            'data' => $idCurso > 0 ? $this->service->turmasOptions($idCurso, $somenteAtivos, $incluirTodas) : [],
            'errors' => [],
        ]);
    }

    public function projetos(): void
    {
        Response::json([
            'success' => true,
            'message' => 'Projetos carregados',
            'data' => $this->service->projetos(),
            'errors' => [],
        ]);
    }

    public function customAlunos(): void
    {
        Response::json([
            'success' => true,
            'message' => 'Relatorio customizado carregado',
            'data' => $this->service->customAlunos($_POST),
            'errors' => [],
        ]);
    }

    public function customAlunosHtml(): void
    {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }
        echo $this->service->customAlunosHtml($_POST);
        exit;
    }

    public function presencaCursoTurma(): void
    {
        $dados = $this->service->presencaCursoTurma($_POST);
        Response::json([
            'success' => true,
            'message' => 'Relatorio de presenca carregado',
            'data' => $dados['linhas'] ?? [],
            'errors' => [],
        ]);
    }

    public function presencaCursoTurmaHtml(): void
    {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }
        echo $this->service->presencaCursoTurmaHtml($_POST);
        exit;
    }

    public function matriculados(): void
    {
        Response::json([
            'success' => true,
            'message' => 'Matriculados carregados',
            'data' => $this->service->matriculados($_POST),
            'errors' => [],
        ]);
    }

    public function frequenciaMensal(): void
    {
        $payload = $_SERVER['REQUEST_METHOD'] === 'GET' ? $_GET : $_POST;
        Response::json([
            'success' => true,
            'message' => 'Frequencia mensal carregada',
            'data' => $this->service->frequenciaMensal($payload),
            'errors' => [],
        ]);
    }

    public function frequenciaIntervalo(): void
    {
        $payload = $_SERVER['REQUEST_METHOD'] === 'GET' ? $_GET : $_POST;
        $turmaInterval = !empty($payload['turmaInterval']) && (string)$payload['turmaInterval'] !== '0';
        Response::json([
            'success' => true,
            'message' => 'Frequencia por intervalo carregada',
            'data' => $this->service->frequenciaIntervalo($payload, $turmaInterval, false, true, true),
            'errors' => [],
        ]);
    }
}
