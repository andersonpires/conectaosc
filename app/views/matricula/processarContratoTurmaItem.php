<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', '86400');
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'erro' => 'Sessão inválida.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
require_once $BASE_para_PATH . '/api/conectabd/conexao.php';
require_once $BASE_para_PATH . '/api/lib/tcpdf/tcpdf.php';
require_once $BASE_para_PATH . '/api/lib/tcpdf/fpdi/autoload.php';
require_once __DIR__ . '/contratoAssinaturaDigital.php';
require_once __DIR__ . '/contratoLoteHelper.php';

$idMatricula = isset($_POST['id_matricula']) ? (int)$_POST['id_matricula'] : 0;
if ($idMatricula <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'erro' => 'Parâmetro id_matricula inválido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $sql = $pdo->prepare("\n        SELECT a.*, c.NomeCurso, t.NomeTurma, m.IdMatricula, m.vData, p.LogoProjeto, p.TermosContrato\n          FROM tbMatricula m\n          JOIN tbAluno a ON m.IdUsuario = a.IdUsuario\n          JOIN tbCurso c ON m.IdCurso = c.IdCurso\n          JOIN tbTurma t ON m.IdTurma = t.IdTurma\n     LEFT JOIN tbProjeto p ON c.IdProjeto = p.IdProjeto\n         WHERE m.IdMatricula = ?\n           AND m.Habilitado = 1\n           AND a.Habilitado = 1\n         LIMIT 1\n    ");
    $sql->execute([$idMatricula]);
    $dados = $sql->fetch(PDO::FETCH_ASSOC);

    if (!$dados) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'erro' => 'Matrícula não encontrada ou inativa.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $configAssinatura = loteContratoCarregarConfig($pdo, $BASE_para_PATH);
    $idSessao = (int)($_SESSION['Cod'] ?? 0);
    $nomeSessao = trim((string)(($_SESSION['Nome'] ?? '') . ' ' . ($_SESSION['Sobrenome'] ?? '')));

    $resultado = loteContratoGerarContratoAssinadoPorMatricula(
        $pdo,
        $BASE_para_PATH,
        $BASE_para_URL,
        $dados,
        $configAssinatura,
        $idSessao,
        $nomeSessao
    );

    if (empty($resultado['ok'])) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'erro' => (string)($resultado['erro'] ?? 'Erro ao assinar contrato.')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $nomeArquivo = (string)($resultado['nomeArquivo'] ?? '');
    $urlDownload = rtrim((string)$BASE_para_URL, '/') . '/app/storage/assinatura/assinados/' . rawurlencode($nomeArquivo);

    echo json_encode([
        'ok' => true,
        'nome_arquivo' => $nomeArquivo,
        'url_download' => $urlDownload,
        'codigo_validacao' => (string)($resultado['codigo'] ?? ''),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'erro' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
