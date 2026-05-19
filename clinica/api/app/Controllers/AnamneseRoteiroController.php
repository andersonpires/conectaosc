<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\JsonResponse;
use App\Middlewares\AuthMiddleware;
use App\Services\ColaboradorResolver;

class AnamneseRoteiroController
{
    private const TABLE_NAME = 'tb_anamnese_infantojuvenil';

    /**
     * GET /anamnese-roteiro?consulta_id=X ou ?aluno_id=X
     */
    public function index(): void
    {
        AuthMiddleware::requireAuth();
        $consultaId = isset($_GET['consulta_id']) ? (int)$_GET['consulta_id'] : null;
        $alunoId    = isset($_GET['aluno_id'])    ? (int)$_GET['aluno_id']    : null;

        $pdo = Database::getConnection();
        $sql = "
            SELECT a.*, a.profissional_id,
                   al.Nome AS paciente_nome
            FROM " . self::TABLE_NAME . " a
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
     * GET /anamnese-roteiro/{id}
     */
    public function show(string $id): void
    {
        AuthMiddleware::requireAuth();
        $id = (int)$id;
        if ($id <= 0) {
            JsonResponse::error('ID inválido', [], 400);
        }

        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT a.*, a.profissional_id,
                   al.Nome AS paciente_nome
            FROM " . self::TABLE_NAME . " a
            JOIN tbAluno al ON al.IdUsuario = a.aluno_id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            JsonResponse::error('Anamnese não encontrada', [], 404);
        }

        $rows = (new ColaboradorResolver())->hydrateProfissionalNome([$row]);
        JsonResponse::success(['anamnese' => $rows[0]]);
    }

    /**
     * POST /anamnese-roteiro
     */
    public function store(): void
    {
        AuthMiddleware::requireProfissionalSaude();
        $userId = AuthMiddleware::getUserId();
        $input  = json_decode(file_get_contents('php://input'), true) ?: [];

        $alunoId    = (int)($input['aluno_id'] ?? 0);
        $consultaId = (int)($input['consulta_id'] ?? 0);

        if ($alunoId <= 0) {
            JsonResponse::error('aluno_id obrigatório', [], 422);
        }

        $data = [
            'aluno_id'       => $alunoId,
            'profissional_id' => $userId,
            'consulta_id'    => $consultaId > 0 ? $consultaId : null,
        ];

        foreach ($this->getColumns() as $col) {
            if (!array_key_exists($col, $input)) {
                continue;
            }
            $val = $input[$col];
            if (is_string($val)) {
                $val = trim($val);
            }
            $data[$col] = ($val === '' || $val === null) ? null : $val;
        }

        $campos       = array_keys($data);
        $placeholders = implode(',', array_fill(0, count($campos), '?'));
        $camposStr    = implode(',', array_map(fn($c) => "`$c`", $campos));

        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO " . self::TABLE_NAME . " ($camposStr) VALUES ($placeholders)");
        $stmt->execute(array_values($data));
        $newId = (int)$pdo->lastInsertId();

        JsonResponse::success(['id' => $newId], 'Anamnese salva com sucesso', 201);
    }

    /**
     * PUT /anamnese-roteiro/{id}
     */
    public function update(string $id): void
    {
        AuthMiddleware::requireProfissionalSaude();
        $userId = AuthMiddleware::getUserId();
        $id     = (int)$id;
        if ($id <= 0) {
            JsonResponse::error('ID inválido', [], 400);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];

        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare("SELECT profissional_id FROM " . self::TABLE_NAME . " WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$existing) {
            JsonResponse::error('Anamnese não encontrada', [], 404);
        }
        if ((int)$existing['profissional_id'] !== $userId && !AuthMiddleware::isSuperAdmin()) {
            JsonResponse::error('Sem permissão para editar esta anamnese', [], 403);
        }

        $updates = [];
        $vals    = [];
        foreach ($this->getColumns() as $col) {
            if (!array_key_exists($col, $input)) {
                continue;
            }
            $val = $input[$col];
            if (is_string($val)) {
                $val = trim($val);
            }
            $updates[] = "`$col` = ?";
            $vals[]    = ($val === '' || $val === null) ? null : $val;
        }

        if (empty($updates)) {
            JsonResponse::success(['message' => 'Nenhuma alteração']);
            return;
        }

        $vals[] = $id;
        $sql    = "UPDATE " . self::TABLE_NAME . " SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt   = $pdo->prepare($sql);
        $stmt->execute($vals);

        JsonResponse::success(['message' => 'Anamnese atualizada']);
    }

    private function getColumns(): array
    {
        return [
            'nome_paciente', 'motivo_atendimento',
            'acomp_psicologico', 'acomp_psi_onde', 'acomp_psi_quando',
            'acomp_psicopedagogico', 'acomp_psicoped_onde', 'acomp_psicoped_quando',
            'acomp_fonoaudiologico', 'acomp_fono_onde', 'acomp_fono_quando',
            'acomp_neurologico', 'acomp_neuro_onde', 'acomp_neuro_quando',
            'acomp_terapia_ocupacional', 'acomp_to_onde', 'acomp_to_quando',
            'acomp_fisioterapia', 'acomp_fisio_onde', 'acomp_fisio_quando',
            'acomp_outros',
            'historia_gestacional', 'antecedentes_morbidos', 'psicomotor', 'linguagem',
            'alimentacao', 'sono', 'escolaridade', 'escola_nome', 'escola_telefone',
            'sociabilidade', 'sexualidade', 'situacao_socioeconomica',
            'dificuldade_visao', 'dificuldade_visao_desc', 'dificuldade_audicao',
            'antec_fam_doencas', 'antec_fam_doencas_quais',
            'antec_fam_alcoolismo', 'antec_fam_homicidio', 'antec_fam_def_mental',
            'antec_fam_suicidio', 'antec_fam_drogadicao',
            'antec_fam_outros', 'antec_fam_grau_parentesco',
            'num_irmaos', 'posicao_familiar', 'situacao_pais',
            'triagem_por', 'triagem_inicio', 'triagem_termino',
            'hipotese_diagnostica', 'conclusao',
            'indicacao_terapeutica', 'necessidade_atendimento',
            'profissional_triagem', 'data_triagem',
            'impressao_tranquilo', 'impressao_ansioso', 'impressao_seguro',
            'impressao_alegre', 'impressao_queixoso', 'impressao_intolerante',
            'impressao_atencao', 'impressao_adequacao_respostas',
            'rotina_paciente', 'perdas_recentes', 'outras_informacoes',
        ];
    }
}
