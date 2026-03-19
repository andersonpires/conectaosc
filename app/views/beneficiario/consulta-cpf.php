<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
header('Content-Type: application/json; charset=utf-8');

$basePath = $BASE_para_PATH;
$apiKeyCPF = null;
$apiCpfPath = $basePath . '/temp/api-cpf.php';
if (is_file($apiCpfPath)) {
    require_once $apiCpfPath;
}

$logPath = __DIR__ . '/consulta-cpf.log';
function logConsultaCpf(string $mensagem, string $logPath): void
{
    $linha = '[' . date('Y-m-d H:i:s') . '] ' . $mensagem . PHP_EOL;
    $ok = @file_put_contents($logPath, $linha, FILE_APPEND);
    if ($ok === false) {
        error_log('[consulta-cpf] ' . $mensagem);
    }
}

$cpf = isset($_GET['cpf']) ? preg_replace('/[^0-9]/', '', $_GET['cpf']) : '';

if (strlen($cpf) !== 11) {
    http_response_code(400);
    echo json_encode(['code' => 400, 'message' => 'CPF inválido.']);
    exit;
}

if (empty($apiKeyCPF)) {
    http_response_code(500);
    echo json_encode(['code' => 500, 'message' => 'API key CPF não configurada.']);
    exit;
}

if (!function_exists('curl_init')) {
    http_response_code(500);
    echo json_encode(['code' => 500, 'message' => 'Extensão cURL indisponível no servidor.']);
    exit;
}

// Teste rápido de saída (TCP 443) para detectar bloqueio de rede
$sockErrNo = 0;
$sockErrStr = '';
$sock = @fsockopen('apicpf.com', 443, $sockErrNo, $sockErrStr, 5);
if (!$sock) {
    logConsultaCpf("Bloqueio de saída TCP: {$sockErrStr} ({$sockErrNo})", $logPath);
    http_response_code(502);
    echo json_encode(['code' => 502, 'message' => 'Bloqueio de saída no servidor (porta 443).']);
    exit;
}
fclose($sock);

$url = "https://apicpf.com/api/consulta?cpf=" . $cpf;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-API-KEY: {$apiKeyCPF}",
]);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$info = curl_getinfo($ch);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);
curl_close($ch);

if ($response === false) {
    $tempos = "namelookup={$info['namelookup_time']} connect={$info['connect_time']} total={$info['total_time']}";
    logConsultaCpf("cURL erro {$curlErrno}: {$curlError} | HTTP {$httpCode} | CPF {$cpf} | {$tempos}", $logPath);
    http_response_code(502);
    echo json_encode(['code' => 502, 'message' => 'Erro ao consultar API.', 'detail' => $curlError]);
    exit;
}

if ($httpCode < 200 || $httpCode >= 300) {
    logConsultaCpf("HTTP {$httpCode} | CPF {$cpf} | Resposta: {$response}", $logPath);
}

http_response_code($httpCode ?: 200);
echo $response;





