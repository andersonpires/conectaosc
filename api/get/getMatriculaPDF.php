<?php
if (ob_get_level() === 0) {
    ob_start();
}

$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];

if (!isset($BASE_para_PATH)) {
    echo '<script>alert("Sessão inválida."); window.close();</script>';
    exit;
}

require_once $BASE_para_PATH . '/api/repositories/RelatorioRepository.php';
require_once $BASE_para_PATH . '/api/services/RelatorioService.php';
require_once $BASE_para_PATH . '/api/conectabd/conexao.php';
require_once $BASE_para_PATH . '/api/lib/tcpdf/tcpdf.php';

use BackEnd\Repositories\RelatorioRepository;
use BackEnd\Services\RelatorioService;

function resolveProjectLogoPath($logoRaw, $basePath): string
{
    if (!is_string($logoRaw)) {
        return '';
    }

    $logoRaw = trim(str_replace('\\', '/', $logoRaw));
    if ($logoRaw === '') {
        return '';
    }

    if (preg_match('/^https?:\/\//i', $logoRaw)) {
        return $logoRaw;
    }

    if (function_exists('bootstrap_resolve_assets_img_file')) {
        $resolved = bootstrap_resolve_assets_img_file($logoRaw);
        if ($resolved !== '') {
            return $resolved;
        }
    }

    $basePath = rtrim($basePath, '/\\');
    $candidates = [
        $basePath . '/' . ltrim($logoRaw, '/'),
        dirname($basePath) . '/' . ltrim($logoRaw, '/'),
        $basePath . '/app/assets/img/' . ltrim($logoRaw, '/'),
        $basePath . '/app/assets/img/logos/' . ltrim($logoRaw, '/'),
        $basePath . '/app/assets/img/sistema/' . ltrim($logoRaw, '/'),
    ];

    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return '';
}

function resolveAlunoFotoPath($fotoRaw): string
{
    if (!is_string($fotoRaw) || trim($fotoRaw) === '') {
        return '';
    }

    if (function_exists('bootstrap_resolve_assets_img_file')) {
        $resolved = bootstrap_resolve_assets_img_file($fotoRaw);
        if ($resolved !== '') {
            return $resolved;
        }
    }

    return '';
}

class RelatorioMatriculadosPdf extends TCPDF
{
    public string $logoSistemaPath = '';
    public string $logoProjetoPath = '';
    public float $logoHeightMm = 18;
    public float $headerTopMm = 8;
    public float $headerLineGapMm = 2;

    public function Header()
    {
        $logoTop = $this->headerTopMm;

        if ($this->logoSistemaPath !== '' && is_file($this->logoSistemaPath)) {
            $this->Image($this->logoSistemaPath, 15, $logoTop, 0, $this->logoHeightMm, '', '', 'T', false, 300);
        }

        if ($this->logoProjetoPath !== '' && (is_file($this->logoProjetoPath) || preg_match('/^https?:\/\//i', $this->logoProjetoPath))) {
            $rightX = $this->getPageWidth() - 15 - 34;
            $this->Image($this->logoProjetoPath, $rightX, $logoTop, 0, $this->logoHeightMm, '', '', 'T', false, 300);
        }

        $lineY = $logoTop + $this->logoHeightMm + $this->headerLineGapMm;
        $this->Line(15, $lineY, $this->getPageWidth() - 15, $lineY);
    }

    public function Footer()
    {
        $this->SetY(-10);
        $this->SetFont('helvetica', '', 8);
        $this->Line(15, $this->GetY() - 2, $this->getPageWidth() - 15, $this->GetY() - 2);
        $this->Cell(0, 8, (string) $this->getAliasNumPage(), 0, 0, 'R');
    }

    public function drawTableHeader(bool $mostrarFoto, array $widths): void
    {
        $this->SetFont('helvetica', 'B', 8);
        $this->Cell($widths['num'], 9, 'No', 1, 0, 'C');
        if ($mostrarFoto) {
            $this->Cell($widths['foto'], 9, 'Foto', 1, 0, 'C');
        }
        $this->Cell($widths['nome'], 9, 'Matriculados', 1, 0, 'L');
        $this->Cell($widths['nascimento'], 9, 'Nascimento', 1, 0, 'C');
        $this->Cell($widths['endereco'], 9, 'Endereço', 1, 0, 'L');
        $this->Ln();
    }

    public function drawAlunoRow(int $indice, array $row, bool $mostrarFoto, array $widths): float
    {
        $nome = (string) ($row['Nome'] ?? '');
        $nascimento = (string) ($row['Nascimento'] ?? '');
        $endereco = trim(implode(' - ', array_filter([
            (string) ($row['Endereco'] ?? ''),
            (string) ($row['Bairro'] ?? ''),
            (string) ($row['Cidade'] ?? ''),
            (string) ($row['UF'] ?? ''),
        ], static fn($value): bool => $value !== '')));
        $fotoPath = resolveAlunoFotoPath((string) ($row['Foto'] ?? ''));

        $rowHeight = max(
            10.0,
            $this->getStringHeight($widths['nome'], $nome, false, true, '', 1, false) + 1.2,
            $this->getStringHeight($widths['endereco'], $endereco, false, true, '', 1, false) + 1.2,
            $mostrarFoto ? 11.2 : 0.0
        );

        $x = $this->GetX();
        $y = $this->GetY();

        $this->Cell($widths['num'], $rowHeight, (string) $indice, 1, 0, 'C');

        if ($mostrarFoto) {
            $this->Cell($widths['foto'], $rowHeight, '', 1, 0, 'C');
            if ($fotoPath !== '' && is_file($fotoPath)) {
                $maxW = max(1.0, $widths['foto'] - 2.4);
                $maxH = max(1.0, $rowHeight - 2.4);
                $imgSize = @getimagesize($fotoPath);
                $drawW = $maxW;
                $drawH = $maxH;

                if (is_array($imgSize) && !empty($imgSize[0]) && !empty($imgSize[1])) {
                    $ratio = (float) $imgSize[0] / (float) $imgSize[1];
                    if ($ratio > 0) {
                        $drawW = min($maxW, $maxH * $ratio);
                        $drawH = $drawW / $ratio;

                        if ($drawH > $maxH) {
                            $drawH = $maxH;
                            $drawW = $drawH * $ratio;
                        }
                    }
                }

                $photoX = $x + $widths['num'] + (($widths['foto'] - $drawW) / 2);
                $photoY = $y + (($rowHeight - $drawH) / 2);
                $this->Image($fotoPath, $photoX, $photoY, $drawW, $drawH, '', '', '', false, 300, '', false, false, 0, false, false, true);
            }
        }

        $this->MultiCell($widths['nome'], $rowHeight, $nome, 1, 'L', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
        $this->Cell($widths['nascimento'], $rowHeight, $nascimento, 1, 0, 'C');
        $this->MultiCell($widths['endereco'], $rowHeight, $endereco, 1, 'L', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');

        $this->SetXY($x, $y + $rowHeight);
        return $rowHeight;
    }
}

$curso = $_GET['curso'] ?? '';
$turma = $_GET['turma'] ?? '';
$mostrarFoto = !empty($_GET['mostrarFoto']) && (string) $_GET['mostrarFoto'] !== '0';

$service = new RelatorioService(new RelatorioRepository());
$data = $service->matriculados(['curso' => $curso, 'turma' => $turma]);

if (empty($data)) {
    echo '<script>alert("Nenhum dado encontrado para os critérios selecionados."); window.close();</script>';
    exit;
}

$cursoNome = (string) ($data[0]['NomeCurso'] ?? '');
$turmaNome = ($turma !== 'todas') ? (string) ($data[0]['NomeTurma'] ?? '') : 'Todas';
$nomeProjeto = (string) ($data[0]['NomeProjeto'] ?? '');
$logoProjeto = (string) ($data[0]['LogoProjeto'] ?? '');

$config = $pdo->query('SELECT LogoImpressao FROM tbConfig LIMIT 1')->fetch(PDO::FETCH_ASSOC);
$logoSistema = (string) ($config['LogoImpressao'] ?? 'logos/LogoImpressao.jpg');
if (trim($logoSistema) === '') {
    $logoSistema = 'logos/LogoImpressao.jpg';
}

$logoSistemaPath = resolveProjectLogoPath($logoSistema, $BASE_para_PATH);
$logoProjetoPath = resolveProjectLogoPath($logoProjeto, $BASE_para_PATH);

$widths = [
    'num' => 10.0,
    'foto' => 18.0,
    'nome' => 88.0,
    'nascimento' => 28.0,
    'endereco' => 141.0,
];

if ($mostrarFoto) {
    $widths['nome'] = 70.0;
    $widths['endereco'] = 141.0;
}

$pdf = new RelatorioMatriculadosPdf('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->logoSistemaPath = $logoSistemaPath;
$pdf->logoProjetoPath = $logoProjetoPath;
$pdf->SetCreator('Conecta OSC');
$pdf->SetAuthor('Conecta OSC');
$pdf->SetTitle('Relatório de Matriculados');
$pdf->SetMargins(15, 38, 15);
$pdf->SetHeaderMargin(0);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 13);
$pdf->Cell(0, 8, 'Relatório de Matriculados', 0, 1, 'C');
$pdf->Ln(1);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Curso: ' . $cursoNome, 0, 1, 'L');
$pdf->Cell(0, 6, 'Projeto: ' . $nomeProjeto, 0, 1, 'L');
$pdf->Cell(0, 6, 'Turma: ' . $turmaNome, 0, 1, 'L');
$pdf->Ln(2);

$pdf->drawTableHeader($mostrarFoto, $widths);
$pdf->SetFont('helvetica', '', 8);
$bottomLimit = $pdf->getPageHeight() - 15;

foreach ($data as $index => $row) {
    $endereco = trim(implode(' - ', array_filter([
        (string) ($row['Endereco'] ?? ''),
        (string) ($row['Bairro'] ?? ''),
        (string) ($row['Cidade'] ?? ''),
        (string) ($row['UF'] ?? ''),
    ], static fn($value): bool => $value !== '')));

    $rowHeight = max(
        10.0,
        $pdf->getStringHeight($widths['nome'], (string) ($row['Nome'] ?? ''), false, true, '', 1, false) + 1.2,
        $pdf->getStringHeight($widths['endereco'], $endereco, false, true, '', 1, false) + 1.2,
        $mostrarFoto ? 11.2 : 0.0
    );

    if ($pdf->GetY() + $rowHeight > $bottomLimit) {
        $pdf->AddPage();
        $pdf->drawTableHeader($mostrarFoto, $widths);
        $pdf->SetFont('helvetica', '', 8);
    }

    $pdf->drawAlunoRow($index + 1, $row, $mostrarFoto, $widths);
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

$pdf->Output('relatorio_matriculados.pdf', 'I');
exit;
