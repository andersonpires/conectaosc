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

function backupCronSqlIdentifier(string $value): string
{
    return '`' . str_replace('`', '``', $value) . '`';
}

function backupCronSqlLiteral(PDO $pdo, mixed $value): string
{
    if ($value === null) {
        return 'NULL';
    }
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }
    return $pdo->quote((string) $value);
}

function backupCronGenerateViaPdo(PDO $pdo, string $dbName, string $arquivoBackup): void
{
    $fp = @fopen($arquivoBackup, 'wb');
    if ($fp === false) {
        throw new RuntimeException('Nao foi possivel abrir o arquivo de backup para escrita.');
    }

    try {
        $header = '-- Backup gerado via PDO em ' . date('Y-m-d H:i:s') . PHP_EOL
            . '-- Banco: ' . $dbName . PHP_EOL . PHP_EOL
            . 'SET NAMES utf8mb4;' . PHP_EOL
            . 'SET FOREIGN_KEY_CHECKS=0;' . PHP_EOL . PHP_EOL;
        fwrite($fp, $header);

        $tablesStmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        if ($tablesStmt === false) {
            $tablesStmt = $pdo->query('SHOW TABLES');
        }

        $tables = [];
        if ($tablesStmt instanceof PDOStatement) {
            while (($row = $tablesStmt->fetch(PDO::FETCH_NUM)) !== false) {
                if (isset($row[0]) && (string) $row[0] !== '') {
                    $tables[] = (string) $row[0];
                }
            }
        }

        foreach ($tables as $table) {
            $tableId = backupCronSqlIdentifier($table);
            $createStmt = $pdo->query('SHOW CREATE TABLE ' . $tableId);
            $createRow = $createStmt ? $createStmt->fetch(PDO::FETCH_NUM) : false;
            if (!is_array($createRow) || !isset($createRow[1])) {
                throw new RuntimeException('Nao foi possivel ler estrutura da tabela: ' . $table);
            }

            fwrite($fp, 'DROP TABLE IF EXISTS ' . $tableId . ';' . PHP_EOL);
            fwrite($fp, $createRow[1] . ';' . PHP_EOL . PHP_EOL);

            $dataStmt = $pdo->query('SELECT * FROM ' . $tableId);
            if (!($dataStmt instanceof PDOStatement)) {
                continue;
            }

            while (($row = $dataStmt->fetch(PDO::FETCH_ASSOC)) !== false) {
                $colunas = [];
                $valores = [];
                foreach ($row as $coluna => $valor) {
                    $colunas[] = backupCronSqlIdentifier((string) $coluna);
                    $valores[] = backupCronSqlLiteral($pdo, $valor);
                }
                fwrite(
                    $fp,
                    'INSERT INTO ' . $tableId . ' (' . implode(', ', $colunas) . ') VALUES (' . implode(', ', $valores) . ');' . PHP_EOL
                );
            }

            fwrite($fp, PHP_EOL);
        }

        $viewsStmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
        if ($viewsStmt instanceof PDOStatement) {
            while (($row = $viewsStmt->fetch(PDO::FETCH_NUM)) !== false) {
                $viewName = isset($row[0]) ? (string) $row[0] : '';
                if ($viewName === '') {
                    continue;
                }
                $viewId = backupCronSqlIdentifier($viewName);
                $createViewStmt = $pdo->query('SHOW CREATE VIEW ' . $viewId);
                $createViewRow = $createViewStmt ? $createViewStmt->fetch(PDO::FETCH_NUM) : false;
                if (!is_array($createViewRow) || !isset($createViewRow[1])) {
                    continue;
                }

                fwrite($fp, 'DROP VIEW IF EXISTS ' . $viewId . ';' . PHP_EOL);
                fwrite($fp, $createViewRow[1] . ';' . PHP_EOL . PHP_EOL);
            }
        }

        fwrite($fp, 'SET FOREIGN_KEY_CHECKS=1;' . PHP_EOL);
    } finally {
        fclose($fp);
    }
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
    'mysqldump',
    'D:/xampp/mysql/bin/mysqldump.exe',
    'C:/xampp/mysql/bin/mysqldump.exe',
];
$mysqldumpCandidates = array_values(array_unique(array_filter(array_map(
    static fn($item): string => trim((string) $item),
    $mysqldumpCandidates
))));

$q = static fn(string $valor): string => '"' . str_replace('"', '\"', $valor) . '"';
$saidaDump = '';
$errosTentativasDump = [];

foreach ($mysqldumpCandidates as $mysqldump) {
    $isCommandOnly = strcasecmp($mysqldump, 'mysqldump') === 0;
    if (!$isCommandOnly && !is_file($mysqldump)) {
        continue;
    }

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

    logBackupCron('Executando mysqldump com: ' . $mysqldump, $pdo, 'banco');
    $saidaDump = (string) shell_exec($comando);
    if (is_file($arquivoBackup) && filesize($arquivoBackup) > 0) {
        break;
    }

    if (is_file($arquivoBackup)) {
        @unlink($arquivoBackup);
    }

    $erroLimpo = trim($saidaDump);
    $errosTentativasDump[] = '[' . $mysqldump . '] ' . ($erroLimpo !== '' ? $erroLimpo : 'sem retorno de erro');
}

if (!is_file($arquivoBackup) || filesize($arquivoBackup) === 0) {
    try {
        logBackupCron('Fallback ativado: gerando backup via PDO.', $pdo, 'banco');
        backupCronGenerateViaPdo($pdo, $dbName, $arquivoBackup);
        logBackupCron('Backup via PDO gerado com sucesso.', $pdo, 'banco', 'ok');
    } catch (Throwable $e) {
        if (is_file($arquivoBackup)) {
            @unlink($arquivoBackup);
        }
        $errosTentativasDump[] = '[fallback-pdo] ' . $e->getMessage();
    }
}

if (!is_file($arquivoBackup) || filesize($arquivoBackup) === 0) {
    @unlink($arquivoBackup);
    $detalhes = trim(implode(' | ', $errosTentativasDump));
    logBackupCron('ERRO: Arquivo de backup nao foi gerado. ' . $detalhes, $pdo, 'banco', 'erro');
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
