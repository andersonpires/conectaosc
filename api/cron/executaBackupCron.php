<?php
declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

$projectRoot = dirname(__DIR__, 2);
require $projectRoot . '/api/conectabd/conexao.php';

$logsDir = __DIR__ . '/logs';
if (!is_dir($logsDir)) {
    @mkdir($logsDir, 0777, true);
}

function ensureBackupLogTable(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS tbBackupLog (
            IdLog INT(11) NOT NULL AUTO_INCREMENT,
            DataHora DATETIME NOT NULL,
            Tipo VARCHAR(30) NOT NULL DEFAULT 'backup',
            Etapa VARCHAR(80) NOT NULL,
            Status VARCHAR(20) NOT NULL,
            Mensagem TEXT NOT NULL,
            PRIMARY KEY (IdLog),
            KEY idx_backup_log_datahora (DataHora)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"
    );
}

function logBackupCron(string $mensagem, ?PDO $pdo = null, string $etapa = 'cron', string $status = 'info'): void
{
    $arquivo = __DIR__ . '/logs/backup_debug.log';
    $data = date('Y-m-d H:i:s');
    file_put_contents($arquivo, '[' . $data . '] ' . $mensagem . PHP_EOL, FILE_APPEND);

    if ($pdo instanceof PDO) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO tbBackupLog (DataHora, Tipo, Etapa, Status, Mensagem) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$data, 'backup', $etapa, $status, $mensagem]);
        } catch (Throwable $e) {
            file_put_contents(
                $arquivo,
                '[' . $data . '] ERRO ao gravar tbBackupLog: ' . $e->getMessage() . PHP_EOL,
                FILE_APPEND
            );
        }
    }
}

function resolveFotoSourceDir(string $projectRoot): string
{
    $candidates = [
        $projectRoot . '/app/assets/img/fotos',
        $projectRoot . '/assets/img/fotos',
    ];

    foreach ($candidates as $candidate) {
        if (is_dir($candidate)) {
            return str_replace('\\', '/', $candidate);
        }
    }

    return '';
}

ensureBackupLogTable($pdo);
logBackupCron('Cron iniciado.', $pdo);

$sql = $pdo->query('SELECT * FROM tbbackupconfig LIMIT 1');
$config = $sql ? $sql->fetch(PDO::FETCH_ASSOC) : false;

if (!$config) {
    logBackupCron('ERRO: Nao encontrou registro na tabela tbbackupconfig.', $pdo, 'configuracao', 'erro');
    exit;
}

logBackupCron(
    'Config carregada: intervalo=' . ($config['IntervaloHoras'] ?? '') . 'h, email=' . ($config['EmailDestino'] ?? ''),
    $pdo,
    'configuracao'
);

$intervalo = (int) ($config['IntervaloHoras'] ?? 0);
$email = trim((string) ($config['EmailDestino'] ?? ''));
$ultimo = (string) ($config['UltimoBackup'] ?? '');

$podeRodar = false;
if ($ultimo === '') {
    logBackupCron('Nunca rodou antes, executando primeiro backup.', $pdo, 'agendamento');
    $podeRodar = true;
} else {
    $diffHoras = (time() - strtotime($ultimo)) / 3600;
    logBackupCron('Diferenca de horas desde ultimo backup: ' . $diffHoras, $pdo, 'agendamento');
    if ($diffHoras >= $intervalo) {
        logBackupCron('Tempo suficiente, vai rodar backup.', $pdo, 'agendamento');
        $podeRodar = true;
    } else {
        logBackupCron('Ainda nao atingiu o intervalo necessario.', $pdo, 'agendamento');
    }
}

if (!$podeRodar) {
    exit;
}

$storageBackupsDir = $projectRoot . '/storage/backups';
$sqlDir = $storageBackupsDir . '/sql';
$fotosBackupDir = $storageBackupsDir . '/fotos';

foreach ([$storageBackupsDir, $sqlDir, $fotosBackupDir] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

$dataHora = date('Y-m-d_H-i-s');
$arquivoBackup = str_replace('\\', '/', $sqlDir . '/backup_' . $dataHora . '.sql');

$dbHost = (string) ($_ENV['DB_HOST'] ?? '127.0.0.1');
$dbPort = (string) ($_ENV['DB_PORT'] ?? '3306');
$dbName = (string) ($_ENV['DB_NAME'] ?? '');
$dbUser = (string) ($_ENV['DB_USER'] ?? '');
$dbPass = (string) ($_ENV['DB_PASS'] ?? '');

$mysqldumpCandidates = [
    (string) ($_ENV['MYSQLDUMP_PATH'] ?? ''),
    'D:/xampp/mysql/bin/mysqldump.exe',
    'C:/xampp/mysql/bin/mysqldump.exe',
    'mysqldump',
];

$mysqldump = '';
foreach ($mysqldumpCandidates as $candidate) {
    $candidate = trim($candidate);
    if ($candidate === '') {
        continue;
    }
    if ($candidate === 'mysqldump' || is_file($candidate)) {
        $mysqldump = $candidate;
        break;
    }
}
if ($mysqldump === '') {
    $mysqldump = 'mysqldump';
}

$q = static fn(string $valor): string => '"' . str_replace('"', '\"', $valor) . '"';
$comando = sprintf(
    '%s --host=%s --port=%s --user=%s --password=%s --single-transaction --quick --skip-lock-tables %s --result-file=%s 2>&1',
    $q($mysqldump),
    $q($dbHost),
    $q($dbPort),
    $q($dbUser),
    $q($dbPass),
    $q($dbName),
    $q($arquivoBackup)
);

logBackupCron('Executando comando mysqldump.', $pdo, 'banco');
$saidaDump = shell_exec($comando);

if (!is_file($arquivoBackup) || filesize($arquivoBackup) === 0) {
    @unlink($arquivoBackup);
    logBackupCron('ERRO: Arquivo de backup nao foi gerado. ' . trim((string) $saidaDump), $pdo, 'banco', 'erro');
    exit;
}

logBackupCron('Arquivo de backup gerado com sucesso.', $pdo, 'banco', 'ok');

if ($email !== '') {
    $assunto = 'Backup Automatico - ' . $dataHora;
    $mensagem = 'Segue em anexo o backup automatico do sistema ConectaOSC.';
    $boundary = md5((string) time());

    $headers = "From: Sistema ConectaOSC <conectaosc@iteva.org.br>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= 'Content-Type: multipart/mixed; boundary="' . $boundary . "\"\r\n";

    $corpo = '--' . $boundary . "\r\n";
    $corpo .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $corpo .= $mensagem . "\r\n\r\n";
    $corpo .= '--' . $boundary . "\r\n";
    $corpo .= "Content-Type: application/octet-stream; name=\"backup.sql\"\r\n";
    $corpo .= "Content-Disposition: attachment; filename=\"backup.sql\"\r\n";
    $corpo .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $corpo .= chunk_split(base64_encode((string) file_get_contents($arquivoBackup))) . "\r\n";
    $corpo .= '--' . $boundary . '--';

    logBackupCron('Tentando enviar e-mail para ' . $email . '...', $pdo, 'email');
    $enviado = mail($email, $assunto, $corpo, $headers);
    logBackupCron(
        $enviado ? 'E-mail enviado com sucesso.' : 'ERRO: Falha ao enviar e-mail.',
        $pdo,
        'email',
        $enviado ? 'ok' : 'erro'
    );
}

@unlink($arquivoBackup);
logBackupCron('Arquivo temporario do banco apagado.', $pdo, 'banco');

logBackupCron('Iniciando backup das fotos...', $pdo, 'fotos');
$diretorioFotos = resolveFotoSourceDir($projectRoot);
if ($diretorioFotos === '') {
    logBackupCron(
        'Nenhuma pasta local de fotos encontrada em conecta/app/assets/img/fotos ou conecta/assets/img/fotos. Backup de fotos ignorado.',
        $pdo,
        'fotos'
    );
} else {
    $arquivoZip = str_replace('\\', '/', $fotosBackupDir . '/fotos_backup_' . $dataHora . '.zip');
    $zip = new ZipArchive();
    if ($zip->open($arquivoZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        $contador = 0;
        $arquivos = scandir($diretorioFotos) ?: [];
        foreach ($arquivos as $arquivo) {
            if ($arquivo === '.' || $arquivo === '..') {
                continue;
            }
            $caminhoFoto = $diretorioFotos . '/' . $arquivo;
            if (is_file($caminhoFoto)) {
                $zip->addFile($caminhoFoto, $arquivo);
                $contador++;
            }
        }
        $zip->close();
        logBackupCron(
            'Backup das fotos ZIP concluido (' . $contador . ' arquivos). Arquivo salvo em: ' . $arquivoZip,
            $pdo,
            'fotos',
            'ok'
        );
    } else {
        logBackupCron('ERRO: Nao foi possivel criar o arquivo ZIP de fotos.', $pdo, 'fotos', 'erro');
    }
}

logBackupCron('Iniciando limpeza de ZIPs antigos...', $pdo, 'limpeza');
$limiteDias = 15;
$agora = time();
$segundosLimite = $limiteDias * 24 * 60 * 60;
$contadorRemovidos = 0;

$arquivosZip = scandir($fotosBackupDir) ?: [];
foreach ($arquivosZip as $arquivoZipItem) {
    if ($arquivoZipItem === '.' || $arquivoZipItem === '..') {
        continue;
    }
    $caminhoCompleto = $fotosBackupDir . '/' . $arquivoZipItem;
    if (is_file($caminhoCompleto) && strtolower((string) pathinfo($caminhoCompleto, PATHINFO_EXTENSION)) === 'zip') {
        $idadeSegundos = $agora - (int) filemtime($caminhoCompleto);
        if ($idadeSegundos >= $segundosLimite) {
            @unlink($caminhoCompleto);
            $contadorRemovidos++;
            logBackupCron('Removido ZIP antigo: ' . $arquivoZipItem, $pdo, 'limpeza', 'ok');
        }
    }
}

logBackupCron('Limpeza concluida. Total removido: ' . $contadorRemovidos . ' ZIPs antigos.', $pdo, 'limpeza', 'ok');

$stmt = $pdo->prepare('UPDATE tbbackupconfig SET UltimoBackup = NOW() WHERE IdConfig = ?');
$stmt->execute([(int) $config['IdConfig']]);

logBackupCron('Timestamp atualizado. Processo finalizado.', $pdo, 'finalizacao', 'ok');
