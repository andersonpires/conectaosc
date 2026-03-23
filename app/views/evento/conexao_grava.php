<?php

// Legacy mysqli connection used by legacy PDF/event scripts.
$host = (string)($_ENV['DB_HOST'] ?? '');
$name = (string)($_ENV['DB_NAME'] ?? '');
$user = (string)($_ENV['DB_USER'] ?? '');
$pass = (string)($_ENV['DB_PASS'] ?? '');
$port = (int)($_ENV['DB_PORT'] ?? 3306);

$conexao_grava = @new mysqli($host, $user, $pass, $name, $port);
if ($conexao_grava->connect_error) {
    die('Conexão falhou: ' . $conexao_grava->connect_error);
}
$conexao_grava->set_charset('utf8mb4');


