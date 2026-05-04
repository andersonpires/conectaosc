<?php
require_once __DIR__ . '/../../bootstrap/runtime.php';
$runtime = bootstrap_runtime();
$BASE_para_PATH = $runtime['base_para_path'];

session_regenerate_id(true);
require_once $BASE_para_PATH . '/api/repositories/RelatorioRepository.php';
require_once $BASE_para_PATH . '/api/services/RelatorioService.php';

use BackEnd\Repositories\RelatorioRepository;
use BackEnd\Services\RelatorioService;

$idCurso = isset($_POST['IdCurso']) ? (int)$_POST['IdCurso'] : 0;
if ($idCurso <= 0) {
    echo '';
    exit;
}

$service = new RelatorioService(new RelatorioRepository());
$options = $service->turmasOptions($idCurso, 0, 0);
foreach ($options as $option) {
    if ($option['value'] === '') {
        continue;
    }
    echo "<option value='" . htmlspecialchars((string)$option['value']) . "'>" . htmlspecialchars((string)$option['label']) . "</option>";
}



