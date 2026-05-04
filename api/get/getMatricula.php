<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
header('Content-Type: application/json');

if (!isset($BASE_para_URL) || !isset($BASE_para_PATH)) {
    header('Location: ' . rtrim((string) $BASE_para_URL, '/') . '/login/');
    exit();
}

require_once $BASE_para_PATH . '/api/repositories/RelatorioRepository.php';
require_once $BASE_para_PATH . '/api/services/RelatorioService.php';

use BackEnd\Repositories\RelatorioRepository;
use BackEnd\Services\RelatorioService;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['curso'], $_POST['turma'])) {
    $service = new RelatorioService(new RelatorioRepository());
    echo json_encode($service->matriculados($_POST), JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Parametros invalidos'], JSON_UNESCAPED_UNICODE);



