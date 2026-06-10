<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];

require_once $BASE_para_PATH . '/api/conectabd/conexao.php';
require_once __DIR__ . '/funcoes.php';

bootstrap_apply_php_runtime();

function chamadaAbortarDownloadFotos(int $statusCode, string $message): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($statusCode);
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
    exit;
}

function chamadaNomeArquivoUnico(string $nomeArquivo, array &$usados): string
{
    $base = pathinfo($nomeArquivo, PATHINFO_FILENAME);
    $ext = '.jpg';
    $candidato = $base . $ext;
    $contador = 2;

    while (isset($usados[strtolower($candidato)])) {
        $candidato = $base . '_' . $contador . $ext;
        $contador++;
    }

    $usados[strtolower($candidato)] = true;
    return $candidato;
}

function chamadaNomeZipFotos(?string $dataSelecionada): string
{
    $data = chamadaCriarDataBase($dataSelecionada);
    $sufixo = $data ? $data->format('Ymd') : date('Ymd');
    return 'fotos_chamada_' . $sufixo . '.zip';
}

if (!isset($_SESSION['Cod'])) {
    chamadaAbortarDownloadFotos(401, 'Sessão expirada. Faça login novamente.');
}

if (!class_exists('ZipArchive')) {
    chamadaAbortarDownloadFotos(500, 'A extensão ZIP do PHP não está habilitada no servidor.');
}

$idCurso = (int)($_GET['NNomeCurso'] ?? 0);
$idTurma = (int)($_GET['NNomeTurma'] ?? 0);
$dataSelecionada = (string)($_GET['dataSelecionada'] ?? '');

if ($idCurso <= 0 || $idTurma <= 0) {
    chamadaAbortarDownloadFotos(400, 'Selecione curso e turma para baixar as fotos.');
}

$rows = buscarAlunosChamada($pdo, $dataSelecionada, $idCurso, $idTurma);
if ($rows === []) {
    chamadaAbortarDownloadFotos(404, 'Nenhum aluno encontrado para gerar o arquivo de fotos.');
}

$tempDir = rtrim((string)$BASE_para_PATH, '/\\') . DIRECTORY_SEPARATOR . 'temp';
if (!is_dir($tempDir) && !mkdir($tempDir, 0775, true) && !is_dir($tempDir)) {
    chamadaAbortarDownloadFotos(500, 'Não foi possível preparar a pasta temporária.');
}

$zipPath = $tempDir . DIRECTORY_SEPARATOR . 'chamada_fotos_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.zip';
$zip = new ZipArchive();

if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    chamadaAbortarDownloadFotos(500, 'Não foi possível criar o arquivo ZIP de fotos.');
}

$nomesUsados = [];
$adicionados = 0;

foreach ($rows as $row) {
    $fotoPath = chamadaResolverFotoAlunoPath((string)($row['Foto'] ?? ''));
    if ($fotoPath === '' || !is_file($fotoPath)) {
        continue;
    }

    $nomeAluno = trim((string)($row['Nome'] ?? 'Aluno'));
    $nomeZip = chamadaNomeArquivoUnico(chamadaNomeFotoParaZip($nomeAluno), $nomesUsados);

    if ($zip->addFile($fotoPath, $nomeZip)) {
        $adicionados++;
    }
}

$zip->close();

if ($adicionados !== count($rows)) {
    @unlink($zipPath);
    chamadaAbortarDownloadFotos(500, 'Não foi possível incluir todas as fotos no arquivo ZIP.');
}

$downloadName = chamadaNomeZipFotos($dataSelecionada);

if (function_exists('ini_set')) {
    ini_set('zlib.output_compression', 'Off');
    ini_set('output_buffering', 'Off');
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Accel-Buffering: no');

$handle = fopen($zipPath, 'rb');
if ($handle === false) {
    @unlink($zipPath);
    chamadaAbortarDownloadFotos(500, 'Não foi possível ler o arquivo ZIP gerado.');
}

while (!feof($handle)) {
    echo fread($handle, 1048576);
    flush();
}

fclose($handle);
@unlink($zipPath);
exit;
