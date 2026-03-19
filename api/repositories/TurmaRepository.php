<?php
declare(strict_types=1);

namespace BackEnd\Repositories;

use PDO;
use RuntimeException;

class TurmaRepository
{
    public function listAll(): array
    {
        $pdo = $this->pdo();
        $sql = "SELECT t.*, c.NomeCurso,
                       IFNULL((
                           SELECT COUNT(DISTINCT m.IdUsuario)
                           FROM tbMatricula m
                           WHERE m.IdCurso = t.IdCurso
                             AND m.IdTurma = t.IdTurma
                             AND m.Habilitado = 1
                       ), 0) AS QtdAlunos
                FROM tbTurma t
                LEFT JOIN tbCurso c ON c.IdCurso = t.IdCurso
                ORDER BY t.NomeTurma ASC";

        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listByCurso(int $idCurso, int $somenteAtivos = 1): array
    {
        $pdo = $this->pdo();
        $sql = "SELECT t.IdTurma, t.NomeTurma, t.Habilitado
                FROM tbTurma t
                INNER JOIN tbCurso c ON c.IdCurso = t.IdCurso
                WHERE t.IdCurso = :IdCurso";
        if ($somenteAtivos === 1) {
            $sql .= " AND t.Habilitado = 1 AND c.Habilitado = 1";
        }
        $sql .= " ORDER BY t.NomeTurma ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':IdCurso' => $idCurso]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createMany(array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        $pdo = $this->pdo();
        $stmt = $pdo->prepare(
            "INSERT INTO tbTurma (NomeTurma, IdCurso, Municipio, Local, Obs, MaxMatriculas)
             VALUES (:NomeTurma, :IdCurso, :Municipio, :Local, :Obs, :MaxMatriculas)"
        );

        $count = 0;
        foreach ($rows as $row) {
            $stmt->execute($row);
            $count += $stmt->rowCount();
        }

        return $count;
    }

    public function update(int $idTurma, array $data): bool
    {
        $pdo = $this->pdo();
        $stmt = $pdo->prepare(
            "UPDATE tbTurma
             SET NomeTurma = :NomeTurma,
                 IdCurso = :IdCurso,
                 Municipio = :Municipio,
                 Local = :Local,
                 Habilitado = :Habilitado,
                 Obs = :Obs,
                 MaxMatriculas = :MaxMatriculas
             WHERE IdTurma = :IdTurma"
        );
        $data['IdTurma'] = $idTurma;
        $stmt->execute($data);

        return $stmt->rowCount() > 0;
    }

    public function delete(int $idTurma): bool
    {
        $pdo = $this->pdo();
        $stmt = $pdo->prepare("DELETE FROM tbTurma WHERE IdTurma = ?");
        $stmt->execute([$idTurma]);

        return $stmt->rowCount() > 0;
    }

    public function countDependenciasChamada(int $idTurma): int
    {
        $stmt = $this->pdo()->prepare("SELECT COUNT(*) FROM tbChamada WHERE IdTurma = ?");
        $stmt->execute([$idTurma]);
        return (int)$stmt->fetchColumn();
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

