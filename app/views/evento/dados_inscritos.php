<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
header('Content-Type: application/json');

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

require_once $BASE_para_PATH . '/api/repositories/EventoRepository.php';
require_once $BASE_para_PATH . '/api/services/EventoService.php';

use BackEnd\Repositories\EventoRepository;
use BackEnd\Services\EventoService;

try {
    $service = new EventoService(new EventoRepository());
    echo json_encode($service->listInscritos(), JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}







