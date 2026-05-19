<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap/runtime.php';

$basePath = __DIR__;
$conexaoPath = $basePath . '/api/conectabd/conexao.php';
if (!is_file($conexaoPath)) {
    $conexaoPath = $basePath . '/conectabd/conexao.php';
}
require $conexaoPath;

$arquivos = [
    'clinica/zz_Suporte/migrations/008_add_licenca_administrativa_tbuser.sql',
    'clinica/zz_Suporte/migrations/009_tipos_consulta_crud_seed.sql',
    'clinica/zz_Suporte/migrations/010_create_tb_anamnese_roteiro.sql',
    'clinica/zz_Suporte/migrations/011_create_tb_evolucao_clinica.sql',
];

foreach ($arquivos as $arquivoRelativo) {
    $arquivo = $basePath . '/' . $arquivoRelativo;
    if (!is_file($arquivo)) {
        echo "[ERRO] Arquivo nao encontrado: {$arquivoRelativo}\n";
        continue;
    }

    $sql = trim((string) file_get_contents($arquivo));
    if ($sql === '') {
        echo "[IGNORADO] Arquivo vazio: {$arquivoRelativo}\n";
        continue;
    }

    try {
        $pdo->exec($sql);
        echo "[OK] {$arquivoRelativo}\n";
    } catch (Throwable $e) {
        echo "[ERRO] {$arquivoRelativo}: {$e->getMessage()}\n";
    }
}
