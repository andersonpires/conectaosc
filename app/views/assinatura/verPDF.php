<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];

require_once $BASE_para_PATH . '/api/conectabd/conexao.php';

function normalizarCodigoValidacao(string $code): string
{
    return strtoupper((string)preg_replace('/[^a-zA-Z0-9]/', '', $code));
}

function buscarDocumentoPorCodigo(PDO $pdo, string $codeRaw): ?array
{
    $codeRawTrim = trim($codeRaw);
    if ($codeRawTrim !== '') {
        $sqlExato = $pdo->prepare("
            SELECT *
              FROM tbpdf_assinado
             WHERE UPPER(TRIM(AssinaturaBase64)) = UPPER(TRIM(?))
          ORDER BY TimestampAssinatura DESC
             LIMIT 1
        ");
        $sqlExato->execute([$codeRawTrim]);
        $row = $sqlExato->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }
    }

    $codeNormalizado = normalizarCodigoValidacao($codeRaw);
    if ($codeNormalizado === '') {
        return null;
    }

    $sqlFallback = $pdo->prepare("
        SELECT *
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
      ORDER BY TimestampAssinatura DESC
         LIMIT 1
    ");
    $sqlFallback->execute([$codeNormalizado]);
    $row = $sqlFallback->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

$codeRaw = (string)($_GET['code'] ?? '');
$codigoDigitado = trim($codeRaw);

if ($codigoDigitado === '') {
    ?>
    <!doctype html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Validação de Documento - ITEVA</title>
        <style>
            body { background:#eef2f7; font-family:Arial,sans-serif; padding:30px; text-align:center; }
            .card { background:#fff; padding:30px 25px; max-width:420px; margin:0 auto; border-radius:16px; box-shadow:0 8px 25px rgba(0,0,0,.12); }
            .icon { font-size:60px; color:#007bff; margin-bottom:10px; }
            h2 { margin:0; font-weight:700; color:#333; }
            p { color:#444; font-size:15px; margin-top:8px; }
            input { width:100%; padding:12px; margin-top:15px; border-radius:8px; border:1px solid #ccc; font-size:16px; box-sizing:border-box; }
            button { width:100%; padding:12px; margin-top:15px; border-radius:8px; border:none; background:#007bff; color:#fff; font-size:17px; cursor:pointer; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="icon">&#128196;&#10004;</div>
            <h2>Validação de Documento</h2>
            <p>Digite o código para verificar a autenticidade.</p>
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

$dados = buscarDocumentoPorCodigo($pdo, $codigoDigitado);
if (!$dados) {
    ?>
    <!doctype html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Documento Não Encontrado</title>
        <style>
            body { background:#eef2f7; font-family:Arial,sans-serif; padding:30px; text-align:center; }
            .card { background:#fff; padding:30px 25px; max-width:420px; margin:0 auto; border-radius:16px; box-shadow:0 8px 25px rgba(0,0,0,.12); }
            .icon { font-size:60px; color:#dc3545; margin-bottom:10px; }
            h2 { margin-top:0; font-weight:700; color:#333; }
            p { color:#444; font-size:15px; margin-top:8px; }
            .btn { display:block; width:100%; padding:12px; border-radius:8px; border:none; background:#007bff; color:#fff; font-size:17px; margin-top:15px; text-decoration:none; box-sizing:border-box; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="icon">&#10060;</div>
            <h2>Documento Não Encontrado</h2>
            <p>Nenhum documento foi localizado com o código informado:</p>
            <p><b><?= htmlspecialchars(normalizarCodigoValidacao($codigoDigitado), ENT_QUOTES, 'UTF-8') ?></b></p>
            <a href="<?= rtrim((string)$BASE_para_URL, '/') ?>/assinatura/pdf/validar/" class="btn">Tentar novamente</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$nomeArquivo = basename((string)($dados['NomeArquivo'] ?? ''));
$nomeDocumento = (string)($dados['NomeDocumento'] ?? '');
$idColab = (int)($dados['IdColaborador'] ?? 0);
$timestamp = (int)($dados['TimestampAssinatura'] ?? 0);
$codigoExibicao = (string)($dados['AssinaturaBase64'] ?? normalizarCodigoValidacao($codigoDigitado));

$urlPDF = rtrim((string)$BASE_para_URL, '/') . '/app/storage/assinatura/assinados/' . rawurlencode($nomeArquivo);

$sqlU = $pdo->prepare('SELECT Nome, Sobrenome FROM tbUser WHERE IdColaborador = ?');
$sqlU->execute([$idColab]);
$user = $sqlU->fetch(PDO::FETCH_ASSOC) ?: null;
$nomeColab = $user ? trim((string)(($user['Nome'] ?? '') . ' ' . ($user['Sobrenome'] ?? ''))) : 'Desconhecido';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Documento Validado - ITEVA</title>
    <style>
        body { background:#eef2f7; font-family:Arial,sans-serif; padding:30px; text-align:center; }
        .card { background:#fff; padding:30px 25px; max-width:620px; margin:0 auto; border-radius:16px; box-shadow:0 8px 25px rgba(0,0,0,.12); text-align:left; }
        .icon { font-size:60px; color:#28a745; text-align:center; margin-bottom:10px; }
        h2 { margin-top:0; text-align:center; font-weight:700; color:#333; }
        p { color:#444; font-size:15px; line-height:1.5; }
        .btn { display:block; width:100%; padding:12px; border-radius:8px; border:none; background:#007bff; color:#fff; font-size:17px; margin-top:15px; text-decoration:none; text-align:center; box-sizing:border-box; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">&#128196;&#10004;</div>
        <h2>Documento Validado</h2>
        <p><b>Nome do documento:</b><br><?= htmlspecialchars($nomeDocumento, ENT_QUOTES, 'UTF-8') ?></p>
        <p><b>Assinado por:</b><br><?= htmlspecialchars($nomeColab, ENT_QUOTES, 'UTF-8') ?></p>
        <p><b>Data/Hora da assinatura:</b><br><?= $timestamp > 0 ? date('d/m/Y H:i:s', $timestamp) : '-' ?></p>
        <p><b>Código de validação:</b><br><span style="font-family:monospace; font-size:16px;"><?= htmlspecialchars($codigoExibicao, ENT_QUOTES, 'UTF-8') ?></span></p>
        <a class="btn" href="<?= htmlspecialchars($urlPDF, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Abrir Documento Assinado</a>
        <a class="btn" href="<?= rtrim((string)$BASE_para_URL, '/') ?>/assinatura/pdf/validar/">Validar Outro Documento</a>
    </div>
</body>
</html>
