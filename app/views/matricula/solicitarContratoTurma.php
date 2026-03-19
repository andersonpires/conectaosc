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

$idTurma = isset($_POST['turma']) ? (int)$_POST['turma'] : 0;
$emailDestino = trim((string)($_POST['email_destino'] ?? ''));

if ($idTurma <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'erro' => 'Parâmetro turma inválido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($emailDestino === '' || !filter_var($emailDestino, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'erro' => 'Informe um e-mail válido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmtTurma = $pdo->prepare("\n        SELECT t.IdTurma, t.NomeTurma, c.IdCurso, c.NomeCurso\n          FROM tbTurma t\n          JOIN tbCurso c ON c.IdCurso = t.IdCurso\n         WHERE t.IdTurma = ?\n         LIMIT 1\n    ");
    $stmtTurma->execute([$idTurma]);
    $turma = $stmtTurma->fetch(PDO::FETCH_ASSOC);

    if (!$turma) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'erro' => 'Turma não encontrada.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $idSolicitante = (int)($_SESSION['Cod'] ?? 0);
    $tokenDownload = bin2hex(random_bytes(20));

    $stmt = $pdo->prepare("\n        INSERT INTO tb_Cron_Contrato\n            (TipoReferencia, IdCurso, IdTurma, NomeCurso, NomeTurma, EmailDestino, Status, IdColaboradorSolicitante, TokenDownload, DataSolicitacao, DataAtualizacao)\n        VALUES\n            ('TURMA', ?, ?, ?, ?, ?, 1, ?, ?, NOW(), NOW())\n    ");

    $stmt->execute([
        (int)$turma['IdCurso'],
        (int)$turma['IdTurma'],
        (string)$turma['NomeCurso'],
        (string)$turma['NomeTurma'],
        $emailDestino,
        $idSolicitante > 0 ? $idSolicitante : null,
        $tokenDownload,
    ]);

    echo json_encode([
        'ok' => true,
        'mensagem' => 'Solicitação registrada. O envio por e-mail será realizado após o processamento.',
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'erro' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
