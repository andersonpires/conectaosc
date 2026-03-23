<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_path'];
$BASE_para_URL = $runtime['base_url'];
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', '86400');
}

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI'] ? '');
    header('Location: ' . rtrim((string)($BASE_para_URL ? ''), '/') . '/login/?redirect=' . $redirect_url);
    exit();
}

require_once $BASE_para_PATH . '/api/conectabd/conexao.php';

$sqlPath = __DIR__ . '/20260224_create_tb_cron_contrato.sql';
if (!is_file($sqlPath)) {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Arquivo SQL não encontrado: ' . $sqlPath;
    exit;
}

$sql = file_get_contents($sqlPath);
if ($sql === false) {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Falha ao ler o arquivo SQL.';
    exit;
}

try {
    $pdo->exec($sql);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'OK: tabela tb_Cron_Contrato criada/atualizada.';
} catch (Throwable $e) {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Erro ao executar SQL: ' . $e->getMessage();
}