<?php

require_once dirname(__DIR__, 3) . '/bootstrap/runtime.php';

use setasign\Fpdi\Tcpdf\Fpdi;

if (!function_exists('contratoMkdirIfMissing')) {
    function contratoMkdirIfMissing(string $path): void
    {
        if (!is_dir($path)) {
            @mkdir($path, 0775, true);
        }
    }
}

if (!function_exists('contratoBuildPublicBase')) {
    function contratoResolveBasePathSegment(string $baseUrl, string $fallback = '/conecta'): string
    {
        $base = trim(str_replace('\\', '/', $baseUrl));

        if ($base === '' || preg_match('#^[a-zA-Z]:/#', $base) || str_contains(strtolower($base), '/htdocs/')) {
            $base = $fallback;
        } elseif (preg_match('#^https?://#i', $base)) {
            $base = (string)(parse_url($base, PHP_URL_PATH) ?? $fallback);
        }

        $base = '/' . ltrim($base, '/');
        $base = rtrim($base, '/');

        return $base === '' ? $fallback : $base;
    }

    function contratoBuildPublicBase(string $baseUrl): array
    {
        $configured = trim((string)($_SESSION['BASE_PUBLIC_URL'] ?? (getenv('APP_PUBLIC_BASE_URL') ?: '')));
        if ($configured !== '') {
            $configured = rtrim($configured, '/');
            $configuredNoScheme = preg_replace('#^https?://#i', '', $configured);
            return [
                'no_scheme' => $configuredNoScheme !== null ? $configuredNoScheme : $configured,
                'with_scheme' => $configured,
            ];
        }

        $host = (string)($_SERVER['HTTP_HOST'] ?? '');
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $baseUrlPath = contratoResolveBasePathSegment($baseUrl);

        if ($host !== '') {
            $publicBaseNoScheme = $host . $baseUrlPath;
            $publicBaseWithScheme = $scheme . '://' . $host . $baseUrlPath;
        } else {
            $publicBaseNoScheme = 'localhost' . $baseUrlPath;
            $publicBaseWithScheme = 'http://localhost' . $baseUrlPath;
        }

        return [
            'no_scheme' => rtrim($publicBaseNoScheme, '/'),
            'with_scheme' => rtrim($publicBaseWithScheme, '/'),
        ];
    }
}

if (!function_exists('contratoResolveAssinaturaDigitalPublicUrl')) {
    function contratoResolveAssinaturaDigitalPublicUrl(string $baseUrl): string
    {
        $configured = trim((string)($_SESSION['BASE_PUBLIC_URL'] ?? (getenv('APP_PUBLIC_BASE_URL') ?: '')));
        if ($configured !== '') {
            return rtrim($configured, '/') . '/assinaturadigital';
        }

        $basePath = contratoResolveBasePathSegment($baseUrl);
        $parsed = parse_url(trim($baseUrl));
        $host = (string)($_SERVER['HTTP_HOST'] ?? ($parsed['host'] ?? ''));
        $host = strtolower($host);
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            ? 'https'
            : (string)($parsed['scheme'] ?? 'http');
        $port = isset($parsed['port']) ? ':' . (int)$parsed['port'] : '';

        if ($host === '' || str_contains($host, 'localhost') || str_starts_with($host, '127.0.0.1')) {
            return 'http://localhost' . $basePath . '/assinaturadigital';
        }

        return $scheme . '://' . $host . $port . $basePath . '/assinaturadigital';
    }
}

if (!function_exists('contratoGerarQrPng')) {
    function contratoGerarQrPng(string $url, string $pathDestino): bool
    {
        $basePath = dirname(__DIR__, 3);
        $invertextoApiToken = bootstrap_invertexto_api_token($basePath);
        if ($invertextoApiToken === '') {
            return false;
        }
        $qrURL = 'https://api.invertexto.com/v1/qrcode?token=' . rawurlencode($invertextoApiToken) . '&text=' . urlencode($url);
        $qrData = @file_get_contents($qrURL);
        if (!$qrData) {
            return false;
        }
        return file_put_contents($pathDestino, $qrData) !== false;
    }
}

if (!function_exists('contratoAssinarPdfComDirigente')) {
    function contratoGerarCodigoValidacaoCurto(PDO $pdo, int $timestampAssinatura): string
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

    function contratoAssinarPdfComDirigente(
        PDO $pdo,
        string $basePath,
        string $baseUrl,
        string $pathOriginalPDF,
        string $nomeArquivoFinal,
        string $nomeDocumento,
        int $idColaborador,
        string $nomeCompleto,
        string $rotuloAssinatura = 'Instituto Tecnológico e Vocacional Avançado - ITEVA'
    ): array {
        try {
            $pathBase = rtrim($basePath, '/\\') . '/app/storage/assinatura/';
            $pathFinais = $pathBase . 'assinados/';
            $pathQR = $pathBase . 'qrcodes/';
            contratoMkdirIfMissing($pathFinais);
            contratoMkdirIfMissing($pathQR);

            $basePublic = contratoBuildPublicBase($baseUrl);
            $assinaturaDigitalPublic = contratoResolveAssinaturaDigitalPublicUrl($baseUrl);

            $timestampAssinatura = time();
            $codigoBase = contratoGerarCodigoValidacaoCurto($pdo, $timestampAssinatura);
            $linkValidacao = $basePublic['with_scheme'] . "/assinatura/pdf/validar/?code=" . urlencode($codigoBase);
            $qrFile = $pathQR . $codigoBase . '.png';

            if (!contratoGerarQrPng($linkValidacao, $qrFile)) {
                throw new RuntimeException('Nao foi possivel gerar o QR Code de validacao.');
            }

            $pdf = new Fpdi();
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pageCount = $pdf->setSourceFile($pathOriginalPDF);
            $pdf->SetAutoPageBreak(false, 0);

            $nomeUpper = mb_strtoupper($nomeCompleto, 'UTF-8');
            $fontFile = rtrim($basePath, '/\\') . "/api/lib/tcpdf/fonts/Licorice-Regular.ttf";
            $fontName = 'helvetica';
            if (is_file($fontFile)) {
                $fontName = TCPDF_FONTS::addTTFfont($fontFile, 'TrueTypeUnicode', '', 32) ?: 'helvetica';
            }

            $realX = 15.0;
            $realY = 15.0;

            for ($i = 1; $i <= $pageCount; $i++) {
                $tplIdx = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($tplIdx);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tplIdx, 0, 0, $size['width'], $size['height'], true);

                if ($i < $pageCount) {
                    $pdf->SetFont('helvetica', '', 6);
                    $pdf->SetTextColor(80, 80, 80);

                    $footerPrefix = "Documento assinado digitalmente, código {$codigoBase}. Confira autenticidade em ";
                    $footerUrl = $assinaturaDigitalPublic;
                    $prefixLen = $pdf->GetStringWidth($footerPrefix);
                    $urlLen = $pdf->GetStringWidth($footerUrl);
                    $textLen = $prefixLen + $urlLen;
                    $xBase = 5.0;
                    $yCenter = $size['height'] / 2;
                    // Com rotação de 90°, o texto se estende para cima a partir do ponto de ancoragem.
                    // Por isso, ancoramos em centro + metade do comprimento para centralizar no eixo vertical.
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

                if ($i === $pageCount) {
                    // Bloco unico no estilo original: QR a esquerda, dados a direita.
                    // Mantemos o conjunto centralizado e com folga suficiente para nao
                    // encostar na assinatura institucional do contrato.
                    $qrSize = 18.0;
                    $gap = 3.0;
                    $textWidth = 98.0;
                    $groupWidth = $qrSize + $gap + $textWidth;
                    $groupHeight = 19.0;

                    $lineYInstitucional = max(40.0, $size['height'] - 78.0);
                    $groupX = max(8.0, ($size['width'] - $groupWidth) / 2);
                    $groupY = max(12.0, $lineYInstitucional - $groupHeight - 8.0);

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
                    $pdf->Cell($textWidth, 5, "Assinatura digital de:", 0, 0, 'L');

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
                    $pdf->Cell($groupWidth, 4, "Código para validação: " . $codigoBase, 0, 0, 'L');
                }
            }

            $pathFinalPDF = $pathFinais . $nomeArquivoFinal;
            $pdf->Output($pathFinalPDF, 'F');

            if (is_file($pathOriginalPDF)) {
                @unlink($pathOriginalPDF);
            }

            if (is_file($qrFile)) {
                @unlink($qrFile);
            }

            $sql = $pdo->prepare("
                INSERT INTO tbpdf_assinado
                    (IdColaborador, NomeOriginal, NomeDocumento, NomeArquivo, AssinaturaBase64, TimestampAssinatura, Xpos, Ypos, urlValidacao)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $sql->execute([
                $idColaborador,
                'contrato:' . $nomeDocumento,
                $nomeDocumento,
                $nomeArquivoFinal,
                $codigoBase,
                $timestampAssinatura,
                $realX,
                $realY,
                $linkValidacao,
            ]);

            return [
                'ok' => true,
                'path' => $pathFinalPDF,
                'nomeArquivo' => $nomeArquivoFinal,
                'codigo' => $codigoBase,
                'validacao' => $linkValidacao,
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'erro' => $e->getMessage(),
            ];
        }
    }
}

