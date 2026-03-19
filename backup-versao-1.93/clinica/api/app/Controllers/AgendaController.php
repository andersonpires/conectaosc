<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\JsonResponse;
use App\Middlewares\AuthMiddleware;

class AgendaController
{
    public function index(): void
    {
        AuthMiddleware::requireAuth();
        $view = $_GET['view'] ?? 'day';
        $date = $_GET['date'] ?? date('Y-m-d');
        $especialidadeId = !empty($_GET['especialidade_id']) ? (int)$_GET['especialidade_id'] : null;
        $profissionalId = isset($_GET['profissional_id']) ? $_GET['profissional_id'] : null;
        $horaInicio = trim($_GET['hora_inicio'] ?? '');
        $horaFim = trim($_GET['hora_fim'] ?? '');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            JsonResponse::error('Data inválida', [], 400);
        }

        $pdo = Database::getConnection();

        if ($view === 'day') {
            $conds = ['c.data_consulta = ?'];
            $params = [$date];
            if ($especialidadeId > 0) {
                $conds[] = 'c.especialidade_id = ?';
                $params[] = $especialidadeId;
            }
            if ($profissionalId !== null && $profissionalId !== '') {
                if (strtolower($profissionalId) === 'plantonista') {
                    $conds[] = 'c.profissional_id IS NULL';
                } else {
                    $conds[] = 'c.profissional_id = ?';
                    $params[] = (int)$profissionalId;
                }
            }
            if ($horaInicio !== '' && preg_match('/^\d{1,2}:\d{2}/', $horaInicio)) {
                $conds[] = 'c.hora_inicio_prevista >= ?';
                $params[] = strlen($horaInicio) === 5 ? $horaInicio . ':00' : $horaInicio;
            }
            if ($horaFim !== '' && preg_match('/^\d{1,2}:\d{2}/', $horaFim)) {
                $conds[] = 'c.hora_inicio_prevista <= ?';
                $params[] = strlen($horaFim) === 5 ? $horaFim . ':00' : $horaFim;
            }
            $where = implode(' AND ', $conds);
            $stmt = $pdo->prepare("
                SELECT c.id, c.aluno_id, c.profissional_id, c.profissional_nome_livre, c.especialidade_id, c.tipo_consulta_id,
                    c.data_consulta, c.hora_inicio_prevista, c.hora_fim_prevista, c.duracao_minutos_prevista, c.status, c.observacao,
                    a.inicio, a.fim_previsto,
                    al.Nome AS paciente_nome, al.WhatsApp AS paciente_telefone,
                    e.nome AS especialidade_nome, t.nome AS tipo_nome,
                    COALESCE(CONCAT(u.Nome, ' ', u.Sobrenome), c.profissional_nome_livre) AS profissional_nome
                FROM tb_consulta c
                JOIN tb_agenda_clinica a ON a.consulta_id = c.id
                JOIN tbAluno al ON al.IdUsuario = c.aluno_id
                LEFT JOIN tbUser u ON u.IdColaborador = c.profissional_id
                JOIN tb_especialidade e ON e.id = c.especialidade_id
                JOIN tb_tipo_consulta t ON t.id = c.tipo_consulta_id
                WHERE {$where}
                ORDER BY a.inicio
            ");
            $stmt->execute($params);
        } elseif ($view === 'week') {
            $start = date('Y-m-d', strtotime('monday this week', strtotime($date)));
            $end = date('Y-m-d', strtotime('sunday this week', strtotime($date)));
            $conds = ['c.data_consulta BETWEEN ? AND ?'];
            $params = [$start, $end];
            if ($especialidadeId > 0) { $conds[] = 'c.especialidade_id = ?'; $params[] = $especialidadeId; }
            if ($profissionalId !== null && $profissionalId !== '') {
                if (strtolower($profissionalId) === 'plantonista') $conds[] = 'c.profissional_id IS NULL';
                else { $conds[] = 'c.profissional_id = ?'; $params[] = (int)$profissionalId; }
            }
            if ($horaInicio !== '' && preg_match('/^\d{1,2}:\d{2}/', $horaInicio)) { $conds[] = 'c.hora_inicio_prevista >= ?'; $params[] = strlen($horaInicio) === 5 ? $horaInicio . ':00' : $horaInicio; }
            if ($horaFim !== '' && preg_match('/^\d{1,2}:\d{2}/', $horaFim)) { $conds[] = 'c.hora_inicio_prevista <= ?'; $params[] = strlen($horaFim) === 5 ? $horaFim . ':00' : $horaFim; }
            $where = implode(' AND ', $conds);
            $stmt = $pdo->prepare("
                SELECT c.id, c.aluno_id, c.profissional_id, c.profissional_nome_livre, c.data_consulta, c.hora_inicio_prevista, c.hora_fim_prevista, c.duracao_minutos_prevista, c.status, c.observacao, c.especialidade_id,
                    a.inicio, a.fim_previsto,
                    al.Nome AS paciente_nome, al.WhatsApp AS paciente_telefone,
                    e.nome AS especialidade_nome, t.nome AS tipo_nome,
                    COALESCE(CONCAT(u.Nome, ' ', u.Sobrenome), c.profissional_nome_livre) AS profissional_nome
                FROM tb_consulta c
                JOIN tb_agenda_clinica a ON a.consulta_id = c.id
                JOIN tbAluno al ON al.IdUsuario = c.aluno_id
                LEFT JOIN tbUser u ON u.IdColaborador = c.profissional_id
                JOIN tb_especialidade e ON e.id = c.especialidade_id
                LEFT JOIN tb_tipo_consulta t ON t.id = c.tipo_consulta_id
                WHERE {$where}
                ORDER BY c.data_consulta, a.inicio
            ");
            $stmt->execute($params);
        } else {
            $start = date('Y-m-01', strtotime($date));
            $end = date('Y-m-t', strtotime($date));
            $conds = ['c.data_consulta BETWEEN ? AND ?'];
            $params = [$start, $end];
            if ($especialidadeId > 0) { $conds[] = 'c.especialidade_id = ?'; $params[] = $especialidadeId; }
            if ($profissionalId !== null && $profissionalId !== '') {
                if (strtolower($profissionalId) === 'plantonista') $conds[] = 'c.profissional_id IS NULL';
                else { $conds[] = 'c.profissional_id = ?'; $params[] = (int)$profissionalId; }
            }
            $where = implode(' AND ', $conds);
            $stmt = $pdo->prepare("
                SELECT c.id, c.aluno_id, c.profissional_id, c.profissional_nome_livre, c.data_consulta, c.hora_inicio_prevista, c.duracao_minutos_prevista, c.status, c.observacao,
                    a.inicio, a.fim_previsto,
                    al.Nome AS paciente_nome, al.WhatsApp AS paciente_telefone,
                    e.nome AS especialidade_nome, t.nome AS tipo_nome,
                    COALESCE(CONCAT(u.Nome, ' ', u.Sobrenome), c.profissional_nome_livre) AS profissional_nome
                FROM tb_consulta c
                JOIN tb_agenda_clinica a ON a.consulta_id = c.id
                JOIN tbAluno al ON al.IdUsuario = c.aluno_id
                LEFT JOIN tbUser u ON u.IdColaborador = c.profissional_id
                JOIN tb_especialidade e ON e.id = c.especialidade_id
                LEFT JOIN tb_tipo_consulta t ON t.id = c.tipo_consulta_id
                WHERE {$where}
                ORDER BY c.data_consulta, a.inicio
            ");
            $stmt->execute($params);
        }

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        JsonResponse::success(['eventos' => $rows, 'view' => $view, 'date' => $date]);
    }

    public function emAtendimento(): void
    {
        AuthMiddleware::requireAuth();
        $pdo = Database::getConnection();
        $userId = AuthMiddleware::getUserId();
        $profSaude = (int)($_SESSION['profissional_saude'] ?? 0);
        $userEspId = isset($_SESSION['especialidade_id']) && $_SESSION['especialidade_id'] ? (int)$_SESSION['especialidade_id'] : null;
        $isSuper = AuthMiddleware::isSuperAdmin();

        $conds = ["c.status = 'em_atendimento'", 'c.data_consulta = CURDATE()'];
        $params = [];
        if (!$isSuper) {
            $conds[] = '(c.profissional_id = ? OR (c.profissional_id IS NULL AND ? = 1 AND (c.especialidade_id = ? OR ? IS NULL)))';
            $params = [$userId, $profSaude, $userEspId, $userEspId];
        }
        $where = implode(' AND ', $conds);
        $stmt = $pdo->prepare("
            SELECT c.id, c.aluno_id, c.data_consulta, c.hora_inicio_prevista,
                   al.Nome AS paciente_nome
            FROM tb_consulta c
            JOIN tbAluno al ON al.IdUsuario = c.aluno_id
            WHERE {$where}
            ORDER BY c.hora_inicio_prevista
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        JsonResponse::success(['consultas' => $rows]);
    }

    /**
     * Consultas que o usuário logado pode atender:
     * - Designadas a ele (profissional_id = userId)
     * - Ou Plantonista + profissional_saude=1 + (super OU especialidade do user = especialidade da consulta)
     */
    public function consultasParaAtendimento(): void
    {
        AuthMiddleware::requireAuth();
        $pdo = Database::getConnection();
        $userId = AuthMiddleware::getUserId();
        $profSaude = (int)($_SESSION['profissional_saude'] ?? 0);
        $userEspId = isset($_SESSION['especialidade_id']) && $_SESSION['especialidade_id'] ? (int)$_SESSION['especialidade_id'] : null;
        $isSuper = AuthMiddleware::isSuperAdmin();
        $date = $_GET['date'] ?? date('Y-m-d');

        $conds = [
            "c.status IN ('agendada','confirmacao_solicitada','em_atendimento')",
            'c.data_consulta >= ?'
        ];
        $params = [$date];

        if ($isSuper) {
            $conds[] = '(c.profissional_id = ? OR (c.profissional_id IS NULL AND ? = 1))';
            $params[] = $userId;
            $params[] = $profSaude;
        } else {
            $conds[] = '(c.profissional_id = ? OR (c.profissional_id IS NULL AND ? = 1 AND ? IS NOT NULL AND c.especialidade_id = ?))';
            $params[] = $userId;
            $params[] = $profSaude;
            $params[] = $userEspId;
            $params[] = $userEspId;
        }

        $where = implode(' AND ', $conds);
        $stmt = $pdo->prepare("
            SELECT c.id, c.aluno_id, c.profissional_id, c.especialidade_id, c.data_consulta, c.hora_inicio_prevista,
                   c.duracao_minutos_prevista, c.status, c.observacao,
                   al.Nome AS paciente_nome, al.WhatsApp AS paciente_telefone,
                   e.nome AS especialidade_nome,
                   COALESCE(CONCAT(u.Nome, ' ', u.Sobrenome), c.profissional_nome_livre) AS profissional_nome
            FROM tb_consulta c
            JOIN tbAluno al ON al.IdUsuario = c.aluno_id
            JOIN tb_especialidade e ON e.id = c.especialidade_id
            LEFT JOIN tbUser u ON u.IdColaborador = c.profissional_id
            WHERE {$where}
            ORDER BY c.data_consulta ASC, c.hora_inicio_prevista ASC
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        JsonResponse::success(['consultas' => $rows]);
    }

    /**
     * Consultas concluídas ou em atendimento que ainda não têm prontuário.
     * GET /agenda/consultas-aguardando-prontuario
     */
    public function consultasAguardandoProntuario(): void
    {
        AuthMiddleware::requireAuth();
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT c.id, c.aluno_id, c.profissional_id, c.data_consulta, c.hora_inicio_prevista,
                   c.duracao_minutos_prevista, c.status, c.observacao,
                   al.Nome AS paciente_nome, al.WhatsApp AS paciente_telefone,
                   e.nome AS especialidade_nome,
                   COALESCE(CONCAT(u.Nome, ' ', u.Sobrenome), c.profissional_nome_livre) AS profissional_nome
            FROM tb_consulta c
            JOIN tbAluno al ON al.IdUsuario = c.aluno_id
            JOIN tb_especialidade e ON e.id = c.especialidade_id
            LEFT JOIN tbUser u ON u.IdColaborador = c.profissional_id
            WHERE c.status IN ('concluida', 'em_atendimento')
              AND NOT EXISTS (SELECT 1 FROM tb_prontuario p WHERE p.consulta_id = c.id)
            ORDER BY c.data_consulta DESC, c.hora_inicio_prevista DESC
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        JsonResponse::success(['consultas' => $rows]);
    }

    /**
     * Retorna para cada dia do mês: data, total, concluidas, pendentes_passado, futuras.
     * Usado no date picker com indicadores (vermelho/amarelo/verde).
     * GET /agenda/dias-com-agendamento?mes=YYYY-MM
     */
    public function diasComAgendamento(): void
    {
        AuthMiddleware::requireAuth();
        $mes = $_GET['mes'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
            JsonResponse::error('Parâmetro mes inválido (use YYYY-MM)', [], 400);
        }
        $pdo = Database::getConnection();
        [$ano, $numMes] = explode('-', $mes);
        $ultimoDia = (int) date('t', strtotime("{$ano}-{$numMes}-01"));
        $hoje = date('Y-m-d');

        $stmt = $pdo->prepare("
            SELECT c.data_consulta,
                   COUNT(*) AS total,
                   SUM(CASE WHEN c.status = 'concluida' THEN 1 ELSE 0 END) AS concluidas,
                   SUM(CASE WHEN c.status NOT IN ('concluida','cancelada') AND c.data_consulta < ? THEN 1 ELSE 0 END) AS pendentes_passado,
                   SUM(CASE WHEN c.data_consulta > ? THEN 1 ELSE 0 END) AS futuras
            FROM tb_consulta c
            WHERE c.data_consulta >= ? AND c.data_consulta <= ?
              AND c.status != 'cancelada'
            GROUP BY c.data_consulta
        ");
        $inicio = "{$ano}-{$numMes}-01";
        $fim = "{$ano}-{$numMes}-" . str_pad((string) $ultimoDia, 2, '0', STR_PAD_LEFT);
        $stmt->execute([$hoje, $hoje, $inicio, $fim]);
        $porData = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $porData[$row['data_consulta']] = [
                'data' => $row['data_consulta'],
                'total' => (int) $row['total'],
                'concluidas' => (int) $row['concluidas'],
                'pendentes_passado' => (int) $row['pendentes_passado'],
                'futuras' => (int) $row['futuras'],
            ];
        }

        $dias = [];
        for ($d = 1; $d <= $ultimoDia; $d++) {
            $dataStr = $ano . '-' . str_pad((string) $numMes, 2, '0', STR_PAD_LEFT) . '-' . str_pad((string) $d, 2, '0', STR_PAD_LEFT);
            $info = $porData[$dataStr] ?? [
                'data' => $dataStr, 'total' => 0, 'concluidas' => 0, 'pendentes_passado' => 0, 'futuras' => 0,
            ];
            $dias[] = $info;
        }
        JsonResponse::success(['dias' => $dias, 'mes' => $mes]);
    }
}
