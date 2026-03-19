<?php
date_default_timezone_set('America/Sao_Paulo');
require dirname(__DIR__, 2) . '/conectabd/conexao.php';

function logBackup($msg) {
    $arquivo = __DIR__ . '/backup_debug.log';
    $data = date('Y-m-d H:i:s');
    file_put_contents($arquivo, "[$data] $msg\n", FILE_APPEND);
}

logBackup("Cron iniciado.");

// Busca configurações
$sql = $pdo->query("SELECT * FROM tbbackupconfig LIMIT 1");
$config = $sql->fetch(PDO::FETCH_ASSOC);

if (!$config) {
    logBackup("ERRO: Não encontrou registro na tabela tbbackupconfig.");
    exit;
}

logBackup("Config carregada: intervalo={$config['IntervaloHoras']}h, email={$config['EmailDestino']}");

// Verifica intervalo
$intervalo = $config['IntervaloHoras'];
$email = $config['EmailDestino'];
$ultimo = $config['UltimoBackup'];

$podeRodar = false;

if (!$ultimo) {
    logBackup("Nunca rodou antes, executando primeiro backup.");
    $podeRodar = true;
} else {
    $diffHoras = (time() - strtotime($ultimo)) / 3600;
    logBackup("Diferença de horas desde último backup: {$diffHoras}");

    if ($diffHoras >= $intervalo) {
        logBackup("Tempo suficiente, vai rodar backup.");
        $podeRodar = true;
    } else {
        logBackup("Ainda não atingiu o intervalo necessário.");
    }
}

if (!$podeRodar) exit;

// Geração do arquivo
$dataHora = date('Y-m-d_H-i-s');
$arquivoBackup = __DIR__ . "/backup_{$dataHora}.sql";

$comando = "mysqldump -u mwtech63_admin_matricula -pIteva@100 mwtech63_matricula > {$arquivoBackup}";
logBackup("Executando comando mysqldump: $comando");

shell_exec($comando);

if (!file_exists($arquivoBackup)) {
    logBackup("ERRO: Arquivo de backup NÃO foi gerado.");
    exit;
}

logBackup("Arquivo de backup gerado com sucesso.");

// Envio do email
$assunto = "Backup Automático - {$dataHora}";
$mensagem = "Segue em anexo o backup automático do sistema ConectaOSC.";
$boundary = md5(time());

$headers = "From: Sistema ConectaOSC <conectaosc@iteva.org.br>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

$corpo  = "--{$boundary}\r\n";
$corpo .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
$corpo .= "{$mensagem}\r\n\r\n";

$conteudo = chunk_split(base64_encode(file_get_contents($arquivoBackup)));

$corpo .= "--{$boundary}\r\n";
$corpo .= "Content-Type: application/octet-stream; name=\"backup.sql\"\r\n";
$corpo .= "Content-Disposition: attachment; filename=\"backup.sql\"\r\n";
$corpo .= "Content-Transfer-Encoding: base64\r\n\r\n";
$corpo .= "{$conteudo}\r\n";
$corpo .= "--{$boundary}--";

logBackup("Tentando enviar e-mail para {$email}...");

$enviado = mail($email, $assunto, $corpo, $headers);

if ($enviado) {
    logBackup("E-mail enviado com sucesso!");
} else {
    logBackup("ERRO: Falha ao enviar e-mail (mail() retornou FALSE).");
}

// Apagar arquivo
unlink($arquivoBackup);
logBackup("Arquivo temporário apagado.");

// ===============================================
// BACKUP DAS FOTOS EM ZIP
// ===============================================
logBackup("Iniciando backup das fotos...");

// Pasta onde estão as fotos
$diretorioFotos = dirname(__DIR__, 2) . "/assets/img/fotos";

// Pasta onde o ZIP será salvo
$destinoBackupFotos = __DIR__ . "/fotos";

if (!is_dir($destinoBackupFotos)) {
    mkdir($destinoBackupFotos, 0777, true);
    logBackup("Diretório de backup de fotos criado: {$destinoBackupFotos}");
}

// Nome do arquivo ZIP
$arquivoZip = $destinoBackupFotos . "/fotos_backup_{$dataHora}.zip";

// Cria o ZIP
$zip = new ZipArchive();
if ($zip->open($arquivoZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {

    // Adiciona todos os arquivos da pasta de fotos
    $arquivos = scandir($diretorioFotos);
    $contador = 0;

    foreach ($arquivos as $arquivo) {
        if ($arquivo != "." && $arquivo != "..") {
            $caminhoFoto = $diretorioFotos . "/" . $arquivo;
            if (is_file($caminhoFoto)) {
                $zip->addFile($caminhoFoto, $arquivo);
                $contador++;
            }
        }
    }

    $zip->close();
    logBackup("Backup das fotos ZIP concluído ({$contador} arquivos). Arquivo salvo em: {$arquivoZip}");

} else {
    logBackup("ERRO: Não foi possível criar o arquivo ZIP de fotos.");
}

// ===============================================
// LIMPEZA AUTOMÁTICA DE ZIPs ANTIGOS (15 DIAS)
// ===============================================
logBackup("Iniciando limpeza de ZIPs antigos...");

$limiteDias = 15;
$segundosLimite = $limiteDias * 24 * 60 * 60;

$agora = time();

$contadorRemovidos = 0;

$arquivosZip = scandir($destinoBackupFotos);

foreach ($arquivosZip as $arquivoZipItem) {
    if ($arquivoZipItem != "." && $arquivoZipItem != "..") {

        $caminhoCompleto = $destinoBackupFotos . "/" . $arquivoZipItem;

        // Só apaga arquivos .zip
        if (is_file($caminhoCompleto) && pathinfo($caminhoCompleto, PATHINFO_EXTENSION) === "zip") {

            $modificado = filemtime($caminhoCompleto);
            $idadeSegundos = $agora - $modificado;

            if ($idadeSegundos >= $segundosLimite) {
                unlink($caminhoCompleto);
                $contadorRemovidos++;
                logBackup("Removido ZIP antigo: {$arquivoZipItem}");
            }
        }
    }
}

logBackup("Limpeza concluída. Total removido: {$contadorRemovidos} ZIPs antigos.");


// Atualizar timestamp
$stmt = $pdo->prepare("UPDATE tbbackupconfig SET UltimoBackup = NOW() WHERE IdConfig = ?");
$stmt->execute([$config['IdConfig']]);

logBackup("Timestamp atualizado. Processo finalizado.\n\n");
?>
