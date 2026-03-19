<?php
declare(strict_types=1);

namespace BackEnd\Repositories;

use PDO;
use RuntimeException;

class CronogramaAulaRepository
{
    public function pdo(): PDO
    {
        $root = dirname(__DIR__, 2);
        require $root . '/api/conectabd/conexao.php';

        if (!isset($pdo) || !$pdo instanceof PDO) {
            throw new RuntimeException('Database connection unavailable');
        }

        return $pdo;
    }

    public function findById(int $idCronogramaAula): ?array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT ca.*, a.DuracaoMinutos, a.NomeAula
             FROM tb_cronograma_aula ca
             INNER JOIN tb_plano_curso_aula a ON a.IdPlanoCursoAula = ca.IdPlanoCursoAula
             WHERE ca.IdCronogramaAula = ?
             LIMIT 1"
        );
        $stmt->execute([$idCronogramaAula]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateAgendamento(
        int $idCronogramaAula,
        ?string $dataAula,
        ?string $horaInicio,
        ?string $horaFim,
        string $statusExecucao
    ): bool {
        $stmt = $this->pdo()->prepare(
            "UPDATE tb_cronograma_aula
             SET DataAula = ?,
                 HoraInicio = ?,
                 HoraFim = ?,
                 StatusExecucao = ?
             WHERE IdCronogramaAula = ?"
        );
        $stmt->execute([$dataAula, $horaInicio, $horaFim, $statusExecucao, $idCronogramaAula]);
        return $stmt->rowCount() > 0;
    }

    public function updateStatus(
        int $idCronogramaAula,
        string $statusExecucao,
        ?string $justificativa,
        ?string $feedback,
        ?string $observacoes
    ): bool {
        $stmt = $this->pdo()->prepare(
            "UPDATE tb_cronograma_aula
             SET StatusExecucao = ?,
                 JustificativaAdiamento = ?,
                 FeedbackProfessor = ?,
                 Observacoes = ?
             WHERE IdCronogramaAula = ?"
        );
        $stmt->execute([$statusExecucao, $justificativa, $feedback, $observacoes, $idCronogramaAula]);
        return $stmt->rowCount() > 0;
    }

    public function listComentarios(int $idCronogramaAula): array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT c.IdCronogramaAulaComentario, c.IdCronogramaAula, c.IdColaborador, c.Comentario, c.DataCriacao,
                    u.Nome, u.Sobrenome
             FROM tb_cronograma_aula_comentario c
             LEFT JOIN tbUser u ON u.IdColaborador = c.IdColaborador
             WHERE c.IdCronogramaAula = ?
             ORDER BY c.IdCronogramaAulaComentario DESC"
        );
        $stmt->execute([$idCronogramaAula]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addComentario(int $idCronogramaAula, int $idColaborador, string $comentario): int
    {
        $stmt = $this->pdo()->prepare(
            "INSERT INTO tb_cronograma_aula_comentario (IdCronogramaAula, IdColaborador, Comentario)
             VALUES (?, ?, ?)"
        );
        $stmt->execute([$idCronogramaAula, $idColaborador, $comentario]);
        return (int) $this->pdo()->lastInsertId();
    }
}
