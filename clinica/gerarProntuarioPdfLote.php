<?php
require_once dirname(__DIR__) . '/bootstrap/runtime.php';

$invertextoApiToken = bootstrap_invertexto_api_token(dirname(__DIR__));
/**
 * Gera um único PDF com vários prontuários.
 * Mantém cabeçalho e rodapé (sem paginação) e ignora prontuários sem conteúdo.
 */

function resolveLogoPathProntuarioLote($logoRaw, $basePath): string
{
    $logo = trim((string) $logoRaw);
    if ($logo === '') return '';
    if (preg_match('#^https?://#i', $logo)) return '';
    $base = rtrim(str_replace('\\', '/', (string) $basePath), '/');
    $assetsImgPath = isset($_SESSION['BASE_ASSETS_IMG_PATH']) && is_string($_SESSION['BASE_ASSETS_IMG_PATH']) && $_SESSION['BASE_ASSETS_IMG_PATH'] !== ''
        ? rtrim(str_replace('\\', '/', (string) $_SESSION['BASE_ASSETS_IMG_PATH']), '/')
        : ($base . '/app/assets/img');
    $logo = str_replace('\\', '/', $logo);
    $candidates = [
        $base . '/' . ltrim($logo, '/'),
        $assetsImgPath . '/' . ltrim($logo, '/'),
        $assetsImgPath . '/logos/' . ltrim($logo, '/'),
        $assetsImgPath . '/sistema/' . ltrim($logo, '/'),
        $assetsImgPath . '/fotos/' . ltrim($logo, '/'),
    ];
    foreach ($candidates as $p) {
        if (is_file($p)) return realpath($p) ?: $p;
    }
    $fallbacks = [
        $assetsImgPath . '/logos/logoimpressao.png',
        $assetsImgPath . '/logos/logoimpressao.jpg',
        $assetsImgPath . '/logos/LogoImpressao.jpg',
        $assetsImgPath . '/logos/LogoImpressao.png',
        $assetsImgPath . '/sistema/logo.png',
        $assetsImgPath . '/logos/logoimpressao.PNG',
    ];
    foreach ($fallbacks as $p) {
        if (is_file($p)) return realpath($p) ?: $p;
    }
    return '';
}

function resolvePacienteFotoPathProntuarioLote($fotoRaw, $basePath): string
{
    $foto = trim((string) $fotoRaw);
    if ($foto === '') return '';
    if (preg_match('#^https?://#i', $foto)) return '';
    $base = rtrim(str_replace('\\', '/', (string) $basePath), '/');
    $assetsImgPath = isset($_SESSION['BASE_ASSETS_IMG_PATH']) && is_string($_SESSION['BASE_ASSETS_IMG_PATH']) && $_SESSION['BASE_ASSETS_IMG_PATH'] !== ''
        ? rtrim(str_replace('\\', '/', (string) $_SESSION['BASE_ASSETS_IMG_PATH']), '/')
        : ($base . '/app/assets/img');
    $foto = str_replace('\\', '/', $foto);
    $fotoNome = basename($foto);
    $candidates = [
        $base . '/' . ltrim($foto, '/'),
        $assetsImgPath . '/' . ltrim($foto, '/'),
        $assetsImgPath . '/fotos/' . $fotoNome,
    ];
    foreach ($candidates as $p) {
        if (is_file($p)) return realpath($p) ?: $p;
    }
    return '';
}

function prontuarioResolveValidacaoPublicUrlLote(string $baseUrl): string
{
    $configured = trim((string)(getenv('APP_PUBLIC_BASE_URL') ?: ''));
    if ($configured === '') {
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
                $configured = trim((string)substr($line, strlen('APP_PUBLIC_BASE_URL=')));
                $len = strlen($configured);
                if ($len >= 2 && (($configured[0] === '"' && $configured[$len - 1] === '"') || ($configured[0] === "'" && $configured[$len - 1] === "'"))) {
                    $configured = substr($configured, 1, -1);
                }
                break 2;
            }
        }
    }
    if ($configured !== '') {
        return rtrim($configured, '/') . '/clinica/verProntuarioPdf.php';
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
        return 'http://localhost' . $basePath . '/clinica/verProntuarioPdf.php';
    }

    return $scheme . '://' . $host . $port . $basePath . '/clinica/verProntuarioPdf.php';
}

function prontuarioGerarCodigoValidacaoCurtoLote(PDO $pdo, int $timestampAssinatura): string
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

function prontuarioGerarNomeArquivoFinalLote(string $diretorioAssinados, int $idColaborador): string
{
    $timestampBase = time();
    $tentativa = 0;
    do {
        $timestampAtual = $timestampBase + $tentativa;
        $nome = 'prontuario_lote_' . $timestampAtual . '_' . $idColaborador . '.pdf';
        $path = rtrim($diretorioAssinados, '/\\') . '/' . $nome;
        $tentativa++;
    } while (file_exists($path) && $tentativa < 10);
    return $nome;
}

$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/conecta/clinica/gerarProntuarioPdfLote.php'));
$projectBasePath = preg_match('#^(.*?)/clinica(?:/|$)#', $scriptName, $matches) ? rtrim((string) ($matches[1] ?? ''), '/') : '';
$sessionCookiePath = $projectBasePath !== '' ? $projectBasePath : '/';

if (session_status() === PHP_SESSION_NONE) {
    session_name('PHPSESSID3');
}
session_set_cookie_params([
    'path' => $sessionCookiePath,
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('America/Sao_Paulo');

if (empty($_SESSION['Cod'])) {
    header('Content-Type: text/html; charset=utf-8');
    die('<div class="alert alert-danger">Faça login no ConectaOSC e acesse o App Clínica novamente.</div>');
}
if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    header('Content-Type: text/html; charset=utf-8');
    http_response_code(405);
    die('<div class="alert alert-danger">A geração exige envio via POST autenticado.</div>');
}

$ids = $_POST['ids'] ?? [];
$ids = is_array($ids) ? array_values(array_unique(array_filter(array_map('intval', $ids), fn($v) => $v > 0))) : [];
if (empty($ids)) {
    header('Content-Type: text/html; charset=utf-8');
    die('<div class="alert alert-danger">Nenhum prontuário selecionado.</div>');
}

$basePath = $_SESSION['BASE_para_PATH'] ?? dirname(__DIR__);
$basePath = rtrim(str_replace('\\', '/', (string) $basePath), '/');
$conexaoPath = $basePath . '/api/conectabd/conexao.php';
if (!is_file($conexaoPath)) $conexaoPath = $basePath . '/conectabd/conexao.php';
require_once $conexaoPath;

$tcpdfPath = $basePath . '/api/lib/tcpdf/tcpdf.php';
if (!is_file($tcpdfPath)) $tcpdfPath = $basePath . '/lib/tcpdf/tcpdf.php';
require_once $tcpdfPath;

$config = $pdo->query("SELECT LogoImpressao FROM tbConfig LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$logoSistema = $config['LogoImpressao'] ?? '';
$logoSistemaPath = resolveLogoPathProntuarioLote($logoSistema, $basePath);

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$orderSql = implode(',', array_map('intval', $ids));
$stmt = $pdo->prepare("
    SELECT p.id, p.profissional_id, p.conteudo_editado, p.conteudo_ia, p.created_at,
           CONCAT(u.Nome, ' ', u.Sobrenome) AS profissional_nome,
           al.Nome AS paciente_nome, al.Foto AS paciente_foto,
           c.data_consulta, c.hora_inicio_prevista
    FROM tb_prontuario p
    JOIN tbUser u ON u.IdColaborador = p.profissional_id
    JOIN tbAluno al ON al.IdUsuario = p.aluno_id
    LEFT JOIN tb_consulta c ON c.id = p.consulta_id
    WHERE p.id IN ($placeholders)
    ORDER BY FIELD(p.id, $orderSql)
");
$stmt->execute($ids);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($rows)) {
    header('Content-Type: text/html; charset=utf-8');
    die('<div class="alert alert-danger">Prontuários não encontrados.</div>');
}

$conteudos = [];
foreach ($rows as $row) {
    $conteudo = (string) ($row['conteudo_editado'] ?: $row['conteudo_ia'] ?: '');
    if (trim(strip_tags($conteudo)) === '') continue;
    $conteudos[] = $row;
}
if (empty($conteudos)) {
    header('Content-Type: text/html; charset=utf-8');
    die('<div class="alert alert-warning">Todos os prontuários selecionados estão sem conteúdo.</div>');
}

$assinar = (string) ($_POST['assinar'] ?? '0') === '1';

class ProntuarioLotePDF extends TCPDF
{
    public $logoSistemaPath = '';
    public $logoHeightMm = 20;
    public $headerTopMm = 8;
    public $headerLineGapMm = 2;

    public function Header()
    {
        $logoTop = $this->headerTopMm;
        if ($this->logoSistemaPath !== '' && is_file($this->logoSistemaPath)) {
            $this->Image($this->logoSistemaPath, 15, $logoTop, 0, $this->logoHeightMm, '', '', 'T', false, 300);
        }
        $lineY = $logoTop + $this->logoHeightMm + $this->headerLineGapMm;
        $this->Line(15, $lineY, $this->getPageWidth() - 15, $lineY);
    }

    public function Footer()
    {
        $pageHeight = $this->getPageHeight();
        $lineY = $pageHeight - 18;
        $this->Line(15, $lineY, $this->getPageWidth() - 15, $lineY);
    }
}

$pdf = new ProntuarioLotePDF();
$pdf->logoSistemaPath = $logoSistemaPath;
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);
$pdf->SetCreator('Conecta OSC');
$pdf->SetAuthor('Conecta OSC');
$pdf->SetTitle('Prontuários em lote');
$pdf->SetMargins(15, 40, 15);
$pdf->SetAutoPageBreak(true, 28);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(22);
$pdf->SetFont('helvetica', '', 10);

$pagesGeradas = 0;
$paginaFimConteudo = 1;
$yFimConteudo = 42.0;
foreach ($conteudos as $row) {
    $conteudo = (string) ($row['conteudo_editado'] ?: $row['conteudo_ia'] ?: '');
    if ($conteudo !== '') {
        // Alguns conteúdos chegam com "? " no início da linha no lugar do marcador.
        $conteudo = preg_replace('/(^|\R)\s*[?]\s+/u', '$1• ', $conteudo);
    }
    if (trim(strip_tags($conteudo)) === '') continue;

    $dataDocumento = '';
    if (!empty($row['data_consulta'])) {
        $dt = DateTime::createFromFormat('Y-m-d', (string) $row['data_consulta']);
        $hora = (string) ($row['hora_inicio_prevista'] ?? '');
        if ($dt) {
            $dataDocumento = $dt->format('d/m/Y');
            if ($hora && preg_match('/^\d{2}:\d{2}/', trim($hora))) {
                $dataDocumento .= ' ' . substr(trim($hora), 0, 5);
            }
        }
    }
    if ($dataDocumento === '') {
        $dataDocumento = date('d/m/Y H:i', strtotime((string) $row['created_at']));
    }

    $htmlConteudo = strip_tags($conteudo, '<p><br><strong><b><em><i><u><ul><ol><li><h1><h2><h3><h4><h5><h6><span><div>');
    if (strip_tags($conteudo) === $conteudo) {
        $htmlConteudo = nl2br(htmlspecialchars($conteudo));
    }
    if (trim(strip_tags($htmlConteudo)) === '') continue;

    $pdf->AddPage();
    $pagesGeradas++;
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 10, 'Prontuário Clínico', 0, 1, 'C');
    $pdf->Ln(2);
    $pdf->SetFont('dejavusans', '', 10);

    $infoTopY = $pdf->GetY();
    $leftX = 15;
    $rightMargin = 15;
    $fotoGap = 4;
    $fotoHeight = 18;
    $fotoWidth = 0.0;
    $pacienteFotoPath = resolvePacienteFotoPathProntuarioLote($row['paciente_foto'] ?? '', $basePath);
    if ($pacienteFotoPath !== '' && is_file($pacienteFotoPath)) {
        $imgSize = @getimagesize($pacienteFotoPath);
        if (is_array($imgSize) && !empty($imgSize[1])) {
            $fotoWidth = max(10, ($imgSize[0] / $imgSize[1]) * $fotoHeight);
        } else {
            $fotoWidth = $fotoHeight;
        }
        $fotoX = $pdf->getPageWidth() - $rightMargin - $fotoWidth;
        $pdf->Image($pacienteFotoPath, $fotoX, $infoTopY, $fotoWidth, $fotoHeight, '', '', 'T', false, 300, '', false, false, 0, true);
    }

    $textoLargura = $pdf->getPageWidth() - $leftX - $rightMargin - ($fotoWidth > 0 ? ($fotoWidth + $fotoGap) : 0);
    $pdf->SetXY($leftX, $infoTopY);
    $pdf->Cell($textoLargura, 5, 'Paciente: ' . (string) $row['paciente_nome'], 0, 1);
    $pdf->Cell($textoLargura, 5, 'Profissional: ' . (string) $row['profissional_nome'], 0, 1);
    $pdf->Cell($textoLargura, 5, 'Data do atendimento: ' . $dataDocumento, 0, 1);
    $pdf->SetY(max($pdf->GetY(), $infoTopY + ($fotoWidth > 0 ? $fotoHeight : 0)) + 3);
    $pdf->WriteHTML($htmlConteudo);

    $paginaFimConteudo = $pdf->getPage();
    $yFimConteudo = $pdf->GetY();
}

if ($pagesGeradas === 0) {
    header('Content-Type: text/html; charset=utf-8');
    die('<div class="alert alert-warning">Nenhum prontuário com conteúdo válido para gerar PDF.</div>');
}

if ($assinar) {
    $baseUrlPath = rtrim((string)($_SESSION['BASE_para_URL'] ?? ''), '/');
    if ($baseUrlPath === '') {
        $baseUrlPath = $projectBasePath;
    }
    $assinaturaDigitalPublic = prontuarioResolveValidacaoPublicUrlLote((string) $baseUrlPath);
    $publicBaseWithScheme = preg_replace('#/clinica/verProntuarioPdf\.php$#i', '', $assinaturaDigitalPublic);
    if (!is_string($publicBaseWithScheme) || trim($publicBaseWithScheme) === '') {
        $publicBaseWithScheme = rtrim((string)$baseUrlPath, '/');
    }

    $pathStorageBase = $basePath . '/app/storage/assinatura/';
    $pathFinais = $pathStorageBase . 'assinados/';
    $pathQR = $pathStorageBase . 'qrcodes/';
    if (!is_dir($pathFinais)) @mkdir($pathFinais, 0775, true);
    if (!is_dir($pathQR)) @mkdir($pathQR, 0775, true);

    $idColab = (int)($_SESSION['Cod'] ?? ($conteudos[0]['profissional_id'] ?? 0));
    $nomeCompleto = trim((string)(($_SESSION['Nome'] ?? '') . ' ' . ($_SESSION['Sobrenome'] ?? '')));
    if ($nomeCompleto === '') {
        $nomeCompleto = trim((string) ($conteudos[0]['profissional_nome'] ?? 'Profissional Responsável'));
    }
    $nomeUpper = mb_strtoupper($nomeCompleto, 'UTF-8');

    $fontFile = $basePath . '/api/lib/tcpdf/fonts/Licorice-Regular.ttf';
    if (!is_file($fontFile)) $fontFile = $basePath . '/lib/tcpdf/fonts/Licorice-Regular.ttf';
    $fontName = 'helvetica';
    if (is_file($fontFile)) {
        $fontName = TCPDF_FONTS::addTTFfont($fontFile, 'TrueTypeUnicode', '', 32) ?: 'helvetica';
    }

    if ($invertextoApiToken === '') {
        header('Content-Type: text/html; charset=utf-8');
        die('<div class="alert alert-danger">Token da Invertexto não configurado.</div>');
    }
    $timestampAssinatura = time();
    $codigoBase = prontuarioGerarCodigoValidacaoCurtoLote($pdo, $timestampAssinatura);
    $linkValidacao = rtrim((string)$publicBaseWithScheme, '/') . '/clinica/verProntuarioPdf.php?code=' . urlencode($codigoBase);
    $qrURL = 'https://api.invertexto.com/v1/qrcode?token=' . rawurlencode($invertextoApiToken) . '&text=' . urlencode($linkValidacao);
    $qrData = @file_get_contents($qrURL);
    if (!$qrData) {
        header('Content-Type: text/html; charset=utf-8');
        die('<div class="alert alert-danger">Erro ao gerar QR Code!</div>');
    }
    $qrFile = $pathQR . $codigoBase . '.png';
    file_put_contents($qrFile, $qrData);

    $numPages = $pdf->getNumPages();
    $realX = 15.0;
    $realY = 15.0;

    for ($pageNum = 1; $pageNum <= $numPages; $pageNum++) {
        $pdf->setPage($pageNum);
        $pageWidth = $pdf->getPageWidth();
        $pageHeight = $pdf->getPageHeight();

        $pdf->SetFont('helvetica', '', 6);
        $pdf->SetTextColor(80, 80, 80);
        $footerPrefix = "Documento assinado digitalmente, código {$codigoBase}. Confira autenticidade em ";
        $footerUrl = $assinaturaDigitalPublic;
        $prefixLen = $pdf->GetStringWidth($footerPrefix);
        $urlLen = $pdf->GetStringWidth($footerUrl);
        $textLen = $prefixLen + $urlLen;
        $xBase = 5.0;
        $yCenter = $pageHeight / 2;
        $yBase = $yCenter + ($textLen / 2);

        $pdf->StartTransform();
        $pdf->Rotate(90, $xBase, $yBase);
        $pdf->SetFont('helvetica', '', 6);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->Text($xBase, $yBase, $footerPrefix);
        $pdf->SetFont('helvetica', 'U', 6);
        $pdf->SetTextColor(0, 102, 204);
        $pdf->SetXY($xBase + $prefixLen, $yBase - 1.1);
        $pdf->Cell($urlLen + 0.6, 3.0, $footerUrl, 0, 0, 'L', false, $assinaturaDigitalPublic);
        $pdf->StopTransform();

        if ($pageNum === $numPages) {
            $groupY = ($pageNum === $paginaFimConteudo ? $yFimConteudo : 42.0) + 4.0;
            $bottomLimit = $pageHeight - 34.0;
            $groupHeight = 19.0;
            if (($groupY + $groupHeight) > $bottomLimit) {
                $pdf->AddPage();
                $numPages = $pdf->getNumPages();
                $pdf->setPage($numPages);
                $pageWidth = $pdf->getPageWidth();
                $pageHeight = $pdf->getPageHeight();
                $groupY = 42.0;

                $pdf->SetFont('helvetica', '', 6);
                $pdf->SetTextColor(80, 80, 80);
                $prefixLen = $pdf->GetStringWidth($footerPrefix);
                $urlLen = $pdf->GetStringWidth($footerUrl);
                $textLen = $prefixLen + $urlLen;
                $yCenter = $pageHeight / 2;
                $yBase = $yCenter + ($textLen / 2);

                $pdf->StartTransform();
                $pdf->Rotate(90, $xBase, $yBase);
                $pdf->SetFont('helvetica', '', 6);
                $pdf->SetTextColor(80, 80, 80);
                $pdf->Text($xBase, $yBase, $footerPrefix);
                $pdf->SetFont('helvetica', 'U', 6);
                $pdf->SetTextColor(0, 102, 204);
                $pdf->SetXY($xBase + $prefixLen, $yBase - 1.1);
                $pdf->Cell($urlLen + 0.6, 3.0, $footerUrl, 0, 0, 'L', false, $assinaturaDigitalPublic);
                $pdf->StopTransform();
            }

            $qrSize = 18.0;
            $gap = 3.0;
            $textWidth = 98.0;
            $groupWidth = $qrSize + $gap + $textWidth;
            $groupX = max(8.0, ($pageWidth - $groupWidth) / 2);
            $realX = $groupX;
            $realY = $groupY;

            $pdf->Image($qrFile, $groupX, $groupY, $qrSize, $qrSize);
            $textX = $groupX + $qrSize + $gap - 4.0;
            $textY = $groupY + 1.7;
            $cursiveSize = 13.0;

            $pdf->SetFont($fontName, '', $cursiveSize);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY($textX, $textY);
            $pdf->Cell($textWidth, 6, $nomeCompleto, 0, 0, 'L');
            $larguraCursiva = $pdf->GetStringWidth($nomeCompleto);

            $pdf->SetFont('helvetica', '', 7.0);
            $pdf->SetXY($textX, $textY + 4.8);
            $pdf->Cell($textWidth, 5, 'Assinatura digital de:', 0, 0, 'L');

            $upperSize = 10.0;
            $pdf->SetFont('helvetica', 'B', $upperSize);
            while ($pdf->GetStringWidth($nomeUpper) > $larguraCursiva && $upperSize > 6.0) {
                $upperSize -= 0.5;
                $pdf->SetFont('helvetica', 'B', $upperSize);
            }
            $pdf->SetXY($textX, $textY + 8.9);
            $pdf->Cell($textWidth, 6, $nomeUpper, 0, 0, 'L');

            $pdf->SetFont('helvetica', '', 7);
            $pdf->SetTextColor(50, 50, 50);
            $pdf->SetXY($groupX, $groupY + $qrSize - 1.65);
            $pdf->Cell($groupWidth, 4, 'Código para validação: ' . $codigoBase, 0, 0, 'L');
        }
    }

    $nomeArquivo = prontuarioGerarNomeArquivoFinalLote($pathFinais, max(1, $idColab));
    $pathFinalPdf = $pathFinais . $nomeArquivo;
    $pdf->Output($pathFinalPdf, 'F');
    if (is_file($qrFile)) @unlink($qrFile);

    $stmtIns = $pdo->prepare("\n        INSERT INTO tbpdf_assinado\n            (IdColaborador, NomeOriginal, NomeDocumento, NomeArquivo, AssinaturaBase64, TimestampAssinatura, Xpos, Ypos, urlValidacao)\n            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)\n    ");
    $stmtIns->execute([
        max(1, $idColab),
        'prontuario_lote:' . date('YmdHis'),
        'Prontuários em lote',
        $nomeArquivo,
        $codigoBase,
        $timestampAssinatura,
        (int) round($realX),
        (int) round($realY),
        $linkValidacao
    ]);

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $nomeArquivo . '"');
    readfile($pathFinalPdf);
    exit;
}

$nomeArquivo = 'prontuarios-lote-' . date('Ymd-His') . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $nomeArquivo . '"');
$pdf->Output($nomeArquivo, 'I');
exit;
