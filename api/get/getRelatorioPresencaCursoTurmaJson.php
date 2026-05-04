<?php
require_once __DIR__ . '/../../bootstrap/runtime.php';
$runtime = bootstrap_runtime();
$BASE_para_PATH = $runtime['base_para_path'];

session_regenerate_id(true);
header('Content-Type: application/json');

require_once $BASE_para_PATH . '/api/repositories/RelatorioRepository.php';
require_once $BASE_para_PATH . '/api/services/RelatorioService.php';

use BackEnd\Repositories\RelatorioRepository;
use BackEnd\Services\RelatorioService;

$service = new RelatorioService(new RelatorioRepository());
$dados = $service->presencaCursoTurma($_POST);
echo json_encode($dados['linhas'] ?? [], JSON_UNESCAPED_UNICODE);



