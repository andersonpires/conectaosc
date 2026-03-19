<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_path'];
$BASE_para_URL = $runtime['base_url'];
require 'conexao_grava.php';
header('Content-Type: application/json');

$query = "SELECT NomeCompleto, Email, OrganizacaoSocial, Telefone FROM InscritosEvento ORDER BY NomeCompleto ASC";
$stmt = $conexao_grava->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

$inscritos = [];
while ($row = $result->fetch_assoc()) {
    $inscritos[] = $row;
}

$stmt->close();
$conexao_grava->close();

echo json_encode($inscritos);
?>


