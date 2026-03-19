<?php
session_set_cookie_params(['httponly' => true]);
ini_set('session.gc_maxlifetime', 86400);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?redirect=$redirect_url");
    exit();
}

require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

$sqlPath = __DIR__ . '/20260216_add_especialidade_id_tbuser.sql';
if (!file_exists($sqlPath)) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Arquivo SQL nao encontrado: {$sqlPath}";
    exit;
}

$sql = file_get_contents($sqlPath);
if ($sql === false) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Falha ao ler o arquivo SQL.";
    exit;
}

try {
    $statements = preg_split('/;\s*[\r\n]+/', $sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement === '') {
            continue;
        }
        $pdo->exec($statement);
    }
    header('Content-Type: text/plain; charset=utf-8');
    echo "OK: coluna especialidade_id criada/atualizada em tbUser.";
} catch (Throwable $e) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Erro ao executar SQL: " . $e->getMessage();
}
