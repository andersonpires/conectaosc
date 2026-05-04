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

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['IdCurso']) || empty($_POST['IdCurso'])) {
    http_response_code(400);
    echo '<option value="">Curso invalido</option>';
    exit;
}

$IdCurso = (int) $_POST['IdCurso'];
$somenteAtivos = isset($_POST['somenteAtivos']) ? (int) $_POST['somenteAtivos'] : 1;
$incluirTodas = isset($_POST['todasTurmas']) && (int) $_POST['todasTurmas'] === 0 ? 0 : 1;

$service = new RelatorioService(new RelatorioRepository());
$options = $service->turmasOptions($IdCurso, $somenteAtivos, $incluirTodas);

foreach ($options as $option) {
    echo '<option value="' . htmlspecialchars((string) $option['value'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars((string) $option['label'], ENT_QUOTES, 'UTF-8') . '</option>';
}



