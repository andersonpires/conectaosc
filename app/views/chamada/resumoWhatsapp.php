<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];

require_once $BASE_para_PATH . '/api/conectabd/conexao.php';
require_once __DIR__ . '/funcoes.php';

bootstrap_apply_php_runtime();

function chamadaResumoWhatsappResponder(array $payload, int $statusCode = 200): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function chamadaResumoWhatsappEntrada(): array
{
    $contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
    if (str_contains($contentType, 'application/json')) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode((string)$raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    return $_POST;
}

function chamadaResumoWhatsappSomenteDigitos(?string $valor): string
{
    return preg_replace('/\D+/', '', (string)$valor) ?? '';
}

function chamadaResumoWhatsappMascarar(?string $whatsapp): string
{
    $digitos = chamadaResumoWhatsappSomenteDigitos($whatsapp);
    if ($digitos === '') {
        return '';
    }

    $tamanho = strlen($digitos);
    if ($tamanho <= 4) {
        return str_repeat('*', $tamanho);
    }

    return str_repeat('*', max(0, $tamanho - 4)) . substr($digitos, -4);
}

function chamadaResumoWhatsappStatus(array $row): array
{
    if ((int)($row['presenca'] ?? 0) === 1) {
        return ['status' => 'Presença', 'codigo' => 'P', 'grupo' => 'presencas'];
    }

    if ((int)($row['faltajust'] ?? 0) === 1) {
        return ['status' => 'Falta justificada', 'codigo' => 'FJ', 'grupo' => 'faltas_justificadas'];
    }

    if ((int)($row['falta'] ?? 0) === 1) {
        return ['status' => 'Falta não justificada', 'codigo' => 'F', 'grupo' => 'faltas_nao_justificadas'];
    }

    return ['status' => 'Chamada não realizada', 'codigo' => 'NA', 'grupo' => 'sem_chamada'];
}

function chamadaResumoWhatsappBuscarDados(PDO $pdo, int $idCurso, int $idTurma, string $dataIso): array
{
    $sql = "SELECT
            tbMatricula.IdMatricula,
            tbTurma.IdTurma,
            tbTurma.NomeTurma,
            tbCurso.IdCurso,
            tbCurso.NomeCurso,
            tbAluno.IdUsuario AS IdAluno,
            tbAluno.Nome,
            tbAluno.Apelido,
            ch.IdChamada,
            ch.presenca,
            ch.falta,
            ch.faltajust
        FROM tbMatricula
        INNER JOIN tbTurma ON tbMatricula.IdTurma = tbTurma.IdTurma
        INNER JOIN tbCurso ON tbMatricula.IdCurso = tbCurso.IdCurso
        INNER JOIN tbAluno ON tbMatricula.IdUsuario = tbAluno.IdUsuario
        LEFT JOIN tbChamada ch
            ON ch.IdChamada = (
                SELECT ch2.IdChamada
                FROM tbChamada ch2
                WHERE ch2.IdMatricula = tbMatricula.IdMatricula
                  AND ch2.IdCurso = tbCurso.IdCurso
                  AND ch2.IdTurma = tbTurma.IdTurma
                  AND ch2.Data = ?
                ORDER BY ch2.IdChamada DESC
                LIMIT 1
            )
        WHERE tbCurso.IdCurso = ?
          AND tbTurma.IdTurma = ?
          AND tbMatricula.Habilitado = 1
        ORDER BY tbAluno.Nome";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dataIso, $idCurso, $idTurma]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function chamadaResumoWhatsappMontarMensagem(array $payload, array $nomesPorGrupo): string
{
    $totais = $payload['totais'];
    $chamada = $payload['chamada'];
    $linhas = [
        'Resumo da chamada',
        '',
        'Curso: ' . $chamada['curso']['nome'],
        'Turma: ' . $chamada['turma']['nome'],
        'Data da chamada: ' . $chamada['data'],
        'Enviado em: ' . $payload['enviado_em_br'],
        '',
    ];

    $secoes = [
        'Presenças:' => 'presencas',
        'Faltas justificadas:' => 'faltas_justificadas',
        'Faltas não justificadas:' => 'faltas_nao_justificadas',
        'Sem chamada realizada:' => 'sem_chamada',
    ];

    foreach ($secoes as $titulo => $grupo) {
        $linhas[] = $titulo;
        $nomes = $nomesPorGrupo[$grupo] ?? [];
        if ($nomes === []) {
            $linhas[] = '- Nenhum aluno';
        } else {
            foreach ($nomes as $nome) {
                $linhas[] = '- ' . $nome;
            }
        }
        $linhas[] = '';
    }

    $linhas[] = 'Totais:';
    $linhas[] = 'Presenças: ' . $totais['presencas'];
    $linhas[] = 'Faltas justificadas: ' . $totais['faltas_justificadas'];
    $linhas[] = 'Faltas não justificadas: ' . $totais['faltas_nao_justificadas'];
    $linhas[] = 'Sem chamada: ' . $totais['sem_chamada'];
    $linhas[] = 'Total de alunos: ' . $totais['alunos'];

    return implode("\n", $linhas);
}

function chamadaResumoWhatsappMontarPayload(array $colaborador, string $whatsapp, int $idCurso, int $idTurma, string $dataSelecionada, array $rows): array
{
    $agora = new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));
    $nomeCurso = (string)($rows[0]['NomeCurso'] ?? '');
    $nomeTurma = (string)($rows[0]['NomeTurma'] ?? '');
    $nomeCompleto = trim((string)(($colaborador['Nome'] ?? '') . ' ' . ($colaborador['Sobrenome'] ?? '')));
    $totais = [
        'alunos' => count($rows),
        'presencas' => 0,
        'faltas_justificadas' => 0,
        'faltas_nao_justificadas' => 0,
        'sem_chamada' => 0,
    ];
    $alunos = [];
    $nomesPorGrupo = [
        'presencas' => [],
        'faltas_justificadas' => [],
        'faltas_nao_justificadas' => [],
        'sem_chamada' => [],
    ];

    foreach ($rows as $row) {
        $status = chamadaResumoWhatsappStatus($row);
        $grupo = $status['grupo'];
        $totais[$grupo]++;

        $nomeAluno = trim((string)($row['Nome'] ?? ''));
        $apelido = trim((string)($row['Apelido'] ?? ''));
        if ($apelido !== '') {
            $nomeAluno = '(' . $apelido . ') ' . $nomeAluno;
        }

        $nomesPorGrupo[$grupo][] = $nomeAluno;
        $alunos[] = [
            'id_aluno' => (int)($row['IdAluno'] ?? 0),
            'id_matricula' => (int)($row['IdMatricula'] ?? 0),
            'nome' => $nomeAluno,
            'status' => $status['status'],
            'codigo_status' => $status['codigo'],
        ];
    }

    $payload = [
        'evento' => 'resumo_chamada_whatsapp',
        'origem' => 'ConectaOSC',
        'enviado_em' => $agora->format(DateTimeInterface::ATOM),
        'enviado_em_br' => $agora->format('d/m/Y H:i'),
        'solicitante' => [
            'id_colaborador' => (int)($colaborador['IdColaborador'] ?? 0),
            'nome' => $nomeCompleto,
            'whatsapp' => $whatsapp,
            'email' => (string)($colaborador['Email'] ?? ''),
        ],
        'chamada' => [
            'data' => $dataSelecionada,
            'curso' => [
                'id' => $idCurso,
                'nome' => $nomeCurso,
            ],
            'turma' => [
                'id' => $idTurma,
                'nome' => $nomeTurma,
            ],
        ],
        'totais' => $totais,
        'alunos' => $alunos,
        'mensagem' => '',
    ];

    $payload['mensagem'] = chamadaResumoWhatsappMontarMensagem($payload, $nomesPorGrupo);
    return $payload;
}

if (!isset($_SESSION['Cod'])) {
    chamadaResumoWhatsappResponder([
        'success' => false,
        'message' => 'Sessão expirada. Faça login novamente.',
    ], 401);
}

$entrada = chamadaResumoWhatsappEntrada();
$idCurso = (int)($entrada['NNomeCurso'] ?? $entrada['idCurso'] ?? 0);
$idTurma = (int)($entrada['NNomeTurma'] ?? $entrada['idTurma'] ?? 0);
$dataSelecionada = trim((string)($entrada['dataSelecionada'] ?? ''));
$dataIso = chamadaDataBrParaIso($dataSelecionada);

if ($idCurso <= 0 || $idTurma <= 0 || $dataIso === null) {
    chamadaResumoWhatsappResponder([
        'success' => false,
        'message' => 'Selecione curso, turma e data da chamada.',
    ], 400);
}

$idColaborador = (int)($_SESSION['Cod'] ?? 0);

try {
    $stmt = $pdo->prepare(
        'SELECT IdColaborador, Nome, Sobrenome, WhatsApp, Email
           FROM tbUser
          WHERE IdColaborador = ?
          LIMIT 1'
    );
    $stmt->execute([$idColaborador]);
    $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$colaborador) {
        chamadaResumoWhatsappResponder([
            'success' => false,
            'message' => 'Colaborador não encontrado.',
        ], 404);
    }

    $whatsapp = chamadaResumoWhatsappSomenteDigitos((string)($colaborador['WhatsApp'] ?? ''));
    if ($whatsapp === '') {
        chamadaResumoWhatsappResponder([
            'success' => false,
            'message' => 'WhatsApp não cadastrado.',
        ], 400);
    }

    $nomeCompleto = trim((string)(($colaborador['Nome'] ?? '') . ' ' . ($colaborador['Sobrenome'] ?? '')));
    $rows = chamadaResumoWhatsappBuscarDados($pdo, $idCurso, $idTurma, $dataIso);
    if ($rows === []) {
        chamadaResumoWhatsappResponder([
            'success' => false,
            'message' => 'Nenhum aluno encontrado para a chamada selecionada.',
        ], 404);
    }

    $payload = chamadaResumoWhatsappMontarPayload($colaborador, $whatsapp, $idCurso, $idTurma, $dataSelecionada, $rows);

    chamadaResumoWhatsappResponder([
        'success' => true,
        'message' => 'Resumo da chamada preparado.',
        'data' => [
            'colaborador' => [
                'id_colaborador' => (int)$colaborador['IdColaborador'],
                'nome' => $nomeCompleto,
                'whatsapp_mascarado' => chamadaResumoWhatsappMascarar($whatsapp),
                'email' => (string)($colaborador['Email'] ?? ''),
            ],
            'chamada' => $payload['chamada'],
            'totais' => $payload['totais'],
            'alunos' => $payload['alunos'],
            'mensagem' => $payload['mensagem'],
        ],
    ]);
} catch (Throwable $e) {
    error_log('[resumo-whatsapp] Falha ao validar colaborador: ' . $e->getMessage());
    chamadaResumoWhatsappResponder([
        'success' => false,
        'message' => 'Não foi possível validar o envio por WhatsApp. Tente novamente.',
    ], 500);
}
