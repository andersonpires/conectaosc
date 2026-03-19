<?php
// Definir variaveis de ambiente padrao se nao existirem
if (!isset($_ENV['DB_HOST'])) {
    $_ENV['DB_HOST'] = '162.241.2.214';
}
if (!isset($_ENV['DB_NAME'])) {
    $_ENV['DB_NAME'] = 'mwtech63_matricula';
}
if (!isset($_ENV['DB_USER'])) {
    $_ENV['DB_USER'] = 'mwtech63_admin_matricula';
}
if (!isset($_ENV['DB_PASS'])) {
    $_ENV['DB_PASS'] = 'Iteva@100';
}
if (!isset($_ENV['DB_PORT'])) {
    $_ENV['DB_PORT'] = '3306';
}

$servername1 = $_ENV['DB_HOST'];
$username1 = $_ENV['DB_USER'];
$password1 = $_ENV['DB_PASS'];
$dbname1 = $_ENV['DB_NAME'];
$port1 = (int) $_ENV['DB_PORT'];

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
    die('Conexao falhou: ' . $e->getMessage());
}
