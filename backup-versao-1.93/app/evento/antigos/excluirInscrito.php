<?php

include 'conexao_grava.php';

$IdInscrito = $_POST['IdInscrito'];

$sql = "DELETE FROM `InscritosEvento` WHERE `IdInscrito` = ?";

// Prepare a consulta e vincule os parâmetros
$stmt = $conexao_grava->prepare($sql);
$stmt->bind_param("i", $idInscrito);

// Defina os valores dos parâmetros
$idInscrito = (int) $IdInscrito; // Converta para inteiro para segurança

// Execute a consulta
$stmt->execute();

// Feche a instrução
$stmt->close();
$conexao_grava->close();
header('Location: listagemInscritos.php');
