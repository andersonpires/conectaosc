<?php
include __DIR__ . '/../temp/loginserver.php';

// Criar conexão

$conexao_grava = new mysqli($servername, $username, $password, $dbname, $port);

// Verificar conexão
if ($conexao_grava->connect_error) {
    die("Conexão falhou: " . $conexao_grava->connect_error);
}
?>
