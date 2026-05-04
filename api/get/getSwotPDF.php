<?php
require_once __DIR__ . '/../../bootstrap/runtime.php';
$runtime = bootstrap_runtime();
$BASE_para_PATH = $runtime['base_para_path'];

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', '86400');
}
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');
ob_start();

require_once $BASE_para_PATH . '/api/lib/tcpdf/tcpdf.php';
require_once $BASE_para_PATH . '/app/views/swot/avaliarSwot.php';
require_once $BASE_para_PATH . '/app/models/swot/swotModel.php';
require_once $BASE_para_PATH . '/api/repositories/RelatorioRepository.php';
require_once $BASE_para_PATH . '/api/services/RelatorioService.php';

use BackEnd\Repositories\RelatorioRepository;
use BackEnd\Services\RelatorioService;

$tema = $_GET['tema'] ?? 'todos';
$usuario = $_GET['usuario'] ?? 'todos';

$dados = SwotModel::listForReport($tema, $usuario);

if (empty($dados)) {
    echo "<div class='alert alert-danger'>Nenhum dado encontrado para os filtros selecionados.</div>";
    exit;
}

function resolveProjectLogoPath($logoRaw, $basePath)
{
    if (!is_string($logoRaw)) {
        return '';
    }

    $logoRaw = trim($logoRaw);
    if ($logoRaw === '') {
        return '';
    }

    if (preg_match('/^https?:\\/\\//i', $logoRaw)) {
        return $logoRaw;
    }

    $logoRaw = str_replace('\\', '/', $logoRaw);
    $basePath = rtrim($basePath, '/\\');
    $assetsImgPath = '';
    if (isset($GLOBALS['runtime']['assets_img_path'])) {
        $assetsImgPath = rtrim((string)$GLOBALS['runtime']['assets_img_path'], '/\\');
    }

    $candidates = [];
    $candidates[] = $basePath . '/' . ltrim($logoRaw, '/');
    $candidates[] = dirname($basePath) . '/' . ltrim($logoRaw, '/');
    if ($assetsImgPath !== '') {
        $candidates[] = $assetsImgPath . '/' . ltrim($logoRaw, '/');
        $candidates[] = $assetsImgPath . '/logos/' . ltrim($logoRaw, '/');
        $candidates[] = $assetsImgPath . '/sistema/' . ltrim($logoRaw, '/');
        $candidates[] = $assetsImgPath . '/fotos/' . ltrim($logoRaw, '/');
    }
    $candidates[] = $basePath . '/app/assets/img/' . ltrim($logoRaw, '/');
    $candidates[] = $basePath . '/app/assets/img/logos/' . ltrim($logoRaw, '/');
    $candidates[] = $basePath . '/app/assets/img/sistema/' . ltrim($logoRaw, '/');
    $candidates[] = $basePath . '/app/assets/img/fotos/' . ltrim($logoRaw, '/');

    foreach ($candidates as $path) {
        if (is_file($path)) {
            return $path;
        }
    }

    return '';
}

$relatorioService = new RelatorioService(new RelatorioRepository());
$logoSistema = $relatorioService->logoImpressao();
$logoSistemaPath = resolveProjectLogoPath($logoSistema, $BASE_para_PATH);

class SwotPDF extends TCPDF
{
    public $logoSistemaPath = '';
    public $logoHeightMm = 18;
    public $headerTopMm = 8;
    public $headerLineGapMm = 3;

    public function Header()
    {
        $logoTop = $this->headerTopMm;
        if ($this->logoSistemaPath && is_file($this->logoSistemaPath)) {
            $this->Image($this->logoSistemaPath, 15, $logoTop, 0, $this->logoHeightMm, '', '', 'T', false, 300);
        }
        $this->SetY($logoTop + 2);
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 10, 'Relatório do SWOT', 0, 0, 'C');
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

$pdf = new SwotPDF();
$pdf->logoSistemaPath = $logoSistemaPath;
$pdf->SetCreator('Conecta OSC');
$pdf->SetAuthor('Conecta OSC');
$pdf->SetTitle('Relatório do SWOT');
$pdf->SetMargins(15, 38, 15);
$pdf->SetHeaderMargin(0);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

$grupos = [
    'INOVAÇÃO EM PROJETOS EXISTENTES' => [],
    'NOVOS PROJETOS PARA INVESTIDORES' => [],
    'NOVOS SERVIÇOS' => [],
    'NOVOS PRODUTOS' => []
];

foreach ($dados as $item) {
    $temaItem = $item['Tema'] ?? '';
    if (!isset($grupos[$temaItem])) {
        $grupos[$temaItem] = [];
    }
    $grupos[$temaItem][] = $item;
}

$html = '<h2 style="text-align:center;"><b>Relatório do SWOT</b></h2>';

foreach ($grupos as $tema => $itens) {
    $html .= '<h4 style="margin-top:18px; margin-bottom:8px;"><b>' . htmlspecialchars($tema) . '</b></h4>';
    $html .= '<table cellpadding="4" border="1">
        <thead>
            <tr>
                <th width="100%"><b>Insight</b></th>
            </tr>
        </thead>
        <tbody>';

    if (empty($itens)) {
        $html .= '<tr><td colspan="2">Nenhum insight.</td></tr>';
    } else {
        foreach ($itens as $item) {
            $nome = trim((string) ($item['Nome'] ?? ''));
            $textoRaw = html_entity_decode((string) ($item['Texto'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $texto = trim(preg_replace('/\s+/', ' ', strip_tags($textoRaw)));
            $linhaInsight = $texto;
            if ($nome !== '') {
                $linhaInsight .= ' (' . $nome . ')';
            }

            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($linhaInsight) . '</td>';
            $html .= '</tr>';
        }
    }

    $html .= '</tbody></table>';
    $html .= '<div style="height:14px;"></div>';
}

$resumosIA = [];
foreach ($grupos as $tema => $itens) {
    if (empty($itens)) {
        continue;
    }
    $lista = [];
    foreach ($itens as $item) {
        $texto = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($item['Texto'] ?? ''))));
        if ($texto !== '') {
            $lista[] = $texto;
        }
    }
    if (!empty($lista)) {
        $resumosIA[$tema] = SwotAvaliacao::consolidarInsights($tema, $lista);
    }
}

if (!empty($resumosIA)) {
    $html .= '<h3 style="margin-top:18px;"><b>Insights consolidados (IA)</b></h3>';
    foreach ($resumosIA as $tema => $insights) {
        $html .= '<h4 style="margin-top:10px;"><b>' . htmlspecialchars($tema) . '</b></h4>';
        if (empty($insights)) {
            $html .= '<p>Nenhum insight consolidado.</p>';
            continue;
        }
        $html .= '<ul>';
        foreach ($insights as $insight) {
            $html .= '<li>' . htmlspecialchars($insight) . '</li>';
        }
        $html .= '</ul>';
    }
}

if (ob_get_length()) {
    ob_clean();
}
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('relatorio_swot.pdf', 'I');
exit;




