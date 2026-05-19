<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\JsonResponse;
use App\Middlewares\AuthMiddleware;
use App\Services\ColaboradorResolver;

class EvolucoesController
{
    public function index(): void
    {
        AuthMiddleware::requireAcessoClinica();

        $pdo = Database::getConnection();
        $sql = "
            SELECT c.id AS consulta_id, c.aluno_id, c.profissional_id AS consulta_profissional_id,
                   COALESCE(e.profissional_id, c.profissional_id) AS profissional_id,
                   c.especialidade_id, c.tipo_consulta_id, c.data_consulta, c.hora_inicio_prevista,
                   c.status AS consulta_status, c.observacao, c.created_at AS consulta_created_at,
                   e.id AS evolucao_id, e.data_evolucao, e.hora_evolucao, e.conteudo,
                   p.id AS prontuario_id,
                   an.id AS anamnese_adulto_id,
                   ai.id AS anamnese_infantojuvenil_id,
                   al.Nome AS paciente_nome, al.Foto AS paciente_foto,
                   t.nome AS tipo_nome, esp.nome AS especialidade_nome
            FROM tb_consulta c
            JOIN tbAluno al ON al.IdUsuario = c.aluno_id
            LEFT JOIN tb_evolucao_clinica e ON e.consulta_id = c.id
            LEFT JOIN tb_prontuario p ON p.consulta_id = c.id
            LEFT JOIN tb_anamnese_psi an ON an.consulta_id = c.id
            LEFT JOIN tb_anamnese_infantojuvenil ai ON ai.consulta_id = c.id
            LEFT JOIN tb_tipo_consulta t ON t.id = c.tipo_consulta_id
            LEFT JOIN tb_especialidade esp ON esp.id = c.especialidade_id
            WHERE 1=1
        ";
        $params = [];

        $alunoId = (int) ($_GET['aluno_id'] ?? 0);
        $profissionalId = (int) ($_GET['profissional_id'] ?? 0);
        $especialidadeId = (int) ($_GET['especialidade_id'] ?? 0);
        $data = trim((string) ($_GET['data'] ?? ''));

        if ($alunoId <= 0) {
            JsonResponse::success(['evolucoes' => []]);
        }

        $sql .= ' AND c.aluno_id = ?';
        $params[] = $alunoId;
        if ($profissionalId > 0) {
            $sql .= ' AND COALESCE(e.profissional_id, c.profissional_id) = ?';
            $params[] = $profissionalId;
        }
        if ($especialidadeId > 0) {
            $sql .= ' AND c.especialidade_id = ?';
            $params[] = $especialidadeId;
        }
        if ($data !== '') {
            $sql .= ' AND c.data_consulta = ?';
            $params[] = $data;
        }

        $sql .= ' ORDER BY c.data_consulta DESC, c.hora_inicio_prevista DESC, c.id DESC, e.id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $rows = (new ColaboradorResolver())->hydrateProfissionalNome($rows);
        foreach ($rows as &$row) {
            $row['id'] = $row['evolucao_id'] ? (int) $row['evolucao_id'] : null;
            $row['data_evolucao'] = $row['data_evolucao'] ?: $row['data_consulta'];
            $row['hora_evolucao'] = $row['hora_evolucao'] ?: $row['hora_inicio_prevista'];
            $row['registro_tipo'] = $row['evolucao_id'] ? 'Evolucao' : 'Consulta';
            $row['tem_evolucao'] = !empty($row['evolucao_id']);
            $row['tem_prontuario'] = !empty($row['prontuario_id']);
            $row['tem_anamnese_adulto'] = !empty($row['anamnese_adulto_id']);
            $row['tem_anamnese_infantojuvenil'] = !empty($row['anamnese_infantojuvenil_id']);
        }
        unset($row);

        JsonResponse::success(['evolucoes' => $rows]);
    }

    public function paciente(string $id): void
    {
        $_GET['aluno_id'] = (int) $id;
        $this->index();
    }

    public function show(string $id): void
    {
        AuthMiddleware::requireAcessoClinica();
        if (AuthMiddleware::isLicencaAdministrativa()) {
            JsonResponse::error('Licenca administrativa nao pode visualizar o conteudo da evolucao', [], 403);
        }

        $evolucaoId = (int) $id;
        if ($evolucaoId <= 0) {
            JsonResponse::error('ID invalido', [], 400);
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT e.*, al.Nome AS paciente_nome, al.Foto AS paciente_foto, al.Nascimento AS paciente_nascimento,
                   c.tipo_consulta_id, t.nome AS tipo_nome, esp.nome AS especialidade_nome
            FROM tb_evolucao_clinica e
            JOIN tbAluno al ON al.IdUsuario = e.aluno_id
            LEFT JOIN tb_consulta c ON c.id = e.consulta_id
            LEFT JOIN tb_tipo_consulta t ON t.id = c.tipo_consulta_id
            LEFT JOIN tb_especialidade esp ON esp.id = e.especialidade_id
            WHERE e.id = ?
            LIMIT 1
        ");
        $stmt->execute([$evolucaoId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            JsonResponse::error('Evolucao nao encontrada', [], 404);
        }

        $rows = (new ColaboradorResolver())->hydrateProfissionalNome([$row]);
        JsonResponse::success(['evolucao' => $rows[0]]);
    }

    public function store(): void
    {
        AuthMiddleware::requireProfissionalSaude();

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $alunoId = (int) ($input['aluno_id'] ?? 0);
        if ($alunoId <= 0) {
            JsonResponse::error('aluno_id obrigatorio', [], 422);
        }

        $consultaId = !empty($input['consulta_id']) ? (int) $input['consulta_id'] : null;
        $especialidadeId = !empty($input['especialidade_id']) ? (int) $input['especialidade_id'] : (isset($_SESSION['especialidade_id']) ? (int) $_SESSION['especialidade_id'] : null);
        $dataEvolucao = trim((string) ($input['data_evolucao'] ?? date('Y-m-d')));
        $horaEvolucao = trim((string) ($input['hora_evolucao'] ?? date('H:i:s')));
        $conteudo = trim((string) ($input['conteudo'] ?? ''));

        if ($conteudo === '') {
            JsonResponse::error('Conteudo obrigatorio', [], 422);
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO tb_evolucao_clinica
                (aluno_id, profissional_id, consulta_id, especialidade_id, data_evolucao, hora_evolucao, conteudo)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $alunoId,
            AuthMiddleware::getUserId(),
            $consultaId,
            $especialidadeId,
            $dataEvolucao,
            $this->normalizeTime($horaEvolucao),
            $conteudo,
        ]);

        JsonResponse::success(['id' => (int) $pdo->lastInsertId()], 'Evolucao criada', 201);
    }

    public function update(string $id): void
    {
        AuthMiddleware::requireProfissionalSaude();

        $evolucaoId = (int) $id;
        if ($evolucaoId <= 0) {
            JsonResponse::error('ID invalido', [], 400);
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT profissional_id FROM tb_evolucao_clinica WHERE id = ? LIMIT 1');
        $stmt->execute([$evolucaoId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            JsonResponse::error('Evolucao nao encontrada', [], 404);
        }
        if ((int) ($row['profissional_id'] ?? 0) !== AuthMiddleware::getUserId() && !AuthMiddleware::isSuperAdmin()) {
            JsonResponse::error('Sem permissao para editar esta evolucao', [], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $updates = [];
        $params = [];
        foreach (['data_evolucao', 'hora_evolucao', 'conteudo', 'consulta_id', 'especialidade_id'] as $field) {
            if (!array_key_exists($field, $input)) {
                continue;
            }
            $value = $input[$field];
            if ($field === 'hora_evolucao' && $value !== null && $value !== '') {
                $value = $this->normalizeTime((string) $value);
            }
            if ($field === 'conteudo') {
                $value = trim((string) $value);
                if ($value === '') {
                    JsonResponse::error('Conteudo obrigatorio', [], 422);
                }
            }
            if (in_array($field, ['consulta_id', 'especialidade_id'], true)) {
                $value = $value ? (int) $value : null;
            }
            $updates[] = "{$field} = ?";
            $params[] = $value;
        }

        if ($updates === []) {
            JsonResponse::success(['id' => $evolucaoId], 'Nenhuma alteracao');
        }

        $params[] = $evolucaoId;
        $sql = 'UPDATE tb_evolucao_clinica SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $update = $pdo->prepare($sql);
        $update->execute($params);

        JsonResponse::success(['id' => $evolucaoId], 'Evolucao atualizada');
    }

    private function normalizeTime(string $time): string
    {
        $trimmed = trim($time);
        return strlen($trimmed) === 5 ? $trimmed . ':00' : $trimmed;
    }
}
