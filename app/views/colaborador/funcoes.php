<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];

function redimensionarImagem($caminhoTemp, $novoCaminho, $larguraMax, $alturaMax, $qualidade)
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

    switch ($tipoImagem) {
        case IMAGETYPE_JPEG:
            imagejpeg($imagemRedimensionada, $novoCaminho, $qualidade);
            break;
        case IMAGETYPE_PNG:
            imagepng($imagemRedimensionada, $novoCaminho, 9 - ($qualidade / 10));
            break;
        case IMAGETYPE_GIF:
            imagegif($imagemRedimensionada, $novoCaminho);
            break;
    }

    imagedestroy($imagemOriginal);
    imagedestroy($imagemRedimensionada);

    return true;
}

function enviarEmail($nomeCad, $emailCad, $mensagem)
{
    $para = $emailCad;
    $assunto = "Cadastro no Sistema ITEVA de Gestão de OSC";
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: no-reply@iteva.org.br' . "\r\n";
    $headers .= 'Cc: cadastro@iteva.org.br' . "\r\n";

    if (@mail($para, $assunto, $mensagem, $headers)) {
        $emailEnviado = "Ok";
    } else {
        $emailEnviado = "Erro";
    }
}
?>
