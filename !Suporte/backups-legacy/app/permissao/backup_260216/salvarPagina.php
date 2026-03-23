<?php
// salvarPagina.php
session_start();
require_once $_SESSION['BASE_para_PATH'] . '/api/conectabd/conexao.php';

$nome = $_POST['NomePagina'] ? '';
$descricao = $_POST['DescricaoPagina'] ? '';
$tipoApp = $_POST['TipoApp'] ? '';
$arquivo = $_POST['ArquivoPHP'] ? '';
$id = $_POST['IdPagina'] ? null;

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE tbPaginas SET NomePagina = ?, DescricaoPagina = ?, TipoApp = ?, ArquivoPHP = ? WHERE IdPagina = ?");
        $stmt->execute([$nome, $descricao, $tipoApp, $arquivo, $id]);
        $msg = "Registro%20atualizado%20com%20sucesso!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO tbPaginas (NomePagina, DescricaoPagina, TipoApp, ArquivoPHP) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nome, $descricao, $tipoApp, $arquivo]);
        $msg = "Registro%20salvo%20com%20sucesso!";
    }
    header("Location: formPaginas.php?msg=$msg");
    exit();
} catch (Exception $e) {
    $erro = urlencode("Erro ao salvar a pÒ¡gina: " . $e->getMessage());
    header("Location: formPaginas.php?erro=$erro");
    exit();
}
