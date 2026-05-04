<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];

if (!isset($BASE_para_URL) || !isset($BASE_para_PATH)) {
    header('Location: ' . rtrim((string) $BASE_para_URL, '/') . '/login/');
    exit();
}

require_once $BASE_para_PATH . '/api/repositories/RelatorioRepository.php';
require_once $BASE_para_PATH . '/api/services/RelatorioService.php';

use BackEnd\Repositories\RelatorioRepository;
use BackEnd\Services\RelatorioService;

header('Content-Type: text/html; charset=utf-8');

$idCurso = $_GET['IdCurso'] ?? '';
if ($idCurso === '') {
    echo '<option value="">Parametro IdCurso invalido</option>';
    exit;
}

$service = new RelatorioService(new RelatorioRepository());
$options = $service->turmasNomeOptions((string)$idCurso);

if ($options === []) {
    echo '<option value="">Nenhuma turma encontrada</option>';
    exit;
}

echo '<option value="">Selecione a turma</option>';
foreach ($options as $option) {
    $value = htmlspecialchars((string)$option['value'], ENT_QUOTES, 'UTF-8');
    $label = htmlspecialchars((string)$option['label'], ENT_QUOTES, 'UTF-8');
    echo "<option value=\"{$value}\">{$label}</option>";
}





