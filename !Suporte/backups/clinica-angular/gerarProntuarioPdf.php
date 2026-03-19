<?php
/**
 * Geração de PDF do prontuário - script standalone (igual gerarcontrato.php)
 * Chamado via include pelo ProntuariosController ou diretamente via ?id=X
 * Requer sessão ConectaOSC ativa.
 */
session_set_cookie_params(['httponly' => true]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('America/Sao_Paulo');

$basePath = $_SESSION['BASE_para_PATH'] ? dirname(__DIR__);
$basePath = rtrim(str_replace('\\', '/', (string) $basePath), '/');
$assetsImgPath = $basePath . '/app/assets/img';
if (isset($_SESSION['BASE_ASSETS_IMG_PATH']) && is_string($_SESSION['BASE_ASSETS_IMG_PATH']) && $_SESSION['BASE_ASSETS_IMG_PATH'] !== '') {
    $assetsImgPath = rtrim(str_replace('\\', '/', $_SESSION['BASE_ASSETS_IMG_PATH']), '/');
}

function resolveLogoPathProntuario($logoRaw, $basePath) {
    if (!is_string($logoRaw)) return '';
    $logoRaw = trim($logoRaw);
    $basePath = rtrim(str_replace('\\', '/', $basePath), '/');
    $assetsImgPath = $GLOBALS['assetsImgPath'] ? ($basePath . '/app/assets/img');
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

if (empty($_SESSION['Cod'])) {
    header('Content-Type: text/html; charset=utf-8');
    die('<div class="alert alert-danger">Faça login no ConectaOSC e acesse o App Clínica novamente.</div>');
}

$id = isset($prontuarioPdfId) ? (int) $prontuarioPdfId : (int) ($_GET['id'] ? 0);
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
$logoSistema = $config['LogoImpressao'] ? '';
$logoSistemaPath = resolveLogoPathProntuario($logoSistema, $basePath);

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
    $hora = $row['hora_inicio_prevista'] ? '';
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
$pdf->Cell(0, 5, 'Paciente: ' . $row['paciente_nome'], 0, 1);
$pdf->Cell(0, 5, 'Profissional: ' . $row['profissional_nome'], 0, 1);
$pdf->Cell(0, 5, 'Data do atendimento: ' . $dataDocumento, 0, 1);
$pdf->Ln(3);
$pdf->SetFont('helvetica', '', 10);

$htmlConteudo = strip_tags($conteudo, '<p><br><strong><b><em><i><u><ul><ol><li><h1><h2><h3><h4><h5><h6><span><div>');
if (strip_tags($conteudo) === $conteudo) {
    $htmlConteudo = nl2br(htmlspecialchars($conteudo));
}
$pdf->WriteHTML($htmlConteudo);

$assinar = isset($_GET['assinar']) && $_GET['assinar'] === '1';

if ($assinar) {
    // Bloco de assinatura digital: QR Code, nome, validação (só quando assinar=1)
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ? '/clinica/gerarProntuarioPdf.php');
    $clinicaPrefix = '/clinica';
    $pos = strpos($scriptName, $clinicaPrefix);
    $projectBasePath = $pos === false ? '' : rtrim(substr($scriptName, 0, $pos), '/');
    $host = $_SERVER['HTTP_HOST'] ? 'localhost';
    $baseUrl = $_SESSION['BASE_para_URL'] ? '';
    if ($baseUrl === '') {
        $baseUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . $host . $projectBasePath;
    }
    $baseUrl = rtrim($baseUrl, '/');
    $codigoBase = base64_encode($id . '|' . $row['profissional_id'] . '|' . time());
    $linkValidacao = $baseUrl . '/clinica/verProntuarioPdf.php?code=' . urlencode($codigoBase);

    $pdf->Ln(6);
    $assinaturaStartY = $pdf->GetY();
    $qrSize = 15;
    $qrGap = 6;
    $textoAssinaturaX = 15 + $qrSize + $qrGap;

    $pdf->write2DBarcode($linkValidacao, 'QRCODE,H', 15, $assinaturaStartY, $qrSize, $qrSize);

    $nomeCompleto = trim($row['profissional_nome']);
    $nomeUpper = mb_strtoupper($nomeCompleto, 'UTF-8');
    $fontAssinaturaOk = false;
    $licoricePhp = $basePath . '/api/lib/tcpdf/fonts/licorice.php';
    if (!is_file($licoricePhp)) {
        $licoricePhp = $basePath . '/lib/tcpdf/fonts/licorice.php';
    }
    if (file_exists($licoricePhp)) {
        $pdf->SetFont('licorice', '', 20);
        $fontAssinaturaOk = true;
    }
    if (!$fontAssinaturaOk) {
        $fontFile = $basePath . '/api/lib/tcpdf/fonts/Licorice-Regular.ttf';
        if (!is_file($fontFile)) {
            $fontFile = $basePath . '/lib/tcpdf/fonts/Licorice-Regular.ttf';
        }
        if (file_exists($fontFile)) {
            $fn = TCPDF_FONTS::addTTFfont($fontFile, 'TrueTypeUnicode', '', 32);
            if ($fn) {
                $pdf->SetFont($fn, '', 20);
                $fontAssinaturaOk = true;
            }
        }
    }
    if (!$fontAssinaturaOk) {
        $pdf->SetFont('helvetica', 'I', 12);
    }
    $pdf->SetXY($textoAssinaturaX, $assinaturaStartY);
    $pdf->Cell(0, 6, $nomeCompleto, 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetX($textoAssinaturaX);
    $pdf->Cell(0, 4, 'Assinatura digital do profissional responsável', 0, 1, 'L');
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetX($textoAssinaturaX);
    $pdf->Cell(0, 4, $nomeUpper, 0, 1, 'L');

    $pdf->SetXY(15, $assinaturaStartY + $qrSize + 3);
    $pdf->SetFont('helvetica', '', 7);
    $pdf->Cell(0, 4, 'Escaneie o QR Code ou acesse iteva.com.br e informe o código abaixo para validar.', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 6);
    $pdf->MultiCell(0, 3, 'Código de validação: ' . $codigoBase, 0, 'L');

    $xposAssinatura = 15;
    $yposAssinatura = $assinaturaStartY;

    $pathAssinados = $basePath . '/app/assinatura/assinados/';
    if (!is_dir($pathAssinados)) {
        mkdir($pathAssinados, 0755, true);
    }
    $nomeArquivo = 'prontuario-' . $id . '-' . time() . '.pdf';
    $pathFinalPdf = $pathAssinados . $nomeArquivo;
    $pdf->Output($pathFinalPdf, 'F');

    $nomeDocumento = 'Prontuário - ' . $row['paciente_nome'];
    $idColab = (int) $row['profissional_id'];
    $timestampAssinatura = time();
    $urlValidacao = $linkValidacao;

    $stmtIns = $pdo->prepare("
        INSERT INTO tbpdf_assinado
            (IdColaborador, NomeOriginal, NomeDocumento, NomeArquivo, AssinaturaBase64, TimestampAssinatura, Xpos, Ypos, urlValidacao)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtIns->execute([
        $idColab,
        $nomeDocumento,
        $nomeDocumento,
        $nomeArquivo,
        $codigoBase,
        $timestampAssinatura,
        (int) round($xposAssinatura),
        (int) round($yposAssinatura),
        $urlValidacao
    ]);

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="prontuario-' . $id . '.pdf"');
    readfile($pathFinalPdf);
    exit;
}

$pdf->Output('prontuario-' . $id . '.pdf', 'I');
exit;
