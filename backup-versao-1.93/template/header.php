<?php
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';
$config = $pdo->query("SELECT * FROM tbConfig LIMIT 1")->fetch(PDO::FETCH_ASSOC);
?>

<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

<meta name="description" content="<?php echo htmlspecialchars($config['MetaDescription'] ?? ''); ?>">
<meta name="author" content="<?php echo htmlspecialchars($config['MetaAuthor'] ?? ''); ?>">
<meta name="keywords" content="<?php echo htmlspecialchars($config['MetaKeywords'] ?? ''); ?>">

<link rel="preconnect" href="https://fonts.gstatic.com">
<link rel="shortcut icon" href="<?php echo $_SESSION['BASE_URL']; ?>/assets/img/icons/<?php echo $config['ShortcutIcon']; ?>" />

<link rel="canonical" href="https://www.iteva.com.br/conectaOSC" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-eOJMYsd53ii+scO/bJGFsiCZc+5NDVN2yr8+0RDqr0Ql0h+rP48ckxlpbzKgwra6" crossorigin="anonymous" />
<link href="//cdn.datatables.net/1.10.15/css/jquery.dataTables.min.css" rel="stylesheet">

<title><?php echo htmlspecialchars($config['TituloPagina']); ?></title>

<link href="<?php echo $_SESSION['BASE_URL']; ?>/assets/css/app.css" rel="stylesheet">
<link href="<?php echo $_SESSION['BASE_URL']; ?>/assets/css/override.php?versao=<?php echo time(); ?>" rel="stylesheet">

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>
