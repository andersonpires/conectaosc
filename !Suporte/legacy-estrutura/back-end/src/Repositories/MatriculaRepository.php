<?php
declare(strict_types=1);

namespace BackEnd\Repositories;

use PDO;
use RuntimeException;

class MatriculaRepository
{
    public function pdo(): PDO
    {
        $root = dirname(__DIR__, 3);
        require $root . '/conectabd/conexao.php';

        if (!isset($pdo) || !$pdo instanceof PDO) {
            throw new RuntimeException('Database connection unavailable');
        }

        return $pdo;
    }

    public function update(int $idMatricula, int $idCurso, int $idTurma, string $dataMatricula): bool
    {
        $stmt = $this->pdo()->prepare(
            "UPDATE tbMatricula
             SET IdCurso = ?, IdTurma = ?, vData = ?
             WHERE IdMatricula = ?"
        );
        $stmt->execute([$idCurso, $idTurma, $dataMatricula, $idMatricula]);

        return $stmt->rowCount() > 0;
    }

    public function listByTurma(int $idTurma, bool $somenteAtivas = true): array
    {
        $sql = "SELECT a.IdUsuario, a.Nome AS NomeAluno, a.Foto, a.Nascimento, a.WhatsApp, a.Telefone,
                       c.NomeCurso, t.NomeTurma, t.IdTurma, t.Local, t.Municipio, m.IdMatricula, m.vData, c.IdCurso, m.Habilitado
                FROM tbMatricula m
                JOIN tbAluno a ON m.IdUsuario = a.IdUsuario
                JOIN tbCurso c ON m.IdCurso = c.IdCurso
                JOIN tbTurma t ON m.IdTurma = t.IdTurma
                WHERE t.IdTurma = ?";
        if ($somenteAtivas) {
            $sql .= " AND m.Habilitado = 1";
        }
        $sql .= " ORDER BY NomeAluno";

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute([$idTurma]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function ativar(int $idMatricula, int $userId): bool
    {
        $dataAtual = date('Y-m-d');
        $stmt = $this->pdo()->prepare(
            "UPDATE tbMatricula
             SET Habilitado = 1, DtMudaHabilitado = ?, UserMudaHabilitado = ?
             WHERE IdMatricula = ?"
        );
        $stmt->execute([$dataAtual, $userId, $idMatricula]);
        return $stmt->rowCount() > 0;
    }

    public function softDelete(int $idMatricula, int $idCurso, int $idTurma, int $userId): bool
    {
        $dataHoje = date('Y-m-d');
        $stmt = $this->pdo()->prepare(
            "UPDATE tbMatricula
             SET Habilitado = 0, DtMudaHabilitado = ?, UserMudaHabilitado = ?
             WHERE IdMatricula = ? AND IdCurso = ? AND IdTurma = ?"
        );
        $stmt->execute([$dataHoje, $userId, $idMatricula, $idCurso, $idTurma]);

        return $stmt->rowCount() > 0;
    }
}

