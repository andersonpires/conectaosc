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

$arquivosRaw = isset($_POST['arquivos']) && is_array($_POST['arquivos']) ? $_POST['arquivos'] : [];

$arquivos = array_values(array_filter(
    array_map(static function (mixed $f): string {
        $s = trim((string)$f);
        return preg_match('/^contrato_[a-zA-Z0-9_]+\.pdf$/', $s) ? $s : '';
    }, $arquivosRaw),
    static fn(string $v): bool => $v !== ''
));

if (empty($arquivos)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'erro' => 'Nenhum arquivo válido informado para compactação.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pathAssinados = rtrim($BASE_para_PATH, '/\\') . '/app/storage/assinatura/assinados/';
    $pathLotes = rtrim($BASE_para_PATH, '/\\') . '/app/storage/assinatura/lotes/';

    if (!is_dir($pathLotes)) {
        @mkdir($pathLotes, 0775, true);
    }

    $zipName = 'contratos_' . date('Ymd_His') . '_' . substr(md5(uniqid('', true)), 0, 6) . '.zip';
    $zipPath = $pathLotes . $zipName;

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Não foi possível criar o arquivo ZIP.');
    }

    $adicionados = 0;
    foreach ($arquivos as $nomeArquivo) {
        $filePath = $pathAssinados . $nomeArquivo;
        if (!is_file($filePath)) {
            continue;
        }
        $conteudo = @file_get_contents($filePath);
        if ($conteudo === false || $conteudo === '') {
            continue;
        }
        if ($zip->addFromString($nomeArquivo, $conteudo)) {
            $adicionados++;
        }
    }

    $zip->close();

    if ($adicionados === 0) {
        @unlink($zipPath);
        throw new RuntimeException('Nenhum arquivo foi encontrado para compactação.');
    }

    $urlZip = rtrim((string)$BASE_para_URL, '/') . '/app/storage/assinatura/lotes/' . rawurlencode($zipName);

    echo json_encode([
        'ok' => true,
        'url_download' => $urlZip,
        'nome_zip' => $zipName,
        'total' => $adicionados,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'erro' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
