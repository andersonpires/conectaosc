<?php
header('Content-Type: text/plain; charset=utf-8');

echo "Teste de ZipArchive\n";
echo "===================\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n";
echo "PHP SAPI: " . PHP_SAPI . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";

echo "\nExtens�o zip carregada: " . (extension_loaded('zip') ? 'SIM' : 'N�O') . "\n";
echo "Classe ZipArchive dispon�vel: " . (class_exists('ZipArchive') ? 'SIM' : 'N�O') . "\n";

if (class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    $tmpDir = sys_get_temp_dir();
    $testZip = rtrim($tmpDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'zip_test_' . uniqid('', true) . '.zip';

    $result = $zip->open($testZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    if ($result === true) {
        $zip->addFromString('teste.txt', "Zip funcionando em " . date('c'));
        $zip->close();

        if (is_file($testZip)) {
            echo "Teste de cria��o de ZIP: OK\n";
            echo "Arquivo teste: {$testZip}\n";
            @unlink($testZip);
        } else {
            echo "Teste de cria��o de ZIP: FALHOU (arquivo n�o foi criado)\n";
        }
    } else {
        echo "Teste de cria��o de ZIP: FALHOU (c�digo open: {$result})\n";
    }
}

echo "\nUsu�rio do processo: " . (function_exists('get_current_user') ? get_current_user() : 'N/D') . "\n";
if (function_exists('posix_geteuid')) {
    echo "EUID: " . posix_geteuid() . "\n";
}
