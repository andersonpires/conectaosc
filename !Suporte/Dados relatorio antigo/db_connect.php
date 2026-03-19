<?php
// db_connect.php

$servername = "108.167.188.85";
$username = "kriade18_cadface";
$password = "F@ce1991!";
$dbname = "kriade18_cadface";
$port = 3306;

// Criando a conexao
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Checando a conexao
if ($conn->connect_error) {
    die("Conexao falhou: " . $conn->connect_error);
}
?>
