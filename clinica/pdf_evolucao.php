<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap/runtime.php';

$scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/conecta/clinica/pdf_evolucao.php'));
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

bootstrap_validate_auth_session_cookie_name(dirname(__DIR__));
date_default_timezone_set('America/Sao_Paulo');

if (empty($_SESSION['Cod']) || (int)($_SESSION['profissional_saude'] ?? 0) !== 1) {
    header('Content-Type: text/html; charset=utf-8');
    die('<div class="alert alert-danger">Acesso restrito a profissionais de sa&uacute;de.</div>');
}

$basePath = rtrim(str_replace('\\', '/', (string)($_SESSION['BASE_para_PATH'] ?? dirname(__DIR__))), '/');
$assetsImgPath = $basePath . '/app/assets/img';
if (isset($_SESSION['BASE_ASSETS_IMG_PATH']) && is_string($_SESSION['BASE_ASSETS_IMG_PATH']) && $_SESSION['BASE_ASSETS_IMG_PATH'] !== '') {
    $assetsImgPath = rtrim(str_replace('\\', '/', $_SESSION['BASE_ASSETS_IMG_PATH']), '/');
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

function resolveLogoPathEvolucao($logoRaw, $basePath, $assetsImgPath): string
{
    if (!is_string($logoRaw)) {
        return '';
    }

    $logoRaw = trim($logoRaw);
    $basePath = rtrim(str_replace('\\', '/', $basePath), '/');
    $assetsImgPath = rtrim(str_replace('\\', '/', $assetsImgPath), '/');

    $candidates = [
        $basePath . '/' . ltrim(str_replace('\\', '/', $logoRaw), '/'),
        $assetsImgPath . '/' . ltrim(str_replace('\\', '/', $logoRaw), '/'),
        $assetsImgPath . '/logos/' . ltrim(str_replace('\\', '/', $logoRaw), '/'),
        $assetsImgPath . '/sistema/' . ltrim(str_replace('\\', '/', $logoRaw), '/'),
        $assetsImgPath . '/fotos/' . ltrim(str_replace('\\', '/', $logoRaw), '/'),
    ];

    foreach ($candidates as $candidate) {
        if ($logoRaw !== '' && is_file($candidate)) {
            return realpath($candidate) ?: $candidate;
        }
    }

    $fallbacks = [
        $assetsImgPath . '/logos/logoimpressao.png',
        $assetsImgPath . '/logos/logoimpressao.jpg',
        $assetsImgPath . '/logos/LogoImpressao.png',
        $assetsImgPath . '/logos/LogoImpressao.jpg',
        $assetsImgPath . '/sistema/logo.png',
        $assetsImgPath . '/fotos/logo.png',
    ];

    foreach ($fallbacks as $candidate) {
        if (is_file($candidate)) {
            return realpath($candidate) ?: $candidate;
        }
    }

    return '';
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Content-Type: text/html; charset=utf-8');
    die('<div class="alert alert-danger">ID da evolu&ccedil;&atilde;o inv&aacute;lido.</div>');
}

$stmt = $pdo->prepare("
    SELECT e.*, al.Nome AS paciente_nome, al.Nascimento AS paciente_nascimento,
           CONCAT(u.Nome, ' ', u.Sobrenome) AS profissional_nome,
           esp.nome AS especialidade_nome,
           c.data_consulta, c.hora_inicio_prevista
    FROM tb_evolucao_clinica e
    JOIN tbAluno al ON al.IdUsuario = e.aluno_id
    JOIN tbUser u ON u.IdColaborador = e.profissional_id
    LEFT JOIN tb_especialidade esp ON esp.id = e.especialidade_id
    LEFT JOIN tb_consulta c ON c.id = e.consulta_id
    WHERE e.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    header('Content-Type: text/html; charset=utf-8');
    die('<div class="alert alert-danger">Evolu&ccedil;&atilde;o n&atilde;o encontrada.</div>');
}

$logoRow = $pdo->query("SELECT LogoImpressao FROM tbConfig LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$logoPath = resolveLogoPathEvolucao((string)($logoRow['LogoImpressao'] ?? ''), $basePath, $assetsImgPath);

class EvolucaoPdf extends TCPDF
{
    public string $logoSistemaPath = '';
    public float $logoHeightMm = 20;
    public float $headerTopMm = 8;
    public float $headerLineGapMm = 2;

    public function Header(): void
    {
        $logoTop = $this->headerTopMm;
        if ($this->logoSistemaPath !== '' && is_file($this->logoSistemaPath)) {
            $this->Image($this->logoSistemaPath, 15, $logoTop, 0, $this->logoHeightMm, '', '', 'T', false, 300);
        }
        $lineY = $logoTop + $this->logoHeightMm + $this->headerLineGapMm;
        $this->Line(15, $lineY, $this->getPageWidth() - 15, $lineY);
    }

    public function Footer(): void
    {
        $pageHeight = $this->getPageHeight();
        $lineY = $pageHeight - 18;
        $this->Line(15, $lineY, $this->getPageWidth() - 15, $lineY);
        $this->SetY($lineY + 2);
        $this->SetFont('helvetica', '', 8);
        $this->Cell(0, 10, html_entity_decode('P&aacute;gina', ENT_QUOTES, 'UTF-8') . ' ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

$pdf = new EvolucaoPdf();
$pdf->logoSistemaPath = $logoPath;
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);
$pdf->SetCreator('Conecta OSC');
$pdf->SetAuthor('Conecta OSC');
$pdf->SetTitle(html_entity_decode('Evolu&ccedil;&atilde;o Cl&iacute;nica', ENT_QUOTES, 'UTF-8') . ' - ' . (string)$row['paciente_nome']);
$pdf->SetMargins(15, 40, 15);
$pdf->SetAutoPageBreak(true, 28);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(22);
$pdf->AddPage();

$dataDocumento = '';
if (!empty($row['data_evolucao'])) {
    $dt = DateTime::createFromFormat('Y-m-d', (string)$row['data_evolucao']);
    if ($dt) {
        $dataDocumento = $dt->format('d/m/Y');
        $hora = trim((string)($row['hora_evolucao'] ?? ''));
        if ($hora !== '' && preg_match('/^\d{2}:\d{2}/', $hora)) {
            $dataDocumento .= ' ' . substr($hora, 0, 5);
        }
    }
}
if ($dataDocumento === '' && !empty($row['created_at'])) {
    $dataDocumento = date('d/m/Y H:i', strtotime((string)$row['created_at']));
}

$pdf->SetFont('helvetica', 'B', 18);
$pdf->Cell(0, 10, html_entity_decode('Evolu&ccedil;&atilde;o Cl&iacute;nica', ENT_QUOTES, 'UTF-8'), 0, 1, 'C');
$pdf->Ln(2);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'Paciente: ' . (string)$row['paciente_nome'], 0, 1);
if ($dataDocumento !== '') {
    $pdf->Cell(0, 5, 'Data do atendimento: ' . $dataDocumento, 0, 1);
}
$pdf->Cell(0, 5, 'Profissional: ' . (string)$row['profissional_nome'], 0, 1);
if (!empty($row['especialidade_nome'])) {
    $pdf->Cell(0, 5, 'Especialidade: ' . (string)$row['especialidade_nome'], 0, 1);
}
$pdf->Ln(3);

$conteudo = (string)($row['conteudo'] ?? '');
$htmlConteudo = strip_tags($conteudo, '<p><br><strong><b><em><i><u><ul><ol><li><h1><h2><h3><h4><h5><h6><span><div>');
if (strip_tags($conteudo) === $conteudo) {
    $htmlConteudo = nl2br(htmlspecialchars($conteudo, ENT_QUOTES, 'UTF-8'));
}

$pdf->SetFont('dejavusans', '', 10);
$pdf->writeHTML($htmlConteudo, true, false, true, false, '');
$pdf->Ln(10);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, (string)$row['profissional_nome'], 0, 1, 'R');
if (!empty($row['especialidade_nome'])) {
    $pdf->Cell(0, 6, (string)$row['especialidade_nome'], 0, 1, 'R');
}

$pdf->Output('evolucao-' . $id . '.pdf', 'I');
exit;
