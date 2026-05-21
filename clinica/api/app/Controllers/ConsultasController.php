<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\JsonResponse;
use App\Middlewares\AuthMiddleware;

class ConsultasController
{
    public function show(string $id): void
    {
        AuthMiddleware::requireAuth();
        $id = (int) $id;
        if ($id <= 0) {
            JsonResponse::error('ID inválido', [], 400);
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT c.id, c.aluno_id, c.profissional_id, c.profissional_nome_livre, c.especialidade_id, c.tipo_consulta_id,
                   c.data_consulta, c.hora_inicio_prevista, c.hora_fim_prevista, c.duracao_minutos_prevista, c.status, c.observacao,
                   a.inicio, a.fim_previsto,
                   al.Nome AS paciente_nome, al.WhatsApp AS paciente_telefone,
                   e.nome AS especialidade_nome, t.nome AS tipo_nome
            FROM tb_consulta c
            LEFT JOIN tb_agenda_clinica a ON a.consulta_id = c.id
            JOIN tbAluno al ON al.IdUsuario = c.aluno_id
            JOIN tb_especialidade e ON e.id = c.especialidade_id
            LEFT JOIN tb_tipo_consulta t ON t.id = c.tipo_consulta_id
            WHERE c.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $consulta = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$consulta) {
            JsonResponse::error('Consulta não encontrada', [], 404);
        }

        JsonResponse::success(['consulta' => $consulta]);
    }

    public function store(): void
    {
        AuthMiddleware::requireAuth();
        $userId = AuthMiddleware::getUserId();
        $input = json_decode(file_get_contents('php://input'), true) ?: [];

        $alunoId = (int)($input['aluno_id'] ?? 0);
        $profissionalId = !empty($input['profissional_id']) ? (int)$input['profissional_id'] : null;
        $profissionalNome = trim($input['profissional_nome_livre'] ?? '') ?: null;
        $especialidadeId = (int)($input['especialidade_id'] ?? 0);
        $tipoConsultaId = (int)($input['tipo_consulta_id'] ?? 0);
        $dataConsulta = $input['data_consulta'] ?? '';
        $horaInicio = $input['hora_inicio_prevista'] ?? '';
        $duracao = (int)($input['duracao_minutos_prevista'] ?? 60);
        $observacao = trim($input['observacao'] ?? '') ?: null;

        $errors = [];
        if ($alunoId <= 0) $errors[] = 'aluno_id obrigatório';
        if ($especialidadeId <= 0) $errors[] = 'especialidade_id obrigatório';
        if ($tipoConsultaId <= 0) $errors[] = 'tipo_consulta_id obrigatório';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataConsulta)) $errors[] = 'data_consulta inválida';
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $horaInicio)) $errors[] = 'hora_inicio_prevista inválida';
        if ($duracao < 10) $duracao = 60;

        if (!empty($errors)) {
            JsonResponse::error('Dados inválidos', $errors, 422);
        }

        $horaFim = date('H:i:s', strtotime($horaInicio) + ($duracao * 60));

        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO tb_consulta (aluno_id, profissional_id, profissional_nome_livre, especialidade_id, tipo_consulta_id,
                    data_consulta, hora_inicio_prevista, duracao_minutos_prevista, hora_fim_prevista, status, observacao, criado_por)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'agendada', ?, ?)
            ");
            $stmt->execute([
                $alunoId, $profissionalId, $profissionalNome, $especialidadeId, $tipoConsultaId,
                $dataConsulta, $horaInicio, $duracao, $horaFim, $observacao, $userId
            ]);
            $consultaId = (int) $pdo->lastInsertId();

            $horaInicioNorm = (strlen($horaInicio) <= 5) ? $horaInicio . ':00' : $horaInicio;
            $horaFimNorm = (strlen($horaFim) <= 5) ? $horaFim . ':00' : $horaFim;
            $inicio = $dataConsulta . ' ' . $horaInicioNorm;
            $fim = $dataConsulta . ' ' . $horaFimNorm;

            $stmtAg = $pdo->prepare("
                INSERT INTO tb_agenda_clinica (consulta_id, data_agenda, inicio, fim_previsto, status)
                VALUES (?, ?, ?, ?, 'agendada')
            ");
            $stmtAg->execute([$consultaId, $dataConsulta, $inicio, $fim]);

            $pdo->commit();
            JsonResponse::success(['id' => $consultaId], 'Consulta agendada', 201);
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log('Erro ao agendar: ' . $e->getMessage());
            JsonResponse::error('Erro ao agendar consulta', [], 500);
        }
    }

    public function update(string $id): void
    {
        AuthMiddleware::requireAuth();
        $id = (int) $id;
        if ($id <= 0) JsonResponse::error('ID inválido', [], 400);

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $profissionalId = array_key_exists('profissional_id', $input) ? ($input['profissional_id'] ? (int)$input['profissional_id'] : null) : null;
        $profissionalNome = isset($input['profissional_nome_livre']) ? trim($input['profissional_nome_livre']) ?: null : null;
        $dataConsulta = $input['data_consulta'] ?? null;
        $horaInicio = $input['hora_inicio_prevista'] ?? null;
        $duracao = isset($input['duracao_minutos_prevista']) ? (int)$input['duracao_minutos_prevista'] : null;
        $observacao = isset($input['observacao']) ? trim($input['observacao']) ?: null : null;
        $especialidadeId = isset($input['especialidade_id']) ? (int)$input['especialidade_id'] : null;
        $tipoConsultaId = isset($input['tipo_consulta_id']) ? (int)$input['tipo_consulta_id'] : null;

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, data_consulta, hora_inicio_prevista, duracao_minutos_prevista, especialidade_id, tipo_consulta_id, status FROM tb_consulta WHERE id = ? AND status != 'concluida'");
        $stmt->execute([$id]);
        $consulta = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$consulta) JsonResponse::error('Consulta não encontrada', [], 404);

        $dataConsulta = $dataConsulta ?? $consulta['data_consulta'];
        $horaInicio = $horaInicio ?? $consulta['hora_inicio_prevista'];
        $duracao = $duracao ?? $consulta['duracao_minutos_prevista'];
        $especialidadeId = $especialidadeId ?? $consulta['especialidade_id'];
        $tipoConsultaId = $tipoConsultaId ?? $consulta['tipo_consulta_id'];
        $horaFim = date('H:i:s', strtotime($horaInicio) + ($duracao * 60));

        $isCancelada = ($consulta['status'] ?? '') === 'cancelada';
        $novoStatus = $isCancelada ? 'agendada' : $consulta['status'];

        $stmt = $pdo->prepare("
            UPDATE tb_consulta SET profissional_id = ?, profissional_nome_livre = ?, data_consulta = ?, hora_inicio_prevista = ?,
                duracao_minutos_prevista = ?, hora_fim_prevista = ?, observacao = ?, especialidade_id = ?, tipo_consulta_id = ?, status = ?
            WHERE id = ?
        ");
        $stmt->execute([$profissionalId, $profissionalNome, $dataConsulta, $horaInicio, $duracao, $horaFim, $observacao, $especialidadeId, $tipoConsultaId, $novoStatus, $id]);

        $inicio = $dataConsulta . ' ' . (strlen($horaInicio) <= 5 ? $horaInicio . ':00' : $horaInicio);
        $fim = $dataConsulta . ' ' . (strlen($horaFim) <= 5 ? $horaFim . ':00' : $horaFim);
        $stmtAg = $pdo->prepare("UPDATE tb_agenda_clinica SET data_agenda = ?, inicio = ?, fim_previsto = ?, status = ? WHERE consulta_id = ?");
        $stmtAg->execute([$dataConsulta, $inicio, $fim, $novoStatus, $id]);

        JsonResponse::success(['id' => $id], $isCancelada ? 'Agendamento reativado e atualizado' : 'Agendamento atualizado');
    }

    public function confirmacao(string $id): void
    {
        AuthMiddleware::requireAuth();
        $this->updateStatus($id, 'confirmacao_solicitada');
    }

    public function cancelar(string $id): void
    {
        AuthMiddleware::requireAuth();
        $this->updateStatus($id, 'cancelada');
    }

    public function excluir(string $id): void
    {
        AuthMiddleware::requireAuth();
        $id = (int) $id;
        if ($id <= 0) JsonResponse::error('ID inválido', [], 400);

        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT id FROM tb_prontuario WHERE consulta_id = ? LIMIT 1");
        $stmt->execute([$id]);
        if ($stmt->fetch()) {
            JsonResponse::error('Não é possível excluir. Existe prontuário vinculado a esta consulta.', [], 422);
        }

        $stmt = $pdo->prepare("SELECT id FROM tb_anamnese_psi WHERE consulta_id = ? LIMIT 1");
        $stmt->execute([$id]);
        if ($stmt->fetch()) {
            JsonResponse::error('Não é possível excluir. Existe anamnese vinculada a esta consulta.', [], 422);
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("DELETE FROM tb_agenda_clinica WHERE consulta_id = ?");
            $stmt->execute([$id]);
            $stmt = $pdo->prepare("DELETE FROM tb_consulta WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                JsonResponse::error('Consulta não encontrada', [], 404);
            }
            $pdo->commit();
            JsonResponse::success(['id' => $id], 'Agendamento excluído');
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log('Erro ao excluir consulta: ' . $e->getMessage());
            JsonResponse::error('Erro ao excluir agendamento', [], 500);
        }
    }

    public function iniciarAtendimento(string $id): void
    {
        AuthMiddleware::requireProfissionalSaude();
        $id = (int) $id;
        if ($id <= 0) JsonResponse::error('ID invÃ¡lido', [], 400);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, aluno_id FROM tb_consulta WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $consulta = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$consulta) {
            JsonResponse::error('Consulta nÃ£o encontrada', [], 404);
        }

        $stmtAberta = $pdo->prepare("
            SELECT c.id, c.data_consulta, c.hora_inicio_prevista
              FROM tb_consulta c
             WHERE c.aluno_id = ?
               AND c.id <> ?
               AND c.status = 'em_atendimento'
               AND NOT EXISTS (SELECT 1 FROM tb_prontuario p WHERE p.consulta_id = c.id)
             ORDER BY c.data_consulta DESC, c.hora_inicio_prevista DESC
             LIMIT 1
        ");
        $stmtAberta->execute([(int)$consulta['aluno_id'], $id]);
        $consultaAberta = $stmtAberta->fetch(\PDO::FETCH_ASSOC);
        if ($consultaAberta) {
            JsonResponse::error(
                'Este paciente ja possui uma consulta em atendimento sem prontuario. Finalize ou reverta a consulta #' . (int)$consultaAberta['id'] . ' antes de iniciar outra.',
                [
                    'consulta_aberta_id' => (int)$consultaAberta['id'],
                    'consulta_aberta_data' => (string) ($consultaAberta['data_consulta'] ?? ''),
                    'consulta_aberta_hora' => (string) ($consultaAberta['hora_inicio_prevista'] ?? ''),
                ],
                422
            );
        }

        $this->updateStatus($id, 'em_atendimento');
    }

    public function reverterAtendimento(string $id): void
    {
        AuthMiddleware::requireAuth();
        $this->updateStatus($id, 'agendada');
    }

    public function concluirAtendimento(string $id): void
    {
        AuthMiddleware::requireProfissionalSaude();
        $consultaId = (int) $id;
        if ($consultaId <= 0) JsonResponse::error('ID invalido', [], 400);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, status FROM tb_consulta WHERE id = ? LIMIT 1");
        $stmt->execute([$consultaId]);
        $consulta = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$consulta) {
            JsonResponse::error('Consulta nao encontrada', [], 404);
        }

        $statusAtual = strtolower((string) ($consulta['status'] ?? ''));
        if ($statusAtual === 'concluida') {
            JsonResponse::success(['id' => $consultaId], 'Atendimento ja estava concluido');
        }
        if ($statusAtual !== 'em_atendimento') {
            JsonResponse::error('Somente consultas em atendimento podem ser concluidas', [], 422);
        }
        if (!$this->consultaTemRegistroClinico($pdo, $consultaId)) {
            JsonResponse::error(
                'Nao foi possivel concluir. Salve ao menos uma anamnese, evolucao ou prontuario antes de encerrar o atendimento.',
                [],
                422
            );
        }

        $this->updateStatus($consultaId, 'concluida');
    }

    public function excluirAtendimento(string $id): void
    {
        AuthMiddleware::requireProfissionalSaude();
        $consultaId = (int) $id;
        if ($consultaId <= 0) JsonResponse::error('ID invÃ¡lido', [], 400);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id FROM tb_consulta WHERE id = ? LIMIT 1");
        $stmt->execute([$consultaId]);
        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            JsonResponse::error('Consulta nÃ£o encontrada', [], 404);
        }

        $pdo->beginTransaction();
        try {
            $this->deleteDadosAtendimento($pdo, $consultaId);
            $pdo->prepare("DELETE FROM tb_agenda_clinica WHERE consulta_id = ?")->execute([$consultaId]);
            $pdo->prepare("DELETE FROM tb_consulta WHERE id = ?")->execute([$consultaId]);
            $pdo->commit();
            JsonResponse::success(['id' => $consultaId], 'Atendimento excluido');
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('Erro ao excluir atendimento: ' . $e->getMessage());
            JsonResponse::error('Erro ao excluir atendimento', [], 500);
        }
    }

    public function reverterAtendimentoCompleto(string $id): void
    {
        AuthMiddleware::requireProfissionalSaude();
        $consultaId = (int) $id;
        if ($consultaId <= 0) JsonResponse::error('ID invÃ¡lido', [], 400);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id FROM tb_consulta WHERE id = ? LIMIT 1");
        $stmt->execute([$consultaId]);
        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            JsonResponse::error('Consulta nÃ£o encontrada', [], 404);
        }

        $pdo->beginTransaction();
        try {
            $this->deleteDadosAtendimento($pdo, $consultaId);
            $pdo->prepare("UPDATE tb_consulta SET status = 'agendada' WHERE id = ?")->execute([$consultaId]);
            $pdo->prepare("UPDATE tb_agenda_clinica SET status = 'agendada' WHERE consulta_id = ?")->execute([$consultaId]);
            $pdo->commit();
            JsonResponse::success(['id' => $consultaId], 'Atendimento revertido para agendado');
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('Erro ao reverter atendimento completo: ' . $e->getMessage());
            JsonResponse::error('Erro ao reverter atendimento', [], 500);
        }
    }

    private function deleteDadosAtendimento(\PDO $pdo, int $consultaId): void
    {
        $tables = [
            'tb_prontuario',
            'tb_anamnese_psi',
            'tb_anamnese_infantojuvenil',
            'tb_evolucao_clinica',
        ];

        foreach ($tables as $table) {
            try {
                $pdo->prepare("DELETE FROM {$table} WHERE consulta_id = ?")->execute([$consultaId]);
            } catch (\PDOException $e) {
                // Tabelas opcionais em bases antigas nao devem bloquear a acao principal.
            }
        }
    }

    private function consultaTemRegistroClinico(\PDO $pdo, int $consultaId): bool
    {
        $tables = [
            'tb_prontuario',
            'tb_anamnese_psi',
            'tb_anamnese_infantojuvenil',
            'tb_evolucao_clinica',
        ];

        foreach ($tables as $table) {
            try {
                $stmt = $pdo->prepare("SELECT 1 FROM {$table} WHERE consulta_id = ? LIMIT 1");
                $stmt->execute([$consultaId]);
                if ($stmt->fetchColumn()) {
                    return true;
                }
            } catch (\PDOException $e) {
                // Bases antigas podem nao ter alguma tabela opcional.
            }
        }

        return false;
    }

    private function updateStatus(int $id, string $status): void
    {
        $id = (int) $id;
        if ($id <= 0) JsonResponse::error('ID inválido', [], 400);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE tb_consulta SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);

        $stmtAg = $pdo->prepare("UPDATE tb_agenda_clinica SET status = ? WHERE consulta_id = ?");
        $stmtAg->execute([$status, $id]);

        if ($stmt->rowCount() === 0) {
            JsonResponse::error('Consulta não encontrada', [], 404);
        }
        JsonResponse::success(['id' => $id], 'Status atualizado');
    }
}
