<?php
/**
 * Geração de PDF do prontuário.
 * Chamado via include pelo ProntuariosController ou via POST autenticado.
 */
$scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/conecta/clinica/gerarProntuarioPdf.php'));
$projectBasePath = preg_match('#^(.*?)/clinica(?:/|$)#', $scriptName, $matches) ? rtrim((string)($matches[1] ?? ''), '/') : '';
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
require_once dirname(__DIR__) . '/bootstrap/runtime.php';

$basePath = $_SESSION['BASE_para_PATH'] ?? dirname(__DIR__);
$basePath = rtrim(str_replace('\\', '/', (string) $basePath), '/');
$invertextoApiToken = bootstrap_invertexto_api_token($basePath);
$assetsImgPath = $basePath . '/app/assets/img';
if (isset($_SESSION['BASE_ASSETS_IMG_PATH']) && is_string($_SESSION['BASE_ASSETS_IMG_PATH']) && $_SESSION['BASE_ASSETS_IMG_PATH'] !== '') {
    $assetsImgPath = rtrim(str_replace('\\', '/', $_SESSION['BASE_ASSETS_IMG_PATH']), '/');
}

function resolveLogoPathProntuario($logoRaw, $basePath) {
    if (!is_string($logoRaw)) return '';
    $logoRaw = trim($logoRaw);
    $basePath = rtrim(str_replace('\\', '/', $basePath), '/');
    $assetsImgPath = $GLOBALS['assetsImgPath'] ?? ($basePath . '/app/assets/img');
    $candidates = [
        $basePath . '/' . ltrim(str_replace('\\', '/', $logoRaw), '/'),
        $assetsImgPath . '/' . ltrim(str_replace('\\', '/', $logoRaw), '/'),
        $assetsImgPath . '/logos/' . ltrim(str_replace('\\', '/', $logoRaw), '/'),
        $assetsImgPath . '/sistema/' . ltrim(str_replace('\\', '/', $logoRaw), '/'),
        $assetsImgPath . '/fotos/' . ltrim(str_replace('\\', '/', $logoRaw), '/'),
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

function resolvePacienteFotoPathProntuario($fotoRaw, $basePath) {
    if (!is_string($fotoRaw)) return '';
    $fotoRaw = trim($fotoRaw);
    if ($fotoRaw === '') return '';
    if (preg_match('#^https?://#i', $fotoRaw)) return '';
    $basePath = rtrim(str_replace('\\', '/', $basePath), '/');
    $assetsImgPath = $GLOBALS['assetsImgPath'] ?? ($basePath . '/app/assets/img');
    $foto = str_replace('\\', '/', $fotoRaw);
    $fotoNome = basename($foto);
    $candidates = [
        $basePath . '/' . ltrim($foto, '/'),
        $assetsImgPath . '/' . ltrim($foto, '/'),
        $assetsImgPath . '/fotos/' . $fotoNome,
    ];
    foreach ($candidates as $p) {
        if (is_file($p)) return realpath($p) ?: $p;
    }
    return '';
}

function prontuarioResolveValidacaoPublicUrl(string $baseUrl): string
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

function prontuarioGerarCodigoValidacaoCurto(PDO $pdo, int $timestampAssinatura): string
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

function prontuarioGerarNomeArquivoFinal(string $diretorioAssinados, int $idColaborador): string
{
    $timestampBase = time();
    $tentativa = 0;

    do {
        $timestampAtual = $timestampBase + $tentativa;
        $nome = 'prontuario_' . $timestampAtual . '_' . $idColaborador . '.pdf';
        $path = rtrim($diretorioAssinados, '/\\') . '/' . $nome;
        $tentativa++;
    } while (file_exists($path) && $tentativa < 10);

    return $nome;
}

if (empty($_SESSION['Cod'])) {
    header('Content-Type: text/html; charset=utf-8');
    die('<div class="alert alert-danger">Faça login no ConectaOSC e acesse o App Clínica novamente.</div>');
}

$id = isset($prontuarioPdfId) ? (int) $prontuarioPdfId : (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
if ($id <= 0) {
    header('Content-Type: text/html; charset=utf-8');
    die('<div class="alert alert-danger">ID do prontuário inválido.</div>');
}

$conexaoPath = $basePath . '/api/conectabd/conexao.php';
if (!is_file($conexaoPath)) {
    $conexaoPath = $basePath . '/conectabd/conexao.php';
}
require_once $conexaoPath;

$tcpdfPath = $basePath . '/api/lib/tcpdf/tcpdf.php';
if (!is_file($tcpdfPath)) {
    $tcpdfPath = $basePath . '/lib/tcpdf/tcpdf.php';
}
require_once $tcpdfPath;

$stmt = $pdo->prepare("
    SELECT p.conteudo_editado, p.conteudo_ia, p.created_at, p.profissional_id,
           CONCAT(u.Nome, ' ', u.Sobrenome) AS profissional_nome,
           al.Nome AS paciente_nome,
           al.Foto AS paciente_foto,
           c.data_consulta, c.hora_inicio_prevista
    FROM tb_prontuario p
    JOIN tbUser u ON u.IdColaborador = p.profissional_id
    JOIN tbAluno al ON al.IdUsuario = p.aluno_id
    LEFT JOIN tb_consulta c ON c.id = p.consulta_id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    header('Content-Type: text/html; charset=utf-8');
    die('<div class="alert alert-danger">Prontuário não encontrado.</div>');
}

$config = $pdo->query("SELECT LogoImpressao FROM tbConfig LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$logoSistema = $config['LogoImpressao'] ?? '';
$logoSistemaPath = resolveLogoPathProntuario($logoSistema, $basePath);
$pacienteFotoPath = resolvePacienteFotoPathProntuario($row['paciente_foto'] ?? '', $basePath);

class ProntuarioPDF extends TCPDF
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
        $this->SetY($lineY + 2);
        $this->SetFont('helvetica', '', 8);
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

$conteudo = $row['conteudo_editado'] ?: $row['conteudo_ia'];
if (is_string($conteudo) && $conteudo !== '') {
    // Alguns conteúdos chegam com "? " no início da linha no lugar do marcador.
    $conteudo = preg_replace('/(^|\R)\s*[?]\s+/u', '$1• ', $conteudo);
}
$pdf = new ProntuarioPDF();
$pdf->logoSistemaPath = $logoSistemaPath;
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);
$pdf->SetCreator('Conecta OSC');
$pdf->SetAuthor('Conecta OSC');
$pdf->SetTitle('Prontuário - ' . $row['paciente_nome']);
$pdf->SetMargins(15, 40, 15);
$pdf->SetAutoPageBreak(true, 28);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(22);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 11);

$dataDocumento = '';
if (!empty($row['data_consulta'])) {
    $dt = DateTime::createFromFormat('Y-m-d', $row['data_consulta']);
    $hora = $row['hora_inicio_prevista'] ?? '';
    if ($dt) {
        $dataDocumento = $dt->format('d/m/Y');
        if ($hora && preg_match('/^\d{2}:\d{2}/', trim($hora))) {
            $dataDocumento .= ' ' . substr(trim($hora), 0, 5);
        }
    }
}
if ($dataDocumento === '') {
    $dataDocumento = date('d/m/Y H:i', strtotime($row['created_at']));
}

$pdf->SetFont('helvetica', 'B', 18);
$pdf->Cell(0, 10, 'Prontuário Clínico', 0, 1, 'C');
$pdf->Ln(2);
$pdf->SetFont('helvetica', '', 10);
$infoTopY = $pdf->GetY();
$leftX = 15;
$rightMargin = 15;
$fotoGap = 4;
$fotoHeight = 18;
$fotoWidth = 0.0;
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
$pdf->Cell($textoLargura, 5, 'Paciente: ' . $row['paciente_nome'], 0, 1);
$pdf->Cell($textoLargura, 5, 'Profissional: ' . $row['profissional_nome'], 0, 1);
$pdf->Cell($textoLargura, 5, 'Data do atendimento: ' . $dataDocumento, 0, 1);
$pdf->SetY(max($pdf->GetY(), $infoTopY + ($fotoWidth > 0 ? $fotoHeight : 0)) + 3);
$pdf->SetFont('dejavusans', '', 10);

$htmlConteudo = strip_tags($conteudo, '<p><br><strong><b><em><i><u><ul><ol><li><h1><h2><h3><h4><h5><h6><span><div>');
if (strip_tags($conteudo) === $conteudo) {
    $htmlConteudo = nl2br(htmlspecialchars($conteudo));
}
$pdf->WriteHTML($htmlConteudo);
$paginaFimConteudo = $pdf->getPage();
$yFimConteudo = $pdf->GetY();

$assinar = (string) ($_POST['assinar'] ?? $_GET['assinar'] ?? '') === '1';

if ($assinar) {
    $baseUrlPath = rtrim((string)($_SESSION['BASE_para_URL'] ?? ''), '/');
    if ($baseUrlPath === '') {
        $baseUrlPath = $projectBasePath;
    }
    $assinaturaDigitalPublic = prontuarioResolveValidacaoPublicUrl((string) $baseUrlPath);
    $publicBaseWithScheme = preg_replace('#/clinica/verProntuarioPdf\.php$#i', '', $assinaturaDigitalPublic);
    if (!is_string($publicBaseWithScheme) || trim($publicBaseWithScheme) === '') {
        $publicBaseWithScheme = rtrim((string)$baseUrlPath, '/');
    }

    $pathStorageBase = $basePath . '/app/storage/assinatura/';
    $pathFinais = $pathStorageBase . 'assinados/';
    $pathQR = $pathStorageBase . 'qrcodes/';
    if (!is_dir($pathFinais)) {
        @mkdir($pathFinais, 0775, true);
    }
    if (!is_dir($pathQR)) {
        @mkdir($pathQR, 0775, true);
    }

    $idColab = (int)($_SESSION['Cod'] ?? $row['profissional_id'] ?? 0);
    $nomeCompleto = trim((string)(($_SESSION['Nome'] ?? '') . ' ' . ($_SESSION['Sobrenome'] ?? '')));
    if ($nomeCompleto === '') {
        $nomeCompleto = trim((string) $row['profissional_nome']);
    }
    $nomeUpper = mb_strtoupper($nomeCompleto, 'UTF-8');

    $fontFile = $basePath . '/api/lib/tcpdf/fonts/Licorice-Regular.ttf';
    if (!is_file($fontFile)) {
        $fontFile = $basePath . '/lib/tcpdf/fonts/Licorice-Regular.ttf';
    }
    $fontName = 'helvetica';
    if (is_file($fontFile)) {
        $fontName = TCPDF_FONTS::addTTFfont($fontFile, 'TrueTypeUnicode', '', 32) ?: 'helvetica';
    }

    if ($invertextoApiToken === '') {
        header('Content-Type: text/html; charset=utf-8');
        die('<div class="alert alert-danger">Token da Invertexto não configurado.</div>');
    }
    $timestampAssinatura = time();
    $codigoBase = prontuarioGerarCodigoValidacaoCurto($pdo, $timestampAssinatura);
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

    $nomeArquivo = prontuarioGerarNomeArquivoFinal($pathFinais, $idColab);
    $pathFinalPdf = $pathFinais . $nomeArquivo;
    $pdf->Output($pathFinalPdf, 'F');

    if (is_file($qrFile)) {
        @unlink($qrFile);
    }

    $nomeDocumento = 'Prontuário - ' . $row['paciente_nome'];
    $stmtIns = $pdo->prepare("
        INSERT INTO tbpdf_assinado
            (IdColaborador, NomeOriginal, NomeDocumento, NomeArquivo, AssinaturaBase64, TimestampAssinatura, Xpos, Ypos, urlValidacao)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtIns->execute([
        $idColab,
        'prontuario:' . $id,
        $nomeDocumento,
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

$pdf->Output('prontuario-' . $id . '.pdf', 'I');
exit;
