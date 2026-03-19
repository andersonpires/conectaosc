<?php
declare(strict_types=1);

namespace BackEnd\Repositories;

use PDO;
use RuntimeException;

class TurmaPlanoCursoRepository
{
    private ?PDO $connection = null;

    public function pdo(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        $root = dirname(__DIR__, 2);
        require $root . '/api/conectabd/conexao.php';

        if (!isset($pdo) || !$pdo instanceof PDO) {
            throw new RuntimeException('Database connection unavailable');
        }

        $this->connection = $pdo;
        return $this->connection;
    }

    public function existsTurma(int $idTurma): bool
    {
        $stmt = $this->pdo()->prepare("SELECT 1 FROM tbTurma WHERE IdTurma = ? LIMIT 1");
        $stmt->execute([$idTurma]);
        return (bool) $stmt->fetchColumn();
    }

    public function existsCurso(int $idCurso): bool
    {
        $stmt = $this->pdo()->prepare("SELECT 1 FROM tbCurso WHERE IdCurso = ? LIMIT 1");
        $stmt->execute([$idCurso]);
        return (bool) $stmt->fetchColumn();
    }

    public function findPlanoById(int $idPlanoCurso): ?array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT IdPlanoCurso, IdCurso, NomePlano, Versao
             FROM tb_plano_curso
             WHERE IdPlanoCurso = ?
               AND Habilitado = 1
             LIMIT 1"
        );
        $stmt->execute([$idPlanoCurso]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findTurmaById(int $idTurma): ?array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT IdTurma, IdCurso, NomeTurma
             FROM tbTurma
             WHERE IdTurma = ?
             LIMIT 1"
        );
        $stmt->execute([$idTurma]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getActiveByTurma(int $idTurma): ?array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT tpc.*, p.NomePlano, p.Versao, p.IdCurso, c.NomeCurso
             FROM tb_turma_plano_curso tpc
             INNER JOIN tb_plano_curso p ON p.IdPlanoCurso = tpc.IdPlanoCurso
             INNER JOIN tbCurso c ON c.IdCurso = p.IdCurso
             WHERE tpc.IdTurma = ?
               AND tpc.Habilitado = 1
             ORDER BY tpc.IdTurmaPlanoCurso DESC
             LIMIT 1"
        );
        $stmt->execute([$idTurma]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function disableActiveByTurma(int $idTurma): void
    {
        $stmt = $this->pdo()->prepare(
            "UPDATE tb_turma_plano_curso
             SET Habilitado = 0
             WHERE IdTurma = ?
               AND Habilitado = 1"
        );
        $stmt->execute([$idTurma]);
    }

    public function updateActiveVinculo(int $idTurmaPlanoCurso, int $idPlanoCurso, int $idColaborador): void
    {
        $stmt = $this->pdo()->prepare(
            "UPDATE tb_turma_plano_curso
             SET IdPlanoCurso = ?,
                 VinculadoPor = ?,
                 DataVinculo = NOW()
             WHERE IdTurmaPlanoCurso = ?
               AND Habilitado = 1"
        );
        $stmt->execute([$idPlanoCurso, $idColaborador, $idTurmaPlanoCurso]);
    }

    public function clearCronogramaByTurmaPlano(int $idTurmaPlanoCurso): void
    {
        $stmtComentarios = $this->pdo()->prepare(
            "DELETE c
             FROM tb_cronograma_aula_comentario c
             INNER JOIN tb_cronograma_aula ca ON ca.IdCronogramaAula = c.IdCronogramaAula
             WHERE ca.IdTurmaPlanoCurso = ?"
        );
        $stmtComentarios->execute([$idTurmaPlanoCurso]);

        $stmtAnexos = $this->pdo()->prepare(
            "DELETE a
             FROM tb_cronograma_aula_anexo a
             INNER JOIN tb_cronograma_aula ca ON ca.IdCronogramaAula = a.IdCronogramaAula
             WHERE ca.IdTurmaPlanoCurso = ?"
        );
        $stmtAnexos->execute([$idTurmaPlanoCurso]);

        $stmtCronograma = $this->pdo()->prepare(
            "DELETE FROM tb_cronograma_aula
             WHERE IdTurmaPlanoCurso = ?"
        );
        $stmtCronograma->execute([$idTurmaPlanoCurso]);
    }

    public function createVinculo(int $idTurma, int $idPlanoCurso, int $idColaborador): int
    {
        $stmt = $this->pdo()->prepare(
            "INSERT INTO tb_turma_plano_curso
             (IdTurma, IdPlanoCurso, VinculadoPor, Habilitado)
             VALUES (?, ?, ?, 1)"
        );
        $stmt->execute([$idTurma, $idPlanoCurso, $idColaborador]);
        return (int) $this->pdo()->lastInsertId();
    }

    public function createPendenciasFromPlano(int $idTurmaPlanoCurso, int $idPlanoCurso): int
    {
        $stmt = $this->pdo()->prepare(
            "INSERT INTO tb_cronograma_aula (IdTurmaPlanoCurso, IdPlanoCursoAula, StatusExecucao)
             SELECT :IdTurmaPlanoCurso, a.IdPlanoCursoAula, 'pendente'
             FROM tb_plano_curso_aula a
             LEFT JOIN tb_cronograma_aula ca
                    ON ca.IdTurmaPlanoCurso = :IdTurmaPlanoCursoJoin
                   AND ca.IdPlanoCursoAula = a.IdPlanoCursoAula
             WHERE a.IdPlanoCurso = :IdPlanoCurso
               AND a.Habilitado = 1
               AND ca.IdCronogramaAula IS NULL
             ORDER BY a.OrdemAula ASC, a.IdPlanoCursoAula ASC"
        );
        $stmt->execute([
            ':IdTurmaPlanoCurso' => $idTurmaPlanoCurso,
            ':IdTurmaPlanoCursoJoin' => $idTurmaPlanoCurso,
            ':IdPlanoCurso' => $idPlanoCurso,
        ]);
        return $stmt->rowCount();
    }

    public function listCronogramaByTurma(int $idTurma): array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT ca.IdCronogramaAula, ca.DataAula, ca.HoraInicio, ca.HoraFim, ca.StatusExecucao,
                    ca.JustificativaAdiamento, ca.FeedbackProfessor, ca.Observacoes,
                    tpc.IdTurmaPlanoCurso, tpc.IdPlanoCurso,
                    a.IdPlanoCursoAula, a.OrdemAula, a.NomeAula, a.DuracaoMinutos, a.Categoria,
                    a.Recursos, a.Materiais, a.Observacoes AS ObservacoesPlano,
                    p.NomePlano, p.Versao
             FROM tb_turma_plano_curso tpc
             INNER JOIN tb_cronograma_aula ca ON ca.IdTurmaPlanoCurso = tpc.IdTurmaPlanoCurso
             INNER JOIN tb_plano_curso_aula a ON a.IdPlanoCursoAula = ca.IdPlanoCursoAula
             INNER JOIN tb_plano_curso p ON p.IdPlanoCurso = tpc.IdPlanoCurso
             WHERE tpc.IdTurma = ?
               AND tpc.Habilitado = 1
             ORDER BY
               CASE WHEN ca.DataAula IS NULL THEN 1 ELSE 0 END,
               ca.DataAula ASC,
               ca.HoraInicio ASC,
               ca.IdCronogramaAula ASC"
        );
        $stmt->execute([$idTurma]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listTurmasComPlanoByCurso(int $idCurso): array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT t.IdTurma, t.NomeTurma, t.Habilitado,
                    tpc.IdTurmaPlanoCurso, tpc.IdPlanoCurso, tpc.DataVinculo,
                    p.NomePlano, p.Versao
             FROM tbTurma t
             LEFT JOIN tb_turma_plano_curso tpc
                    ON tpc.IdTurma = t.IdTurma
                   AND tpc.Habilitado = 1
             LEFT JOIN tb_plano_curso p
                    ON p.IdPlanoCurso = tpc.IdPlanoCurso
             WHERE t.IdCurso = ?
             ORDER BY t.NomeTurma ASC"
        );
        $stmt->execute([$idCurso]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
