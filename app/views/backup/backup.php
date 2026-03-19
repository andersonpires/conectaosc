<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];

require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
require_once $BASE_para_PATH . '/api/conectabd/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

$dataHora = date('Y-m-d_H-i-s');
$arquivoBackup = $BASE_para_PATH . '/temp/backup_' . $dataHora . '.sql';
$arquivoBackup = str_replace('\\', '/', $arquivoBackup);

$dbHost = $_ENV['DB_HOST'] ?? '127.0.0.1';
$dbPort = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_NAME'] ?? '';
$dbUser = $_ENV['DB_USER'] ?? '';
$dbPass = $_ENV['DB_PASS'] ?? '';

if ($dbName === '' || $dbUser === '') {
    http_response_code(500);
    echo 'Configuracao de banco incompleta para gerar backup.';
    exit;
}

$mysqldumpCandidates = [
    $_ENV['MYSQLDUMP_PATH'] ?? '',
    'D:/xampp/mysql/bin/mysqldump.exe',
    'C:/xampp/mysql/bin/mysqldump.exe',
    'mysqldump',
];
$mysqldump = '';
foreach ($mysqldumpCandidates as $candidate) {
    $candidate = trim((string)$candidate);
    if ($candidate === '') {
        continue;
    }
    if ($candidate === 'mysqldump') {
        $mysqldump = $candidate;
        break;
    }
    if (is_file($candidate)) {
        $mysqldump = $candidate;
        break;
    }
}
if ($mysqldump === '') {
    $mysqldump = 'mysqldump';
}

$q = static fn(string $v): string => '"' . str_replace('"', '\"', $v) . '"';
$comando = sprintf(
    '%s --host=%s --port=%s --user=%s --password=%s --single-transaction --quick --skip-lock-tables %s --result-file=%s 2>&1',
    $q($mysqldump),
    $q((string)$dbHost),
    $q((string)$dbPort),
    $q((string)$dbUser),
    $q((string)$dbPass),
    $q((string)$dbName),
    $q($arquivoBackup)
);

$saida = shell_exec($comando);

if (!is_file($arquivoBackup) || filesize($arquivoBackup) === 0) {
    if (is_file($arquivoBackup)) {
        @unlink($arquivoBackup);
    }
    http_response_code(500);
    echo 'Erro ao gerar o backup.' . ($saida ? '<br><small>' . htmlspecialchars((string)$saida) . '</small>' : '');
    exit;
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($arquivoBackup) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($arquivoBackup));

while (ob_get_level() > 0) {
    ob_end_clean();
}
flush();
readfile($arquivoBackup);
@unlink($arquivoBackup);
exit;
