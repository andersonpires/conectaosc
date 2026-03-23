<?php
date_default_timezone_set('America/Sao_Paulo');

$runtime = require dirname(__DIR__, 2) . '/bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];

require_once $BASE_para_PATH . '/api/conectabd/conexao.php';

function cronContratoLog(string $msg): void
{
    $arquivo = __DIR__ . '/executaCronContrato.log';
    $data = date('Y-m-d H:i:s');
    @file_put_contents($arquivo, "[{$data}] {$msg}\n", FILE_APPEND);
}

function cronContratoMailLog(string $msg): void
{
    $arquivo = __DIR__ . '/executaCronContrato_mail.log';
    $data = date('Y-m-d H:i:s');
    @file_put_contents($arquivo, "[{$data}] {$msg}\n", FILE_APPEND);
}

function cronContratoBasePathSegment(string $baseUrl, string $basePath): string
{
    $slug = '/' . trim(basename($basePath), '/');
    $candidate = trim(str_replace('\\', '/', $baseUrl));

    if (
        $candidate === '' ||
        preg_match('#^[a-zA-Z]:/#', $candidate) ||
        str_contains(strtolower($candidate), '/htdocs/') ||
        str_starts_with($candidate, '/home/') ||
        str_starts_with($candidate, '/home1/') ||
        str_starts_with($candidate, '/var/')
    ) {
        return $slug;
    }

    if ($candidate !== '' && preg_match('#^https?://#i', $candidate)) {
        $path = (string)(parse_url($candidate, PHP_URL_PATH) ?? '');
        if ($path !== '') {
            return '/' . trim($path, '/');
        }
    }

    if ($candidate !== '' && str_starts_with($candidate, '/')) {
        return '/' . trim($candidate, '/');
    }

    return $slug;
}

function cronContratoDomain(): string
{
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($host !== '' && str_contains($host, '/')) {
        $host = (string) strtok($host, '/');
    }
    $host = preg_replace('/:\d+$/', '', $host);

    if ($host !== '' && !str_contains($host, 'localhost') && !str_starts_with($host, '127.0.0.1')) {
        return 'https://' . $host;
    }

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        return 'http://localhost';
    }

    $publicOrigin = bootstrap_env('APP_PUBLIC_ORIGIN', '');
    if ($publicOrigin === '') {
        $publicBaseUrl = bootstrap_env('APP_PUBLIC_BASE_URL', '');
        if ($publicBaseUrl !== '') {
            $baseParts = parse_url($publicBaseUrl);
            $baseScheme = (string)($baseParts['scheme'] ?? '');
            $baseHost = (string)($baseParts['host'] ?? '');
            $basePort = isset($baseParts['port']) ? (int)$baseParts['port'] : 0;
            if ($baseScheme !== '' && $baseHost !== '') {
                $publicOrigin = $baseScheme . '://' . $baseHost . ($basePort > 0 ? ':' . $basePort : '');
            }
        }
    }

    if ($publicOrigin !== '' && !preg_match('#^https?://#i', $publicOrigin)) {
        $publicOrigin = 'https://' . ltrim($publicOrigin, '/');
    }
    $publicOrigin = rtrim($publicOrigin, '/');

    return $publicOrigin !== '' ? $publicOrigin : 'http://localhost';
}

function cronContratoPublicBase(string $baseUrl, string $basePath): string
{
    $domain = cronContratoDomain();
    $pathSegment = cronContratoBasePathSegment($baseUrl, $basePath);
    return rtrim($domain, '/') . rtrim($pathSegment, '/');
}

function cronContratoAssinaturaDigitalUrl(string $baseUrl, string $basePath): string
{
    return cronContratoPublicBase($baseUrl, $basePath) . '/assinaturadigital';
}

function cronContratoSanitizeFileName(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return 'beneficiario';
    }

    $value = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $value);
    $value = preg_replace('/_+/', '_', $value);
    $value = trim((string)$value, '_');

    return $value === '' ? 'beneficiario' : $value;
}

function cronContratoEnviarEmail(string $destino, string $assunto, string $linkDownload, string $baseUrl, string $basePath): bool
{
    $from = 'no-reply@iteva.org.br';
    $logoUrl = cronContratoPublicBase($baseUrl, $basePath) . '/assets/img/logos/LogoImpressao.jpg';

    $message = "
<html>
<head>
  <title>Notificação de Download</title>
  <style>
    .button {
      background-color: #007bff;
      border: none;
      color: white;
      padding: 15px 32px;
      text-align: center;
      text-decoration: none;
      display: inline-block;
      font-size: 16px;
      margin: 4px 2px;
      cursor: pointer;
      border-radius: 8px;
    }
  </style>
</head>
<body style='font-family: Arial, sans-serif;'>
  <p><img src='" . htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') . "' alt='ConectaOSC' style='max-height:70px;'></p>
  <p>Olá,</p>
  <p>Clique no botão abaixo para baixar o seu arquivo:</p>
  <a href='" . htmlspecialchars($linkDownload, ENT_QUOTES, 'UTF-8') . "' class='button' style='color: #ffffff;' target='_blank' rel='noopener noreferrer'>Download do .zip</a>
</body>
</html>
";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . $from . "\r\n";
    $headers .= 'Reply-To: ' . $from . "\r\n";
    $headers .= 'X-Mailer: PHP/' . phpversion();

    cronContratoMailLog('Tentando envio | para=' . $destino . ' | assunto=' . $assunto . ' | link=' . $linkDownload);
    $ok = @mail($destino, $assunto, $message, $headers);
    if ($ok) {
        cronContratoMailLog('Resultado envio | para=' . $destino . ' | status=OK');
        return true;
    }

    $mailErro = error_get_last();
    $mailMsg = is_array($mailErro) ? (string)($mailErro['message'] ?? 'sem detalhe') : 'sem detalhe';
    cronContratoMailLog('Resultado envio | para=' . $destino . ' | status=FALHA | erro=' . $mailMsg);
    return false;
}

cronContratoLog('Cron de contratos iniciado.');

try {
    // Fast path: apenas verifica existencia de job pendente e encerra cedo.
    $temDemanda = $pdo->query("\n        SELECT 1\n          FROM tb_Cron_Contrato\n         WHERE Status = 1\n           AND TipoReferencia IN ('CURSO', 'TURMA')\n         LIMIT 1\n    ")->fetchColumn();

    if (!$temDemanda) {
        cronContratoLog('Nenhum job pendente encontrado.');
        exit;
    }

    // Carrega dependencias pesadas apenas quando ha trabalho real.
    require_once $BASE_para_PATH . '/api/lib/tcpdf/tcpdf.php';
    require_once $BASE_para_PATH . '/api/lib/tcpdf/fpdi/autoload.php';
    require_once $BASE_para_PATH . '/app/views/matricula/contratoAssinaturaDigital.php';
    require_once $BASE_para_PATH . '/app/views/matricula/contratoLoteHelper.php';

    $pathLotes = rtrim($BASE_para_PATH, '/\\') . '/app/storage/assinatura/lotes/';
    if (!is_dir($pathLotes)) {
        @mkdir($pathLotes, 0775, true);
    }

    $basePublic = cronContratoPublicBase((string)$BASE_para_URL, (string)$BASE_para_PATH);
    $_SERVER['HTTP_HOST'] = (string)parse_url($basePublic, PHP_URL_HOST);
    $_SERVER['HTTPS'] = str_starts_with($basePublic, 'https://') ? 'on' : 'off';

    $jobs = $pdo->query("\n        SELECT *\n          FROM tb_Cron_Contrato\n         WHERE Status = 1\n           AND TipoReferencia IN ('CURSO', 'TURMA')\n      ORDER BY IdCronContrato ASC\n         LIMIT 5\n    ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

    if (empty($jobs)) {
        cronContratoLog('Nenhum job pendente encontrado.');
        exit;
    }

    $configAssinatura = loteContratoCarregarConfig($pdo, $BASE_para_PATH);

    foreach ($jobs as $job) {
        $idJob = (int)$job['IdCronContrato'];
        $tipo = strtoupper(trim((string)($job['TipoReferencia'] ?? 'CURSO')));
        $idCurso = (int)($job['IdCurso'] ?? 0);
        $idTurma = (int)($job['IdTurma'] ?? 0);

        if ($tipo === 'CURSO' && $idCurso <= 0) {
            $pdo->prepare('UPDATE tb_Cron_Contrato SET Status = 9, MensagemErro = ?, DataAtualizacao = NOW() WHERE IdCronContrato = ?')
                ->execute(['IdCurso inválido no job.', $idJob]);
            continue;
        }

        if ($tipo === 'TURMA' && $idTurma <= 0) {
            $pdo->prepare('UPDATE tb_Cron_Contrato SET Status = 9, MensagemErro = ?, DataAtualizacao = NOW() WHERE IdCronContrato = ?')
                ->execute(['IdTurma inválido no job.', $idJob]);
            continue;
        }

        $lockStmt = $pdo->prepare('UPDATE tb_Cron_Contrato SET Status = 2, DataAtualizacao = NOW() WHERE IdCronContrato = ? AND Status = 1');
        $lockStmt->execute([$idJob]);
        if ($lockStmt->rowCount() === 0) {
            continue;
        }

        cronContratoLog("Job {$idJob}: processando {$tipo}.");

        try {
            if ($tipo === 'CURSO') {
                $stmtLista = $pdo->prepare("\n                    SELECT a.*, c.NomeCurso, t.NomeTurma, m.IdMatricula, m.vData, p.LogoProjeto, p.TermosContrato\n                      FROM tbMatricula m\n                      JOIN (\n                            SELECT MAX(IdMatricula) AS IdMatricula\n                              FROM tbMatricula\n                             WHERE IdCurso = ?\n                               AND Habilitado = 1\n                             GROUP BY IdUsuario\n                           ) ult ON ult.IdMatricula = m.IdMatricula\n                      JOIN tbAluno a ON m.IdUsuario = a.IdUsuario\n                      JOIN tbCurso c ON m.IdCurso = c.IdCurso\n                      JOIN tbTurma t ON m.IdTurma = t.IdTurma\n                 LEFT JOIN tbProjeto p ON c.IdProjeto = p.IdProjeto\n                     WHERE c.IdCurso = ?\n                       AND c.Habilitado = 1\n                       AND t.Habilitado = 1\n                       AND a.Habilitado = 1\n                       AND m.Habilitado = 1\n                  ORDER BY t.NomeTurma, a.Nome\n                ");
                $stmtLista->execute([$idCurso, $idCurso]);
            } else {
                $stmtLista = $pdo->prepare("\n                    SELECT a.*, c.NomeCurso, t.NomeTurma, m.IdMatricula, m.vData, p.LogoProjeto, p.TermosContrato\n                      FROM tbMatricula m\n                      JOIN tbAluno a ON m.IdUsuario = a.IdUsuario\n                      JOIN tbCurso c ON m.IdCurso = c.IdCurso\n                      JOIN tbTurma t ON m.IdTurma = t.IdTurma\n                 LEFT JOIN tbProjeto p ON c.IdProjeto = p.IdProjeto\n                     WHERE m.IdTurma = ?\n                       AND m.Habilitado = 1\n                       AND a.Habilitado = 1\n                  ORDER BY a.Nome\n                ");
                $stmtLista->execute([$idTurma]);
            }

            $lista = $stmtLista->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $total = count($lista);
            if ($total === 0) {
                throw new RuntimeException('Nenhum aluno ativo encontrado para o lote solicitado.');
            }

            $pdo->prepare('UPDATE tb_Cron_Contrato SET TotalRegistros = ?, Processados = 0, DataAtualizacao = NOW() WHERE IdCronContrato = ?')
                ->execute([$total, $idJob]);

            $prefixoZip = $tipo === 'CURSO' ? ('contratos_curso_' . $idCurso) : ('contratos_turma_' . $idTurma);
            $zipName = $prefixoZip . '_' . date('Ymd_His') . '_' . $idJob . '.zip';
            $zipPath = $pathLotes . $zipName;

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Não foi possível criar o arquivo ZIP final.');
            }

            $processados = 0;
            $idSolicitante = (int)($job['IdColaboradorSolicitante'] ?? 0);
            $idColaboradorNome = $idSolicitante > 0 ? $idSolicitante : 0;
            $nomeSolicitante = '';

            if ($idSolicitante > 0) {
                $stNome = $pdo->prepare('SELECT Nome, Sobrenome FROM tbUser WHERE IdColaborador = ? LIMIT 1');
                $stNome->execute([$idSolicitante]);
                $rowNome = $stNome->fetch(PDO::FETCH_ASSOC) ?: [];
                $nomeSolicitante = trim((string)(($rowNome['Nome'] ?? '') . ' ' . ($rowNome['Sobrenome'] ?? '')));
            }

            foreach ($lista as $dados) {
                $resultado = loteContratoGerarContratoAssinadoPorMatricula(
                    $pdo,
                    $BASE_para_PATH,
                    $BASE_para_URL,
                    $dados,
                    $configAssinatura,
                    $idSolicitante,
                    $nomeSolicitante
                );

                if (empty($resultado['ok'])) {
                    cronContratoLog('Job ' . $idJob . ': falha matrícula ' . (int)($dados['IdMatricula'] ?? 0) . ' - ' . (string)($resultado['erro'] ?? 'erro desconhecido'));
                    continue;
                }

                $pathPdf = (string)($resultado['path'] ?? '');
                if (!is_file($pathPdf)) {
                    continue;
                }

                $nomeAluno = cronContratoSanitizeFileName((string)($dados['Nome'] ?? 'beneficiario'));
                $nomeTurma = cronContratoSanitizeFileName((string)($dados['NomeTurma'] ?? 'turma'));
                $entryName = $nomeTurma . '__' . $nomeAluno . '__matricula_' . (int)($dados['IdMatricula'] ?? 0) . '_' . $idColaboradorNome . '.pdf';

                $conteudoPdf = @file_get_contents($pathPdf);
                if ($conteudoPdf === false || $conteudoPdf === '') {
                    cronContratoLog('Job ' . $idJob . ': falha ao ler PDF para ZIP - ' . $pathPdf);
                    continue;
                }

                $okAdd = $zip->addFromString($entryName, $conteudoPdf);
                if ($okAdd !== true) {
                    cronContratoLog('Job ' . $idJob . ': ZipArchive::addFromString falhou para ' . $entryName);
                    continue;
                }
                $processados++;

                $pdo->prepare('UPDATE tb_Cron_Contrato SET Processados = ?, DataAtualizacao = NOW() WHERE IdCronContrato = ?')
                    ->execute([$processados, $idJob]);
            }

            $oldHandler = set_error_handler(static function (int $severity, string $message): bool {
                throw new RuntimeException($message);
            });
            try {
                $closeResult = $zip->close();
                if ($closeResult !== true) {
                    throw new RuntimeException('Falha ao finalizar o arquivo ZIP.');
                }
            } finally {
                restore_error_handler();
            }

            if ($processados === 0) {
                throw new RuntimeException('Nenhum contrato foi gerado com sucesso para compactação.');
            }

            $urlZip = rtrim((string)$basePublic, '/') . '/app/storage/assinatura/lotes/' . rawurlencode($zipName);

            $upd = $pdo->prepare("\n                UPDATE tb_Cron_Contrato\n                   SET Status = 0,\n                       NomeZip = ?,\n                       CaminhoZip = ?,\n                       UrlZip = ?,\n                       Processados = ?,\n                       TotalRegistros = ?,\n                       MensagemErro = NULL,\n                       DataAtualizacao = NOW()\n                 WHERE IdCronContrato = ?\n            ");
            $upd->execute([$zipName, $zipPath, $urlZip, $processados, $total, $idJob]);

            $nomeCurso = trim((string)($job['NomeCurso'] ?? 'Curso'));
            $nomeTurmaRef = trim((string)($job['NomeTurma'] ?? ($tipo === 'CURSO' ? 'Todas as turmas' : 'Turma')));
            $subject = 'Envio de contratos do curso/turma ' . $nomeCurso . ' - ' . $nomeTurmaRef;

            $emailDestino = trim((string)($job['EmailDestino'] ?? ''));
            if ($emailDestino !== '') {
                $okEmail = cronContratoEnviarEmail($emailDestino, $subject, $urlZip, (string)$BASE_para_URL, (string)$BASE_para_PATH);
                if ($okEmail) {
                    cronContratoLog('Job ' . $idJob . ': envio de e-mail para ' . $emailDestino . ' => OK');
                } else {
                    $mailErro = error_get_last();
                    $mailMsg = is_array($mailErro) ? (string)($mailErro['message'] ?? 'sem detalhe') : 'sem detalhe';
                    cronContratoLog('Job ' . $idJob . ': envio de e-mail para ' . $emailDestino . ' => FALHA (' . $mailMsg . ')');
                }
            }

            cronContratoLog("Job {$idJob}: concluído com sucesso ({$processados}/{$total}).");
        } catch (Throwable $e) {
            cronContratoLog('Job ' . $idJob . ': erro - ' . $e->getMessage());
            $pdo->prepare('UPDATE tb_Cron_Contrato SET Status = 9, MensagemErro = ?, DataAtualizacao = NOW() WHERE IdCronContrato = ?')
                ->execute([$e->getMessage(), $idJob]);
        }
    }
} catch (Throwable $e) {
    cronContratoLog('Erro geral do cron: ' . $e->getMessage());
}

cronContratoLog('Cron de contratos finalizado.');
