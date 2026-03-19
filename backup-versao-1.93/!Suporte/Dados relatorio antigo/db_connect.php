<?php
// db_connect.php
<?php
$servername = "108.167.188.85";
$username = "kriade18_cadface";
$password = "F@ce1991!";
$dbname = "kriade18_cadface";
$port = 3306;

// Criando a conexão
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Checando a conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}
?>