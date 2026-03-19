<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\JsonResponse;
use App\Middlewares\AuthMiddleware;

class ConsultasController
{
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
        $this->updateStatus($id, 'em_atendimento');
    }

    public function reverterAtendimento(string $id): void
    {
        AuthMiddleware::requireAuth();
        $this->updateStatus($id, 'agendada');
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
