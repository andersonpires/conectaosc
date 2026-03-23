<?php
declare(strict_types=1);

namespace BackEnd\Repositories;

use PDO;
use RuntimeException;

class PlanoCursoRepository
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

    public function listAll(int $idCurso = 0): array
    {
        $pdo = $this->pdo();
        $sql = "SELECT p.*, c.NomeCurso,
                       (SELECT COUNT(*) FROM tb_plano_curso_aula a WHERE a.IdPlanoCurso = p.IdPlanoCurso AND a.Habilitado = 1) AS QtdAulas,
                       (SELECT COUNT(*) FROM tb_turma_plano_curso tpc WHERE tpc.IdPlanoCurso = p.IdPlanoCurso AND tpc.Habilitado = 1) AS QtdTurmasVinculadas
                FROM tb_plano_curso p
                INNER JOIN tbCurso c ON c.IdCurso = p.IdCurso
                WHERE p.Habilitado = 1";
        $params = [];
        if ($idCurso > 0) {
            $sql .= " AND p.IdCurso = :IdCurso";
            $params[':IdCurso'] = $idCurso;
        }
        $sql .= " ORDER BY p.DataCriacao DESC, p.IdPlanoCurso DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listResumoCursos(string $estado = 'todos'): array
    {
        $pdo = $this->pdo();
        $estado = strtolower(trim($estado));
        if (!in_array($estado, ['todos', 'com', 'sem'], true)) {
            $estado = 'todos';
        }

        $sql = "SELECT c.IdCurso,
                       c.NomeCurso,
                       COUNT(DISTINCT p.IdPlanoCurso) AS QtdPlanos,
                       COUNT(DISTINCT CASE WHEN pAtivo.IdPlanoCurso IS NOT NULL THEN t.IdTurma END) AS QtdTurmasComPlano
                FROM tbCurso c
                LEFT JOIN tb_plano_curso p
                       ON p.IdCurso = c.IdCurso
                      AND p.Habilitado = 1
                LEFT JOIN tbTurma t
                       ON t.IdCurso = c.IdCurso
                LEFT JOIN tb_turma_plano_curso tpc
                       ON tpc.IdTurma = t.IdTurma
                      AND tpc.Habilitado = 1
                LEFT JOIN tb_plano_curso pAtivo
                       ON pAtivo.IdPlanoCurso = tpc.IdPlanoCurso
                      AND pAtivo.Habilitado = 1
                GROUP BY c.IdCurso, c.NomeCurso";

        if ($estado === 'com') {
            $sql .= " HAVING COUNT(DISTINCT p.IdPlanoCurso) > 0";
        } elseif ($estado === 'sem') {
            $sql .= " HAVING COUNT(DISTINCT p.IdPlanoCurso) = 0";
        }

        $sql .= " ORDER BY c.NomeCurso ASC";

        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $idPlanoCurso): ?array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT p.*, c.NomeCurso
             FROM tb_plano_curso p
             INNER JOIN tbCurso c ON c.IdCurso = p.IdCurso
             WHERE p.IdPlanoCurso = ? AND p.Habilitado = 1
             LIMIT 1"
        );
        $stmt->execute([$idPlanoCurso]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo()->prepare(
            "INSERT INTO tb_plano_curso
             (IdCurso, NomePlano, Versao, Descricao, Habilitado, CriadoPor, AtualizadoPor)
             VALUES
             (:IdCurso, :NomePlano, :Versao, :Descricao, 1, :CriadoPor, :AtualizadoPor)"
        );
        $stmt->execute($data);
        return (int) $this->pdo()->lastInsertId();
    }

    public function update(int $idPlanoCurso, array $data): bool
    {
        $data['IdPlanoCurso'] = $idPlanoCurso;
        $stmt = $this->pdo()->prepare(
            "UPDATE tb_plano_curso
             SET NomePlano = :NomePlano,
                 Versao = :Versao,
                 Descricao = :Descricao,
                 AtualizadoPor = :AtualizadoPor
             WHERE IdPlanoCurso = :IdPlanoCurso
               AND Habilitado = 1"
        );
        $stmt->execute($data);
        return $stmt->rowCount() > 0;
    }

    public function softDelete(int $idPlanoCurso, int $idColaborador): bool
    {
        $stmt = $this->pdo()->prepare(
            "UPDATE tb_plano_curso
             SET Habilitado = 0,
                 AtualizadoPor = ?
             WHERE IdPlanoCurso = ?
               AND Habilitado = 1"
        );
        $stmt->execute([$idColaborador, $idPlanoCurso]);
        return $stmt->rowCount() > 0;
    }

    public function hardDelete(int $idPlanoCurso): bool
    {
        $stmt = $this->pdo()->prepare(
            "DELETE FROM tb_plano_curso
             WHERE IdPlanoCurso = ?"
        );
        $stmt->execute([$idPlanoCurso]);
        return $stmt->rowCount() > 0;
    }

    public function countVinculosAtivos(int $idPlanoCurso): int
    {
        $stmt = $this->pdo()->prepare(
            "SELECT COUNT(*)
             FROM tb_turma_plano_curso
             WHERE IdPlanoCurso = ?
               AND Habilitado = 1"
        );
        $stmt->execute([$idPlanoCurso]);
        return (int) $stmt->fetchColumn();
    }

    public function deleteImpact(int $idPlanoCurso): array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT
                (SELECT COUNT(*) FROM tb_plano_curso_aula a WHERE a.IdPlanoCurso = :IdPlanoCurso) AS QtdAulas,
                (SELECT COUNT(*) FROM tb_turma_plano_curso tpc WHERE tpc.IdPlanoCurso = :IdPlanoCurso) AS QtdVinculos,
                (SELECT COUNT(*) FROM tb_turma_plano_curso tpc WHERE tpc.IdPlanoCurso = :IdPlanoCurso AND tpc.Habilitado = 1) AS QtdVinculosAtivos,
                (SELECT COUNT(DISTINCT tpc.IdTurma) FROM tb_turma_plano_curso tpc WHERE tpc.IdPlanoCurso = :IdPlanoCurso) AS QtdTurmas,
                (SELECT COUNT(*)
                 FROM tb_cronograma_aula ca
                 INNER JOIN tb_turma_plano_curso tpc2 ON tpc2.IdTurmaPlanoCurso = ca.IdTurmaPlanoCurso
                 WHERE tpc2.IdPlanoCurso = :IdPlanoCurso) AS QtdCronogramas,
                (SELECT COUNT(*)
                 FROM tb_cronograma_aula ca
                 INNER JOIN tb_turma_plano_curso tpc3 ON tpc3.IdTurmaPlanoCurso = ca.IdTurmaPlanoCurso
                 WHERE tpc3.IdPlanoCurso = :IdPlanoCurso
                   AND ca.DataAula IS NOT NULL
                   AND ca.HoraInicio IS NOT NULL) AS QtdCronogramasAgendados"
        );
        $stmt->execute(['IdPlanoCurso' => $idPlanoCurso]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: [
            'QtdAulas' => 0,
            'QtdVinculos' => 0,
            'QtdVinculosAtivos' => 0,
            'QtdTurmas' => 0,
            'QtdCronogramas' => 0,
            'QtdCronogramasAgendados' => 0,
        ];
    }

    public function listAulas(int $idPlanoCurso): array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT *
             FROM tb_plano_curso_aula
             WHERE IdPlanoCurso = ?
               AND Habilitado = 1
             ORDER BY OrdemAula ASC, IdPlanoCursoAula ASC"
        );
        $stmt->execute([$idPlanoCurso]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAulaById(int $idPlanoCursoAula): ?array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT *
             FROM tb_plano_curso_aula
             WHERE IdPlanoCursoAula = ?
               AND Habilitado = 1
             LIMIT 1"
        );
        $stmt->execute([$idPlanoCursoAula]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function nextOrdemAula(int $idPlanoCurso): int
    {
        $stmt = $this->pdo()->prepare(
            "SELECT COALESCE(MAX(OrdemAula), 0) + 1
             FROM tb_plano_curso_aula
             WHERE IdPlanoCurso = ?"
        );
        $stmt->execute([$idPlanoCurso]);
        return (int) $stmt->fetchColumn();
    }

    public function normalizeDisabledOrdensByPlano(int $idPlanoCurso): int
    {
        $stmt = $this->pdo()->prepare(
            "UPDATE tb_plano_curso_aula
             SET OrdemAula = -IdPlanoCursoAula
             WHERE IdPlanoCurso = ?
               AND Habilitado = 0
               AND OrdemAula > 0"
        );
        $stmt->execute([$idPlanoCurso]);
        return $stmt->rowCount();
    }

    public function createAula(array $data): int
    {
        $stmt = $this->pdo()->prepare(
            "INSERT INTO tb_plano_curso_aula
             (IdPlanoCurso, OrdemAula, NomeAula, Descricao, DuracaoMinutos, Categoria, Recursos, Materiais, Observacoes, Habilitado)
             VALUES
             (:IdPlanoCurso, :OrdemAula, :NomeAula, :Descricao, :DuracaoMinutos, :Categoria, :Recursos, :Materiais, :Observacoes, 1)"
        );
        $stmt->execute($data);
        return (int) $this->pdo()->lastInsertId();
    }

    public function updateOrdemAula(int $idPlanoCursoAula, int $ordemAula): bool
    {
        $stmt = $this->pdo()->prepare(
            "UPDATE tb_plano_curso_aula
             SET OrdemAula = ?
             WHERE IdPlanoCursoAula = ?
               AND Habilitado = 1"
        );
        $stmt->execute([$ordemAula, $idPlanoCursoAula]);
        return $stmt->rowCount() > 0;
    }

    public function updateAula(int $idPlanoCursoAula, array $data): bool
    {
        $data['IdPlanoCursoAula'] = $idPlanoCursoAula;
        $stmt = $this->pdo()->prepare(
            "UPDATE tb_plano_curso_aula
             SET NomeAula = :NomeAula,
                 Descricao = :Descricao,
                 DuracaoMinutos = :DuracaoMinutos,
                 Categoria = :Categoria,
                 Recursos = :Recursos,
                 Materiais = :Materiais,
                 Observacoes = :Observacoes
             WHERE IdPlanoCursoAula = :IdPlanoCursoAula
               AND Habilitado = 1"
        );
        $stmt->execute($data);
        return $stmt->rowCount() > 0;
    }

    public function softDeleteAula(int $idPlanoCursoAula): bool
    {
        $stmt = $this->pdo()->prepare(
            "UPDATE tb_plano_curso_aula
             SET Habilitado = 0,
                 OrdemAula = -IdPlanoCursoAula
             WHERE IdPlanoCursoAula = ?
               AND Habilitado = 1"
        );
        $stmt->execute([$idPlanoCursoAula]);
        return $stmt->rowCount() > 0;
    }

    public function listVinculosAtivosByPlano(int $idPlanoCurso): array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT IdTurmaPlanoCurso, IdTurma
             FROM tb_turma_plano_curso
             WHERE IdPlanoCurso = ?
               AND Habilitado = 1
             ORDER BY IdTurmaPlanoCurso ASC"
        );
        $stmt->execute([$idPlanoCurso]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createPendenteCronogramaIfMissing(int $idTurmaPlanoCurso, int $idPlanoCursoAula): bool
    {
        $stmt = $this->pdo()->prepare(
            "INSERT INTO tb_cronograma_aula (IdTurmaPlanoCurso, IdPlanoCursoAula, StatusExecucao)
             SELECT ?, ?, 'pendente'
             FROM DUAL
             WHERE NOT EXISTS (
                 SELECT 1
                 FROM tb_cronograma_aula
                 WHERE IdTurmaPlanoCurso = ?
                   AND IdPlanoCursoAula = ?
             )"
        );
        $stmt->execute([$idTurmaPlanoCurso, $idPlanoCursoAula, $idTurmaPlanoCurso, $idPlanoCursoAula]);
        return $stmt->rowCount() > 0;
    }

    public function listCronogramaSlotsByVinculo(int $idTurmaPlanoCurso): array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT IdCronogramaAula, IdPlanoCursoAula, DataAula, HoraInicio
             FROM tb_cronograma_aula
             WHERE IdTurmaPlanoCurso = ?
             ORDER BY
               CASE WHEN DataAula IS NULL THEN 1 ELSE 0 END,
               DataAula ASC,
               HoraInicio ASC,
               IdCronogramaAula ASC"
        );
        $stmt->execute([$idTurmaPlanoCurso]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateCronogramaAulaRef(int $idCronogramaAula, int $idPlanoCursoAula): bool
    {
        $stmt = $this->pdo()->prepare(
            "UPDATE tb_cronograma_aula
             SET IdPlanoCursoAula = ?
             WHERE IdCronogramaAula = ?"
        );
        $stmt->execute([$idPlanoCursoAula, $idCronogramaAula]);
        return $stmt->rowCount() > 0;
    }
}
