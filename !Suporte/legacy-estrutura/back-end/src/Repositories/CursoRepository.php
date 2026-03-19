<?php
declare(strict_types=1);

namespace BackEnd\Repositories;

use PDO;
use PDOException;
use RuntimeException;

class CursoRepository
{
    public function listAll(): array
    {
        $pdo = $this->pdo();
        $sql = "SELECT c.*, IFNULL(p.nomeProjeto, '-') AS nomeProjeto, IFNULL(c.Programa, '-') AS Programa
                FROM tbCurso c
                LEFT JOIN tbProjeto p ON c.IdProjeto = p.IdProjeto
                ORDER BY c.NomeCurso ASC";

        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listAtivos(): array
    {
        $pdo = $this->pdo();
        $stmt = $pdo->query("SELECT IdCurso, NomeCurso FROM tbCurso WHERE Habilitado = 1 ORDER BY NomeCurso ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $pdo = $this->pdo();
        $sql = "INSERT INTO tbCurso
                (NomeCurso, Duracao, Tipo, CargaHoraria, Termo, IdProjeto, Programa, Informacoes, Habilitado, IdadeMin, IdadeMax)
                VALUES
                (:NomeCurso, :Duracao, :Tipo, :CargaHoraria, :Termo, :IdProjeto, :Programa, :Informacoes, :Habilitado, :IdadeMin, :IdadeMax)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        return (int) $pdo->lastInsertId();
    }

    public function update(int $idCurso, array $data): bool
    {
        $pdo = $this->pdo();
        $sql = "UPDATE tbCurso
                SET NomeCurso = :NomeCurso,
                    Duracao = :Duracao,
                    Tipo = :Tipo,
                    CargaHoraria = :CargaHoraria,
                    Termo = :Termo,
                    IdProjeto = :IdProjeto,
                    Programa = :Programa,
                    Informacoes = :Informacoes,
                    Habilitado = :Habilitado,
                    IdadeMin = :IdadeMin,
                    IdadeMax = :IdadeMax
                WHERE IdCurso = :IdCurso";
        $stmt = $pdo->prepare($sql);
        $data['IdCurso'] = $idCurso;
        $stmt->execute($data);

        return $stmt->rowCount() > 0;
    }

    public function delete(int $idCurso): bool
    {
        $pdo = $this->pdo();
        $stmt = $pdo->prepare("DELETE FROM tbCurso WHERE IdCurso = ?");
        $stmt->execute([$idCurso]);

        return $stmt->rowCount() > 0;
    }

    public function listProjetos(): array
    {
        $pdo = $this->pdo();
        $stmt = $pdo->query("SELECT IdProjeto, nomeProjeto FROM tbProjeto ORDER BY nomeProjeto ASC");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countDependenciasMatricula(int $idCurso): int
    {
        $stmt = $this->pdo()->prepare("SELECT COUNT(*) FROM tbMatricula WHERE IdCurso = ?");
        $stmt->execute([$idCurso]);
        return (int)$stmt->fetchColumn();
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

