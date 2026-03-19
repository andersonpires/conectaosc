<?php
declare(strict_types=1);

namespace BackEnd\Repositories;

use PDO;
use RuntimeException;

class CrmRepository
{
    public function listarTarefas(): array
    {
        $stmt = $this->pdo()->query(
            "SELECT t.IdTarefa, t.IdUsuario, t.DescricaoTarefa, t.DataHoraExecucao,
                    t.Notificado, t.IdColaborador, t.Status,
                    u.Nome AS NomeColaborador, a.Nome AS NomeAluno
             FROM tbTarefa t
             JOIN tbUser u ON t.IdColaborador = u.IdColaborador
             JOIN tbAluno a ON t.IdUsuario = a.IdUsuario
             ORDER BY t.DataHoraExecucao DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function marcarTarefaFeito(int $idTarefa): bool
    {
        $stmt = $this->pdo()->prepare("UPDATE tbTarefa SET Status = 'feito' WHERE IdTarefa = ?");
        $stmt->execute([$idTarefa]);
        return $stmt->rowCount() > 0;
    }

    public function excluirTarefa(int $idTarefa): bool
    {
        $stmt = $this->pdo()->prepare("DELETE FROM tbTarefa WHERE IdTarefa = ?");
        $stmt->execute([$idTarefa]);
        return $stmt->rowCount() > 0;
    }

    public function buscarAlunosFaltosos(
        string $idCurso,
        string $idTurma,
        int $minFaltas,
        int $dias,
        bool $considerarMatricula,
        int $somenteMatriculados
    ): array {
        $params = [];
        $where = "WHERE c.falta = 1";

        if ($idCurso !== '' && $idCurso !== '0') {
            $where .= " AND t.IdCurso = ?";
            $params[] = $idCurso;
        }
        if ($idTurma !== '') {
            $where .= " AND t.IdTurma = ?";
            $params[] = $idTurma;
        }

        $joinMatricula = '';
        $extraSelect = '';
        if ($considerarMatricula) {
            $joinMatricula = "LEFT JOIN tbMatricula m ON m.IdUsuario = a.IdUsuario
                                AND m.IdCurso = cu.IdCurso
                                AND m.IdTurma = t.IdTurma";
            $extraSelect = ", cu.IdCurso, t.IdTurma, m.Habilitado";
            if ($somenteMatriculados === 1) {
                $where .= " AND m.Habilitado = 1";
            }
        }

        if ($dias > 0) {
            $dataInicio = (new \DateTime())->modify("-$dias days")->format('Y-m-d');
            $where .= " AND c.Data >= ?";
            $params[] = $dataInicio;
        }

        $sql = "SELECT a.IdUsuario, a.Nome, a.Apelido, a.Foto, a.Telefone, a.WhatsApp, a.Endereco, a.Bairro, a.Cidade, a.UF,
                       cu.NomeCurso, t.NomeTurma $extraSelect,
                       COUNT(c.falta) AS TotalFaltas,
                       (SELECT COUNT(*) FROM tbNota n WHERE n.IdUsuario = a.IdUsuario) AS TotalNotas,
                       (SELECT COUNT(*) FROM tbTarefa tt WHERE tt.IdUsuario = a.IdUsuario AND tt.Status = 'pendente') AS TotalTarefas
                FROM tbChamada c
                JOIN tbAluno a ON a.IdUsuario = c.IdAluno
                JOIN tbCurso cu ON c.IdCurso = cu.IdCurso
                JOIN tbTurma t ON c.IdTurma = t.IdTurma
                $joinMatricula
                $where
                GROUP BY a.IdUsuario
                HAVING TotalFaltas >= ?
                ORDER BY a.Nome";

        $params[] = $minFaltas > 0 ? $minFaltas : 1;

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function dadosAluno(int $idUsuario, bool $incluirIdsCursoTurma): ?array
    {
        $extra = $incluirIdsCursoTurma ? ", cu.IdCurso, t.IdTurma" : "";
        $sql = "SELECT a.Foto, a.Nome, a.Apelido, a.Telefone, a.WhatsApp, a.Endereco, a.Bairro, a.Cidade, a.UF,
                       cu.NomeCurso, t.NomeTurma $extra,
                       (SELECT COUNT(*) FROM tbChamada ch WHERE ch.IdAluno = a.IdUsuario AND ch.falta = 1) AS TotalFaltas
                FROM tbAluno a
                LEFT JOIN tbCurso cu ON cu.IdCurso = (
                    SELECT IdCurso FROM tbMatricula m WHERE m.IdUsuario = a.IdUsuario ORDER BY m.IdMatricula DESC LIMIT 1
                )
                LEFT JOIN tbTurma t ON t.IdTurma = (
                    SELECT IdTurma FROM tbMatricula m WHERE m.IdUsuario = a.IdUsuario ORDER BY m.IdMatricula DESC LIMIT 1
                )
                WHERE a.IdUsuario = ?";
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute([$idUsuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function listarNotasAluno(int $idUsuario): array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT n.IdNota, n.TextoNota, n.DataHoraCriacao, u.Nome AS NomeColaborador
             FROM tbNota n
             JOIN tbUser u ON n.IdColaborador = u.IdColaborador
             WHERE n.IdUsuario = ?
             ORDER BY n.DataHoraCriacao DESC"
        );
        $stmt->execute([$idUsuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function salvarNota(int $idUsuario, int $idColaborador, string $textoNota): bool
    {
        $stmt = $this->pdo()->prepare("INSERT INTO tbNota (IdUsuario, IdColaborador, TextoNota) VALUES (?, ?, ?)");
        return $stmt->execute([$idUsuario, $idColaborador, $textoNota]);
    }

    public function salvarTarefa(int $idUsuario, int $idColaborador, string $descricaoTarefa, string $dataExecucao): bool
    {
        $stmt = $this->pdo()->prepare("INSERT INTO tbTarefa (IdUsuario, IdColaborador, DescricaoTarefa, DataHoraExecucao) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$idUsuario, $idColaborador, $descricaoTarefa, $dataExecucao]);
    }

    public function getNomeAluno(int $idUsuario): ?string
    {
        $stmt = $this->pdo()->prepare("SELECT Nome FROM tbAluno WHERE IdUsuario = ?");
        $stmt->execute([$idUsuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['Nome'] ?? null;
    }

    public function dadosColaborador(int $idColaborador): ?array
    {
        $stmt = $this->pdo()->prepare("SELECT Email, WhatsApp FROM tbUser WHERE IdColaborador = ?");
        $stmt->execute([$idColaborador]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getPreferenciasNotificacao(int $idColaborador): ?array
    {
        $stmt = $this->pdo()->prepare("SELECT * FROM tbNotificacao WHERE IdColaborador = ?");
        $stmt->execute([$idColaborador]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function garantirPreferenciasNotificacao(int $idColaborador): void
    {
        $this->pdo()->prepare("INSERT IGNORE INTO tbNotificacao (IdColaborador) VALUES (?)")->execute([$idColaborador]);
    }

    public function salvarPreferenciasNotificacao(
        int $idColaborador,
        int $notaEmail,
        int $notaWhatsapp,
        int $tarefaEmail,
        int $tarefaWhatsapp
    ): bool {
        $stmt = $this->pdo()->prepare(
            "UPDATE tbNotificacao
             SET Nota_Email = ?, Nota_WhatsApp = ?, Tarefa_Email = ?, Tarefa_WhatsApp = ?
             WHERE IdColaborador = ?"
        );
        return $stmt->execute([$notaEmail, $notaWhatsapp, $tarefaEmail, $tarefaWhatsapp, $idColaborador]);
    }

    public function dadosNotificacaoColaborador(int $idColaborador): ?array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT u.Email, u.WhatsApp, u.Nome,
                    n.Nota_Email, n.Nota_WhatsApp, n.Tarefa_Email, n.Tarefa_WhatsApp
             FROM tbUser u
             LEFT JOIN tbNotificacao n ON n.IdColaborador = u.IdColaborador
             WHERE u.IdColaborador = ?"
        );
        $stmt->execute([$idColaborador]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function apiZap(): ?string
    {
        $stmt = $this->pdo()->query("SELECT APIzap FROM tbConfig LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $url = trim((string)($row['APIzap'] ?? ''));
        return $url !== '' ? $url : null;
    }

    public function listarTarefasPendentesParaNotificacao(string $agora, string $limite): array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT t.IdTarefa, t.IdColaborador, t.DataHoraExecucao, a.Nome AS NomeAluno
             FROM tbTarefa t
             JOIN tbAluno a ON t.IdUsuario = a.IdUsuario
             WHERE t.DataHoraExecucao BETWEEN ? AND ?
               AND t.Status = 'pendente'
               AND (t.Notificado IS NULL OR t.Notificado = 0)"
        );
        $stmt->execute([$agora, $limite]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function marcarTarefaNotificada(int $idTarefa): void
    {
        $stmt = $this->pdo()->prepare("UPDATE tbTarefa SET Notificado = 1 WHERE IdTarefa = ?");
        $stmt->execute([$idTarefa]);
    }

    public function registrarLogNotificacao(?int $idTarefa, ?int $tipoEnvio, string $dataEnvio, ?string $erro): void
    {
        $stmt = $this->pdo()->prepare(
            "INSERT INTO tbLogNotificaTarefa (IdTarefa, TipoEnvio, DataEnvio, Erro)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$idTarefa, $tipoEnvio, $dataEnvio, $erro]);
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

