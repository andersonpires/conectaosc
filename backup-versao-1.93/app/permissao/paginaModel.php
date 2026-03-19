<?php
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

class PaginaModel
{
    public static function listarPaginas(): array
    {
        global $pdo;
        $stmt = $pdo->query("SELECT IdPagina, NomePagina, DescricaoPagina, TipoApp, ArquivoPHP FROM tbPaginas ORDER BY NomePagina ASC");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public static function buscarPagina(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT IdPagina, NomePagina, DescricaoPagina, TipoApp, ArquivoPHP FROM tbPaginas WHERE IdPagina = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function criarPagina(string $nome, string $descricao, string $tipoApp, string $arquivo): int
    {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO tbPaginas (NomePagina, DescricaoPagina, TipoApp, ArquivoPHP) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nome, $descricao, $tipoApp, $arquivo]);
        return (int) $pdo->lastInsertId();
    }

    public static function atualizarPagina(int $id, string $nome, string $descricao, string $tipoApp, string $arquivo): bool
    {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE tbPaginas SET NomePagina = ?, DescricaoPagina = ?, TipoApp = ?, ArquivoPHP = ? WHERE IdPagina = ?");
        return $stmt->execute([$nome, $descricao, $tipoApp, $arquivo, $id]);
    }

    public static function excluirPagina(int $id): bool
    {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM tbPaginas WHERE IdPagina = ?");
        $stmt->execute([$id]);
        return (bool) $stmt->rowCount();
    }
}
