<?php

// Legacy mysqli connection kept for old quiz/event scripts.
$host = (string)($_ENV['DB_HOST'] ?? '162.241.2.214');
$name = (string)($_ENV['DB_NAME'] ?? 'mwtech63_matricula');
$user = (string)($_ENV['DB_USER'] ?? 'mwtech63_admin_matricula');
$pass = (string)($_ENV['DB_PASS'] ?? 'Iteva@100');
$port = (int)($_ENV['DB_PORT'] ?? 3306);

$conexao_grava = @new mysqli($host, $user, $pass, $name, $port);
if ($conexao_grava->connect_error) {
    die('Conexão falhou: ' . $conexao_grava->connect_error);
}
$conexao_grava->set_charset('utf8mb4');


