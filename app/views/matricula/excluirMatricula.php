<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location: " . rtrim((string)$BASE_para_URL, '/') . "/login/?erro=" . urlencode("Ocorreu um erro! Talvez você tenha perdido sua última ação. Verifique."));
    exit();
}

require_once $BASE_para_PATH . '/api/repositories/MatriculaRepository.php';
require_once $BASE_para_PATH . '/api/services/MatriculaService.php';

$service = new \BackEnd\Services\MatriculaService(new \BackEnd\Repositories\MatriculaRepository());
$deleted = $service->softDelete($_POST, (int)($_SESSION['Cod'] ?? 0));

$idTurma = isset($_POST['idturma']) ? (int)$_POST['idturma'] : 0;
$base = rtrim((string)$BASE_para_URL, '/');
if ($deleted) {
    $resultado = 'Registro excluído com sucesso';
    header('Location: ' . $base . '/matriculas/turma/?msg=' . urlencode($resultado) . '&turma=' . $idTurma);
} else {
    $resultado = 'Matrícula sem alteração.';
    header('Location: ' . $base . '/matriculas/turma/?erro=' . urlencode($resultado) . '&turma=' . $idTurma);
}
exit();
?>








