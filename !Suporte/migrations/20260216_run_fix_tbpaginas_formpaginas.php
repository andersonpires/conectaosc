<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_path'];
$BASE_para_URL = $runtime['base_url'];
/**
 * Executor da migration 20260216 - Garantir URL do cadastro de paginas
 * Execute via navegador: /conectaosc3/api/migrations/20260216_run_fix_tbpaginas_formpaginas.php
 */

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location: " . rtrim((string)($BASE_para_URL ?? ''), '/') . "/login/?redirect=$redirect_url");
    exit();
}

require_once $BASE_para_PATH . '/api/conectabd/conexao.php';

$sqlFile = __DIR__ . '/20260216_fix_tbpaginas_formpaginas.sql';
if (!file_exists($sqlFile)) {
    echo "Arquivo SQL nao encontrado: " . htmlspecialchars($sqlFile);
    exit();
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    echo "Erro ao ler o arquivo SQL.";
    exit();
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->rowCount();
    echo "OK: tbPaginas atualizada. Linhas afetadas: " . (int) $rows;
} catch (Throwable $e) {
    echo "Erro ao executar SQL: " . $e->getMessage();
}


