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
    // Define o DSN (Data Source Name)
    $dsn = "mysql:host={$servername1};port={$port1};dbname={$dbname1};charset=utf8";
    
    // Cria a instância PDO com as opções desejadas
    $pdo = new PDO($dsn, $username1, $password1, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Erros como exceções
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch padrão como array associativo
        // PDO::ATTR_PERSISTENT      => true,                   // Opcional: conexão persistente
    ]);
} catch (PDOException $e) {
    die("Conexão falhou: " . $e->getMessage());
}
?>
