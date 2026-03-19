<?php
// Inclui a conexão com o banco de dados
require_once __DIR__ . '/../conectabd/conexao.php';

// Configura o fuso horário para o Brasil
date_default_timezone_set('America/Sao_Paulo');

// Caminho da pasta que deseja verificar (relativo ao diretório deste script)
$pasta = __DIR__ . "/../assets/img/fotos";

// Tamanho mínimo em bytes (130 KB = 130 * 1024)
$tamanhoMinimo = 130 * 1024;

// Função para redimensionar e converter imagens
function redimensionarEConverterImagem($caminhoTemp, $novoCaminho, $larguraMax, $alturaMax, $qualidade)
{
    list($larguraOriginal, $alturaOriginal, $tipoImagem) = getimagesize($caminhoTemp);

    switch ($tipoImagem) {
        case IMAGETYPE_JPEG:
            $imagemOriginal = imagecreatefromjpeg($caminhoTemp);
            break;
        case IMAGETYPE_PNG:
            $imagemOriginal = imagecreatefrompng($caminhoTemp);
            break;
        case IMAGETYPE_GIF:
            $imagemOriginal = imagecreatefromgif($caminhoTemp);
            break;
        default:
            return false; // Tipo não suportado
    }

    $ratio = min($larguraMax / $larguraOriginal, $alturaMax / $alturaOriginal);
    $novaLargura = $larguraOriginal * $ratio;
    $novaAltura = $alturaOriginal * $ratio;

    $imagemRedimensionada = imagecreatetruecolor($novaLargura, $novaAltura);

    // Preenche com branco (necessário para JPG, pois não suporta transparência)
    $fundoBranco = imagecolorallocate($imagemRedimensionada, 255, 255, 255);
    imagefill($imagemRedimensionada, 0, 0, $fundoBranco);

    imagecopyresampled(
        $imagemRedimensionada,
        $imagemOriginal,
        0,
        0,
        0,
        0,
        $novaLargura,
        $novaAltura,
        $larguraOriginal,
        $alturaOriginal
    );

    // Sempre salva como JPG
    $novoCaminho = preg_replace('/\.[^.\s]{3,4}$/', '.jpg', $novoCaminho); // Troca a extensão para .jpg
    if (imagejpeg($imagemRedimensionada, $novoCaminho, $qualidade)) {
        imagedestroy($imagemOriginal);
        imagedestroy($imagemRedimensionada);
        return $novoCaminho; // Retorna o novo caminho se bem-sucedido
    }

    imagedestroy($imagemOriginal);
    imagedestroy($imagemRedimensionada);
    return false;
}

try {
    // Verifica se a pasta existe
    if (!is_dir($pasta)) {
        throw new Exception("A pasta especificada não existe.");
    }

    // Abre a pasta
    $arquivos = scandir($pasta);

    $qtdEncontrada = 0;
    $economia = 0;

    foreach ($arquivos as $arquivo) {
        // Ignora "." e ".."
        if ($arquivo !== "." && $arquivo !== "..") {
            $caminhoArquivo = $pasta . DIRECTORY_SEPARATOR . $arquivo;

            // Verifica se é um arquivo e não uma pasta
            if (is_file($caminhoArquivo)) {
                $tamanhoAntes = filesize($caminhoArquivo); // Tamanho do arquivo antes

                // Verifica se o tamanho do arquivo é igual ou maior que 130 KB
                if ($tamanhoAntes >= $tamanhoMinimo) {
                    $qtdEncontrada++;

                    $novoCaminho = redimensionarEConverterImagem($caminhoArquivo, $caminhoArquivo, 800, 800, 75);

                    if ($novoCaminho) {
                        $tamanhoDepois = filesize($novoCaminho);
                        $economia += ($tamanhoAntes - $tamanhoDepois) / 1024; // Converte economia para KB

                        // Se a extensão original era PNG ou GIF, exclui o arquivo original e atualiza no banco
                        if (strtolower(pathinfo($arquivo, PATHINFO_EXTENSION)) === 'png' || strtolower(pathinfo($arquivo, PATHINFO_EXTENSION)) === 'gif') {
                            if (unlink($caminhoArquivo)) {
                                $novoNome = basename($novoCaminho);

                                // Atualiza na tabela tbAluno
                                $stmtUpdateAluno = $pdo->prepare("UPDATE tbAluno SET Foto = ? WHERE Foto = ?");
                                $stmtUpdateAluno->execute([$novoNome, $arquivo]);


                                // Se não encontrou na tabela tbAluno, tenta atualizar na tabela tbUser
                                if ($stmtUpdateAluno->rowCount() === 0) {
                                    $stmtUpdateUser = $pdo->prepare("UPDATE tbUser SET Foto = ? WHERE Foto = ?");
                                    $stmtUpdateUser->execute([$novoNome, $arquivo]);
                                }
                            }
                        }
                    } else {
                        $resultado = "Falha ao excluir o arquivo original ou redimensionar";
                        $stmtErro = $pdo->prepare("INSERT INTO tbReduzfoto (DataHora, Resultado) VALUES (?, ?)");
                        $datetime = date("Y-m-d H:i:s");
                        $erro = "Ocorreu um erro: " . $e->getMessage();
                        $stmtErro->execute([$datetime, $erro]);
                    }
                }
            }
        }
    }

    // Registra o resultado no banco
    $stmt = $pdo->prepare("INSERT INTO tbReduzfoto (DataHora, QtdEncontrada, Economia, Resultado) VALUES (?, ?, ?, ?)");
    $datetime = date("Y-m-d H:i:s");

    if ($qtdEncontrada > 0) {
        $resultado = "Processamento concluído com sucesso";
        $stmt->execute([$datetime, $qtdEncontrada, $economia, $resultado]);
    } else {
        $resultado = "Sem arquivos pesados";
        $qtdEncontrada = 0;
        $economia = 0;
        $stmt->execute([$datetime, $qtdEncontrada, $economia, $resultado]);
    }

} catch (Exception $e) {
    // Registra erros no banco
    $stmtErro = $pdo->prepare("INSERT INTO tbReduzfoto (DataHora, Resultado) VALUES (?, ?)");
    $datetime = date("Y-m-d H:i:s");
    $erro = "Ocorreu um erro: " . $e->getMessage();
    $stmtErro->execute([$datetime, $erro]);
}
