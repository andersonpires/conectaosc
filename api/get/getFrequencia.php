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

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo nao permitido']);
    exit;
}

$service = new RelatorioService(new RelatorioRepository());
$data = $service->frequenciaMensal($_POST);

if ($data === []) {
    http_response_code(400);
    echo json_encode(['error' => 'Parametros invalidos ou nenhum dado encontrado']);
    exit;
}

echo json_encode($data);



