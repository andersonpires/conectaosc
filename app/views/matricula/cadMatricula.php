<?php $runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    header("Location: " . rtrim((string)$BASE_para_URL, '/') . "/login/?erro=" . urlencode("Ocorreu um erro! Talvez você tenha perdido sua última ação. Verifique."));
    exit();
}

require_once $BASE_para_PATH . '/api/repositories/MatriculaRepository.php';
require_once $BASE_para_PATH . '/api/services/MatriculaService.php';

$service = new \BackEnd\Services\MatriculaService(new \BackEnd\Repositories\MatriculaRepository());
$result = $service->processarLote($_POST);

if (($result['ok'] ?? false) !== true) {
    header('Location: ' . $BASE_para_URL . '/beneficiarios/lista?erro=' . urlencode($result['erro'] ?? 'Erro ao processar matriculas') . '&matricula=1');
    exit();
}

$_SESSION['resultado_matriculas_lote'] = $result['resultado'];
header('Location: ' . $BASE_para_URL . '/matriculas/resultado');
exit();
?>








