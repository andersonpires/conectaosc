<?php
declare(strict_types=1);

namespace BackEnd\Repositories;

use PDO;
use RuntimeException;

class PaginaRepository
{
    public function listarPaginas(): array
    {
        $stmt = $this->pdo()->query("SELECT IdPagina, NomePagina, DescricaoPagina, TipoApp, ArquivoPHP FROM tbPaginas ORDER BY NomePagina ASC");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function buscarPagina(int $id): ?array
    {
        $stmt = $this->pdo()->prepare("SELECT IdPagina, NomePagina, DescricaoPagina, TipoApp, ArquivoPHP FROM tbPaginas WHERE IdPagina = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function criarPagina(string $nome, string $descricao, string $tipoApp, string $arquivo): int
    {
        $pdo = $this->pdo();
        $stmt = $pdo->prepare("INSERT INTO tbPaginas (NomePagina, DescricaoPagina, TipoApp, ArquivoPHP) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nome, $descricao, $tipoApp, $arquivo]);
        return (int)$pdo->lastInsertId();
    }

    public function atualizarPagina(int $id, string $nome, string $descricao, string $tipoApp, string $arquivo): bool
    {
        $stmt = $this->pdo()->prepare("UPDATE tbPaginas SET NomePagina = ?, DescricaoPagina = ?, TipoApp = ?, ArquivoPHP = ? WHERE IdPagina = ?");
        return $stmt->execute([$nome, $descricao, $tipoApp, $arquivo, $id]);
    }

    public function excluirPagina(int $id): bool
    {
        $stmt = $this->pdo()->prepare("DELETE FROM tbPaginas WHERE IdPagina = ?");
        $stmt->execute([$id]);
        return (bool)$stmt->rowCount();
    }

    private function pdo(): PDO
    {
        $root = dirname(__DIR__, 3);
        require $root . '/conectabd/conexao.php';
        if (!isset($pdo) || !$pdo instanceof PDO) {
            throw new RuntimeException('Database connection unavailable');
        }
        return $pdo;
    }
}


