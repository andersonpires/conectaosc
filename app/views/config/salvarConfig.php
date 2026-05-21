<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header('Location: ' . rtrim((string) $BASE_para_URL, '/') . '/login/?redirect=' . $redirect_url);
    exit();
}

require_once $BASE_para_PATH . '/api/repositories/ConfigRepository.php';
require_once $BASE_para_PATH . '/api/services/ConfigService.php';

try {
    $service = new \BackEnd\Services\ConfigService(new \BackEnd\Repositories\ConfigRepository());
    $service->updateConfig($_POST);

    header('Location: ' . rtrim((string) $BASE_para_URL, '/') . '/configuracoes?msg=' . urlencode('Configurações atualizadas com sucesso!'));
    exit();
} catch (Throwable $e) {
    header('Location: ' . rtrim((string) $BASE_para_URL, '/') . '/configuracoes?erro=' . urlencode('Erro ao atualizar configuracoes: ' . $e->getMessage()));
    exit();
}





