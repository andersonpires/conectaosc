<?php
declare(strict_types=1);

$_reduzCronLogsDir = __DIR__ . '/logs';
if (!is_dir($_reduzCronLogsDir)) {
    @mkdir($_reduzCronLogsDir, 0777, true);
}
ini_set('error_log', $_reduzCronLogsDir . '/reduzfoto_errors.log');
unset($_reduzCronLogsDir);

require_once dirname(__DIR__) . '/conectabd/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

$tamanhoMinimo = 130 * 1024;

function resolvePastaFotosLocal(string $projectRoot): string
{
    $candidates = [
        $projectRoot . '/app/assets/img/fotos',
        $projectRoot . '/assets/img/fotos',
    ];

    foreach ($candidates as $candidate) {
        if (is_dir($candidate)) {
            return $candidate;
        }
    }

    return '';
}

function registraReducaoFoto(PDO $pdo, int $qtdEncontrada, float $economia, string $resultado): void
{
    $stmt = $pdo->prepare('INSERT INTO tbReduzfoto (DataHora, QtdEncontrada, Economia, Resultado) VALUES (?, ?, ?, ?)');
    $stmt->execute([date('Y-m-d H:i:s'), $qtdEncontrada, $economia, $resultado]);
}

function registraErroReducaoFoto(PDO $pdo, string $resultado): void
{
    $stmt = $pdo->prepare('INSERT INTO tbReduzfoto (DataHora, Resultado) VALUES (?, ?)');
    $stmt->execute([date('Y-m-d H:i:s'), $resultado]);
}

function redimensionarEConverterImagem(string $caminhoTemp, string $novoCaminho, int $larguraMax, int $alturaMax, int $qualidade): string|false
{
    $info = @getimagesize($caminhoTemp);
    if ($info === false) {
        return false;
    }

    [$larguraOriginal, $alturaOriginal, $tipoImagem] = $info;

    switch ($tipoImagem) {
        case IMAGETYPE_JPEG:
            $imagemOriginal = @imagecreatefromjpeg($caminhoTemp);
            break;
        case IMAGETYPE_PNG:
            $imagemOriginal = @imagecreatefrompng($caminhoTemp);
            break;
        case IMAGETYPE_GIF:
            $imagemOriginal = @imagecreatefromgif($caminhoTemp);
            break;
        default:
            return false;
    }

    if (!$imagemOriginal) {
        return false;
    }

    $ratio = min($larguraMax / $larguraOriginal, $alturaMax / $alturaOriginal, 1);
    $novaLargura = max(1, (int) round($larguraOriginal * $ratio));
    $novaAltura = max(1, (int) round($alturaOriginal * $ratio));

    $imagemRedimensionada = imagecreatetruecolor($novaLargura, $novaAltura);
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

    $novoCaminho = preg_replace('/\.[^.\s]{3,4}$/', '.jpg', $novoCaminho);
    $ok = imagejpeg($imagemRedimensionada, $novoCaminho, $qualidade);

    imagedestroy($imagemOriginal);
    imagedestroy($imagemRedimensionada);

    return $ok ? $novoCaminho : false;
}

try {
    $projectRoot = dirname(__DIR__, 2);
    $pasta = resolvePastaFotosLocal($projectRoot);

    if (!is_dir($pasta)) {
        registraReducaoFoto($pdo, 0, 0, 'Nenhuma pasta local de fotos encontrada em conecta/app/assets/img/fotos ou conecta/assets/img/fotos');
        return;
    }

    $arquivos = scandir($pasta) ?: [];
    $qtdEncontrada = 0;
    $economia = 0.0;

    foreach ($arquivos as $arquivo) {
        if ($arquivo === '.' || $arquivo === '..') {
            continue;
        }

        $caminhoArquivo = $pasta . DIRECTORY_SEPARATOR . $arquivo;
        if (!is_file($caminhoArquivo)) {
            continue;
        }

        $tamanhoAntes = (int) filesize($caminhoArquivo);
        if ($tamanhoAntes < $tamanhoMinimo) {
            continue;
        }

        $qtdEncontrada++;
        $novoCaminho = redimensionarEConverterImagem($caminhoArquivo, $caminhoArquivo, 800, 800, 75);

        if ($novoCaminho === false) {
            registraErroReducaoFoto($pdo, 'Falha ao redimensionar imagem: ' . $arquivo);
            continue;
        }

        $tamanhoDepois = (int) filesize($novoCaminho);
        $economia += ($tamanhoAntes - $tamanhoDepois) / 1024;

        $novoNome = basename($novoCaminho);
        if ($novoNome !== $arquivo && is_file($caminhoArquivo)) {
            @unlink($caminhoArquivo);
        }

        if ($novoNome !== $arquivo) {
            $stmtUpdateAluno = $pdo->prepare('UPDATE tbAluno SET Foto = ? WHERE Foto = ?');
            $stmtUpdateAluno->execute([$novoNome, $arquivo]);

            $stmtUpdateUser = $pdo->prepare('UPDATE tbUser SET Foto = ? WHERE Foto = ?');
            $stmtUpdateUser->execute([$novoNome, $arquivo]);
        }
    }

    if ($qtdEncontrada > 0) {
        registraReducaoFoto($pdo, $qtdEncontrada, $economia, 'Processamento concluido com sucesso');
    } else {
        registraReducaoFoto($pdo, 0, 0, 'Sem arquivos pesados');
    }
} catch (Throwable $e) {
    registraErroReducaoFoto($pdo, 'Ocorreu um erro: ' . $e->getMessage());
}
