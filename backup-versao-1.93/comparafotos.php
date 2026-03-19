<?php

include __DIR__ . '/conectabd/conexao.php';

// Consulta ao banco de dados para obter os nomes das fotos
$sqlAluno = "SELECT Foto FROM tbAluno";
$sqlUser = "SELECT Foto FROM tbUser";

$stmtAluno = $pdo->query($sqlAluno);
$stmtUser = $pdo->query($sqlUser);

$fotosBancoAluno = $stmtAluno->fetchAll(PDO::FETCH_COLUMN);
$fotosBancoUser = $stmtUser->fetchAll(PDO::FETCH_COLUMN);

// Mesclar as duas listas
$fotosBanco = array_merge($fotosBancoAluno, $fotosBancoUser);
echo 'Fotos do banco de dados' . $fotosBanco . '<br>';

// Diretórios
$pastaDestino = __DIR__ . '/assets/img/fotos/';
echo 'Pasta destino' . $pastaDestino . '<br>';
$pastaOrigem = __DIR__ . '/../matricula/imagens/';
echo 'Pasta origem' . $pastaOrigem . '<br>';

// Verifica se a pasta de destino existe
if (!is_dir($pastaDestino)) {
    mkdir($pastaDestino, 0777, true);
}

// Lista os arquivos na pasta de destino
$fotosPasta = array_diff(scandir($pastaDestino), ['.', '..']);
echo 'Fotos Pasta' . $fotosPasta . '<br>';

// Verifica quais arquivos estão no banco, mas não na pasta
$fotosFaltantes = array_diff($fotosBanco, $fotosPasta);
echo 'Fotos faltantes' . $fotosFaltantes . '<br>';

// Copia os arquivos faltantes
foreach ($fotosFaltantes as $foto) {
    $origem = $pastaOrigem . $foto;
    $destino = $pastaDestino . $foto;
    if (file_exists($origem)) {
        copy($origem, $destino);
    }
}

// Revalida se todos os arquivos foram copiados
$fotosPastaAtualizado = array_diff(scandir($pastaDestino), ['.', '..']);
$fotosRestantes = array_diff($fotosBanco, $fotosPastaAtualizado);

if (empty($fotosRestantes)) {
    echo "Todos os arquivos foram copiados com sucesso.";
} else {
    echo "Os seguintes arquivos ainda estão faltando:";
    print_r($fotosRestantes);
}
