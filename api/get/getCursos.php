<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header('Location: ' . rtrim((string) $BASE_para_URL, '/') . '/login/?redirect=' . $redirect_url);
    exit();
}

require_once $BASE_para_PATH . '/api/repositories/RelatorioRepository.php';
require_once $BASE_para_PATH . '/api/services/RelatorioService.php';

use BackEnd\Repositories\RelatorioRepository;
use BackEnd\Services\RelatorioService;

header('Content-Type: text/html; charset=utf-8');

$somenteAtivos = isset($_POST['somenteAtivos']) ? (int) $_POST['somenteAtivos'] : 1;
$service = new RelatorioService(new RelatorioRepository());
$options = $service->cursosOptions($somenteAtivos);

echo '<option value="">Selecione o Curso</option>';
foreach ($options as $row) {
    $value = (string) ($row['value'] ?? '');
    $label = (string) ($row['label'] ?? '');
    if (!is_numeric($value)) {
        continue;
    }

    echo '<option value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
}



