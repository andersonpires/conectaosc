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

    chamadaResumoWhatsappResponder([
        'success' => true,
        'message' => 'WhatsApp cadastrado.',
        'data' => [
            'colaborador' => [
                'id_colaborador' => (int)$colaborador['IdColaborador'],
                'nome' => $nomeCompleto,
                'whatsapp_mascarado' => chamadaResumoWhatsappMascarar($whatsapp),
                'email' => (string)($colaborador['Email'] ?? ''),
            ],
            'chamada' => [
                'id_curso' => $idCurso,
                'id_turma' => $idTurma,
                'data' => $dataSelecionada,
                'data_iso' => $dataIso,
            ],
        ],
    ]);
} catch (Throwable $e) {
    error_log('[resumo-whatsapp] Falha ao validar colaborador: ' . $e->getMessage());
    chamadaResumoWhatsappResponder([
        'success' => false,
        'message' => 'Não foi possível validar o envio por WhatsApp. Tente novamente.',
    ], 500);
}
