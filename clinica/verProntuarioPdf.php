<?php
/**
 * Validacao publica de autenticidade do prontuario.
 */
date_default_timezone_set('America/Sao_Paulo');
if (session_status() === PHP_SESSION_NONE) {
    session_name('PHPSESSID3');
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/conecta/clinica/verProntuarioPdf.php'));
    $projectBasePath = preg_match('#^(.*?)/clinica(?:/|$)#', $scriptName, $matches) ? rtrim((string)($matches[1] ?? ''), '/') : '';
    $sessionCookiePath = $projectBasePath !== '' ? $projectBasePath : '/';
    session_set_cookie_params([
        'path' => $sessionCookiePath,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    @session_start();
}

$basePath = $_SESSION['BASE_para_PATH'] ?? dirname(__DIR__);
$basePath = rtrim(str_replace('\\', '/', (string) $basePath), '/');
$conexaoPath = $basePath . '/api/conectabd/conexao.php';
if (!is_file($conexaoPath)) {
    $conexaoPath = $basePath . '/conectabd/conexao.php';
}
require_once $conexaoPath;

function carregarNomePublicoColaborador(int $idColaborador): array
{
    if ($idColaborador <= 0) {
        return [];
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/clinica/verProntuarioPdf.php');
    $pos = strpos($scriptName, '/clinica');
    $projectBasePath = $pos === false ? '' : rtrim(substr($scriptName, 0, $pos), '/');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $baseUrl = ($isHttps ? 'https' : 'http') . '://' . $host . $projectBasePath;
    $url = rtrim($baseUrl, '/') . '/api/v1/colaboradores-publicos/' . $idColaborador . '/nome';

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Accept: application/json\r\nContent-Type: application/json",
            'timeout' => 15,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    if (!is_string($response) || trim($response) === '') {
        return [];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded) || !($decoded['success'] ?? false) || !is_array($decoded['data'] ?? null)) {
        return [];
    }

    return $decoded['data'];
}

function resolverNomeProfissionalValidacao(array $row): string
{
    $profissionalId = (int) ($row['profissional_id'] ?? 0);
    if ($profissionalId > 0) {
        $colaborador = carregarNomePublicoColaborador($profissionalId);
        $nome = trim((string) ($colaborador['nome_completo'] ?? ''));
        if ($nome !== '') {
            return $nome;
        }
    }

    return trim((string) ($row['profissional_nome_livre'] ?? ''));
}

function normalizarCodigoValidacao(string $code): string
{
    return strtoupper((string) preg_replace('/[^a-zA-Z0-9]/', '', $code));
}

function buscarAssinaturaProntuarioPorCodigo(PDO $pdo, string $code): ?array
{
    $normalizado = normalizarCodigoValidacao($code);
    if ($normalizado === '') {
        return null;
    }

    $sql = $pdo->prepare(
        "SELECT *
           FROM tbpdf_assinado
          WHERE REPLACE(
                    REPLACE(
                      REPLACE(
                        REPLACE(
                          REPLACE(
                            REPLACE(
                              REPLACE(UPPER(AssinaturaBase64), ' ', ''),
                            '-', ''),
                          '.', ''),
                        '/', ''),
                      '+', ''),
                    '=', ''),
                  '_', '') = ?
            AND (
                  NomeOriginal LIKE 'prontuario:%'
                  OR urlValidacao LIKE '%/clinica/verProntuarioPdf.php%'
                )
          LIMIT 1"
    );
    $sql->execute([$normalizado]);
    $row = $sql->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function extrairIdProntuarioAssinado(array $assinatura): int
{
    $nomeOriginal = trim((string) ($assinatura['NomeOriginal'] ?? ''));
    if (preg_match('/^prontuario:(\d+)$/', $nomeOriginal, $matches)) {
        return (int) $matches[1];
    }

    $urlValidacao = trim((string) ($assinatura['urlValidacao'] ?? ''));
    if ($urlValidacao !== '') {
        $query = (string) parse_url($urlValidacao, PHP_URL_QUERY);
        if ($query !== '') {
            parse_str($query, $params);
            if (!empty($params['id'])) {
                return (int) $params['id'];
            }
        }
    }

    return 0;
}

$configuredPublicBase = trim((string)(getenv('APP_PUBLIC_BASE_URL') ?: ''));
if ($configuredPublicBase === '') {
    $projectRoot = dirname(__DIR__);
    $envCandidates = [$projectRoot . '/.env', $projectRoot . '/temp/.env'];
    foreach ($envCandidates as $envPath) {
        if (!is_file($envPath) || !is_readable($envPath)) {
            continue;
        }
        $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim((string)$line);
            if ($line === '' || str_starts_with($line, '#') || !str_starts_with($line, 'APP_PUBLIC_BASE_URL=')) {
                continue;
            }
            $configuredPublicBase = trim((string)substr($line, strlen('APP_PUBLIC_BASE_URL=')));
            $len = strlen($configuredPublicBase);
            if ($len >= 2 && (($configuredPublicBase[0] === '"' && $configuredPublicBase[$len - 1] === '"') || ($configuredPublicBase[0] === "'" && $configuredPublicBase[$len - 1] === "'"))) {
                $configuredPublicBase = substr($configuredPublicBase, 1, -1);
            }
            break 2;
        }
    }
}

if ($configuredPublicBase !== '') {
    $urlBase = rtrim($configuredPublicBase, '/');
} else {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/clinica/verProntuarioPdf.php');
    $pos = strpos($scriptName, '/clinica');
    $projectBasePath = $pos === false ? '' : rtrim(substr($scriptName, 0, $pos), '/');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $urlBase = ($isHttps ? 'https' : 'http') . '://' . $host . $projectBasePath;
}
$urlValidacao = rtrim($urlBase, '/') . '/clinica/verProntuarioPdf.php';
$urlPdfAssinado = '';
$nomeArquivoAssinado = '';
$pdfAssinadoDisponivel = false;

$css = '
body{background:#eef2f7;font-family:Arial,sans-serif;padding:30px;text-align:center;}
.card{background:white;padding:30px 25px;max-width:500px;margin:0 auto;border-radius:16px;box-shadow:0 8px 25px rgba(0,0,0,.12);animation:fadeIn .5s ease;text-align:left;}
.icon-ok{font-size:60px;color:#28a745;text-align:center;margin-bottom:10px;}
.icon-cert{font-size:60px;color:#007bff;margin-bottom:10px;}
.icon-error{font-size:60px;color:#dc3545;margin-bottom:10px;}
h2{margin:0;font-weight:700;color:#333;text-align:center;}
p{color:#444;font-size:15px;line-height:1.5;}
input{width:100%;padding:12px;margin-top:15px;border-radius:8px;border:1px solid #ccc;font-size:16px;box-sizing:border-box;}
button,.btn{display:block;width:100%;padding:12px;border-radius:8px;border:none;background:#007bff;color:white;font-size:17px;cursor:pointer;margin-top:15px;text-decoration:none;text-align:center;box-sizing:border-box;}
button:hover,.btn:hover{background:#0069d9;}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
';

if (!isset($_GET['code']) || empty(trim((string) $_GET['code']))) {
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Validação de Prontuário - ITEVA</title><meta name="viewport" content="width=device-width,initial-scale=1"><style><?= $css ?></style></head>
<body>
<div class="card">
<div class="icon-cert">&#128274;</div>
<h2>Validação de Prontuário</h2>
<p>Digite o código de validação ou escaneie o QR Code do documento para verificar a autenticidade.</p>
<form method="GET" action="">
<input type="text" name="code" placeholder="Insira o código de validação" required>
<button type="submit">Validar</button>
</form>
</div>
</body>
</html>
<?php
    exit;
}

$codeRaw = trim((string) ($_GET['code'] ?? ''));
$assinatura = buscarAssinaturaProntuarioPorCodigo($pdo, $codeRaw);
$codigoLegado = false;
$prontuarioId = 0;
$profissionalId = 0;
$timestamp = 0;

if ($assinatura) {
    $prontuarioId = extrairIdProntuarioAssinado($assinatura);
    $profissionalId = (int) ($assinatura['IdColaborador'] ?? 0);
    $timestamp = (int) ($assinatura['TimestampAssinatura'] ?? 0);
} else {
    $codigoLegado = true;
    $decoded = @base64_decode($codeRaw, true);
    if ($decoded !== false && strpos($decoded, '|') !== false) {
        $partes = explode('|', $decoded, 3);
        $prontuarioId = (int) ($partes[0] ?? 0);
        $profissionalId = (int) ($partes[1] ?? 0);
        $timestamp = (int) ($partes[2] ?? 0);
    }
}

if ($prontuarioId <= 0) {
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Código Inválido</title><meta name="viewport" content="width=device-width,initial-scale=1"><style><?= $css ?></style></head>
<body>
<div class="card">
<div class="icon-error">&#10060;</div>
<h2>Código Inválido</h2>
<p>O código informado não é válido.</p>
<a href="verProntuarioPdf.php" class="btn">Tentar Novamente</a>
</div>
</body>
</html>
<?php
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.id, p.created_at, p.profissional_id,
           c.profissional_nome_livre,
           al.Nome AS paciente_nome,
           c.data_consulta, c.hora_inicio_prevista
    FROM tb_prontuario p
    JOIN tbAluno al ON al.IdUsuario = p.aluno_id
    LEFT JOIN tb_consulta c ON c.id = p.consulta_id
    WHERE p.id = ?
");
$stmt->execute([$prontuarioId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    $signatarioId = $profissionalId > 0 ? $profissionalId : (int) ($row['profissional_id'] ?? 0);
    $row['profissional_nome'] = resolverNomeProfissionalValidacao([
        'profissional_id' => $signatarioId,
        'profissional_nome_livre' => $row['profissional_nome_livre'] ?? '',
    ]);
}

if (
    !$row
    || ($codigoLegado && $profissionalId > 0 && (int) $row['profissional_id'] !== $profissionalId)
) {
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Documento Não Encontrado</title><meta name="viewport" content="width=device-width,initial-scale=1"><style><?= $css ?></style></head>
<body>
<div class="card">
<div class="icon-error">&#10060;</div>
<h2>Documento Não Encontrado</h2>
<p>Nenhum prontuário foi localizado com o código informado.</p>
<a href="verProntuarioPdf.php" class="btn">Tentar Novamente</a>
</div>
</body>
</html>
<?php
    exit;
}

if ($assinatura && !empty($assinatura['NomeArquivo'])) {
    $nomeArquivoAssinado = (string) $assinatura['NomeArquivo'];
    $urlPdfAssinado = rtrim($urlBase, '/') . '/app/storage/assinatura/assinados/' . rawurlencode($nomeArquivoAssinado);
    $pathPdfAssinado = $basePath . '/app/storage/assinatura/assinados/' . $nomeArquivoAssinado;
    $pdfAssinadoDisponivel = is_file($pathPdfAssinado);
}

$dataDoc = date('d/m/Y H:i', $timestamp ?: strtotime((string) $row['created_at']));
if (!empty($row['data_consulta'])) {
    $dt = DateTime::createFromFormat('Y-m-d', (string) $row['data_consulta']);
    if ($dt) {
        $dataDoc = $dt->format('d/m/Y');
        if (!empty($row['hora_inicio_prevista']) && preg_match('/^\d{2}:\d{2}/', trim((string) $row['hora_inicio_prevista']))) {
            $dataDoc .= ' ' . substr(trim((string) $row['hora_inicio_prevista']), 0, 5);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Validação de Prontuário - ITEVA</title><meta name="viewport" content="width=device-width,initial-scale=1"><style><?= $css ?></style></head>
<body>
<div class="card">
<div class="icon-ok">&#128196;</div>
<h2>Documento Validado</h2>
<p><b>Nome do documento:</b><br>Prontuário Clínico #<?= (int) $row['id'] ?></p>
<p><b>Assinado por:</b><br><?= htmlspecialchars((string) $row['profissional_nome'], ENT_QUOTES, 'UTF-8') ?></p>
<p><b>Data/Hora da assinatura:</b><br><?= htmlspecialchars($dataDoc, ENT_QUOTES, 'UTF-8') ?></p>
<p><b>Paciente:</b><br><?= htmlspecialchars((string) $row['paciente_nome'], ENT_QUOTES, 'UTF-8') ?></p>
<p><b>Código de validação:</b><br><span style="font-family:monospace;font-size:16px;"><?= htmlspecialchars((string) ($assinatura['AssinaturaBase64'] ?? $codeRaw), ENT_QUOTES, 'UTF-8') ?></span></p>
<p><b>URL de validação:</b><br><a href="<?= htmlspecialchars($urlValidacao, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($urlValidacao, ENT_QUOTES, 'UTF-8') ?></a></p>
<?php if ($urlPdfAssinado !== '' && $pdfAssinadoDisponivel): ?>
<a href="<?= htmlspecialchars($urlPdfAssinado, ENT_QUOTES, 'UTF-8') ?>" class="btn" target="_blank" rel="noopener noreferrer">Abrir Documento Assinado</a>
<?php endif; ?>
<?php if ($urlPdfAssinado !== '' && !$pdfAssinadoDisponivel): ?>
<p><b>Documento assinado:</b><br>Registro encontrado, mas o arquivo PDF nao esta disponivel neste ambiente.</p>
<?php endif; ?>
<a href="verProntuarioPdf.php" class="btn">Validar Outro Documento</a>
</div>
</body>
</html>
