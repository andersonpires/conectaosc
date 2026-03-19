<?php
declare(strict_types=1);

namespace BackEnd\Repositories;

use PDO;
use RuntimeException;

class AnexoRepository
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

    public function existsCronogramaAula(int $idCronogramaAula): bool
    {
        $stmt = $this->pdo()->prepare(
            "SELECT 1
             FROM tb_cronograma_aula
             WHERE IdCronogramaAula = ?
             LIMIT 1"
        );
        $stmt->execute([$idCronogramaAula]);
        return (bool) $stmt->fetchColumn();
    }

    public function insertPlanoAulaAnexo(array $data): int
    {
        $stmt = $this->pdo()->prepare(
            "INSERT INTO tb_plano_curso_anexo
             (IdPlanoCursoAula, NomeOriginal, NomeArquivo, Descricao, NomeFisico, MimeType, Extensao, TamanhoBytes, HashArquivo, CaminhoRelativo, EnviadoPor)
             VALUES
             (:IdPlanoCursoAula, :NomeOriginal, :NomeArquivo, :Descricao, :NomeFisico, :MimeType, :Extensao, :TamanhoBytes, :HashArquivo, :CaminhoRelativo, :EnviadoPor)"
        );
        $stmt->execute($data);
        return (int) $this->pdo()->lastInsertId();
    }

    public function updatePlanoAulaAnexoStorage(int $idPlanoCursoAnexo, string $nomeFisico, string $caminhoRelativo): void
    {
        $stmt = $this->pdo()->prepare(
            "UPDATE tb_plano_curso_anexo
             SET NomeFisico = :NomeFisico,
                 CaminhoRelativo = :CaminhoRelativo
             WHERE IdPlanoCursoAnexo = :IdPlanoCursoAnexo
             LIMIT 1"
        );
        $stmt->execute([
            'IdPlanoCursoAnexo' => $idPlanoCursoAnexo,
            'NomeFisico' => $nomeFisico,
            'CaminhoRelativo' => $caminhoRelativo,
        ]);
    }

    public function deletePlanoAulaAnexo(int $idPlanoCursoAnexo): void
    {
        $stmt = $this->pdo()->prepare(
            "DELETE FROM tb_plano_curso_anexo
             WHERE IdPlanoCursoAnexo = ?
             LIMIT 1"
        );
        $stmt->execute([$idPlanoCursoAnexo]);
    }

    public function insertCronogramaAulaAnexo(array $data): int
    {
        $stmt = $this->pdo()->prepare(
            "INSERT INTO tb_cronograma_aula_anexo
             (IdCronogramaAula, NomeOriginal, NomeFisico, MimeType, Extensao, TamanhoBytes, HashArquivo, CaminhoRelativo, EnviadoPor)
             VALUES
             (:IdCronogramaAula, :NomeOriginal, :NomeFisico, :MimeType, :Extensao, :TamanhoBytes, :HashArquivo, :CaminhoRelativo, :EnviadoPor)"
        );
        $stmt->execute($data);
        return (int) $this->pdo()->lastInsertId();
    }

    public function listPlanoAulaAnexos(int $idPlanoCursoAula): array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT *
             FROM tb_plano_curso_anexo
             WHERE IdPlanoCursoAula = ?
             ORDER BY IdPlanoCursoAnexo DESC"
        );
        $stmt->execute([$idPlanoCursoAula]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listCronogramaAulaAnexos(int $idCronogramaAula): array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT *
             FROM tb_cronograma_aula_anexo
             WHERE IdCronogramaAula = ?
             ORDER BY IdCronogramaAulaAnexo DESC"
        );
        $stmt->execute([$idCronogramaAula]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
