<?php
if (ob_get_level() === 0) {
    ob_start();
}

$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$ASSETS_IMG_PATH = rtrim((string)($runtime['assets_img_path'] ?? ($BASE_para_PATH . '/app/assets/img')), '/\\');

require_once $BASE_para_PATH . '/api/lib/tcpdf/tcpdf.php';
require_once $BASE_para_PATH . '/api/repositories/BeneficiarioRepository.php';
require_once $BASE_para_PATH . '/api/services/BeneficiarioService.php';

use BackEnd\Repositories\BeneficiarioRepository;
use BackEnd\Services\BeneficiarioService;

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    echo '<script>alert("Beneficiário inválido."); window.close();</script>';
    exit;
}

$service = new BeneficiarioService(new BeneficiarioRepository());
$dados = $service->findById($id);
if (!$dados) {
    echo '<script>alert("Beneficiário não encontrado."); window.close();</script>';
    exit;
}

$logoPath = $ASSETS_IMG_PATH . '/logos/LogoImpressao.jpg';
if (!is_file($logoPath)) {
    $logoPath = '';
}

class BeneficiarioFichaPDF extends TCPDF
{
    public string $logoPath = '';
    public string $titulo = 'Ficha de Cadastro';

    public function Header(): void
    {
        if ($this->logoPath !== '' && is_file($this->logoPath)) {
            $this->Image($this->logoPath, 12, 8, 26, 0, '', '', 'T', false, 300);
        }
        $this->SetY(10);
        $this->SetFont('helvetica', 'B', 13);
        $this->Cell(0, 8, $this->titulo, 0, 1, 'C');
        $this->Ln(2);
        $this->Line(12, 24, $this->getPageWidth() - 12, 24);
    }

    public function Footer(): void
    {
        $this->SetY(-15);
        $this->SetFont('helvetica', '', 8);
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

$pdf = new BeneficiarioFichaPDF();
$pdf->logoPath = $logoPath;
$pdf->SetCreator('Conecta OSC');
$pdf->SetAuthor('Conecta OSC');
$pdf->SetTitle('Ficha de Cadastro de Beneficiário');
$pdf->SetMargins(12, 28, 12);
$pdf->SetAutoPageBreak(true, 18);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

$ordemCampos = [
    'Nome', 'Apelido', 'CPF', 'Nascimento', 'SexoBio', 'Telefone', 'Email',
    'Endereco', 'Bairro', 'Cidade', 'UF', 'CEP', 'Escolaridade', 'Profissao',
    'RendaMensal', 'RendaFamiliar', 'NomeResp1', 'CpfResp1', 'TelefoneResp1',
    'NomeResp2', 'Obs',
];

$campos = [];
foreach ($ordemCampos as $campo) {
    if (array_key_exists($campo, $dados)) {
        $campos[$campo] = $dados[$campo];
    }
}

foreach ($dados as $k => $v) {
    if (!array_key_exists($k, $campos) && is_scalar($v)) {
        $campos[$k] = $v;
    }
}

$html = '<table cellpadding="4" border="1">';
foreach ($campos as $label => $valor) {
    if ($valor === null || $valor === '') {
        continue;
    }
    $texto = is_scalar($valor) ? (string)$valor : json_encode($valor, JSON_UNESCAPED_UNICODE);
    $html .= '<tr>'
        . '<td width="35%"><b>' . htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') . '</b></td>'
        . '<td width="65%">' . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '</td>'
        . '</tr>';
}
$html .= '</table>';

$pdf->writeHTML($html, true, false, true, false, '');
if (ob_get_length()) {
    ob_clean();
}
$pdf->Output('ficha_beneficiario_' . $id . '.pdf', 'I');
