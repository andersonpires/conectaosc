<?php
require_once dirname(__DIR__, 2) . '/bootstrap/runtime.php';

$dbConfig = bootstrap_database_config(dirname(__DIR__, 2));
$servername1 = $dbConfig['host'];
$username1 = $dbConfig['user'];
$password1 = $dbConfig['pass'];
$dbname1 = $dbConfig['name'];
$port1 = (int) $dbConfig['port'];

if ($dbname1 === '' || $username1 === '') {
    error_log('[conexao] Variáveis de ambiente do banco não configuradas corretamente.');
    if (!headers_sent()) {
        http_response_code(500);
    }
    die('Erro interno ao conectar ao banco de dados.');
}

try {
    $dsn = "mysql:host={$servername1};port={$port1};dbname={$dbname1};charset=utf8mb4";

    $pdo = new PDO($dsn, $username1, $password1, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ]);
    // Garante disponibilidade da conexao no escopo global para includes carregados dentro de metodos.
    $GLOBALS['pdo'] = $pdo;
} catch (PDOException $e) {
    error_log('[conexao] Falha ao conectar ao banco: ' . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
    }
    die('Erro interno ao conectar ao banco de dados.');
}
