<?php
// Configurações do banco de dados
$servername = "localhost";
$username = "root";
$password = "F@ce1991!";
$dbname = "mwtech63_matricula";
$port = 3306;

// Diretório para salvar o backup
$backupDir = __DIR__ . "/backups/";

// Nome do arquivo de backup
$date = date("Y-m-d_H-i-s");
$backupFile = $backupDir . "backup_{$dbname}_{$date}.sql";

// Garantir que o diretório de backup exista
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Comando para realizar o backup
$mysqldump = "mysqldump --user={$username} --password='{$password}' --host={$servername} --port={$port} {$dbname} > {$backupFile}";

// Executar o comando
exec($mysqldump, $output, $returnVar);

if ($returnVar === 0) {
    echo "Backup gerado com sucesso: {$backupFile}\n";
} else {
    echo "Erro ao gerar o backup. Código de retorno: {$returnVar}\n";
    echo "Saída: " . implode("\n", $output) . "\n";
}
?>
