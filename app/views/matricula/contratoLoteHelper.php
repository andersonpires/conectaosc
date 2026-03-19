<?php

if (!function_exists('loteContratoResolveProjectLogoPath')) {
    function loteContratoResolveProjectLogoPath($logoRaw, string $basePath): string
    {
        return bootstrap_resolve_assets_img_file(is_string($logoRaw) ? $logoRaw : '');
    }
}

if (!class_exists('LoteContratoPDF')) {
    class LoteContratoPDF extends TCPDF
    {
        public string $logoPath = '';
        public string $logoSistemaPath = '';
        public float $logoHeightMm = 20;
        public float $headerTopMm = 8;
        public float $headerLineGapMm = 2;

        public function Header(): void
        {
            $logoTop = $this->headerTopMm;

            if ($this->logoSistemaPath && is_file($this->logoSistemaPath)) {
                $this->Image($this->logoSistemaPath, 15, $logoTop, 0, $this->logoHeightMm, '', '', 'T', false, 300);
            }

            if ($this->logoPath && is_file($this->logoPath)) {
                $rightX = $this->getPageWidth() - 15 - 35;
                $this->Image($this->logoPath, $rightX, $logoTop, 0, $this->logoHeightMm, '', '', 'T', false, 300);
            }

            $lineY = $logoTop + $this->logoHeightMm + $this->headerLineGapMm;
            $this->Line(15, $lineY, $this->getPageWidth() - 15, $lineY);
        }

        public function Footer(): void
        {
        }
    }
}

if (!function_exists('loteContratoCarregarConfig')) {
    function loteContratoCarregarConfig(PDO $pdo, string $basePath): array
    {
        $config = $pdo->query("\n            SELECT c.LogoImpressao,\n                   c.CargoDirigente,\n                   c.IdColaboradorDirigente,\n                   u.Nome AS NomeDirigente,\n                   u.Sobrenome AS SobrenomeDirigente\n              FROM tbConfig c\n         LEFT JOIN tbUser u ON u.IdColaborador = c.IdColaboradorDirigente\n             LIMIT 1\n        ")->fetch(PDO::FETCH_ASSOC) ?: [];

        $logoSistema = (string)($config['LogoImpressao'] ?? '');
        if (trim($logoSistema) === '') {
            $logoSistema = 'logos/LogoImpressao.jpg';
        }

        $nomeDirigente = trim((string)(($config['NomeDirigente'] ?? '') . ' ' . ($config['SobrenomeDirigente'] ?? '')));
        $cargoDirigente = trim((string)($config['CargoDirigente'] ?? ''));

        return [
            'logoSistemaPath' => loteContratoResolveProjectLogoPath($logoSistema, $basePath),
            'responsavelSistema' => 'Instituto Tecnológico e Vocacional Avançado - ITEVA',
            'nomeDirigente' => $nomeDirigente,
            'cargoDirigente' => $cargoDirigente,
            'linhaDirigenteCargo' => trim($nomeDirigente . ($cargoDirigente !== '' ? (' - ' . $cargoDirigente) : '')),
            'idDirigente' => (int)($config['IdColaboradorDirigente'] ?? 0),
        ];
    }
}

if (!function_exists('loteContratoGerarHtml')) {
    function loteContratoGerarHtml(array $dados, string $responsavelSistema, string $linhaDirigenteCargo): string
    {
        $nome = (string)($dados['Nome'] ?? '');
        $cpf = (string)($dados['CPF'] ?? '');
        $nascimento = (string)($dados['Nascimento'] ?? '');
        $endereco = (string)($dados['Endereco'] ?? '');
        $bairro = (string)($dados['Bairro'] ?? '');
        $cidade = (string)($dados['Cidade'] ?? '');
        $uf = (string)($dados['UF'] ?? '');
        $telefone = (string)($dados['Telefone'] ?? '');
        $whatsapp = (string)($dados['WhatsApp'] ?? '');
        $responsavel = (string)($dados['NomeResp1'] ?? '');
        $cpfResp = (string)($dados['CpfResp1'] ?? '');
        $telefoneResp = (string)($dados['TelefoneResp1'] ?? ($dados['WhatsAppResp1'] ?? ''));
        $termosContrato = trim((string)($dados['TermosContrato'] ?? ''));

        $responsavelRows = '';
        if (trim($responsavel) !== '') {
            $responsavelRows .= '<tr><td><b>Nome</b></td><td>' . htmlspecialchars($responsavel, ENT_QUOTES, 'UTF-8') . '</td></tr>';
        }
        if (trim($cpfResp) !== '') {
            $responsavelRows .= '<tr><td><b>CPF</b></td><td>' . htmlspecialchars($cpfResp, ENT_QUOTES, 'UTF-8') . '</td></tr>';
        }
        if (trim($telefoneResp) !== '') {
            $responsavelRows .= '<tr><td><b>Contato</b></td><td>' . htmlspecialchars($telefoneResp, ENT_QUOTES, 'UTF-8') . '</td></tr>';
        }

        $responsavelHtml = '';
        if ($responsavelRows !== '') {
            $responsavelHtml = '<h4>Responsável</h4><table cellpadding="4" border="1">' . $responsavelRows . '</table>';
        }

        $termosHtml = $termosContrato !== '' ? ('<h4>Termos do Contrato</h4>' . html_entity_decode($termosContrato)) : '';

        $temDadosResponsavel = trim($responsavel) !== '' || trim($cpfResp) !== '' || trim($telefoneResp) !== '';
        $nomeAssinaturaBeneficiarioOuResponsavel = $temDadosResponsavel
            ? (trim($responsavel) !== '' ? $responsavel : 'Responsável')
            : $nome;

        $assinaturaHtml = '';
        if (trim($responsavelSistema) !== '') {
            $assinaturaHtml .= '<div style="clear:both; page-break-inside:avoid;"><table cellpadding="0" cellspacing="0" border="0"><tr><td style="height:42mm;"></td></tr></table><p style="text-align:center;">______________________________________________<br>';
            if ($linhaDirigenteCargo !== '') {
                $assinaturaHtml .= '<b>' . htmlspecialchars($linhaDirigenteCargo, ENT_QUOTES, 'UTF-8') . '</b><br>';
            }
            $assinaturaHtml .= htmlspecialchars($responsavelSistema, ENT_QUOTES, 'UTF-8');
            $assinaturaHtml .= '</p>';
        }
        $assinaturaHtml .= '<table cellpadding="0" cellspacing="0" border="0"><tr><td style="height:12mm;"></td></tr></table><p style="text-align:center;">______________________________________________<br>' . htmlspecialchars($nomeAssinaturaBeneficiarioOuResponsavel, ENT_QUOTES, 'UTF-8') . '</p></div>';

        return '
<h2 style="text-align:center;"><b>TERMO DE RESPONSABILIDADE E COMPROMISSO</b></h2>
<h4>Dados do Beneficiário</h4>
<table cellpadding="4" border="1">
    <tr><td><b>Nome</b></td><td>' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . '</td></tr>
    <tr><td><b>Data de nascimento</b></td><td>' . htmlspecialchars($nascimento, ENT_QUOTES, 'UTF-8') . '</td></tr>
    <tr><td><b>CPF</b></td><td>' . htmlspecialchars($cpf, ENT_QUOTES, 'UTF-8') . '</td></tr>
    <tr><td><b>Endereço</b></td><td>' . htmlspecialchars($endereco, ENT_QUOTES, 'UTF-8') . '</td></tr>
    <tr><td><b>Bairro</b></td><td>' . htmlspecialchars($bairro, ENT_QUOTES, 'UTF-8') . '</td></tr>
    <tr><td><b>Cidade/UF</b></td><td>' . htmlspecialchars(trim($cidade . ' / ' . $uf), ENT_QUOTES, 'UTF-8') . '</td></tr>
    <tr><td><b>Telefone</b></td><td>' . htmlspecialchars($telefone, ENT_QUOTES, 'UTF-8') . '</td></tr>
    <tr><td><b>WhatsApp</b></td><td>' . htmlspecialchars($whatsapp, ENT_QUOTES, 'UTF-8') . '</td></tr>
</table>' . $responsavelHtml . $termosHtml . $assinaturaHtml;
    }
}

if (!function_exists('loteContratoGerarContratoAssinadoPorMatricula')) {
    function loteContratoGerarContratoAssinadoPorMatricula(
        PDO $pdo,
        string $basePath,
        string $baseUrl,
        array $dados,
        array $configAssinatura,
        ?int $idAssinanteFallback = null,
        ?string $nomeAssinanteFallback = null
    ): array {
        $logoProjetoPath = loteContratoResolveProjectLogoPath((string)($dados['LogoProjeto'] ?? ''), $basePath);

        $pdf = new LoteContratoPDF();
        $pdf->logoPath = $logoProjetoPath;
        $pdf->logoSistemaPath = (string)($configAssinatura['logoSistemaPath'] ?? '');
        $pdf->SetCreator('Conecta OSC');
        $pdf->SetAuthor('Conecta OSC');
        $pdf->SetTitle('Contrato - ' . (string)($dados['Nome'] ?? ''));
        $pdf->SetMargins(15, 40, 15);
        $pdf->SetHeaderMargin(0);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 11);

        $html = loteContratoGerarHtml(
            $dados,
            (string)($configAssinatura['responsavelSistema'] ?? 'Instituto Tecnológico e Vocacional Avançado - ITEVA'),
            (string)($configAssinatura['linhaDirigenteCargo'] ?? '')
        );

        $pdf->writeHTML($html, true, false, true, false, '');

        $pathBaseAssinatura = rtrim($basePath, '/\\') . '/app/storage/assinatura/';
        $pathOriginais = $pathBaseAssinatura . 'originais/';
        if (!is_dir($pathOriginais)) {
            @mkdir($pathOriginais, 0775, true);
        }

        $idAssinante = (int)($configAssinatura['idDirigente'] ?? 0);
        if ($idAssinante <= 0) {
            $idAssinante = (int)$idAssinanteFallback;
        }
        if ($idAssinante <= 0) {
            $idAssinante = 0;
        }

        $nomeAssinante = trim((string)($configAssinatura['nomeDirigente'] ?? ''));
        if ($nomeAssinante === '') {
            $nomeAssinante = trim((string)$nomeAssinanteFallback);
        }
        if ($nomeAssinante === '') {
            $nomeAssinante = 'Dirigente';
        }

        $timestampArquivo = time();
        $nomeArquivoFinal = 'contrato_' . $timestampArquivo . '_' . $idAssinante . '.pdf';
        $pathOriginalTemp = $pathOriginais . 'contrato_orig_' . $timestampArquivo . '_' . $idAssinante . '_' . substr(md5(uniqid('', true)), 0, 6) . '.pdf';

        $pdf->Output($pathOriginalTemp, 'F');

        $nomeDocumento = 'Contrato - ' . trim((string)($dados['Nome'] ?? 'Beneficiário'));
        if (trim((string)($dados['NomeCurso'] ?? '')) !== '') {
            $nomeDocumento .= ' - ' . trim((string)($dados['NomeCurso'] ?? ''));
        }

        $assinado = contratoAssinarPdfComDirigente(
            $pdo,
            $basePath,
            $baseUrl,
            $pathOriginalTemp,
            $nomeArquivoFinal,
            $nomeDocumento,
            $idAssinante,
            $nomeAssinante,
            (string)($configAssinatura['responsavelSistema'] ?? 'Instituto Tecnológico e Vocacional Avançado - ITEVA')
        );

        return $assinado;
    }
}
