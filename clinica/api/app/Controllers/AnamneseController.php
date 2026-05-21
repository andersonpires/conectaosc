<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\JsonResponse;
use App\Middlewares\AuthMiddleware;
use App\Services\ColaboradorResolver;

class AnamneseController
{
    /**
     * GET /anamnese?consulta_id=X ou ?aluno_id=X
     */
    public function index(): void
    {
        AuthMiddleware::requireAuth();
        $consultaId = isset($_GET['consulta_id']) ? (int)$_GET['consulta_id'] : null;
        $alunoId = isset($_GET['aluno_id']) ? (int)$_GET['aluno_id'] : null;

        $pdo = Database::getConnection();
        $sql = "
            SELECT a.*, a.profissional_id,
                   c.data_consulta, c.hora_inicio_prevista,
                   al.Nome AS paciente_nome
            FROM tb_anamnese_psi a
            JOIN tb_consulta c ON c.id = a.consulta_id
            JOIN tbAluno al ON al.IdUsuario = a.aluno_id
            WHERE 1=1
        ";
        $params = [];
        if ($consultaId > 0) {
            $sql .= " AND a.consulta_id = ?";
            $params[] = $consultaId;
        }
        if ($alunoId > 0) {
            $sql .= " AND a.aluno_id = ?";
            $params[] = $alunoId;
        }
        $sql .= " ORDER BY a.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $rows = (new ColaboradorResolver())->hydrateProfissionalNome($rows);

        JsonResponse::success(['anamneses' => $rows]);
    }

    /**
     * GET /anamnese/{id}
     */
    public function show(string $id): void
    {
        AuthMiddleware::requireAuth();
        $id = (int) $id;
        if ($id <= 0) JsonResponse::error('ID invalido', [], 400);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT a.*, a.profissional_id,
                   al.Nome AS paciente_nome, c.data_consulta, c.hora_inicio_prevista
            FROM tb_anamnese_psi a
            JOIN tbAluno al ON al.IdUsuario = a.aluno_id
            JOIN tb_consulta c ON c.id = a.consulta_id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) JsonResponse::error('Anamnese nao encontrada', [], 404);

        $rows = (new ColaboradorResolver())->hydrateProfissionalNome([$row]);
        JsonResponse::success(['anamnese' => $rows[0]]);
    }

    /**
     * POST /anamnese
     */
    public function store(): void
    {
        AuthMiddleware::requireProfissionalSaude();
        $userId = AuthMiddleware::getUserId();
        $input = json_decode(file_get_contents('php://input'), true) ?: [];

        $consultaId = (int)($input['consulta_id'] ?? 0);
        $alunoId = (int)($input['aluno_id'] ?? 0);
        if ($consultaId <= 0 || $alunoId <= 0) {
            JsonResponse::error('consulta_id e aluno_id obrigatorios', [], 422);
        }

        $cols = $this->getAnamneseColumns();
        $data = ['consulta_id' => $consultaId, 'aluno_id' => $alunoId, 'profissional_id' => $userId];
        foreach ($cols as $col) {
            if (array_key_exists($col, $input)) {
                $val = $input[$col];
                if (is_string($val)) $val = trim($val);
                $data[$col] = $val === '' || $val === null ? null : $val;
            }
        }

        $campos = array_keys($data);
        $placeholders = implode(',', array_fill(0, count($campos), '?'));
        $camposStr = implode(',', array_map(fn($c) => "`$c`", $campos));
        $vals = array_values($data);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO tb_anamnese_psi ($camposStr) VALUES ($placeholders)");
        $stmt->execute($vals);
        $id = (int) $pdo->lastInsertId();

        JsonResponse::success(['id' => $id, 'message' => 'Anamnese salva com sucesso'], 201);
    }

    /**
     * PUT /anamnese/{id}
     */
    public function update(string $id): void
    {
        AuthMiddleware::requireProfissionalSaude();
        $userId = AuthMiddleware::getUserId();
        $id = (int) $id;
        if ($id <= 0) JsonResponse::error('ID invalido', [], 400);

        $input = json_decode(file_get_contents('php://input'), true) ?: [];

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT profissional_id FROM tb_anamnese_psi WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$existing) JsonResponse::error('Anamnese nao encontrada', [], 404);
        if ((int)$existing['profissional_id'] !== $userId && !AuthMiddleware::isSuperAdmin()) {
            JsonResponse::error('Sem permissao para editar esta anamnese', [], 403);
        }

        $cols = $this->getAnamneseColumns();
        $updates = [];
        $vals = [];
        foreach ($cols as $col) {
            if (array_key_exists($col, $input)) {
                $val = $input[$col];
                if (is_string($val)) $val = trim($val);
                $updates[] = "`$col` = ?";
                $vals[] = $val === '' || $val === null ? null : $val;
            }
        }
        if (empty($updates)) {
            JsonResponse::success(['message' => 'Nenhuma alteracao']);
            return;
        }
        $vals[] = $id;
        $sql = "UPDATE tb_anamnese_psi SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($vals);

        JsonResponse::success(['message' => 'Anamnese atualizada']);
    }

    public function destroy(string $id): void
    {
        AuthMiddleware::requireAcessoClinica();
        $id = (int) $id;
        if ($id <= 0) JsonResponse::error('ID invalido', [], 400);

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT a.id, a.consulta_id, a.profissional_id,
                   c.profissional_id AS consulta_profissional_id,
                   c.profissional_nome_livre
              FROM tb_anamnese_psi a
              JOIN tb_consulta c ON c.id = a.consulta_id
             WHERE a.id = ?
             LIMIT 1
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) JsonResponse::error('Anamnese nao encontrada', [], 404);

        $this->validarSenhaProfissionalExclusao($pdo, $row, $input);

        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM tb_anamnese_psi WHERE id = ?")->execute([$id]);
            $pdo->prepare("UPDATE tb_consulta SET status = 'agendada' WHERE id = ?")->execute([(int) $row['consulta_id']]);
            $pdo->prepare("UPDATE tb_agenda_clinica SET status = 'agendada' WHERE consulta_id = ?")->execute([(int) $row['consulta_id']]);
            $pdo->commit();
            JsonResponse::success(['id' => $id], 'Anamnese excluida e agendamento reativado');
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('Erro ao excluir anamnese: ' . $e->getMessage());
            JsonResponse::error('Erro ao excluir anamnese', [], 500);
        }
    }

    private function validarSenhaProfissionalExclusao(\PDO $pdo, array $row, array $input): void
    {
        $nomeLivre = strtolower(trim((string) ($row['profissional_nome_livre'] ?? '')));
        $isPlantonista = (int) ($row['consulta_profissional_id'] ?? 0) <= 0 || $nomeLivre === 'plantonista';
        if ($isPlantonista) {
            return;
        }

        $senha = (string) ($input['senha_profissional'] ?? '');
        if ($senha === '') {
            JsonResponse::error('Informe a senha do profissional responsavel por esta anamnese.', [], 422);
        }

        $profissionalId = (int) ($row['profissional_id'] ?? 0);
        if ($profissionalId <= 0) {
            JsonResponse::error('Nao foi possivel validar o profissional responsavel por esta anamnese.', [], 422);
        }

        $stmt = $pdo->prepare("SELECT Senha FROM tbUser WHERE IdColaborador = ? AND Habilitado = 1 LIMIT 1");
        $stmt->execute([$profissionalId]);
        $hash = (string) ($stmt->fetchColumn() ?: '');
        if ($hash === '' || !password_verify($senha, $hash)) {
            JsonResponse::error('Senha do profissional invalida.', [], 403);
        }
    }

    private function getAnamneseColumns(): array
    {
        return [
            'crenca_religiao', 'crenca_conforto_preocupacao', 'quem_ajuda_dia_dia',
            'cuidador_de_alguem', 'cuidador_impacto_rotina',
            'acompanhamento_psicologico', 'medicacoes_psicotropicos', 'internacao_psiquiatrica',
            'percebido_ultimos_meses', 'lidar_situacoes_dificeis',
            'avaliacao_saude_geral', 'cansado_desanimado', 'qualidade_sono',
            'acesso_servicos_saude', 'renda_suficiente_necessidades',
            'ori_nome_completo', 'ori_onde_estamos', 'ori_dia_mes_ano', 'ori_quem_esta_aqui',
            'mem_tres_palavras', 'mem_cafe_manha',
            'ate_contar_20_0', 'ate_sim_bata_palma', 'ate_frase_maria',
            'lin_nomeacao', 'lin_compreensao', 'lin_repeticao', 'lin_fluencia',
            'fex_planejamento', 'fex_sequencia', 'fex_flexibilidade', 'fex_resolucao_problemas',
            'uso_alcool_substancias', 'pensou_tentou_machucar', 'sente_sozinho_isolado',
            'o_que_ajuda_momentos_dificeis', 'atividades_fazem_bem',
            'sentimento_ultimos_meses', 'consegue_pedir_ajuda', 'tem_objetivos_motivam',
            'quando_triste_o_que_faz',
            'dificuldades_memoria', 'atividades_basicas_sozinho', 'o_que_espera_projeto',
            'fortalecer_mais', 'observacoes_gerais'
        ];
    }
}
