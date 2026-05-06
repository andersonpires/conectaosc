<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
$ASSETS_IMG_PATH = rtrim((string)($runtime['assets_img_path'] ?? ($BASE_para_PATH . '/app/assets/img')), '/\\');
$ASSETS_IMG_URL = rtrim((string)($runtime['assets_img_url'] ?? ($BASE_para_URL . '/assets/img')), '/');
require_once $BASE_para_PATH . '/api/conectabd/conexao.php';
$config = $pdo->query("SELECT * FROM tbConfig LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$shortcutIcon = (string)($config['ShortcutIcon'] ?? 'icon-48x48.png');
$shortcutIcon = trim($shortcutIcon);
if ($shortcutIcon === '') {
    $shortcutIcon = 'icon-48x48.png';
}
$shortcutIcon = ltrim(str_replace('\\', '/', $shortcutIcon), '/');
$iconFullPath = $ASSETS_IMG_PATH . '/icons/' . $shortcutIcon;
if (!is_file($iconFullPath)) {
    $shortcutIcon = basename($shortcutIcon);
    $iconFullPath = $ASSETS_IMG_PATH . '/icons/' . $shortcutIcon;
}
if (!is_file($iconFullPath)) {
    $shortcutIcon = 'icon-48x48.png';
}

$canonicalUrl = rtrim((string)$BASE_para_URL, '/');
if (!preg_match('#^https?://#i', $canonicalUrl)) {
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    if ($host !== '') {
        $canonicalUrl = ($isHttps ? 'https' : 'http') . '://' . $host . $canonicalUrl;
    }
}
?>

<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

<meta name="description" content="<?php echo htmlspecialchars($config['MetaDescription'] ?? ''); ?>">
<meta name="author" content="<?php echo htmlspecialchars($config['MetaAuthor'] ?? ''); ?>">
<meta name="keywords" content="<?php echo htmlspecialchars($config['MetaKeywords'] ?? ''); ?>">

<link rel="preconnect" href="https://fonts.gstatic.com">
<link rel="icon" type="image/png" href="<?php echo htmlspecialchars($ASSETS_IMG_URL, ENT_QUOTES, 'UTF-8'); ?>/icons/<?php echo htmlspecialchars($shortcutIcon, ENT_QUOTES, 'UTF-8'); ?>">
<link rel="shortcut icon" href="<?php echo htmlspecialchars($ASSETS_IMG_URL, ENT_QUOTES, 'UTF-8'); ?>/icons/<?php echo htmlspecialchars($shortcutIcon, ENT_QUOTES, 'UTF-8'); ?>" />

<link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-eOJMYsd53ii+scO/bJGFsiCZc+5NDVN2yr8+0RDqr0Ql0h+rP48ckxlpbzKgwra6" crossorigin="anonymous" />
<link href="//cdn.datatables.net/1.10.15/css/jquery.dataTables.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

<title><?php echo htmlspecialchars($config['TituloPagina']); ?></title>

<link href="<?php echo $BASE_para_URL; ?>/assets/css/app.css" rel="stylesheet">
<link href="<?php echo $BASE_para_URL; ?>/assets/css/override.php?versao=<?php echo time(); ?>" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>
<script>
(function () {
    var AVATAR = <?php echo json_encode(bootstrap_avatar_data_uri(), JSON_UNESCAPED_SLASHES); ?>;
    document.addEventListener('error', function (e) {
        var el = e.target;
        if (el.tagName === 'IMG' && el.getAttribute('src') && el.src !== AVATAR) {
            el.onerror = null;
            el.src = AVATAR;
        }
    }, true);
}());
</script>

