<?php
declare(strict_types=1);

namespace BackEnd\Repositories;

use PDO;
use RuntimeException;

class SwotRepository
{
    public function listUsuariosAtivos(): array
    {
        $stmt = $this->pdo()->query("SELECT IdColaborador, Nome, Sobrenome FROM tbUser WHERE habilitado = 1 ORDER BY Nome ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listByUser(int $idColaborador): array
    {
        $stmt = $this->pdo()->prepare("SELECT * FROM tb_swot WHERE IdColaborador = ? ORDER BY DataCriacao DESC, IdSwot DESC");
        $stmt->execute([$idColaborador]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listForReport(?string $tema = null, mixed $idColaborador = null): array
    {
        $sql = "SELECT s.*, u.Nome, u.Sobrenome
                FROM tb_swot s
                JOIN tbUser u ON s.IdColaborador = u.IdColaborador
                WHERE 1=1";
        $params = [];

        if (!empty($tema) && $tema !== 'todos') {
            $sql .= " AND s.Tema = ?";
            $params[] = $tema;
        }
        if (!empty($idColaborador) && (string)$idColaborador !== 'todos') {
            $sql .= " AND s.IdColaborador = ?";
            $params[] = (int)$idColaborador;
        }

        $sql .= " ORDER BY s.DataCriacao DESC, s.IdSwot DESC";
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->pdo()->prepare("SELECT * FROM tb_swot WHERE IdSwot = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function validColumns(): array
    {
        $stmt = $this->pdo()->query("DESCRIBE tb_swot");
        $cols = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cols[] = (string)$row['Field'];
        }
        return $cols;
    }

    public function create(array $dados): bool
    {
        $colunasValidas = $this->validColumns();
        $dadosFiltrados = array_filter($dados, static fn(string $key): bool => in_array($key, $colunasValidas, true), ARRAY_FILTER_USE_KEY);
        if ($dadosFiltrados === []) {
            return false;
        }

        $campos = array_keys($dadosFiltrados);
        $valores = array_map(static fn($valor) => is_array($valor) ? implode(',', $valor) : $valor, array_values($dadosFiltrados));

        $colunas = implode(',', array_map(static fn(string $campo): string => "`{$campo}`", $campos));
        $placeholders = implode(',', array_fill(0, count($campos), '?'));
        $stmt = $this->pdo()->prepare("INSERT INTO tb_swot ({$colunas}) VALUES ({$placeholders})");
        return $stmt->execute($valores);
    }

    public function update(array $dados): bool
    {
        $id = (int)($dados['IdSwot'] ?? 0);
        if ($id <= 0) {
            return false;
        }

        $colunasValidas = $this->validColumns();
        $camposAtualizar = [];
        $valores = [];
        foreach ($dados as $coluna => $valor) {
            if ($coluna !== 'IdSwot' && in_array((string)$coluna, $colunasValidas, true)) {
                $camposAtualizar[] = "`{$coluna}` = ?";
                $valores[] = is_array($valor) ? implode(',', $valor) : $valor;
            }
        }

        if ($camposAtualizar === []) {
            return true;
        }

        $valores[] = $id;
        $stmt = $this->pdo()->prepare("UPDATE tb_swot SET " . implode(', ', $camposAtualizar) . " WHERE IdSwot = ?");
        return $stmt->execute($valores);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo()->prepare("DELETE FROM tb_swot WHERE IdSwot = ?");
        return $stmt->execute([$id]);
    }

    private function pdo(): PDO
    {
        $root = dirname(__DIR__, 2);
        require $root . '/api/conectabd/conexao.php';
        if (!isset($pdo) || !$pdo instanceof PDO) {
            throw new RuntimeException('Database connection unavailable');
        }
        return $pdo;
    }
}

