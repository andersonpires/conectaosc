<?php
declare(strict_types=1);

namespace BackEnd\Repositories;

use PDO;
use RuntimeException;

class PlanoCursoAulaTodoRepository
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

    public function existsPlanoAula(int $idPlanoCursoAula): bool
    {
        $stmt = $this->pdo()->prepare(
            "SELECT 1
             FROM tb_plano_curso_aula
             WHERE IdPlanoCursoAula = ?
               AND Habilitado = 1
             LIMIT 1"
        );
        $stmt->execute([$idPlanoCursoAula]);
        return (bool) $stmt->fetchColumn();
    }

    public function listByAula(int $idPlanoCursoAula): array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT t.*,
                    u.Nome AS NomeConcluidoPor,
                    u.Sobrenome AS SobrenomeConcluidoPor,
                    u.Foto AS FotoConcluidoPor
             FROM tb_plano_curso_aula_todo t
             LEFT JOIN tbUser u ON u.IdColaborador = t.ConcluidoPor
             WHERE t.IdPlanoCursoAula = ?
               AND t.Habilitado = 1
             ORDER BY t.OrdemItem ASC, t.IdPlanoCursoAulaTodo ASC"
        );
        $stmt->execute([$idPlanoCursoAula]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $idPlanoCursoAulaTodo): ?array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT *
             FROM tb_plano_curso_aula_todo
             WHERE IdPlanoCursoAulaTodo = ?
               AND Habilitado = 1
             LIMIT 1"
        );
        $stmt->execute([$idPlanoCursoAulaTodo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function nextOrdemItem(int $idPlanoCursoAula): int
    {
        $stmt = $this->pdo()->prepare(
            "SELECT COALESCE(MAX(OrdemItem), 0) + 1
             FROM tb_plano_curso_aula_todo
             WHERE IdPlanoCursoAula = ?"
        );
        $stmt->execute([$idPlanoCursoAula]);
        return (int) $stmt->fetchColumn();
    }

    public function normalizeDisabledOrdensByAula(int $idPlanoCursoAula): int
    {
        $stmt = $this->pdo()->prepare(
            "UPDATE tb_plano_curso_aula_todo
             SET OrdemItem = -IdPlanoCursoAulaTodo
             WHERE IdPlanoCursoAula = ?
               AND Habilitado = 0
               AND OrdemItem > 0"
        );
        $stmt->execute([$idPlanoCursoAula]);
        return $stmt->rowCount();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo()->prepare(
            "INSERT INTO tb_plano_curso_aula_todo
             (IdPlanoCursoAula, TextoTopico, CorHex, OrdemItem, Concluido, ConcluidoPor, CriadoPor, AtualizadoPor, Habilitado)
             VALUES
             (:IdPlanoCursoAula, :TextoTopico, :CorHex, :OrdemItem, 0, NULL, :CriadoPor, :AtualizadoPor, 1)"
        );
        $stmt->execute($data);
        return (int) $this->pdo()->lastInsertId();
    }

    public function update(int $idPlanoCursoAulaTodo, array $data): bool
    {
        $data['IdPlanoCursoAulaTodo'] = $idPlanoCursoAulaTodo;
        $stmt = $this->pdo()->prepare(
            "UPDATE tb_plano_curso_aula_todo
             SET TextoTopico = :TextoTopico,
                 CorHex = :CorHex,
                 AtualizadoPor = :AtualizadoPor
             WHERE IdPlanoCursoAulaTodo = :IdPlanoCursoAulaTodo
               AND Habilitado = 1"
        );
        $stmt->execute($data);
        return $stmt->rowCount() > 0;
    }

    public function updateStatus(int $idPlanoCursoAulaTodo, bool $concluido, ?int $concluidoPor, ?int $atualizadoPor): bool
    {
        $stmt = $this->pdo()->prepare(
            "UPDATE tb_plano_curso_aula_todo
             SET Concluido = :Concluido,
                 DataConclusao = :DataConclusao,
                 ConcluidoPor = :ConcluidoPor,
                 AtualizadoPor = :AtualizadoPor
             WHERE IdPlanoCursoAulaTodo = :IdPlanoCursoAulaTodo
               AND Habilitado = 1"
        );
        $stmt->execute([
            'IdPlanoCursoAulaTodo' => $idPlanoCursoAulaTodo,
            'Concluido' => $concluido ? 1 : 0,
            'DataConclusao' => $concluido ? date('Y-m-d H:i:s') : null,
            'ConcluidoPor' => $concluido ? $concluidoPor : null,
            'AtualizadoPor' => $atualizadoPor,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function updateOrdemItem(int $idPlanoCursoAulaTodo, int $ordemItem): bool
    {
        $stmt = $this->pdo()->prepare(
            "UPDATE tb_plano_curso_aula_todo
             SET OrdemItem = ?
             WHERE IdPlanoCursoAulaTodo = ?
               AND Habilitado = 1"
        );
        $stmt->execute([$ordemItem, $idPlanoCursoAulaTodo]);
        return $stmt->rowCount() > 0;
    }

    public function softDelete(int $idPlanoCursoAulaTodo, ?int $atualizadoPor): bool
    {
        $stmt = $this->pdo()->prepare(
            "UPDATE tb_plano_curso_aula_todo
             SET Habilitado = 0,
                 OrdemItem = -IdPlanoCursoAulaTodo,
                 AtualizadoPor = :AtualizadoPor
             WHERE IdPlanoCursoAulaTodo = :IdPlanoCursoAulaTodo
               AND Habilitado = 1"
        );
        $stmt->execute([
            'IdPlanoCursoAulaTodo' => $idPlanoCursoAulaTodo,
            'AtualizadoPor' => $atualizadoPor,
        ]);
        return $stmt->rowCount() > 0;
    }
}
