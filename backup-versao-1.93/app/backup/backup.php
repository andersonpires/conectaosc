<?php
session_set_cookie_params(['httponly' => true]);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?redirect=$redirect_url");
    exit();
}

require_once $_SESSION['BASE_PATH'] . '/checa-token.php';

// Definir a data e hora no formato desejado
$dataHora = date('Y-m-d_H-i-s');
$arquivoBackup = "backup_{$dataHora}.sql";

// Montar o comando mysqldump
$comando = "mysqldump -u mwtech63_admin_matricula -pIteva@100 mwtech63_matricula > {$arquivoBackup}";

// Executar o comando
shell_exec($comando);

// Verificar se o arquivo foi gerado
if (file_exists($arquivoBackup)) {
    // Definir headers para download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($arquivoBackup) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($arquivoBackup));

    // Limpar o buffer de saída
    ob_clean();
    flush();

    // Ler o arquivo
    readfile($arquivoBackup);

    // Excluir o arquivo depois do download
    unlink($arquivoBackup);

    exit;
} else {
    echo "Erro ao gerar o backup.";
}
