<?php
/**
 * Executor da migration 20260216 - Garantir URL do cadastro de paginas
 * Execute via navegador: /conectaosc/app/migrations/20260216_run_fix_tbpaginas_formpaginas.php
 */
session_set_cookie_params(['httponly' => true]);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?redirect=$redirect_url");
    exit();
}

require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

$sqlFile = __DIR__ . '/20260216_fix_tbpaginas_formpaginas.sql';
if (!file_exists($sqlFile)) {
    echo "Arquivo SQL não encontrado: " . htmlspecialchars($sqlFile);
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
