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

$idsRecebidos = $_POST['checkbox'] ?? [];
if (!is_array($idsRecebidos)) {
    $idsRecebidos = [];
}

$ids = array_values(array_unique(array_filter(
    array_map('intval', $idsRecebidos),
    static fn (int $id): bool => $id > 0
)));

if ($ids === []) {
    echo '<script>alert("Selecione pelo menos um beneficiário para gerar o IPAI/Anamnese."); window.history.back();</script>';
    exit;
}

$service = new BeneficiarioService(new BeneficiarioRepository());
$beneficiarios = $service->findByIds($ids);

if ($beneficiarios === []) {
    echo '<script>alert("Nenhum beneficiário válido foi encontrado para gerar o relatório."); window.history.back();</script>';
    exit;
}

$logoPath = '';
$candidatosLogo = [
    $ASSETS_IMG_PATH . '/logos/LogoImpressao.jpg',
    $ASSETS_IMG_PATH . '/logos/LogoImpressao.jpeg',
    $ASSETS_IMG_PATH . '/logos/LogoImpressao.png',
    $ASSETS_IMG_PATH . '/logos/logoImpressao.jpg',
];
foreach ($candidatosLogo as $candidato) {
    if (is_file($candidato)) {
        $logoPath = $candidato;
        break;
    }
}

class IpaiAnamneseLotePDF extends TCPDF
{
    public string $logoPath = '';
    public string $emitidoEm = '';
    public array $headerBeneficiario = [];

    public function Header(): void
    {
        if ($this->logoPath !== '' && is_file($this->logoPath)) {
            $this->Image($this->logoPath, 12, 7, 28, 0, '', '', 'T', false, 300);
        }

        $this->SetY(9);
        $this->SetFont('dejavusans', 'B', 11);
        $this->Cell(0, 6, 'IPAI / Anamnese Psicossocial', 0, 1, 'C');
        $this->SetFont('dejavusans', '', 9);
        $this->Cell(0, 5, 'Conecta OSC - ITEVA', 0, 1, 'C');
        $this->Line(12, 24, $this->getPageWidth() - 12, 24);

        if ($this->headerBeneficiario !== []) {
            $nome = (string)($this->headerBeneficiario['nome'] ?? 'Não informado');
            $cpf = (string)($this->headerBeneficiario['cpf'] ?? 'Não informado');
            $nascimento = (string)($this->headerBeneficiario['nascimento'] ?? 'Não informado');
            $telefone = (string)($this->headerBeneficiario['telefone'] ?? 'Não informado');

            $this->SetY(25.5);
            $this->SetFont('dejavusans', '', 8.3);
            $this->Cell(0, 5, "Beneficiário: $nome", 0, 1, 'L');
            $this->SetX(12);
            $this->Cell(0, 4.5, "CPF: $cpf   |   Nascimento: $nascimento   |   Telefone: $telefone", 0, 1, 'L');
            $this->Line(12, 35.5, $this->getPageWidth() - 12, 35.5);
        }
    }

    public function Footer(): void
    {
        $this->SetY(-14);
        $this->SetFont('dejavusans', '', 8);
        $rodape = 'ITEVA - Emissão: ' . $this->emitidoEm . ' - Página '
            . $this->getAliasNumPage() . '/' . $this->getAliasNbPages();
        $this->Cell(0, 8, $rodape, 0, 0, 'C');
    }
}

function formatarDataBR(mixed $valor): string
{
    if ($valor === null || $valor === '') {
        return 'Não informado';
    }

    $texto = trim((string)$valor);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $texto)) {
        $dt = DateTime::createFromFormat('Y-m-d', $texto);
        return $dt ? $dt->format('d/m/Y') : $texto;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $texto)) {
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $texto);
        return $dt ? $dt->format('d/m/Y H:i:s') : $texto;
    }
    return $texto;
}

function valorCampoPDF(mixed $valor): string
{
    if ($valor === null || $valor === '') {
        return 'Não informado';
    }
    if (is_bool($valor)) {
        return $valor ? 'Sim' : 'Não';
    }
    if (is_scalar($valor)) {
        return formatarDataBR($valor);
    }
    return json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'Não informado';
}

function valorBeneficiario(array $beneficiario, string $campo): string
{
    return valorCampoPDF($beneficiario[$campo] ?? '');
}

function linhasEspaco(int $quantidade = 2): string
{
    $linhas = '';
    for ($i = 0; $i < $quantidade; $i++) {
        $linhas .= '<div class="linha"></div>';
    }
    return $linhas;
}

function htmlQuestionarioEstruturado(array $beneficiario): string
{
    $nome = htmlspecialchars(valorBeneficiario($beneficiario, 'Nome'), ENT_QUOTES, 'UTF-8');
    $nascimento = htmlspecialchars(valorBeneficiario($beneficiario, 'Nascimento'), ENT_QUOTES, 'UTF-8');
    $telefone = htmlspecialchars(valorBeneficiario($beneficiario, 'Telefone'), ENT_QUOTES, 'UTF-8');
    $cpf = htmlspecialchars(valorBeneficiario($beneficiario, 'CPF'), ENT_QUOTES, 'UTF-8');
    $sexo = htmlspecialchars(valorBeneficiario($beneficiario, 'SexoBio'), ENT_QUOTES, 'UTF-8');
    $estadoCivil = htmlspecialchars(valorBeneficiario($beneficiario, 'EstadoCivil'), ENT_QUOTES, 'UTF-8');
    $endereco = htmlspecialchars(valorBeneficiario($beneficiario, 'Endereco'), ENT_QUOTES, 'UTF-8');
    $bairro = htmlspecialchars(valorBeneficiario($beneficiario, 'Bairro'), ENT_QUOTES, 'UTF-8');
    $cidade = htmlspecialchars(valorBeneficiario($beneficiario, 'Cidade'), ENT_QUOTES, 'UTF-8');
    $uf = htmlspecialchars(valorBeneficiario($beneficiario, 'UF'), ENT_QUOTES, 'UTF-8');
    $escolaridade = htmlspecialchars(valorBeneficiario($beneficiario, 'Escolaridade'), ENT_QUOTES, 'UTF-8');
    $profissao = htmlspecialchars(valorBeneficiario($beneficiario, 'Profissao'), ENT_QUOTES, 'UTF-8');
    $rendaMensal = htmlspecialchars(valorBeneficiario($beneficiario, 'RendaMensal'), ENT_QUOTES, 'UTF-8');
    $rendaFamiliar = htmlspecialchars(valorBeneficiario($beneficiario, 'RendaFamiliar'), ENT_QUOTES, 'UTF-8');
    $responsavel = htmlspecialchars(valorBeneficiario($beneficiario, 'NomeResp1'), ENT_QUOTES, 'UTF-8');
    $telefoneResp = htmlspecialchars(valorBeneficiario($beneficiario, 'TelefoneResp1'), ENT_QUOTES, 'UTF-8');
    $obs = htmlspecialchars(valorBeneficiario($beneficiario, 'Obs'), ENT_QUOTES, 'UTF-8');

    return '
    <style>
        .secao { font-size: 11pt; font-weight: bold; margin-top: 8px; margin-bottom: 4px; }
        .texto { font-size: 10pt; line-height: 1.35; }
        .linha { border-bottom: 1px solid #000; height: 12px; }
        .opcoes { margin-top: 2px; margin-bottom: 2px; }
    </style>

    <div class="secao">1 - DADOS GERAIS DE IDENTIFICAÇÃO DO BENEFICIÁRIO</div>
    <table border="1" cellpadding="3">
        <tr><td width="20%"><b>Nome</b></td><td width="80%">' . $nome . '</td></tr>
        <tr><td><b>Data de nascimento</b></td><td>' . $nascimento . '</td></tr>
        <tr><td><b>Telefone</b></td><td>' . $telefone . '</td></tr>
        <tr><td><b>CPF</b></td><td>' . $cpf . '</td></tr>
        <tr><td><b>Endereço</b></td><td>' . $endereco . ' - ' . $bairro . ' - ' . $cidade . '/' . $uf . '</td></tr>
    </table>

    <div class="secao">2 - INFORMAÇÕES GERAIS E CONDIÇÃO SOCIOECONÔMICA</div>
    <div class="texto">
        Sexo: <b>' . $sexo . '</b> &nbsp;&nbsp; Estado civil: <b>' . $estadoCivil . '</b><br>
        Escolaridade: <b>' . $escolaridade . '</b> &nbsp;&nbsp; Profissão: <b>' . $profissao . '</b><br>
        Renda mensal: <b>' . $rendaMensal . '</b> &nbsp;&nbsp; Renda familiar: <b>' . $rendaFamiliar . '</b>
    </div>
    <div class="texto opcoes">Auxílios ou benefícios: ( ) Sim  ( ) Não  ( ) BPC  ( ) Pensionista  ( ) Aposentado  ( ) Renda própria</div>
    <div class="texto">Desenvolve alguma atividade remunerada? Qual?</div>' . linhasEspaco(2) . '
    <div class="texto">Tem alguma condição prévia de saúde? Qual?</div>' . linhasEspaco(2) . '
    <div class="texto">Tipo de residência: ( ) Própria  ( ) Alugada  ( ) Cedida  ( ) Ocupada</div>
    <div class="texto">Tem filhos? Quantos?</div>' . linhasEspaco(1) . '
    <div class="texto">Total de cômodos da casa: ( ) 1  ( ) 2  ( ) 3  ( ) Mais de 3</div>
    <div class="texto">Renda per capita: ( ) Menos de 1 salário  ( ) 1 salário  ( ) 1-2 salários  ( ) 2-3 salários  ( ) 4+ salários</div>
    <div class="texto">Cor/Raça: ( ) Branca  ( ) Parda  ( ) Preta  ( ) Amarela  ( ) Prefiro não responder  ( ) Outra</div>

    <div class="secao">3 - CONTEXTO PSICOSSOCIAL E REDE DE APOIO</div>
    <div class="texto">Quantas pessoas residem na sua casa?</div>' . linhasEspaco(1) . '
    <div class="texto">Com quem você pode contar em caso de necessidade?</div>' . linhasEspaco(2) . '
    <div class="texto">Participa de grupos, atividades comunitárias ou religiosas?</div>' . linhasEspaco(2) . '

    <div class="secao">4 - CONDIÇÕES DE SAÚDE E FUNCIONALIDADE</div>
    <div class="texto">Possui diagnóstico médico em acompanhamento? Qual?</div>' . linhasEspaco(2) . '
    <div class="texto">Faz uso contínuo de medicação? Qual(is)?</div>' . linhasEspaco(2) . '
    <div class="texto">Consegue realizar atividades do cotidiano com autonomia?</div>' . linhasEspaco(2) . '

    <div class="secao">5 - BEM-ESTAR SUBJETIVO E HUMOR</div>
    <div class="texto">Nos últimos meses, como você tem se sentido emocionalmente?</div>' . linhasEspaco(2) . '
    <div class="texto">Costuma sentir tristeza, ansiedade, irritação ou desânimo? Com que frequência?</div>' . linhasEspaco(2) . '

    <div class="secao">6 - COGNIÇÃO E FUNÇÕES EXECUTIVAS (RASTREAMENTO BREVE)</div>
    <div class="texto">Memória imediata, orientação temporal/espacial e atenção (anotações do profissional):</div>' . linhasEspaco(4) . '
    <div class="texto">Fluência verbal e resolução de problemas:</div>' . linhasEspaco(3) . '

    <div class="secao">7 - RISCO E PROTEÇÃO PSICOSSOCIAL</div>
    <div class="texto">Há histórico de uso de álcool ou outras substâncias? ( ) Sim  ( ) Não</div>
    <div class="texto">Já pensou ou tentou se machucar alguma vez? ( ) Sim  ( ) Não</div>
    <div class="texto">Costuma se sentir sozinho(a) ou isolado(a)? ( ) Sim  ( ) Às vezes  ( ) Não</div>
    <div class="texto">O que mais te ajuda nos momentos difíceis?</div>' . linhasEspaco(2) . '
    <div class="texto">Quais atividades te fazem bem ou te dão prazer?</div>' . linhasEspaco(2) . '

    <div class="secao">8 - ASPECTOS EMOCIONAIS E DE ENFRENTAMENTO</div>
    <div class="texto">Quando enfrenta dificuldades, consegue pedir ajuda? ( ) Sim  ( ) Às vezes  ( ) Não</div>
    <div class="texto">Atualmente sente que tem objetivos que te motivam? ( ) Sim  ( ) Às vezes  ( ) Não</div>
    <div class="texto">Quando está triste ou preocupado(a), o que costuma fazer para se sentir melhor?</div>' . linhasEspaco(3) . '

    <div class="secao">9 - MEMÓRIA, COTIDIANO E EXPECTATIVAS</div>
    <div class="texto">Percebe dificuldades de memória? ( ) Não  ( ) Sim, raramente  ( ) Sim, com frequência</div>
    <div class="texto">Consegue realizar sozinho atividades básicas (alimentação, higiene e deslocamentos)?</div>
    <div class="texto opcoes">( ) Sim, sem dificuldade  ( ) Sim, com alguma ajuda  ( ) Não, dependo de ajuda</div>
    <div class="texto">O que mais espera conquistar participando deste projeto?</div>' . linhasEspaco(2) . '
    <div class="texto">Gostaria de fortalecer mais o quê?</div>
    <div class="texto opcoes">( ) Aprendizados  ( ) Convivência  ( ) Saúde e bem-estar  ( ) Outro:</div>' . linhasEspaco(2) . '

    <div class="secao">10 - OBSERVAÇÕES E IMPRESSÕES GERAIS DO PROFISSIONAL</div>' . linhasEspaco(5) . '

    <div class="secao">Dados complementares do cadastro</div>
    <table border="1" cellpadding="3">
        <tr><td width="22%"><b>Responsável</b></td><td width="78%">' . $responsavel . '</td></tr>
        <tr><td><b>Telefone do responsável</b></td><td>' . $telefoneResp . '</td></tr>
        <tr><td><b>Observações (tbAluno)</b></td><td>' . $obs . '</td></tr>
    </table>
    ';
}

$pdf = new IpaiAnamneseLotePDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->logoPath = $logoPath;
$pdf->emitidoEm = date('d/m/Y H:i');
$pdf->SetCreator('Conecta OSC');
$pdf->SetAuthor('Conecta OSC');
$pdf->SetTitle('IPAI - Anamnese Psicossocial');
$pdf->SetMargins(12, 38, 12);
$pdf->SetAutoPageBreak(true, 18);
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);

foreach ($beneficiarios as $beneficiario) {
    $pdf->headerBeneficiario = [
        'nome' => valorCampoPDF($beneficiario['Nome'] ?? ''),
        'cpf' => valorCampoPDF($beneficiario['CPF'] ?? ''),
        'nascimento' => valorCampoPDF($beneficiario['Nascimento'] ?? ''),
        'telefone' => valorCampoPDF($beneficiario['Telefone'] ?? ''),
    ];

    $pdf->AddPage();
    $pdf->SetFont('dejavusans', 'B', 11);
    $pdf->Cell(0, 7, 'Questionário Psicossocial - Formulário Estruturado', 0, 1, 'L');
    $pdf->SetFont('dejavusans', '', 9);
    $pdf->Ln(1);
    $pdf->writeHTML(htmlQuestionarioEstruturado($beneficiario), true, false, true, false, '');
}

if (ob_get_length()) {
    ob_clean();
}

$nomeArquivo = 'ipai_anamnese_lote_' . date('Ymd_His') . '.pdf';
$pdf->Output($nomeArquivo, 'I');