<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if (!isset($BASE_para_PATH)) {
    echo json_encode([['Erro: sessao invalida']]);
    exit;
}

require_once $BASE_para_PATH . '/api/repositories/RelatorioRepository.php';
require_once $BASE_para_PATH . '/api/services/RelatorioService.php';

use BackEnd\Repositories\RelatorioRepository;
use BackEnd\Services\RelatorioService;

$service = new RelatorioService(new RelatorioRepository());
$data = $service->frequenciaMensal($_GET);

if (empty($data)) {
    echo json_encode([['Nenhum dado encontrado para os criterios selecionados.']]);
    exit;
}

$tabela = [];
$alunos = [];
$dias = [];
$cursoNome = $data[0]['NomeCurso'] ?? '';
$turmaNome = $data[0]['NomeTurma'] ?? '';
$mesAno = (string)($_GET['mesAno'] ?? '');
[$ano, $mes] = str_contains($mesAno, '-') ? explode('-', $mesAno, 2) : ['', ''];

foreach ($data as $row) {
    $aluno = (string)($row['Aluno'] ?? '');
    $dia = (int)($row['Dia'] ?? 0);
    $status = ((int)($row['presenca'] ?? 0) === 1) ? 'P' : (((int)($row['falta'] ?? 0) === 1) ? 'F' : (((int)($row['faltajust'] ?? 0) === 1) ? 'FJ' : 'NA'));

    if (!isset($tabela[$aluno])) {
        $tabela[$aluno] = [];
        $alunos[] = $aluno;
    }

    if (!in_array($dia, $dias, true)) {
        $dias[] = $dia;
    }

    $tabela[$aluno][$dia] = $status;
}

sort($dias);

$excelData = [];
$excelData[] = ['SISTEMA DE FREQUENCIA'];
$excelData[] = ['Curso: ' . $cursoNome];
$excelData[] = ['Turma: ' . $turmaNome];
$excelData[] = ['Mes/Ano: ' . $mes . '/' . $ano];
$excelData[] = [''];
$excelData[] = array_merge(['No', 'Nome do Aluno'], $dias);

foreach ($alunos as $index => $aluno) {
    $rowData = [$index + 1, $aluno];
    foreach ($dias as $dia) {
        $rowData[] = $tabela[$aluno][$dia] ?? 'NA';
    }
    $excelData[] = $rowData;
}

$excelData[] = ['Obs.: P = Presenca; F = Falta; FJ = Falta Justificada; e NA = Nao se Aplica.'];
echo json_encode($excelData);





