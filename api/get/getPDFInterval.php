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

function resolveProjectLogoPath($logoRaw, $basePath)
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

    $basePath = rtrim($basePath, '/\\');
    $assetsImgPath = '';
    if (isset($GLOBALS['runtime']['assets_img_path'])) {
        $assetsImgPath = rtrim((string) $GLOBALS['runtime']['assets_img_path'], '/\\');
    }

    $candidates = [
        $basePath . '/' . ltrim($logoRaw, '/'),
        dirname($basePath) . '/' . ltrim($logoRaw, '/'),
        $basePath . '/app/assets/img/' . ltrim($logoRaw, '/'),
        $basePath . '/app/assets/img/logos/' . ltrim($logoRaw, '/'),
        $basePath . '/app/assets/img/sistema/' . ltrim($logoRaw, '/'),
        $basePath . '/app/assets/img/fotos/' . ltrim($logoRaw, '/'),
    ];

    if ($assetsImgPath !== '') {
        $candidates[] = $assetsImgPath . '/' . ltrim($logoRaw, '/');
        $candidates[] = $assetsImgPath . '/logos/' . ltrim($logoRaw, '/');
        $candidates[] = $assetsImgPath . '/sistema/' . ltrim($logoRaw, '/');
        $candidates[] = $assetsImgPath . '/fotos/' . ltrim($logoRaw, '/');
    }

    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return '';
}

class RelatorioIntervaloPdf extends TCPDF
{
    public string $logoSistemaPath = '';
    public string $logoProjetoPath = '';
    public float $logoHeightMm = 20;
    public float $headerTopMm = 8;
    public float $headerLineGapMm = 2;
    public float $tableHeaderHeightMm = 22;

    public function Header()
    {
        $logoTop = $this->headerTopMm;

        if ($this->logoSistemaPath !== '' && is_file($this->logoSistemaPath)) {
            $this->Image($this->logoSistemaPath, 15, $logoTop, 0, $this->logoHeightMm, '', '', 'T', false, 300);
        }

        if ($this->logoProjetoPath !== '' && (is_file($this->logoProjetoPath) || preg_match('/^https?:\/\//i', $this->logoProjetoPath))) {
            $rightX = $this->getPageWidth() - 15 - 35;
            $this->Image($this->logoProjetoPath, $rightX, $logoTop, 0, $this->logoHeightMm, '', '', 'T', false, 300);
        }

        $lineY = $logoTop + $this->logoHeightMm + $this->headerLineGapMm;
        $this->Line(15, $lineY, $this->getPageWidth() - 15, $lineY);
    }

    public function Footer()
    {
    }

    private function drawRotatedHeaderCell(float $w, float $h, string $label): void
    {
        $x = $this->GetX();
        $y = $this->GetY();

        $this->Rect($x, $y, $w, $h);
        $this->SetFont('helvetica', 'B', 6.5);
        $this->StartTransform();
        $this->Rotate(-90, $x + ($w / 2), $y + ($h / 2));
        $this->SetXY($x - (($h - $w) / 2), $y + (($h - $w) / 2));
        $this->Cell($h, $w, $label, 0, 0, 'C');
        $this->StopTransform();
        $this->SetXY($x + $w, $y);
    }

    public function drawTableHeader(array $dias, array $dayLabels, float $wNum, float $wNome, float $wDia): void
    {
        $startX = $this->GetX();
        $startY = $this->GetY();
        $this->SetFont('helvetica', 'B', 8);
        $headerHeight = $this->tableHeaderHeightMm;
        $this->Cell($wNum, $headerHeight, 'Nº', 1, 0, 'C');
        $this->Cell($wNome, $headerHeight, 'Nome do beneficiário(a)', 1, 0, 'L');

        foreach ($dias as $idx => $dia) {
            $label = (string) ($dayLabels[$idx] ?? $dia);
            $this->drawRotatedHeaderCell($wDia, $headerHeight, $label);
        }
        $this->SetXY($startX, $startY + $headerHeight);
    }

    public function drawAlunoRow(int $indice, string $nome, array $dias, array $tabelaAluno, float $wNum, float $wNome, float $wDia): float
    {
        $baseHeight = 6.0;
        $nomeHeight = $this->getStringHeight($wNome, $nome, false, true, '', 1, false);
        $rowHeight = max($baseHeight, $nomeHeight + 1.2);

        $x = $this->GetX();
        $y = $this->GetY();

        $this->Cell($wNum, $rowHeight, (string) $indice, 1, 0, 'C');

        $this->MultiCell(
            $wNome,
            $rowHeight,
            $nome,
            1,
            'L',
            false,
            0,
            '',
            '',
            true,
            0,
            false,
            true,
            $rowHeight,
            'M'
        );

        foreach ($dias as $dia) {
            $status = (string) ($tabelaAluno[$dia] ?? 'NA');
            $this->Cell($wDia, $rowHeight, $status, 1, 0, 'C');
        }

        $this->SetXY($x, $y + $rowHeight);
        return $rowHeight;
    }
}

$curso = $_GET['curso2'] ?? '';
$turma = $_GET['turma2'] ?? '';
$dataInicio = $_GET['dataInicio'] ?? '';
$dataFim = $_GET['dataFim'] ?? '';
$habilitado = $_GET['habilitado'] ?? 0;

$dataInicioTs = strtotime($dataInicio);
$dataFimTs = strtotime($dataFim);

if (!$dataInicioTs || !$dataFimTs) {
    echo '<script>alert("Datas inválidas."); window.close();</script>';
    exit;
}

$dataInicioFormatada = date('d/m/Y', $dataInicioTs);
$dataFimFormatada = date('d/m/Y', $dataFimTs);

$service = new RelatorioService(new RelatorioRepository());
$payload = $_GET;
$payload['dataInicio'] = date('Y-m-d', $dataInicioTs);
$payload['dataFim'] = date('Y-m-d', $dataFimTs);
$payload['habilitado2'] = $habilitado;
$data = $service->frequenciaIntervalo($payload, false, true, false, false);

if (empty($data)) {
    echo '<script>alert("Nenhum dado encontrado para os critérios selecionados."); window.close();</script>';
    exit;
}

$cursoNome = (string) ($data[0]['NomeCurso'] ?? '');
$cursoDuracao = (string) ($data[0]['Duracao'] ?? '');
$cursoTipo = (string) ($data[0]['Tipo'] ?? '');
$cursoCH = (string) ($data[0]['CargaHoraria'] ?? '');
$cursoTermo = (string) ($data[0]['Termo'] ?? '');
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

$alunos = [];
$alunosInfo = [];
$tabela = [];
$diasSet = [];

foreach ($data as $row) {
    $idAluno = (int) ($row['IdAluno'] ?? 0);
    $nomeAluno = (string) ($row['Aluno'] ?? '');

    $diaKey = sprintf('%02d/%02d/%02d', (int) ($row['Dia'] ?? 0), (int) ($row['Mes'] ?? 0), (int) ((int) ($row['Ano'] ?? 0) % 100));
    $diasSet[$diaKey] = true;

    if (!isset($alunosInfo[$idAluno])) {
        $alunosInfo[$idAluno] = ['nome' => $nomeAluno];
        $alunos[] = $idAluno;
    }

    if ((int) ($row['presenca'] ?? 0) === 1) {
        $status = 'P';
    } elseif ((int) ($row['falta'] ?? 0) === 1) {
        $status = 'F';
    } elseif ((int) ($row['faltajust'] ?? 0) === 1) {
        $status = 'FJ';
    } else {
        $status = 'NA';
    }

    $tabela[$idAluno][$diaKey] = $status;
}

$dias = array_keys($diasSet);
usort($dias, static function ($a, $b) {
    $da = DateTime::createFromFormat('d/m/y', $a);
    $db = DateTime::createFromFormat('d/m/y', $b);
    if ($da && $db) {
        return $da <=> $db;
    }
    return strcmp($a, $b);
});

$margin = 15;
$wNum = 10.0;
$diaCount = max(1, count($dias));

$portraitPageWidth = 210.0;
$portraitUsable = $portraitPageWidth - ($margin * 2);
$portraitNome = 70.0;
$portraitDia = ($portraitUsable - $wNum - $portraitNome) / $diaCount;

if ($portraitDia >= 4.8) {
    $orientation = 'P';
    $pageWidth = $portraitPageWidth;
    $usableWidth = $portraitUsable;
    $wNome = $portraitNome;
    $wDia = $portraitDia;
} else {
    $orientation = 'L';
    $pageWidth = 297.0;
    $usableWidth = $pageWidth - ($margin * 2);
    $wNome = ($diaCount > 22) ? 56.0 : 70.0;
    $wDia = ($usableWidth - $wNum - $wNome) / $diaCount;

    if ($wDia < 4.2) {
        $wNome = max(45.0, $usableWidth - $wNum - ($diaCount * 4.2));
        $wDia = ($usableWidth - $wNum - $wNome) / $diaCount;
    }
}

$dayLabels = [];
foreach ($dias as $dia) {
    $dt = DateTime::createFromFormat('d/m/y', $dia);
    if ($dt) {
        $dayLabels[] = $dt->format('d/m/y');
    } else {
        $dayLabels[] = (string) $dia;
    }
}

$pdf = new RelatorioIntervaloPdf($orientation, 'mm', 'A4', true, 'UTF-8', false);
$pdf->logoSistemaPath = $logoSistemaPath;
$pdf->logoProjetoPath = $logoProjetoPath;
$pdf->SetCreator('Conecta OSC');
$pdf->SetAuthor('Conecta OSC');
$pdf->SetTitle('Relatório de Frequência por Intervalo');
$pdf->SetMargins($margin, 40, $margin);
$pdf->SetHeaderMargin(0);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();

$infoHtml = '
<h2 style="text-align:center;">Relatório de Frequência</h2>
<table cellpadding="4" border="1">
  <tr>
    <td><b>Curso</b><br>' . htmlspecialchars($cursoNome, ENT_QUOTES, 'UTF-8') . '</td>
    <td><b>Projeto</b><br>' . htmlspecialchars($nomeProjeto, ENT_QUOTES, 'UTF-8') . '</td>
    <td><b>Turma</b><br>' . htmlspecialchars($turmaNome, ENT_QUOTES, 'UTF-8') . '</td>
  </tr>
  <tr>
    <td><b>Tipo</b><br>' . htmlspecialchars($cursoTipo, ENT_QUOTES, 'UTF-8') . '</td>
    <td><b>Duração</b><br>' . htmlspecialchars($cursoDuracao, ENT_QUOTES, 'UTF-8') . '</td>
    <td><b>Carga Horária</b><br>' . htmlspecialchars($cursoCH, ENT_QUOTES, 'UTF-8') . '</td>
  </tr>
  <tr>
    <td><b>Termo</b><br>' . htmlspecialchars($cursoTermo, ENT_QUOTES, 'UTF-8') . '</td>
    <td colspan="2"><b>Intervalo</b><br>' . htmlspecialchars($dataInicioFormatada . ' a ' . $dataFimFormatada, ENT_QUOTES, 'UTF-8') . '</td>
  </tr>
</table>
<br>';

$pdf->SetFont('helvetica', '', 10);
$pdf->writeHTML($infoHtml, true, false, true, false, '');

$pdf->drawTableHeader($dias, $dayLabels, $wNum, $wNome, $wDia);
$pdf->SetFont('helvetica', '', 8);

$bottomLimit = $pdf->getPageHeight() - 15;
$indice = 1;

foreach ($alunos as $idAluno) {
    $nomeAluno = (string) ($alunosInfo[$idAluno]['nome'] ?? '');
    $nomeHeight = $pdf->getStringHeight($wNome, $nomeAluno, false, true, '', 1, false);
    $rowHeight = max(6.0, $nomeHeight + 1.2);

    if ($pdf->GetY() + $rowHeight > $bottomLimit) {
        $pdf->AddPage();
        $pdf->drawTableHeader($dias, $dayLabels, $wNum, $wNome, $wDia);
        $pdf->SetFont('helvetica', '', 8);
    }

    $pdf->drawAlunoRow($indice, $nomeAluno, $dias, $tabela[$idAluno] ?? [], $wNum, $wNome, $wDia);
    $indice++;
}

$pdf->Ln(2);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->MultiCell(0, 5, 'Obs.: P = Presença; F = Falta; FJ = Falta Justificada; NA = Não se aplica.', 0, 'L', false, 1);

while (ob_get_level() > 0) {
    ob_end_clean();
}

$pdf->Output('relatorio_frequencia_intervalo.pdf', 'I');
exit;
