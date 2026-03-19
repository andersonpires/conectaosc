<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_path'];
$BASE_para_URL = $runtime['base_url'];
ini_set('session.gc_maxlifetime', 86400);

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location: " . rtrim((string)($BASE_para_URL ?? ''), '/') . "/login/?redirect=$redirect_url");
    exit();
}

require_once $BASE_para_PATH . '/api/conectabd/conexao.php';

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

