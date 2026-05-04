<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
header('Content-Type: application/json; charset=utf-8');

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header('Location: ' . rtrim((string) $BASE_para_URL, '/') . '/login/?redirect=' . $redirect_url);
    exit();
}

require_once $BASE_para_PATH . '/api/repositories/CursoRepository.php';
require_once $BASE_para_PATH . '/api/services/CursoService.php';
require_once $BASE_para_PATH . '/api/repositories/TurmaRepository.php';
require_once $BASE_para_PATH . '/api/services/TurmaService.php';

$cursoService = new \BackEnd\Services\CursoService(new \BackEnd\Repositories\CursoRepository());
$turmaService = new \BackEnd\Services\TurmaService(new \BackEnd\Repositories\TurmaRepository());

$idTurma = isset($_GET['idTurma']) ? (int)$_GET['idTurma'] : 0;
$idCurso = isset($_GET['idCurso']) ? (int)$_GET['idCurso'] : 0;

if ($idTurma > 0 && $turmaService->hasDependenciasChamada($idTurma)) {
    echo json_encode([
        'status' => 'erro',
        'tabela' => 'tbChamada',
        'mensagem' => 'Existem registros na tabela tbChamada que impedem a exclusao da turma.'
    ]);
    exit;
}

if ($idCurso > 0 && $cursoService->hasDependenciasMatricula($idCurso)) {
    echo json_encode([
        'status' => 'erro',
        'tabela' => 'tbMatricula',
        'mensagem' => 'Existem registros na tabela tbMatricula que impedem a exclusao do curso.'
    ]);
    exit;
}

echo json_encode([
    'status' => 'sucesso',
    'mensagem' => 'A exclusao da turma e do curso pode ser realizada sem problemas.'
]);





