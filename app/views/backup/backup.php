<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];

require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
require_once $BASE_para_PATH . '/api/conectabd/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

if (!function_exists('backupSqlIdentifier')) {
    function backupSqlIdentifier(string $value): string
    {
        return '`' . str_replace('`', '``', $value) . '`';
    }
}

if (!function_exists('backupSqlLiteral')) {
    function backupSqlLiteral(PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }
        return $pdo->quote((string)$value);
    }
}

if (!function_exists('backupGenerateViaPdo')) {
    function backupGenerateViaPdo(PDO $pdo, string $dbName, string $arquivoBackup): void
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
                    if (isset($row[0]) && (string)$row[0] !== '') {
                        $tables[] = (string)$row[0];
                    }
                }
            }

            foreach ($tables as $table) {
                $tableId = backupSqlIdentifier($table);
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
                        $colunas[] = backupSqlIdentifier((string)$coluna);
                        $valores[] = backupSqlLiteral($pdo, $valor);
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
                    $viewName = isset($row[0]) ? (string)$row[0] : '';
                    if ($viewName === '') {
                        continue;
                    }
                    $viewId = backupSqlIdentifier($viewName);
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
}

if (!function_exists('backupIsLocalhostRequest')) {
    function backupIsLocalhostRequest(): bool
    {
        $rawHost = (string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '');
        $host = strtolower(trim($rawHost));
        if ($host === '') {
            return false;
        }

        if (($pos = strpos($host, ':')) !== false) {
            $host = substr($host, 0, $pos);
        }

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }
}

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

$isLocalhostRequest = backupIsLocalhostRequest();
$mysqldumpCandidates = $isLocalhostRequest
    ? [
        'D:/xampp/mysql/bin/mysqldump.exe',
        'C:/xampp/mysql/bin/mysqldump.exe',
        (string)($_ENV['MYSQLDUMP_PATH'] ?? ''),
        'mysqldump',
    ]
    : [
        (string)($_ENV['MYSQLDUMP_PATH'] ?? ''),
        'mysqldump',
    ];
$mysqldumpCandidates = array_values(array_unique(array_filter(array_map(
    static fn($item): string => trim((string)$item),
    $mysqldumpCandidates
))));

$q = static fn(string $v): string => '"' . str_replace('"', '\"', $v) . '"';
$saida = '';
$errosTentativas = [];

foreach ($mysqldumpCandidates as $mysqldump) {
    $isCommandOnly = strcasecmp($mysqldump, 'mysqldump') === 0;
    if (!$isCommandOnly && !is_file($mysqldump)) {
        continue;
    }

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

    $saida = (string)shell_exec($comando);
    if (is_file($arquivoBackup) && filesize($arquivoBackup) > 0) {
        break;
    }

    if (is_file($arquivoBackup)) {
        @unlink($arquivoBackup);
    }

    $erroLimpo = trim($saida);
    $errosTentativas[] = '[' . $mysqldump . '] ' . ($erroLimpo !== '' ? $erroLimpo : 'sem retorno de erro');
}

if (!is_file($arquivoBackup) || filesize($arquivoBackup) === 0) {
    try {
        backupGenerateViaPdo($pdo, (string)$dbName, $arquivoBackup);
        $errosTentativas[] = '[fallback-pdo] backup gerado com sucesso.';
    } catch (Throwable $e) {
        if (is_file($arquivoBackup)) {
            @unlink($arquivoBackup);
        }
        $errosTentativas[] = '[fallback-pdo] ' . $e->getMessage();
    }
}

if (!is_file($arquivoBackup) || filesize($arquivoBackup) === 0) {
    if (is_file($arquivoBackup)) {
        @unlink($arquivoBackup);
    }
    http_response_code(500);
    $detalhes = trim(implode(PHP_EOL, $errosTentativas));
    echo 'Erro ao gerar o backup.'
        . ($detalhes !== '' ? '<br><small>' . nl2br(htmlspecialchars($detalhes)) . '</small>' : '');
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
