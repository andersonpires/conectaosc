<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
$invertextoApiToken = bootstrap_invertexto_api_token($BASE_para_PATH);
$appJsVersion = @filemtime($BASE_para_PATH . '/app/assets/js/app.js') ?: time();
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', '86400');
}

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header('Location: ' . rtrim((string) $BASE_para_URL, '/') . '/login/?redirect=' . $redirect_url);
    exit;
}

require_once $BASE_para_PATH . '/api/lib/tcpdf/tcpdf.php';
require_once $BASE_para_PATH . '/api/lib/tcpdf/fpdi/autoload.php';
require_once $BASE_para_PATH . '/api/conectabd/conexao.php';
require_once $BASE_para_PATH . '/api/legacy/checa-token.php';

use setasign\Fpdi\Tcpdf\Fpdi;

function assinaturaResolveAssinaturaDigitalPublicUrl(string $baseUrl): string
{
    $configured = trim((string)($_SESSION['BASE_PUBLIC_URL'] ?? (getenv('APP_PUBLIC_BASE_URL') ?: '')));
    if ($configured !== '') {
        return rtrim($configured, '/') . '/assinaturadigital';
    }

    $parsed = parse_url(trim($baseUrl));
    $basePath = preg_match('#^https?://#i', trim($baseUrl))
        ? (string)($parsed['path'] ?? '')
        : trim($baseUrl);
    $basePath = '/' . ltrim(str_replace('\\', '/', $basePath), '/');
    $basePath = rtrim($basePath, '/');
    $basePath = $basePath === '' ? '/conecta' : $basePath;

    $host = (string)($_SERVER['HTTP_HOST'] ?? ($parsed['host'] ?? ''));
    $host = strtolower($host);
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        ? 'https'
        : (string)($parsed['scheme'] ?? 'http');
    $port = isset($parsed['port']) ? ':' . (int)$parsed['port'] : '';

    if ($host === '' || str_contains($host, 'localhost') || str_starts_with($host, '127.0.0.1')) {
        return 'http://localhost' . $basePath . '/assinaturadigital';
    }

    return $scheme . '://' . $host . $port . $basePath . '/assinaturadigital';
}

function assinaturaResolvePublicBaseUrl(string $baseUrl): string
{
    $configured = trim((string)($_SESSION['BASE_PUBLIC_URL'] ?? (getenv('APP_PUBLIC_BASE_URL') ?: '')));
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $parsed = parse_url(trim($baseUrl));
    if (preg_match('#^https?://#i', trim($baseUrl))) {
        $scheme = (string)($parsed['scheme'] ?? 'http');
        $host = (string)($parsed['host'] ?? '');
        $port = isset($parsed['port']) ? ':' . (int)$parsed['port'] : '';
        $path = '/' . ltrim((string)($parsed['path'] ?? ''), '/');
        $path = rtrim($path, '/');
        if ($host !== '') {
            return $scheme . '://' . $host . $port . $path;
        }
    }

    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $path = '/' . ltrim(trim($baseUrl), '/');
    $path = rtrim($path, '/');

    if ($host !== '') {
        return $scheme . '://' . $host . $path;
    }

    return $path !== '' ? $path : '/conecta';
}

function assinaturaGerarCodigoValidacaoCurto(PDO $pdo, int $timestampAssinatura): string
{
    $alfabeto = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    for ($tentativa = 0; $tentativa < 20; $tentativa++) {
        $prefixo = '';
        for ($i = 0; $i < 2; $i++) {
            $prefixo .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }

        $codigo = $prefixo . $timestampAssinatura;
        $stmt = $pdo->prepare('SELECT 1 FROM tbpdf_assinado WHERE AssinaturaBase64 = ? LIMIT 1');
        $stmt->execute([$codigo]);
        if (!$stmt->fetchColumn()) {
            return $codigo;
        }
    }

    return 'ZZ' . $timestampAssinatura;
}

function assinaturaGerarNomeArquivoFinal(string $diretorioAssinados, int $idColaborador): string
{
    $timestampBase = time();
    $tentativa = 0;

    do {
        $timestampAtual = $timestampBase + $tentativa;
        $nome = 'documento_' . $timestampAtual . '_' . $idColaborador . '.pdf';
        $path = rtrim($diretorioAssinados, '/\\') . '/' . $nome;
        $tentativa++;
    } while (file_exists($path) && $tentativa < 10);

    return $nome;
}

$idColab = (int)($_SESSION['Cod'] ?? 0);
$nomeCompleto = trim((string)(($_SESSION['Nome'] ?? '') . ' ' . ($_SESSION['Sobrenome'] ?? '')));
if ($nomeCompleto === '') {
    $nomeCompleto = 'Colaborador';
}

$incluirCargo = (string)($_POST['incluir_cargo'] ?? '0') === '1';
$cargoPersonalizado = trim((string)($_POST['cargo_personalizado'] ?? ''));
$cargoPadrao = '';
if ($incluirCargo && $idColab > 0) {
    $stmtCargo = $pdo->prepare('SELECT COALESCE(Cargo, "") AS Cargo FROM tbUser WHERE IdColaborador = ? LIMIT 1');
    $stmtCargo->execute([$idColab]);
    $cargoPadrao = trim((string)($stmtCargo->fetchColumn() ?: ''));
}

$nomeUpper = $nomeCompleto;
$cargoParaExibir = $incluirCargo ? ($cargoPersonalizado !== '' ? $cargoPersonalizado : $cargoPadrao) : '';
if ($cargoParaExibir !== '') {
    $nomeUpper .= ' - ' . $cargoParaExibir;
}
$nomeUpper = mb_strtoupper($nomeUpper, 'UTF-8');

$publicBaseWithScheme = assinaturaResolvePublicBaseUrl((string)$BASE_para_URL);
$assinaturaDigitalPublic = assinaturaResolveAssinaturaDigitalPublicUrl($BASE_para_URL);

$pathBase = $BASE_para_PATH . '/app/storage/assinatura/';
$pathOriginais = $pathBase . 'originais/';
$pathFinais = $pathBase . 'assinados/';
$pathQR = $pathBase . 'qrcodes/';
if (!is_dir($pathFinais)) {
    @mkdir($pathFinais, 0775, true);
}
if (!is_dir($pathQR)) {
    @mkdir($pathQR, 0775, true);
}

$nomeArquivo = (string)($_POST['file'] ?? '');
$nomeDocumento = (string)($_POST['nomeDocumento'] ?? '');
$pathOriginalPDF = $pathOriginais . $nomeArquivo;

if ($nomeArquivo === '' || $nomeDocumento === '') {
    die("<div class='alert alert-danger'>Erro: dados não recebidos.</div>");
}

if (!file_exists($pathOriginalPDF)) {
    die("<div class='alert alert-danger'>Erro: arquivo original não encontrado.</div>");
}

$xPx = (float)($_POST['xpos'] ?? 0);
$yPx = (float)($_POST['ypos'] ?? 0);
$canvasW = (float)($_POST['canvasWidth'] ?? 0);
$canvasH = (float)($_POST['canvasHeight'] ?? 0);
$selectedPage = (int)($_POST['selected_page'] ?? 1);

if ($canvasW <= 0 || $canvasH <= 0) {
    die('Erro: dimensões inválidas.');
}

if ($invertextoApiToken === '') {
    die("<div class='alert alert-danger'>Token da Invertexto não configurado.</div>");
}

$timestampAssinatura = time();
$codigoBase = assinaturaGerarCodigoValidacaoCurto($pdo, $timestampAssinatura);
$linkValidacao = rtrim((string)$publicBaseWithScheme, '/') . '/assinatura/pdf/validar/?code=' . $codigoBase;

$nomeArquivoFinal = assinaturaGerarNomeArquivoFinal($pathFinais, $idColab);
$pathFinalPDF = $pathFinais . $nomeArquivoFinal;
$urlPDFInt = $BASE_para_URL . '/app/storage/assinatura/assinados/' . $nomeArquivoFinal;

$qrURL = 'https://api.invertexto.com/v1/qrcode?token=' . rawurlencode($invertextoApiToken) . '&text=' . urlencode($linkValidacao);
$qrData = @file_get_contents($qrURL);
if (!$qrData) {
    die("<div class='alert alert-danger'>Erro ao gerar QR Code.</div>");
}

$qrFile = $pathQR . $codigoBase . '.png';
file_put_contents($qrFile, $qrData);

try {
    $pdf = new Fpdi();
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pageCount = $pdf->setSourceFile($pathOriginalPDF);

    if ($selectedPage < 1 || $selectedPage > $pageCount) {
        $selectedPage = $pageCount;
    }

    $fontFile = $BASE_para_PATH . '/api/lib/tcpdf/fonts/Licorice-Regular.ttf';
    $fontName = 'helvetica';
    if (is_file($fontFile)) {
        $fontName = TCPDF_FONTS::addTTFfont($fontFile, 'TrueTypeUnicode', '', 32) ?: 'helvetica';
    }

    $pdf->SetAutoPageBreak(false, 0);

    $realX = 0.0;
    $realY = 0.0;

    for ($i = 1; $i <= $pageCount; $i++) {
        $tplIdx = $pdf->importPage($i);
        $size = $pdf->getTemplateSize($tplIdx);

        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($tplIdx, 0, 0, $size['width'], $size['height'], true);

        if ($i !== $selectedPage) {
            $pdf->SetFont('helvetica', '', 6);
            $pdf->SetTextColor(80, 80, 80);
            $footerPrefix = "Documento assinado digitalmente, código {$codigoBase}. Confira autenticidade em ";
            $footerUrl = $assinaturaDigitalPublic;
            $prefixLen = $pdf->GetStringWidth($footerPrefix);
            $urlLen = $pdf->GetStringWidth($footerUrl);
            $textLen = $prefixLen + $urlLen;
            $xBase = 5.0;
            $yCenter = $size['height'] / 2;
            $yBase = $yCenter + ($textLen / 2);

            $pdf->StartTransform();
            $pdf->Rotate(90, $xBase, $yBase);
            $pdf->Text($xBase, $yBase, $footerPrefix);
            $pdf->SetFont('helvetica', 'U', 6);
            $pdf->SetTextColor(0, 102, 204);
            $urlX = $xBase + $prefixLen;
            $pdf->Text($urlX, $yBase, $footerUrl);
            $pdf->Link($urlX, $yBase - 2.1, $urlLen + 0.6, 3.0, $footerUrl);
            $pdf->StopTransform();
            continue;
        }

        $realX = ($xPx / $canvasW) * $size['width'];
        $realY = ($yPx / $canvasH) * $size['height'];

        if ($realX < 5) {
            $realX = 5;
        }
        if ($realY < 5) {
            $realY = 5;
        }
        if ($realX > $size['width'] - 60) {
            $realX = $size['width'] - 60;
        }
        if ($realY > $size['height'] - 30) {
            $realY = $size['height'] - 30;
        }

        $qrX = max(0.0, $realX);
        $qrY = $realY;

        $pdf->Image($qrFile, $qrX, $qrY, 18, 18);

        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetTextColor(50, 50, 50);
        $pdf->SetXY($qrX, $qrY + 17);
        $pdf->Cell(0, 4, 'Código para validação: ' . $codigoBase, 0, 0, 'L');

        $pdf->SetFont($fontName, '', 18);
        $pdf->SetTextColor(0, 0, 0);
        $assinaturaX = $realX + 18;
        $assinaturaY = $realY;
        $pdf->SetXY($assinaturaX, $assinaturaY);
        $pdf->Cell(0, 6, $nomeCompleto);

        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY($assinaturaX, $assinaturaY + 6);
        $pdf->Cell(0, 5, 'Assinatura digital de:');

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetXY($assinaturaX, $assinaturaY + 11);
        $pdf->Cell(0, 5, $nomeUpper);
    }

    $pdf->Output($pathFinalPDF, 'F');

    if (file_exists($pathOriginalPDF)) {
        @unlink($pathOriginalPDF);
    }

    if (file_exists($qrFile)) {
        @unlink($qrFile);
    }

    $sql = $pdo->prepare(
        'INSERT INTO tbpdf_assinado
            (IdColaborador, NomeOriginal, NomeDocumento, NomeArquivo, AssinaturaBase64, TimestampAssinatura, Xpos, Ypos, urlValidacao)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $sql->execute([
        $idColab,
        $nomeArquivo,
        $nomeDocumento,
        $nomeArquivoFinal,
        $codigoBase,
        $timestampAssinatura,
        $realX,
        $realY,
        $linkValidacao,
    ]);
} catch (Throwable $e) {
    $erro = $e->getMessage();

    $msgErro = "
        <div class='alert alert-danger'>
            <h4><b>Não foi possível processar este PDF</b></h4>
            <p>Este documento usa um tipo de compressão que não é compatível com o sistema atual.</p>
            <p><b>Detalhes técnicos:</b> {$erro}</p>
            <br>
            <a href='" . rtrim((string)$BASE_para_URL, '/') . "/assinatura/pdf' class='btn btn-secondary'>
                Tentar outro PDF
            </a>
        </div>
    ";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
</head>
<body>
    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>
        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>
            <script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js?v=<?php echo $appJsVersion; ?>"></script>
            <main class="content">
                <div class="container mt-4">
                    <?php if (isset($msgErro)) { ?>
                        <?= $msgErro ?>
                    <?php } else { ?>
                        <div class="alert alert-success">
                            PDF assinado com sucesso!<br><br>
                            <a href="<?= htmlspecialchars($linkValidacao, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-primary">
                                Verificar Assinatura
                            </a>
                            &nbsp;
                            <a href="<?= htmlspecialchars($urlPDFInt, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-secondary">
                                Abrir PDF Assinado
                            </a>
                        </div>
                    <?php } ?>
                </div>
            </main>
            <footer class="footer">
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>
        </div>
    </div>
</body>
</html>
