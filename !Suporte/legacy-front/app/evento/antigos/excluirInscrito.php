<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_path'];
$BASE_para_URL = $runtime['base_url'];

include 'conexao_grava.php';

$IdInscrito = $_POST['IdInscrito'];

$sql = "DELETE FROM `InscritosEvento` WHERE `IdInscrito` = ?";

// Prepare a consulta e vincule os par?metros
$stmt = $conexao_grava->prepare($sql);
$stmt->bind_param("i", $idInscrito);

// Defina os valores dos par?metros
$idInscrito = (int) $IdInscrito; // Converta para inteiro para seguran?a

// Execute a consulta
$stmt->execute();

// Feche a instru??o
$stmt->close();
$conexao_grava->close();
header('Location: listagemInscritos.php');


