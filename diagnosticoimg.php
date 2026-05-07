<?php
declare(strict_types=1);

$runtime  = require __DIR__ . '/bootstrap/runtime.php';
$basePath = rtrim((string)($runtime['base_para_path'] ?? __DIR__), '/\\');

$backup    = '/var/lib/conectaosc/img-backup';
$assetsImg = $basePath . '/app/assets/img';
$fotosDir  = $assetsImg . '/fotos';
$assetsUrl = rtrim((string)($runtime['assets_img_url'] ?? ''), '/');

$testarArquivo = '4abce797129374790bbb50594a52c971.jpeg';

function listar(string $dir, int $max = 8): array
{
    if (!is_dir($dir)) {
        return ['[diretório não existe]'];
    }
    $items = @scandir($dir) ?: [];
    $items = array_values(array_filter($items, fn($f) => !in_array($f, ['.', '..'])));
    $total = count($items);
    $preview = array_slice($items, 0, $max);
    if ($total > $max) {
        $preview[] = "... e mais " . ($total - $max) . " arquivo(s)";
    }
    return $preview ?: ['[vazio]'];
}

function check(string $path): string
{
    if (!file_exists($path)) return '❌ não existe';
    if (is_dir($path))  return '📁 diretório OK';
    return '✅ arquivo OK (' . number_format(filesize($path)) . ' bytes)';
}

?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Diagnóstico de imagens</title>
<style>
body{font-family:monospace;margin:24px;background:#f9fafb;color:#111}
h2{margin:20px 0 6px}
pre{background:#fff;border:1px solid #d1d5db;padding:12px;border-radius:6px;white-space:pre-wrap;word-break:break-all}
.ok{color:#065f46}.err{color:#991b1b}.warn{color:#92400e}
table{border-collapse:collapse;width:100%;max-width:900px}
td,th{border:1px solid #e5e7eb;padding:6px 10px;text-align:left;font-size:.9rem}
th{background:#f3f4f6}
</style>
</head>
<body>
<h1>Diagnóstico de imagens — <?php echo date('Y-m-d H:i:s'); ?></h1>

<h2>1. Caminhos calculados pelo runtime</h2>
<pre>basePath      = <?php echo htmlspecialchars($basePath) ?>
assetsImg dir = <?php echo htmlspecialchars($assetsImg) ?>
fotos dir     = <?php echo htmlspecialchars($fotosDir) ?>
assetsImgUrl  = <?php echo htmlspecialchars($assetsUrl) ?>
</pre>

<h2>2. Backup da imagem Docker <code><?php echo htmlspecialchars($backup) ?></code></h2>
<pre><?php
if (!is_dir($backup)) {
    echo '<span class="err">❌ Diretório de backup NÃO existe — o Dockerfile provavelmente não gerou o backup.</span>' . "\n";
    echo "Isso significa que as fotos não estão na imagem Docker construída a partir do GitHub.\n";
} else {
    $fotos = is_dir("$backup/fotos") ? count(array_diff(scandir("$backup/fotos"), ['.', '..'])) : 0;
    echo '<span class="ok">✅ Backup existe.</span>' . "\n";
    echo "Arquivos em $backup/fotos: $fotos\n";
    echo "Primeiros arquivos:\n";
    foreach (listar("$backup/fotos") as $f) {
        echo "  $f\n";
    }
}
?></pre>

<h2>3. Volume montado <code><?php echo htmlspecialchars($assetsImg) ?></code></h2>
<pre><?php
$subDirs = ['fotos', 'avatars', 'banners', 'icons', 'logos', 'sistema'];
foreach ($subDirs as $sub) {
    $path = "$assetsImg/$sub";
    if (is_dir($path)) {
        $count = count(array_diff(scandir($path), ['.', '..']));
        echo "<span class='ok'>✅ $sub/</span> — $count arquivo(s)\n";
    } else {
        echo "<span class='warn'>⚠️  $sub/ não existe</span>\n";
    }
}
?></pre>

<h2>4. Arquivo de teste: <code><?php echo htmlspecialchars($testarArquivo) ?></code></h2>
<pre><?php
$caminhos = [
    "Backup"  => "$backup/fotos/$testarArquivo",
    "Volume"  => "$fotosDir/$testarArquivo",
];
foreach ($caminhos as $label => $path) {
    echo "$label: " . check($path) . "\n    $path\n\n";
}
$urlGerada = $assetsUrl . '/fotos/' . rawurlencode($testarArquivo);
echo "URL gerada pelo PHP:\n    $urlGerada\n";
?></pre>

<?php
$httpHost  = (string)($_SERVER['HTTP_HOST'] ?? '');
$isHttps   = !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off';
$scheme    = $isHttps ? 'https' : 'http';
$testeHttp = $scheme . '://' . $httpHost . $assetsUrl . '/fotos/' . rawurlencode($testarArquivo);
?>
<h2>5. Teste HTTP ao vivo</h2>
<p>Clique para testar se a URL resolve corretamente:</p>
<p><a href="<?php echo htmlspecialchars($testeHttp) ?>" target="_blank"><?php echo htmlspecialchars($testeHttp) ?></a></p>
<img src="<?php echo htmlspecialchars($testeHttp) ?>" width="80" height="80" style="border:1px solid #ccc;border-radius:6px"
     onerror="this.style.outline='3px solid red';this.title='ERRO — imagem não carregou'"
     onload="this.style.outline='3px solid green';this.title='OK — imagem carregou'">

<h2>6. Entrypoint / verificação de permissões</h2>
<pre><?php
$owner = function_exists('posix_getpwuid') && function_exists('posix_stat')
    ? (posix_getpwuid(posix_stat($fotosDir)['uid'])['name'] ?? 'desconhecido')
    : 'N/A (posix não disponível)';
$writable = is_writable($fotosDir) ? '<span class="ok">✅ gravável</span>' : '<span class="err">❌ sem permissão de escrita</span>';
echo "fotos dir gravável: $writable\n";
echo "owner (uid): $owner\n";
?></pre>

</body>
</html>
