<?php

/**
 * Monta e envia o ZIP dos arquivos fisicos para a migracao.
 * Sem layout: so gera e faz o download (mesmo padrao de app/views/backup/backup.php).
 *
 * Estrutura do ZIP (NAO alterar - o importador da plataforma nova depende dela):
 *   img/...      = conteudo de app/assets/img     (fotos, photos, avatars, notas, logos)
 *   storage/...  = conteudo de app/storage        (assinatura/assinados, lotes, originais)
 *
 * Aceita ?parte=img ou ?parte=storage para baixar em pedacos (evita tempo limite
 * em pacotes grandes). Sem parametro, gera o pacote completo.
 */

$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = (string)($runtime['base_para_path'] ?? '');
$BASE_para_URL = (string)($runtime['base_para_url'] ?? '');

if ($BASE_para_PATH === '') {
    http_response_code(500);
    exit('Runtime indisponível.');
}

// Exige apenas estar logado (o checa-token.php valida "pagina permitida", que
// barraria esta rota nova por nao estar em PaginasPermitidas).
if (!isset($_SESSION['Cod'])) {
    header('Location: ' . rtrim((string) $BASE_para_URL, '/') . '/login/');
    exit();
}

@set_time_limit(0);
@ini_set('memory_limit', '1024M');
@ignore_user_abort(true);

if (!class_exists('ZipArchive')) {
    http_response_code(500);
    exit('ZipArchive indisponível no servidor.');
}

require_once __DIR__ . '/_origens.php';

// Cada prefixo pode vir de mais de uma pasta (storage na raiz ou dentro de app).
$origensDisponiveis = exportacaoOrigens($BASE_para_PATH);

$parte = strtolower(trim((string) ($_GET['parte'] ?? '')));
if ($parte !== '' && !isset($origensDisponiveis[$parte])) {
    http_response_code(400);
    exit('Parte inválida. Use ?parte=img ou ?parte=storage.');
}

$pastas = $parte === '' ? $origensDisponiveis : [$parte => $origensDisponiveis[$parte]];

$tmp = tempnam(sys_get_temp_dir(), 'legexp');
if ($tmp === false) {
    http_response_code(500);
    exit('Não foi possível criar o arquivo temporário.');
}
$tmpZip = $tmp . '.zip';
@unlink($tmp);

$zip = new ZipArchive();
if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    exit('Falha ao criar o ZIP.');
}

$total = 0;
foreach ($pastas as $prefixo => $origens) {
    exportacaoVarrer($prefixo, $origens, function (string $abs, string $noZip) use ($zip, &$total) {
        $zip->addFile($abs, $noZip);
        $total++;
    });
}

if ($total === 0) {
    $zip->close();
    @unlink($tmpZip);
    http_response_code(404);
    exit('Nenhum arquivo encontrado para exportar.');
}

// close() faz a gravacao de verdade: se falhar (disco cheio), o ZIP sai corrompido.
if (!$zip->close()) {
    @unlink($tmpZip);
    http_response_code(500);
    exit('Falha ao finalizar o ZIP (verifique o espaço em disco do servidor).');
}

if (!is_file($tmpZip) || filesize($tmpZip) === 0) {
    @unlink($tmpZip);
    http_response_code(500);
    exit('ZIP gerado vazio.');
}

$sufixo = $parte === '' ? 'completo' : $parte;
$nomeArquivo = 'legado-arquivos-' . $sufixo . '-' . date('Ymd-His') . '.zip';

// Limpa qualquer buffer antes de enviar: sem isso o PHP carrega o ZIP inteiro na memoria.
while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
header('Content-Length: ' . filesize($tmpZip));
header('Cache-Control: no-store');
header('X-Accel-Buffering: no');

readfile($tmpZip);
@unlink($tmpZip);
exit;
