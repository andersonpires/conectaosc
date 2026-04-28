<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\JsonResponse;
use App\Middlewares\AuthMiddleware;

class ProntuariosController
{
    public function index(): void
    {
        AuthMiddleware::requireAuth();
        $alunoId = isset($_GET['aluno_id']) ? (int) $_GET['aluno_id'] : null;
        $pacienteNome = trim((string) ($_GET['paciente_nome'] ?? ''));
        $dataConsulta = trim((string) ($_GET['data'] ?? ''));
        $horaInicio = trim((string) ($_GET['hora_inicio'] ?? ''));
        $horaFim = trim((string) ($_GET['hora_fim'] ?? ''));
        $profissionalId = isset($_GET['profissional_id']) && $_GET['profissional_id'] !== ''
            ? (int) $_GET['profissional_id']
            : null;
        $pdo = Database::getConnection();

        $sql = "
            SELECT p.id, p.consulta_id, p.aluno_id, p.profissional_id, p.conteudo_ia, p.conteudo_editado, p.versao, p.status,
                   p.created_at,
                   CONCAT(u.Nome, ' ', u.Sobrenome) AS profissional_nome,
                   c.data_consulta, c.hora_inicio_prevista,
                   al.Nome AS paciente_nome, al.Foto
            FROM tb_prontuario p
            JOIN tbUser u ON u.IdColaborador = p.profissional_id
            JOIN tb_consulta c ON c.id = p.consulta_id
            JOIN tbAluno al ON al.IdUsuario = p.aluno_id
            WHERE 1=1
        ";
        $params = [];
        if ($alunoId > 0) {
            $sql .= " AND p.aluno_id = ?";
            $params[] = $alunoId;
        }
        if ($pacienteNome !== '') {
            $sql .= " AND al.Nome LIKE ?";
            $params[] = '%' . $pacienteNome . '%';
        }
        if ($dataConsulta !== '') {
            $sql .= " AND c.data_consulta = ?";
            $params[] = $dataConsulta;
        }
        if ($horaInicio !== '') {
            $sql .= " AND c.hora_inicio_prevista >= ?";
            $params[] = strlen($horaInicio) === 5 ? ($horaInicio . ':00') : $horaInicio;
        }
        if ($horaFim !== '') {
            $sql .= " AND c.hora_inicio_prevista <= ?";
            $params[] = strlen($horaFim) === 5 ? ($horaFim . ':00') : $horaFim;
        }
        if ($profissionalId > 0) {
            $sql .= " AND p.profissional_id = ?";
            $params[] = $profissionalId;
        }
        $sql .= " ORDER BY p.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $userId = AuthMiddleware::getUserId();
        $isSuper = AuthMiddleware::isSuperAdmin();
        foreach ($rows as &$r) {
            $r['can_edit'] = ((int)$r['profissional_id'] === $userId || $isSuper);
        }
        unset($r);

        JsonResponse::success(['prontuarios' => $rows]);
    }

    /**
     * Lista pacientes que possuem prontuário (vw_paciente_prontuario).
     * GET /prontuarios/pacientes?search=...
     */
    public function pacientesComProntuario(): void
    {
        AuthMiddleware::requireAuth();
        $search = trim($_GET['search'] ?? '');
        $pdo = Database::getConnection();
        try {
            $sql = "SELECT aluno_id, paciente_nome FROM vw_paciente_prontuario WHERE 1=1";
            $params = [];
            if ($search !== '') {
                $sql .= " AND paciente_nome LIKE ?";
                $params[] = '%' . $search . '%';
            }
            $sql .= " ORDER BY paciente_nome";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $rows = $pdo->query("
                SELECT DISTINCT al.IdUsuario AS aluno_id, al.Nome AS paciente_nome
                FROM tbAluno al
                INNER JOIN tb_prontuario p ON p.aluno_id = al.IdUsuario
                WHERE al.Habilitado = 1
                ORDER BY al.Nome
            ")->fetchAll(\PDO::FETCH_ASSOC);
            if ($search !== '') {
                $searchLower = mb_strtolower($search);
                $rows = array_filter($rows, fn($r) => stripos($r['paciente_nome'] ?? '', $search) !== false);
                $rows = array_values($rows);
            }
        }
        JsonResponse::success(['pacientes' => $rows]);
    }

    public function show(string $id): void
    {
        AuthMiddleware::requireAuth();
        $id = (int) $id;
        if ($id <= 0) JsonResponse::error('ID inválido', [], 400);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT p.id, p.consulta_id, p.aluno_id, p.profissional_id, p.conteudo_ia, p.conteudo_editado, p.status, p.created_at,
                   CONCAT(u.Nome, ' ', u.Sobrenome) AS profissional_nome, al.Nome AS paciente_nome
            FROM tb_prontuario p
            JOIN tbUser u ON u.IdColaborador = p.profissional_id
            JOIN tbAluno al ON al.IdUsuario = p.aluno_id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) JsonResponse::error('Prontuário não encontrado', [], 404);

        $row['can_edit'] = ((int)$row['profissional_id'] === AuthMiddleware::getUserId() || AuthMiddleware::isSuperAdmin());
        JsonResponse::success(['prontuario' => $row]);
    }

    public function store(): void
    {
        AuthMiddleware::requireProfissionalSaude();
        $userId = AuthMiddleware::getUserId();
        $input = json_decode(file_get_contents('php://input'), true) ?: [];

        $consultaId = (int)($input['consulta_id'] ?? 0);
        $alunoId = (int)($input['aluno_id'] ?? 0);
        $conteudoIa = $input['conteudo_ia'] ?? '';
        $conteudoEditado = $input['conteudo_editado'] ?? $conteudoIa;
        $status = in_array($input['status'] ?? '', ['rascunho', 'finalizado']) ? $input['status'] : 'rascunho';

        if ($alunoId <= 0) {
            JsonResponse::error('aluno_id obrigatório', [], 422);
        }

        $pdo = Database::getConnection();

        if ($consultaId <= 0) {
            $esp = $pdo->query("SELECT id FROM tb_especialidade WHERE ativo = 1 LIMIT 1")->fetchColumn();
            $tipo = $pdo->query("SELECT id FROM tb_tipo_consulta WHERE ativo = 1 LIMIT 1")->fetchColumn();
            $hoje = date('Y-m-d');
            $agora = date('H:i:s');
            $stmt = $pdo->prepare("
                INSERT INTO tb_consulta (aluno_id, profissional_id, especialidade_id, tipo_consulta_id,
                    data_consulta, hora_inicio_prevista, duracao_minutos_prevista, hora_fim_prevista, status, criado_por)
                VALUES (?, ?, ?, ?, ?, ?, 60, ?, 'concluida', ?)
            ");
            $stmt->execute([$alunoId, $userId, $esp, $tipo, $hoje, $agora, $agora, $userId]);
            $consultaId = (int) $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO tb_agenda_clinica (consulta_id, data_agenda, inicio, fim_previsto, status) VALUES (?, ?, ?, ?, 'concluida')")
                ->execute([$consultaId, $hoje, $hoje . ' ' . $agora, $hoje . ' ' . $agora]);
        }

        $stmt = $pdo->prepare("
            INSERT INTO tb_prontuario (consulta_id, aluno_id, profissional_id, conteudo_ia, conteudo_editado, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$consultaId, $alunoId, $userId, $conteudoIa, $conteudoEditado, $status]);
        $id = (int) $pdo->lastInsertId();

        if ($consultaId > 0) {
            $pdo->prepare("UPDATE tb_consulta SET status = 'concluida' WHERE id = ?")->execute([$consultaId]);
            $pdo->prepare("UPDATE tb_agenda_clinica SET status = 'concluida' WHERE consulta_id = ?")->execute([$consultaId]);
        }

        JsonResponse::success(['id' => $id], 'Prontuário salvo', 201);
    }

    public function update(string $id): void
    {
        AuthMiddleware::requireAuth();
        $id = (int) $id;
        if ($id <= 0) JsonResponse::error('ID inválido', [], 400);

        $pdo = Database::getConnection();
        $row = $pdo->prepare("SELECT profissional_id FROM tb_prontuario WHERE id = ?");
        $row->execute([$id]);
        $prontuario = $row->fetch(\PDO::FETCH_ASSOC);
        if (!$prontuario) JsonResponse::error('Prontuário não encontrado', [], 404);

        if ($prontuario['profissional_id'] != AuthMiddleware::getUserId() && !AuthMiddleware::isSuperAdmin()) {
            JsonResponse::error('Sem permissão para editar', [], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $conteudoEditado = $input['conteudo_editado'] ?? null;
        $status = isset($input['status']) && in_array($input['status'], ['rascunho', 'finalizado']) ? $input['status'] : null;

        $updates = [];
        $params = [];
        if ($conteudoEditado !== null) {
            $updates[] = 'conteudo_editado = ?';
            $params[] = $conteudoEditado;
        }
        if ($status !== null) {
            $updates[] = 'status = ?';
            $params[] = $status;
        }
        if (empty($updates)) {
            JsonResponse::error('Nenhum campo para atualizar', [], 400);
        }
        $params[] = $id;
        $sql = "UPDATE tb_prontuario SET versao = versao + 1, " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        JsonResponse::success(['id' => $id], 'Prontuário atualizado');
    }

    public function destroy(string $id): void
    {
        AuthMiddleware::requireAuth();
        $id = (int) $id;
        if ($id <= 0) JsonResponse::error('ID inválido', [], 400);

        $pdo = Database::getConnection();
        $row = $pdo->prepare("SELECT profissional_id FROM tb_prontuario WHERE id = ?");
        $row->execute([$id]);
        $prontuario = $row->fetch(\PDO::FETCH_ASSOC);
        if (!$prontuario) JsonResponse::error('Prontuário não encontrado', [], 404);

        if ($prontuario['profissional_id'] != AuthMiddleware::getUserId() && !AuthMiddleware::isSuperAdmin()) {
            JsonResponse::error('Sem permissão para excluir', [], 403);
        }

        $stmt = $pdo->prepare("DELETE FROM tb_prontuario WHERE id = ?");
        $stmt->execute([$id]);
        JsonResponse::success([], 'Prontuário excluído');
    }

    private function getPromptBaseProntuario(string $especialidadeNome): string
    {
        $esp = mb_strtolower(trim($especialidadeNome));
        $titulo = 'Profissional de saúde';
        $evolucoes = 'clínicas';
        if (strpos($esp, 'psicolog') !== false) {
            $titulo = 'Psicólogo';
            $evolucoes = 'psicológicas';
        } elseif (strpos($esp, 'fisioter') !== false) {
            $titulo = 'Fisioterapeuta';
            $evolucoes = 'fisioterápicas';
        } elseif (strpos($esp, 'nutri') !== false) {
            $titulo = 'Nutricionista';
            $evolucoes = 'nutricionais';
        } elseif (strpos($esp, 'fono') !== false) {
            $titulo = 'Fonoaudiólogo';
            $evolucoes = 'fonoaudiológicas';
        }
        $introducao = "Você é um {$titulo} atuando em uma organização social, e deve elaborar prontuários e evoluções {$evolucoes} multiprofissionais, conforme a Resolução do conselho profissional associado à sua especialidade.\n\n";
        return $introducao . "
## FORMATO OBRIGAT�RIO (HTML)

CRÍTICO: Responda APENAS com HTML puro. NUNCA use markdown. NÒO envolva em ```html ou ``` � envie somente o código HTML, sem cercas de código.

Use:
- <strong>TÍTULO</strong> para cada seção (em negrito).
- <p>texto</p> para cada parágrafo ou bloco de texto.
- Entre CADA seção, insira OBRIGATORIAMENTE uma linha em branco: <p></p> antes do próximo título.
- Na CONDUTA: cada item em uma linha, usando <p>⬢ item</p> ou similar.

Exemplo de estrutura:
<p><strong>DADOS DO PACIENTE</strong></p>
<p>Paciente X, Y anos, estado civil...</p>
<p></p>
<p><strong>ESTADO MENTAL</strong></p>
<p>Orientado em tempo, espaço e pessoa. Linguagem preservada...</p>
<p></p>
<p><strong>DO HUMOR</strong></p>
<p>Eutímico.</p>

## ESTRUTURA OBRIGAT�RIA (todas as seções, na ordem)

1. DADOS DO PACIENTE: APENAS identificação � nome completo, idade, sexo, estado civil, filhos, profissão, escolaridade, procedência (cidade). Via de demanda se aplicável. NUNCA incluir renda. NÒO coloque aqui avaliação cognitiva, orientação, linguagem, sono-vigília nem nenhum dado da anamnese/avaliação do profissional � isso vai em ESTADO MENTAL.
2. HIST�RICO: Apenas se houver prontuários anteriores (síntese). Primeiro prontuário = NÒO incluir.
3. ESTADO MENTAL (ou DO COGNITIVO): Toda a avaliação do profissional � orientação (tempo/espaço/pessoa), memória, atenção, linguagem, agitação psicomotora, senso-percepção, pensamento (curso e conteúdo), compreensão do quadro clínico, histórico psiquiátrico, funções executivas. Ciclo sono-vigília e aceitação da dieta quando aplicável. Receptivo/colaborativo ao atendimento.
4. DO HUMOR: Ansioso, deprimido, eutímico, disfórico, lábil, afetividade congruente.
5. DO SOCIAL: Rede de apoio funcional.
6. IMPRESSÒO: Síntese técnica, recursos adaptativos, insight, negação, sobrecarga, ansiedade ou risco.
7. MANEJO: Técnicas utilizadas (anamnese, escuta ativa, psicoeducação, etc.).
8. CONDUTA: Um item por linha. Encaminhamentos, continuidade, intervenções.

## REGRAS DE CONTE�aDO

- Use termos técnicos (orientado em tempo/espaço/pessoa, humor ansioso, afetividade congruente, etc.).
- Desenvolva cada seção com 2 a 5 frases quando houver dados. Seja completo, não superficial.
- Objetividade não significa brevidade excessiva: cubra todos os aspectos relevantes de cada tópico.
- Hipóteses entre parênteses (ex.: negação? crise focal?).
- NUNCA incluir renda do paciente. ";
    }

    private function sanitizeConteudoIa(string $html): string
    {
        $html = trim($html);
        $html = preg_replace('/^\s*```(?:html)?\s*\n?/i', '', $html);
        $html = preg_replace('/\n?```\s*$/i', '', $html);
        return trim($html);
    }

    public function gerarIa(): void
    {
        AuthMiddleware::requireProfissionalSaude();
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $consultaId = (int)($input['consulta_id'] ?? 0);
        $alunoId = (int)($input['aluno_id'] ?? 0);
        $observacoesAdicionais = trim($input['observacoes_adicionais'] ?? '');

        if ($alunoId <= 0) {
            JsonResponse::error('aluno_id obrigatório', [], 422);
        }

        $pdo = Database::getConnection();

        $camposIgnorar = ['Foto', 'IdUsuario', 'IdTipo', 'Habilitado', 'Versatilis', 'IdColaboradorEnt', 'IdColaboradorAlt', 'TimeEntrada', 'TimeAlterado', 'RendaMensal', 'RendaFamiliar', 'AvaliacaoVulnerabilidadeIA'];
        $labelsAluno = [
            'Nome' => 'Nome', 'Apelido' => 'Apelido', 'SexoBio' => 'Sexo', 'Nascimento' => 'Nascimento',
            'CPF' => 'CPF', 'Identidade' => 'Identidade', 'NIS' => 'NIS',
            'NomeResp1' => 'Responsável 1', 'Parentesco' => 'Parentesco', 'CpfResp1' => 'CPF Resp', 'TelefoneResp1' => 'Tel Resp', 'WhatsAppResp1' => 'WhatsApp Resp',
            'NomeResp2' => 'Responsável 2',
            'CEP' => 'CEP', 'Endereco' => 'Endereço', 'Numero' => 'Número', 'Complemento' => 'Complemento',
            'Bairro' => 'Bairro', 'Cidade' => 'Cidade', 'UF' => 'UF',
            'Telefone' => 'Telefone', 'WhatsApp' => 'WhatsApp', 'Email' => 'E-mail',
            'Escolaridade' => 'Escolaridade', 'Profissao' => 'Profissão', 'EstadoCivil' => 'Estado civil',
            'Obs' => 'Observações', 'ObsSaude' => 'Observações de saúde',
            'NumPessoasReside' => 'Pessoas na residência', 'ComQuemMora' => 'Com quem mora',
            'DependentesResponsabilidade' => 'Dependentes sob responsabilidade',
            'PCDEmCasa' => 'PCD em casa', 'DeficienciasCasa' => 'Deficiências em casa',
            'ParticipaProgramaSocial' => 'Programa social',
            'BenefOutroProjeto' => 'Benefício outro projeto',
            'SituacaoMoradia' => 'Situação moradia', 'TipoConstrucao' => 'Tipo construção',
            'AbastecimentoAgua' => 'Água', 'EsgotamentoSanitario' => 'Esgoto', 'PossuiRedeEletrica' => 'Rede elétrica',
            'PossuiIluminacaoPublica' => 'Iluminação pública', 'TotalComodosCasa' => 'Cômodos',
            'TransporteUtilizado' => 'Transporte', 'TemCuidador' => 'Tem cuidador', 'NomeContatoCuidador' => 'Nome cuidador',
            'AcompanhamentoMedico' => 'Acompanhamento médico', 'MotivoAcompanhamento' => 'Motivo acompanhamento',
            'UsoMedicamentos' => 'Uso medicamentos', 'QuaisMedicamentos' => 'Quais medicamentos',
            'DoencaCronica' => 'Doença crônica', 'CirurgiaRealizada' => 'Cirurgia', 'QualCirurgiaQuando' => 'Qual cirurgia',
            'LimitacoesLocomocao' => 'Limitações locomoção',
            'TratamentoFisioterapia' => 'Fisioterapia', 'MotivoFisioterapia' => 'Motivo fisioterapia',
            'PraticaAtividadeFisica' => 'Atividade física', 'TipoFrequenciaAtividade' => 'Tipo/frequência atividade',
            'RecomendacaoEsforcoFisico' => 'Recomendação esforço',
            'Fumante' => 'Fumante', 'TempoFumante' => 'Tempo fumante',
            'BebidaAlcoolica' => 'Bebida alcoólica', 'FrequenciaAlcool' => 'Frequência álcool',
            'HistoricoFamiliarDoencas' => 'Histórico familiar doenças',
            'AlergiaMedicamento' => 'Alergia medicamento', 'QuaisAlergias' => 'Quais alergias',
            'AvaliacaoVulnerabilidadeIA' => 'Avaliação socioassistencial',
            'AtividadeRemunerada' => 'Atividade remunerada', 'ContatoEmergencia' => 'Contato emergência',
            'CorRaca' => 'Cor/raça', 'ImportanciaFamiliaAmigos' => 'Importância família', 'VinculoFamiliar' => 'Vínculo familiar',
            'RecebeVisitas' => 'Recebe visitas', 'ImpactoAudicaoVisao' => 'Audição/visão',
            'AvaliacaoEscrita' => 'Escrita', 'AvaliacaoLeitura' => 'Leitura', 'CondicionamentoFisico' => 'Condicionamento físico',
            'OutroCursoAtual' => 'Outro curso', 'InteresseCursoEspecifico' => 'Interesse curso',
        ];

        $stmt = $pdo->prepare("SELECT * FROM tbAluno WHERE IdUsuario = ?");
        $stmt->execute([$alunoId]);
        $aluno = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$aluno) JsonResponse::error('Paciente não encontrado', [], 404);

        $descricaoAluno = "DADOS DO CADASTRO (tbAluno):\n\n";
        foreach ($aluno as $campo => $valor) {
            if (in_array($campo, $camposIgnorar)) continue;
            $v = $valor;
            if ($v === null || $v === '') continue;
            if (is_numeric($v) && in_array($campo, ['PCDEmCasa', 'PossuiRedeEletrica', 'PossuiIluminacaoPublica', 'AcompanhamentoMedico', 'UsoMedicamentos', 'CirurgiaRealizada', 'TratamentoFisioterapia', 'PraticaAtividadeFisica', 'RecomendacaoEsforcoFisico', 'Fumante', 'BebidaAlcoolica', 'AlergiaMedicamento', 'TemCuidador'])) {
                $v = $v == 1 ? 'Sim' : 'Não';
            }
            $label = $labelsAluno[$campo] ?? str_replace(['_', '1', '2'], [' ', '', ''], ucfirst(strtolower($campo)));
            $descricaoAluno .= strtoupper($label) . ": " . trim((string)$v) . "\n";
        }

        $dataConsultaFormatada = '';
        $especialidadeNome = 'Psicologia';
        if ($consultaId > 0) {
            $stmtCons = $pdo->prepare("
                SELECT c.data_consulta, e.nome AS especialidade_nome
                FROM tb_consulta c
                LEFT JOIN tb_especialidade e ON e.id = c.especialidade_id
                WHERE c.id = ?
            ");
            $stmtCons->execute([$consultaId]);
            $consulta = $stmtCons->fetch(\PDO::FETCH_ASSOC);
            if ($consulta) {
                if (!empty($consulta['data_consulta'])) {
                    $dt = \DateTime::createFromFormat('Y-m-d', $consulta['data_consulta']);
                    $dataConsultaFormatada = $dt ? $dt->format('d/m/Y') : '';
                }
                if (!empty($consulta['especialidade_nome'])) {
                    $especialidadeNome = $consulta['especialidade_nome'];
                }
            }
            $stmtAn = $pdo->prepare("SELECT * FROM tb_anamnese_psi WHERE consulta_id = ? LIMIT 1");
            $stmtAn->execute([$consultaId]);
            $anamnese = $stmtAn->fetch(\PDO::FETCH_ASSOC);
            if ($anamnese) {
                $labelsAnam = [
                    'crenca_religiao' => 'Crença/religião', 'crenca_conforto_preocupacao' => 'Crença: conforto ou preocupação',
                    'quem_ajuda_dia_dia' => 'Quem ajuda no dia a dia', 'cuidador_de_alguem' => 'Cuidador de alguém',
                    'cuidador_impacto_rotina' => 'Impacto do cuidado na rotina',
                    'acompanhamento_psicologico' => 'Acompanhamento psicológico prévio',
                    'medicacoes_psicotropicos' => 'Medicações psicotrópicas',
                    'internacao_psiquiatrica' => 'Internação psiquiátrica',
                    'percebido_ultimos_meses' => 'Percebido nos últimos meses',
                    'lidar_situacoes_dificeis' => 'Lidar com situações difíceis',
                    'avaliacao_saude_geral' => 'Avaliação saúde geral', 'cansado_desanimado' => 'Cansado/desanimado',
                    'qualidade_sono' => 'Qualidade do sono', 'acesso_servicos_saude' => 'Acesso serviços saúde',
                    'renda_suficiente_necessidades' => 'Renda suficiente',
                    'ori_nome_completo' => 'Orientação: nome', 'ori_onde_estamos' => 'Orientação: local',
                    'ori_dia_mes_ano' => 'Orientação: data', 'ori_quem_esta_aqui' => 'Orientação: pessoas',
                    'mem_tres_palavras' => 'Memória: três palavras', 'mem_cafe_manha' => 'Memória: café',
                    'ate_contar_20_0' => 'Atenção: contar', 'ate_sim_bata_palma' => 'Atenção: palma',
                    'ate_frase_maria' => 'Atenção: frase Maria', 'lin_nomeacao' => 'Linguagem: nomeação',
                    'lin_compreensao' => 'Linguagem: compreensão', 'lin_repeticao' => 'Linguagem: repetição',
                    'lin_fluencia' => 'Linguagem: fluência', 'fex_planejamento' => 'Executiva: planejamento',
                    'fex_sequencia' => 'Executiva: sequência', 'fex_flexibilidade' => 'Executiva: flexibilidade',
                    'fex_resolucao_problemas' => 'Executiva: resolução problemas',
                    'uso_alcool_substancias' => 'Uso álcool/substâncias', 'pensou_tentou_machucar' => 'Pensou/tentou machucar',
                    'sente_sozinho_isolado' => 'Sente sozinho/isolado',
                    'o_que_ajuda_momentos_dificeis' => 'O que ajuda', 'atividades_fazem_bem' => 'Atividades que fazem bem',
                    'sentimento_ultimos_meses' => 'Sentimento últimos meses', 'consegue_pedir_ajuda' => 'Consegue pedir ajuda',
                    'tem_objetivos_motivam' => 'Objetivos que motivam', 'quando_triste_o_que_faz' => 'Quando triste, o que faz',
                    'dificuldades_memoria' => 'Dificuldades memória', 'atividades_basicas_sozinho' => 'Atividades básicas sozinho',
                    'o_que_espera_projeto' => 'Expectativa projeto', 'fortalecer_mais' => 'O que fortalecer',
                    'observacoes_gerais' => 'Observações gerais do profissional',
                ];
                $partes = [];
                foreach ($anamnese as $k => $v) {
                    if (in_array($k, ['id', 'consulta_id', 'aluno_id', 'profissional_id', 'created_at', 'updated_at'])) continue;
                    if ($v === null || $v === '') continue;
                    $label = $labelsAnam[$k] ?? str_replace('_', ' ', ucfirst($k));
                    $partes[] = strtoupper($label) . ": " . trim((string)$v);
                }
                $anamneseTexto = !empty($partes) ? "\n\nANAMNESE PSICOL�GICA (tb_anamnese_psi):\n\n" . implode("\n", $partes) : '';
            }
        }

        $basePath = $_SESSION['BASE_para_PATH'] ?? dirname(dirname(dirname(dirname(__DIR__))));
        if (!file_exists($basePath . '/temp/openaikey.php')) {
            JsonResponse::error('Configuração de IA indisponível', [], 500);
        }
        require_once $basePath . '/temp/openaikey.php';

        $prontuariosAnterioresTexto = '';
        $ehPrimeiroProntuario = false;
        $stmtPront = $pdo->prepare("SELECT COUNT(*) FROM tb_prontuario WHERE aluno_id = ?");
        $stmtPront->execute([$alunoId]);
        if ((int) $stmtPront->fetchColumn() === 0) {
            $ehPrimeiroProntuario = true;
        } else {
            $stmtAnt = $pdo->prepare("SELECT COALESCE(conteudo_editado, conteudo_ia) AS conteudo, created_at FROM tb_prontuario WHERE aluno_id = ? ORDER BY created_at DESC LIMIT 5");
            $stmtAnt->execute([$alunoId]);
            $anteriores = $stmtAnt->fetchAll(\PDO::FETCH_ASSOC);
            $partesAnt = [];
            foreach ($anteriores as $i => $a) {
                $txt = strip_tags($a['conteudo'] ?? '');
                $data = isset($a['created_at']) ? date('d/m/Y H:i', strtotime($a['created_at'])) : '';
                $partesAnt[] = "--- Prontuário " . ($i + 1) . " ($data) ---\n" . substr($txt, 0, 3000);
            }
            $prontuariosAnterioresTexto = "\n\nPRONTUÁRIOS ANTERIORES DO PACIENTE (use para elaborar o tópico HIST�RICO):\n\n" . implode("\n\n", $partesAnt);
        }

        $clinicaPath = $basePath . '/clinica';
        $modelosExemplo = '';
        $modelosPath = $clinicaPath . '/!Suporte/prontuarios_modelo_anonimizados.md';
        if (file_exists($modelosPath)) {
            $conteudo = file_get_contents($modelosPath);
            $modelosExemplo = "\n\nUse como REFERENCIA os modelos abaixo:\n\n---\n" . substr($conteudo, 0, 6000) . "\n---";
        }

        $contexto = $descricaoAluno . $anamneseTexto . $prontuariosAnterioresTexto;
        if ($observacoesAdicionais !== '') {
            $contexto .= "\n\nOBSERVACOES ADICIONAIS DO ATENDIMENTO:\n" . $observacoesAdicionais;
        }
        if ($dataConsultaFormatada !== '') {
            $contexto .= "\n\nDATA DA CONSULTA (use exatamente dd/mm/aaaa): " . $dataConsultaFormatada;
        }
        $contexto .= "\n\nESPECIALIDADE: " . $especialidadeNome . ".";

        $instrucaoHistorico = $ehPrimeiroProntuario
            ? " Nao inclua o topico HISTORICO pois este e o primeiro prontuario deste paciente."
            : " Inclua o topico HISTORICO com base nos prontuarios anteriores fornecidos.";

        $promptBase = $this->getPromptBaseProntuario($especialidadeNome);
        $prompt = $promptBase . $instrucaoHistorico . " IMPORTANTE: Use <p></p> entre cada seção para criar linha em branco. CONDUTA: um item por linha com <p>⬢ texto</p>. Seja completo em cada tópico." . $modelosExemplo . "\n\n---\nDADOS COLETADOS:\n\n" . $contexto;

        $client = @file_get_contents('https://api.openai.com/v1/chat/completions', false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer " . ($apiKey ?? ''),
                'content' => json_encode([
                    'model' => 'gpt-5.2',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.2,
                ]),
                'timeout' => 80,
            ]
        ]));

        if ($client === false) {
            JsonResponse::error('Falha ao comunicar com serviço de IA', [], 500);
        }

        $resp = json_decode($client, true);
        $texto = $resp['choices'][0]['message']['content'] ?? '';

        if (empty($texto)) {
            JsonResponse::error('Resposta da IA vazia', [], 500);
        }

        $texto = $this->sanitizeConteudoIa($texto);
        JsonResponse::success(['conteudo_ia' => $texto]);
    }

    public function streamIa(): void
    {
        AuthMiddleware::requireProfissionalSaude();
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $consultaId = (int)($input['consulta_id'] ?? 0);
        $alunoId = (int)($input['aluno_id'] ?? 0);
        $observacoesAdicionais = trim($input['observacoes_adicionais'] ?? '');

        $enviarEvento = function (string $evento, string $data): void {
            $data = str_replace(["\r\n", "\r"], "\n", $data);
            echo "event: {$evento}\n";
            foreach (explode("\n", $data) as $linha) {
                echo 'data: ' . $linha . "\n";
            }
            echo "\n";
            if (function_exists('flush')) flush();
        };

        if ($alunoId <= 0) {
            header('Content-Type: text/event-stream; charset=utf-8');
            header('Cache-Control: no-cache');
            $enviarEvento('error', 'aluno_id obrigatório');
            exit;
        }

        $pdo = Database::getConnection();
        $camposIgnorar = ['Foto', 'IdUsuario', 'IdTipo', 'Habilitado', 'Versatilis', 'IdColaboradorEnt', 'IdColaboradorAlt', 'TimeEntrada', 'TimeAlterado', 'RendaMensal', 'RendaFamiliar', 'AvaliacaoVulnerabilidadeIA'];
        $labelsAluno = [
            'Nome' => 'Nome', 'Apelido' => 'Apelido', 'SexoBio' => 'Sexo', 'Nascimento' => 'Nascimento',
            'CPF' => 'CPF', 'Identidade' => 'Identidade', 'NIS' => 'NIS',
            'NomeResp1' => 'Responsável 1', 'Parentesco' => 'Parentesco', 'CpfResp1' => 'CPF Resp', 'TelefoneResp1' => 'Tel Resp', 'WhatsAppResp1' => 'WhatsApp Resp',
            'NomeResp2' => 'Responsável 2',
            'CEP' => 'CEP', 'Endereco' => 'Endereço', 'Numero' => 'Número', 'Complemento' => 'Complemento',
            'Bairro' => 'Bairro', 'Cidade' => 'Cidade', 'UF' => 'UF',
            'Telefone' => 'Telefone', 'WhatsApp' => 'WhatsApp', 'Email' => 'E-mail',
            'Escolaridade' => 'Escolaridade', 'Profissao' => 'Profissão', 'EstadoCivil' => 'Estado civil',
            'Obs' => 'Observações', 'ObsSaude' => 'Observações de saúde',
            'NumPessoasReside' => 'Pessoas na residência', 'ComQuemMora' => 'Com quem mora',
            'DependentesResponsabilidade' => 'Dependentes sob responsabilidade',
            'PCDEmCasa' => 'PCD em casa', 'DeficienciasCasa' => 'Deficiências em casa',
            'ParticipaProgramaSocial' => 'Programa social',
            'BenefOutroProjeto' => 'Benefício outro projeto',
            'SituacaoMoradia' => 'Situação moradia', 'TipoConstrucao' => 'Tipo construção',
            'AbastecimentoAgua' => 'Água', 'EsgotamentoSanitario' => 'Esgoto', 'PossuiRedeEletrica' => 'Rede elétrica',
            'PossuiIluminacaoPublica' => 'Iluminação pública', 'TotalComodosCasa' => 'Cômodos',
            'TransporteUtilizado' => 'Transporte', 'TemCuidador' => 'Tem cuidador', 'NomeContatoCuidador' => 'Nome cuidador',
            'AcompanhamentoMedico' => 'Acompanhamento médico', 'MotivoAcompanhamento' => 'Motivo acompanhamento',
            'UsoMedicamentos' => 'Uso medicamentos', 'QuaisMedicamentos' => 'Quais medicamentos',
            'DoencaCronica' => 'Doença crônica', 'CirurgiaRealizada' => 'Cirurgia', 'QualCirurgiaQuando' => 'Qual cirurgia',
            'LimitacoesLocomocao' => 'Limitações locomoção',
            'TratamentoFisioterapia' => 'Fisioterapia', 'MotivoFisioterapia' => 'Motivo fisioterapia',
            'PraticaAtividadeFisica' => 'Atividade física', 'TipoFrequenciaAtividade' => 'Tipo/frequência atividade',
            'RecomendacaoEsforcoFisico' => 'Recomendação esforço',
            'Fumante' => 'Fumante', 'TempoFumante' => 'Tempo fumante',
            'BebidaAlcoolica' => 'Bebida alcoólica', 'FrequenciaAlcool' => 'Frequência álcool',
            'HistoricoFamiliarDoencas' => 'Histórico familiar doenças',
            'AlergiaMedicamento' => 'Alergia medicamento', 'QuaisAlergias' => 'Quais alergias',
            'AvaliacaoVulnerabilidadeIA' => 'Avaliação socioassistencial',
            'AtividadeRemunerada' => 'Atividade remunerada', 'ContatoEmergencia' => 'Contato emergência',
            'CorRaca' => 'Cor/raça', 'ImportanciaFamiliaAmigos' => 'Importância família', 'VinculoFamiliar' => 'Vínculo familiar',
            'RecebeVisitas' => 'Recebe visitas', 'ImpactoAudicaoVisao' => 'Audição/visão',
            'AvaliacaoEscrita' => 'Escrita', 'AvaliacaoLeitura' => 'Leitura', 'CondicionamentoFisico' => 'Condicionamento físico',
            'OutroCursoAtual' => 'Outro curso', 'InteresseCursoEspecifico' => 'Interesse curso',
        ];

        $stmt = $pdo->prepare("SELECT * FROM tbAluno WHERE IdUsuario = ?");
        $stmt->execute([$alunoId]);
        $aluno = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$aluno) {
            header('Content-Type: text/event-stream; charset=utf-8');
            header('Cache-Control: no-cache');
            $enviarEvento('error', 'Paciente não encontrado');
            exit;
        }

        $descricaoAluno = "DADOS DO CADASTRO (tbAluno):\n\n";
        foreach ($aluno as $campo => $valor) {
            if (in_array($campo, $camposIgnorar)) continue;
            $v = $valor;
            if ($v === null || $v === '') continue;
            if (is_numeric($v) && in_array($campo, ['PCDEmCasa', 'PossuiRedeEletrica', 'PossuiIluminacaoPublica', 'AcompanhamentoMedico', 'UsoMedicamentos', 'CirurgiaRealizada', 'TratamentoFisioterapia', 'PraticaAtividadeFisica', 'RecomendacaoEsforcoFisico', 'Fumante', 'BebidaAlcoolica', 'AlergiaMedicamento', 'TemCuidador'])) {
                $v = $v == 1 ? 'Sim' : 'Não';
            }
            $label = $labelsAluno[$campo] ?? str_replace(['_', '1', '2'], [' ', '', ''], ucfirst(strtolower($campo)));
            $descricaoAluno .= strtoupper($label) . ": " . trim((string)$v) . "\n";
        }

        $anamneseTexto = '';
        $dataConsultaFormatada = '';
        $especialidadeNome = 'Psicologia';
        if ($consultaId > 0) {
            $stmtCons = $pdo->prepare("SELECT c.data_consulta, e.nome AS especialidade_nome FROM tb_consulta c LEFT JOIN tb_especialidade e ON e.id = c.especialidade_id WHERE c.id = ?");
            $stmtCons->execute([$consultaId]);
            $consulta = $stmtCons->fetch(\PDO::FETCH_ASSOC);
            if ($consulta) {
                if (!empty($consulta['data_consulta'])) {
                    $dt = \DateTime::createFromFormat('Y-m-d', $consulta['data_consulta']);
                    $dataConsultaFormatada = $dt ? $dt->format('d/m/Y') : '';
                }
                if (!empty($consulta['especialidade_nome'])) $especialidadeNome = $consulta['especialidade_nome'];
            }
            $stmtAn = $pdo->prepare("SELECT * FROM tb_anamnese_psi WHERE consulta_id = ? LIMIT 1");
            $stmtAn->execute([$consultaId]);
            $anamnese = $stmtAn->fetch(\PDO::FETCH_ASSOC);
            if ($anamnese) {
                $labelsAnam = [
                    'crenca_religiao' => 'Crença/religião', 'crenca_conforto_preocupacao' => 'Crença: conforto ou preocupação',
                    'quem_ajuda_dia_dia' => 'Quem ajuda no dia a dia', 'cuidador_de_alguem' => 'Cuidador de alguém',
                    'cuidador_impacto_rotina' => 'Impacto do cuidado na rotina',
                    'acompanhamento_psicologico' => 'Acompanhamento psicológico prévio',
                    'medicacoes_psicotropicos' => 'Medicações psicotrópicas',
                    'internacao_psiquiatrica' => 'Internação psiquiátrica',
                    'percebido_ultimos_meses' => 'Percebido nos últimos meses',
                    'lidar_situacoes_dificeis' => 'Lidar com situações difíceis',
                    'avaliacao_saude_geral' => 'Avaliação saúde geral', 'cansado_desanimado' => 'Cansado/desanimado',
                    'qualidade_sono' => 'Qualidade do sono', 'acesso_servicos_saude' => 'Acesso serviços saúde',
                    'renda_suficiente_necessidades' => 'Renda suficiente',
                    'ori_nome_completo' => 'Orientação: nome', 'ori_onde_estamos' => 'Orientação: local',
                    'ori_dia_mes_ano' => 'Orientação: data', 'ori_quem_esta_aqui' => 'Orientação: pessoas',
                    'mem_tres_palavras' => 'Memória: três palavras', 'mem_cafe_manha' => 'Memória: café',
                    'ate_contar_20_0' => 'Atenção: contar', 'ate_sim_bata_palma' => 'Atenção: palma',
                    'ate_frase_maria' => 'Atenção: frase Maria', 'lin_nomeacao' => 'Linguagem: nomeação',
                    'lin_compreensao' => 'Linguagem: compreensão', 'lin_repeticao' => 'Linguagem: repetição',
                    'lin_fluencia' => 'Linguagem: fluência', 'fex_planejamento' => 'Executiva: planejamento',
                    'fex_sequencia' => 'Executiva: sequência', 'fex_flexibilidade' => 'Executiva: flexibilidade',
                    'fex_resolucao_problemas' => 'Executiva: resolução problemas',
                    'uso_alcool_substancias' => 'Uso álcool/substâncias', 'pensou_tentou_machucar' => 'Pensou/tentou machucar',
                    'sente_sozinho_isolado' => 'Sente sozinho/isolado',
                    'o_que_ajuda_momentos_dificeis' => 'O que ajuda', 'atividades_fazem_bem' => 'Atividades que fazem bem',
                    'sentimento_ultimos_meses' => 'Sentimento últimos meses', 'consegue_pedir_ajuda' => 'Consegue pedir ajuda',
                    'tem_objetivos_motivam' => 'Objetivos que motivam', 'quando_triste_o_que_faz' => 'Quando triste, o que faz',
                    'dificuldades_memoria' => 'Dificuldades memória', 'atividades_basicas_sozinho' => 'Atividades básicas sozinho',
                    'o_que_espera_projeto' => 'Expectativa projeto', 'fortalecer_mais' => 'O que fortalecer',
                    'observacoes_gerais' => 'Observações gerais do profissional',
                ];
                $partes = [];
                foreach ($anamnese as $k => $v) {
                    if (in_array($k, ['id', 'consulta_id', 'aluno_id', 'profissional_id', 'created_at', 'updated_at'])) continue;
                    if ($v === null || $v === '') continue;
                    $label = $labelsAnam[$k] ?? str_replace('_', ' ', ucfirst($k));
                    $partes[] = strtoupper($label) . ": " . trim((string)$v);
                }
                $anamneseTexto = !empty($partes) ? "\n\nANAMNESE PSICOL�GICA (tb_anamnese_psi):\n\n" . implode("\n", $partes) : '';
            }
        }

        $basePath = $_SESSION['BASE_para_PATH'] ?? dirname(dirname(dirname(dirname(__DIR__))));
        if (!file_exists($basePath . '/temp/openaikey.php')) {
            header('Content-Type: text/event-stream; charset=utf-8');
            header('Cache-Control: no-cache');
            $enviarEvento('error', 'Configuração de IA indisponível');
            exit;
        }
        require_once $basePath . '/temp/openaikey.php';

        $prontuariosAnterioresTexto = '';
        $ehPrimeiroProntuario = false;
        $stmtPront = $pdo->prepare("SELECT COUNT(*) FROM tb_prontuario WHERE aluno_id = ?");
        $stmtPront->execute([$alunoId]);
        if ((int) $stmtPront->fetchColumn() === 0) {
            $ehPrimeiroProntuario = true;
        } else {
            $stmtAnt = $pdo->prepare("SELECT COALESCE(conteudo_editado, conteudo_ia) AS conteudo, created_at FROM tb_prontuario WHERE aluno_id = ? ORDER BY created_at DESC LIMIT 5");
            $stmtAnt->execute([$alunoId]);
            $anteriores = $stmtAnt->fetchAll(\PDO::FETCH_ASSOC);
            $partesAnt = [];
            foreach ($anteriores as $i => $a) {
                $txt = strip_tags($a['conteudo'] ?? '');
                $data = isset($a['created_at']) ? date('d/m/Y H:i', strtotime($a['created_at'])) : '';
                $partesAnt[] = "--- Prontuário " . ($i + 1) . " ($data) ---\n" . substr($txt, 0, 3000);
            }
            $prontuariosAnterioresTexto = "\n\nPRONTUÁRIOS ANTERIORES DO PACIENTE (use para elaborar o tópico HIST�RICO):\n\n" . implode("\n\n", $partesAnt);
        }

        $clinicaPath = $basePath . '/clinica';
        $modelosExemplo = '';
        $modelosPath = $clinicaPath . '/!Suporte/prontuarios_modelo_anonimizados.md';
        if (file_exists($modelosPath)) {
            $modelosExemplo = "\n\nUse como REFERENCIA os modelos abaixo:\n\n---\n" . substr(file_get_contents($modelosPath), 0, 6000) . "\n---";
        }

        $contexto = $descricaoAluno . $anamneseTexto . $prontuariosAnterioresTexto;
        if ($observacoesAdicionais !== '') $contexto .= "\n\nOBSERVACOES ADICIONAIS DO ATENDIMENTO:\n" . $observacoesAdicionais;
        if ($dataConsultaFormatada !== '') $contexto .= "\n\nDATA DA CONSULTA (use dd/mm/aaaa): " . $dataConsultaFormatada;
        $contexto .= "\n\nESPECIALIDADE: " . $especialidadeNome . ".";

        $instrucaoHistorico = $ehPrimeiroProntuario
            ? " Nao inclua o topico HISTORICO pois este e o primeiro prontuario deste paciente."
            : " Inclua o topico HISTORICO com base nos prontuarios anteriores fornecidos.";

        $promptBase = $this->getPromptBaseProntuario($especialidadeNome);
        $prompt = $promptBase . $instrucaoHistorico . " IMPORTANTE: Use <p></p> entre cada seção para criar linha em branco. CONDUTA: um item por linha com <p>⬢ texto</p>. Seja completo em cada tópico." . $modelosExemplo . "\n\n---\nDADOS COLETADOS:\n\n" . $contexto;

        set_time_limit(0);
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-transform');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        while (ob_get_level() > 0) ob_end_flush();
        ob_implicit_flush(true);

        $buffer = '';
        $resultado = '';
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . trim($apiKey ?? ''),
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'gpt-5.2',
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'temperature' => 0.4,
                'stream' => true,
            ], JSON_UNESCAPED_UNICODE),
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use (&$buffer, &$resultado, $enviarEvento) {
                $buffer .= $data;
                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 1);
                    $line = trim($line);
                    if ($line === '' || strpos($line, 'data:') !== 0) continue;
                    $payload = trim(substr($line, 5));
                    if ($payload === '' || $payload === '[DONE]') continue;
                    $json = json_decode($payload, true);
                    if (!is_array($json)) continue;
                    $delta = $json['choices'][0]['delta']['content'] ?? null;
                    if ($delta !== null && $delta !== '') {
                        $resultado .= $delta;
                        $enviarEvento('delta', $delta);
                    }
                }
                return strlen($data);
            },
        ]);

        $ok = curl_exec($ch);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);

        if ($curlErrno) {
            $enviarEvento('error', 'Erro de comunicação: ' . $curlError);
            exit;
        }

        $enviarEvento('done', $this->sanitizeConteudoIa($resultado));
        exit;
    }

    public function pdf(string $id): void
    {
        AuthMiddleware::requireAuth();
        $id = (int) $id;
        if ($id <= 0) JsonResponse::error('ID inválido', [], 400);

        $baseUrl = $_SESSION['BASE_para_URL'] ?? '';
        if ($baseUrl === '') {
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/clinica/api/index.php');
            $apiPrefix = '/clinica/api';
            $pos = strpos($scriptName, $apiPrefix);
            $projectBasePath = $pos === false ? '' : rtrim(substr($scriptName, 0, $pos), '/');
            $baseUrl = ($isHttps ? 'https' : 'http') . '://' . $host . $projectBasePath;
        }
        $pdfUrl = rtrim($baseUrl, '/') . '/clinica/gerarProntuarioPdf.php?id=' . $id;
        $query = [];
        if (!empty($_GET['assinar']) && $_GET['assinar'] === '1') {
            $query['assinar'] = '1';
        }
        foreach (['incluir_profissional', 'incluir_data_hora', 'incluir_foto', 'incluir_cursos_turmas'] as $flagName) {
            if (isset($_GET[$flagName])) {
                $query[$flagName] = (string) $_GET[$flagName] === '0' ? '0' : '1';
            }
        }
        if (!empty($query)) {
            $pdfUrl .= '&' . http_build_query($query);
        }
        header('Location: ' . $pdfUrl);
        exit;
    }

    public function pdfPost(): void
    {
        AuthMiddleware::requireAuth();
        $idsPost = $_POST['ids'] ?? null;

        if (is_array($idsPost) && count($idsPost) > 0) {
            $ids = array_values(array_unique(array_filter(array_map('intval', $idsPost), fn($v) => $v > 0)));
            if (empty($ids)) {
                JsonResponse::error('IDs invalidos', [], 400);
            }

            $pdo = Database::getConnection();
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT id, profissional_id FROM tb_prontuario WHERE id IN ({$placeholders})");
            $stmt->execute($ids);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            if (count($rows) !== count($ids)) {
                JsonResponse::error('Um ou mais prontuarios nao foram encontrados', [], 404);
            }

            $userId = AuthMiddleware::getUserId();
            $isSuper = AuthMiddleware::isSuperAdmin();
            $isProfSaude = (int) ($_SESSION['profissional_saude'] ?? 0) === 1;
            foreach ($rows as $row) {
                if ((int) ($row['profissional_id'] ?? 0) !== $userId && !$isSuper && !$isProfSaude) {
                    JsonResponse::error('Acesso negado', [], 403);
                }
            }

            $baseUrl = $_SESSION['BASE_para_URL'] ?? '';
            if ($baseUrl === '') {
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
                $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/clinica/api/index.php');
                $apiPrefix = '/clinica/api';
                $pos = strpos($scriptName, $apiPrefix);
                $projectBasePath = $pos === false ? '' : rtrim(substr($scriptName, 0, $pos), '/');
                $baseUrl = ($isHttps ? 'https' : 'http') . '://' . $host . $projectBasePath;
            }

            $pdfUrl = rtrim($baseUrl, '/') . '/clinica/gerarProntuarioPdfLote.php';
            $assinar = !empty($_POST['assinar']) && (string) $_POST['assinar'] === '1' ? '1' : '0';
            $incluirProfissional = !isset($_POST['incluir_profissional']) || (string) $_POST['incluir_profissional'] !== '0' ? '1' : '0';
            $incluirDataHora = !isset($_POST['incluir_data_hora']) || (string) $_POST['incluir_data_hora'] !== '0' ? '1' : '0';
            $incluirFoto = !isset($_POST['incluir_foto']) || (string) $_POST['incluir_foto'] !== '0' ? '1' : '0';
            $incluirCursosTurmas = !isset($_POST['incluir_cursos_turmas']) || (string) $_POST['incluir_cursos_turmas'] !== '0' ? '1' : '0';
            $_SESSION['prontuario_pdf_token'] = bin2hex(random_bytes(32));
            $token = $_SESSION['prontuario_pdf_token'];

            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Abrindo prontuarios</title></head><body>';
            echo '<form id="prontuarioPdfLoteForm" method="POST" action="' . htmlspecialchars($pdfUrl, ENT_QUOTES, 'UTF-8') . '">';
            foreach ($ids as $idItem) {
                echo '<input type="hidden" name="ids[]" value="' . (int) $idItem . '">';
            }
            echo '<input type="hidden" name="assinar" value="' . htmlspecialchars($assinar, ENT_QUOTES, 'UTF-8') . '">';
            echo '<input type="hidden" name="incluir_profissional" value="' . htmlspecialchars($incluirProfissional, ENT_QUOTES, 'UTF-8') . '">';
            echo '<input type="hidden" name="incluir_data_hora" value="' . htmlspecialchars($incluirDataHora, ENT_QUOTES, 'UTF-8') . '">';
            echo '<input type="hidden" name="incluir_foto" value="' . htmlspecialchars($incluirFoto, ENT_QUOTES, 'UTF-8') . '">';
            echo '<input type="hidden" name="incluir_cursos_turmas" value="' . htmlspecialchars($incluirCursosTurmas, ENT_QUOTES, 'UTF-8') . '">';
            echo '<input type="hidden" name="token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
            echo '</form>';
            echo '<script>document.getElementById("prontuarioPdfLoteForm").submit();</script>';
            echo '</body></html>';
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            JsonResponse::error('ID invalido', [], 400);
        }

        $_GET['assinar'] = !empty($_POST['assinar']) && (string) $_POST['assinar'] === '1' ? '1' : '0';
        $_GET['incluir_profissional'] = !isset($_POST['incluir_profissional']) || (string) $_POST['incluir_profissional'] !== '0' ? '1' : '0';
        $_GET['incluir_data_hora'] = !isset($_POST['incluir_data_hora']) || (string) $_POST['incluir_data_hora'] !== '0' ? '1' : '0';
        $_GET['incluir_foto'] = !isset($_POST['incluir_foto']) || (string) $_POST['incluir_foto'] !== '0' ? '1' : '0';
        $_GET['incluir_cursos_turmas'] = !isset($_POST['incluir_cursos_turmas']) || (string) $_POST['incluir_cursos_turmas'] !== '0' ? '1' : '0';
        $this->pdf((string) $id);
    }
}
