<?php
$servername = "localhost";
$username = "mwtech63_admin_matricula";
$password = "Iteva@100";
$dbname = "mwtech63_matricula";
$port = 3306;

try {
    // Define o DSN (Data Source Name)
    $dsn = "mysql:host={$servername};port={$port};dbname={$dbname};charset=utf8";
    
    // Cria a instância PDO com as opções desejadas
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Erros como exceções
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch padrão como array associativo
        // PDO::ATTR_PERSISTENT      => true,                   // Opcional: conexão persistente
    ]);
} catch (PDOException $e) {
    die("Conexão falhou: " . $e->getMessage());
}
?>
