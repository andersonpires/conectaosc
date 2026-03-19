<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_PATH = $runtime['base_path'];
$BASE_URL = $runtime['base_url'];
require_once $BASE_PATH . '/api/conectabd/conexao.php';
$config = $pdo->query("SELECT * FROM tbConfig LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$shortcutIcon = (string)($config['ShortcutIcon'] ?? 'icon-48x48.png');
$shortcutIcon = trim($shortcutIcon);
if ($shortcutIcon === '') {
    $shortcutIcon = 'icon-48x48.png';
}
$shortcutIcon = ltrim(str_replace('\\', '/', $shortcutIcon), '/');
$iconFullPath = $BASE_PATH . '/app/assets/img/icons/' . $shortcutIcon;
if (!is_file($iconFullPath)) {
    $shortcutIcon = basename($shortcutIcon);
    $iconFullPath = $BASE_PATH . '/app/assets/img/icons/' . $shortcutIcon;
}
if (!is_file($iconFullPath)) {
    $shortcutIcon = 'icon-48x48.png';
}
?>

<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

<meta name="description" content="<?php echo htmlspecialchars($config['MetaDescription'] ?? ''); ?>">
<meta name="author" content="<?php echo htmlspecialchars($config['MetaAuthor'] ?? ''); ?>">
<meta name="keywords" content="<?php echo htmlspecialchars($config['MetaKeywords'] ?? ''); ?>">

<link rel="preconnect" href="https://fonts.gstatic.com">
<link rel="shortcut icon" href="<?php echo $BASE_URL; ?>/assets/img/icons/<?php echo htmlspecialchars($shortcutIcon); ?>" />

<link rel="canonical" href="https://www.iteva.com.br/conectaOSC" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-eOJMYsd53ii+scO/bJGFsiCZc+5NDVN2yr8+0RDqr0Ql0h+rP48ckxlpbzKgwra6" crossorigin="anonymous" />
<link href="//cdn.datatables.net/1.10.15/css/jquery.dataTables.min.css" rel="stylesheet">

<title><?php echo htmlspecialchars($config['TituloPagina']); ?></title>

<link href="<?php echo $BASE_URL; ?>/assets/css/app.css" rel="stylesheet">
<link href="<?php echo $BASE_URL; ?>/assets/css/override.php?versao=<?php echo time(); ?>" rel="stylesheet">

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>

