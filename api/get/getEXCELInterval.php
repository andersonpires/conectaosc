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
$turmaInterval = !empty($_GET['turmaInterval']) && (string)$_GET['turmaInterval'] !== '0';

$data = $service->frequenciaIntervalo(
    $_GET,
    $turmaInterval,
    false,
    false,
    false
);

if (empty($data)) {
    echo json_encode([['Nenhum dado encontrado para os criterios selecionados.']]);
    exit;
}

$tabela = [];
$alunos = [];
$dias = [];
$cursoNome = $data[0]['NomeCurso'] ?? '';
$turmaNome = $data[0]['NomeTurma'] ?? '';
$dataInicio = (string)($_GET['dataInicio'] ?? '');
$dataFim = (string)($_GET['dataFim'] ?? '');

foreach ($data as $row) {
    $aluno = (string)($row['Aluno'] ?? '');
    $dataCompleta = (string)($row['Data'] ?? '');
    $status = ((int)($row['presenca'] ?? 0) === 1) ? 'P' : (((int)($row['falta'] ?? 0) === 1) ? 'F' : (((int)($row['faltajust'] ?? 0) === 1) ? 'FJ' : 'NA'));

    if (!isset($tabela[$aluno])) {
        $tabela[$aluno] = [];
        $alunos[] = $aluno;
    }
    if (!in_array($dataCompleta, $dias, true)) {
        $dias[] = $dataCompleta;
    }
    $tabela[$aluno][$dataCompleta] = $status;
}

usort($dias, static fn(string $a, string $b): int => strtotime($a) <=> strtotime($b));

$excelData = [];
$excelData[] = ['SISTEMA DE FREQUENCIA'];
$excelData[] = ['Curso: ' . $cursoNome];
$excelData[] = ['Turma: ' . $turmaNome];
$excelData[] = ['De ' . date('d/m/Y', strtotime($dataInicio)) . ' a ' . date('d/m/Y', strtotime($dataFim))];
$excelData[] = [''];

$headerRow = ['No', 'Nome do Aluno'];
if ($turmaInterval) {
    $headerRow[] = 'Turma';
}
$headerRow = array_merge($headerRow, ['Qtd P', 'Qtd F', 'Qtd FJ', 'Qtd Aulas']);
$headerRow = array_merge($headerRow, array_map(static fn(string $dia): string => date('d/m/y', strtotime($dia)), $dias));
$excelData[] = $headerRow;

foreach ($alunos as $index => $aluno) {
    $rowData = [$index + 1, $aluno];
    if ($turmaInterval) {
        $linhaAluno = null;
        foreach ($data as $item) {
            if ((string)($item['Aluno'] ?? '') === $aluno) {
                $linhaAluno = $item;
                break;
            }
        }
        $rowData[] = (string)($linhaAluno['NomeTurma'] ?? '');
    }

    $totalP = 0;
    $totalF = 0;
    $totalFJ = 0;
    foreach ($dias as $dia) {
        $status = $tabela[$aluno][$dia] ?? 'NA';
        if ($status === 'P') {
            $totalP++;
        } elseif ($status === 'F') {
            $totalF++;
        } elseif ($status === 'FJ') {
            $totalFJ++;
        }
    }

    $rowData[] = $totalP;
    $rowData[] = $totalF;
    $rowData[] = $totalFJ;
    $rowData[] = $totalP + $totalF + $totalFJ;

    foreach ($dias as $dia) {
        $rowData[] = $tabela[$aluno][$dia] ?? 'NA';
    }

    $excelData[] = $rowData;
}

$excelData[] = [''];
$excelData[] = ['Obs.: P = Presenca; F = Falta; FJ = Falta Justificada; e NA = Nao se Aplica.'];
echo json_encode($excelData);





