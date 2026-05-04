<?php
require_once __DIR__ . '/../../bootstrap/runtime.php';
$runtime = bootstrap_runtime();
$BASE_para_PATH = $runtime['base_para_path'];

session_regenerate_id(true);
require_once $BASE_para_PATH . '/api/repositories/RelatorioRepository.php';
require_once $BASE_para_PATH . '/api/services/RelatorioService.php';

use BackEnd\Repositories\RelatorioRepository;
use BackEnd\Services\RelatorioService;

$service = new RelatorioService(new RelatorioRepository());
echo $service->presencaCursoTurmaHtml($_POST);



