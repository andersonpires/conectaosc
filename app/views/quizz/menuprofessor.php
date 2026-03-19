<nav id="sidebar" class="sidebar js-sidebar">
    <div class="sidebar-content js-simplebar">
        <a class="sidebar-brand" href="#">
            <center>
                <image src="imagens/logo.png" class="align-middle" style="width: 90%;">
            </center>
        </a>
        <ul class="sidebar-nav">
            <li class="sidebar-header">
                Eventos
            </li>

            <li class="sidebar-item" id="quizz_avaliacao">
                <a class="sidebar-link" href="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/quizz/avaliacao-professor/">
                    <i class="align-middle" data-feather="user-plus"></i> <span class="align-middle">Questionario</span>
                </a>
            </li>
            <li class="sidebar-item" id="<?php echo htmlspecialchars((string)$gp); ?>">
                <a class="sidebar-link" href="<?php echo htmlspecialchars((string)$gp); ?>">
                    <i class="align-middle" data-feather="align-left"></i> <span class="align-middle">Resultados</span>
                </a>
            </li>

            <li class="sidebar-item" id="quizz_sucesso">
                <a class="sidebar-link" href="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/quizz/avaliacao-professor/sucesso/">
                    <i class="align-middle" data-feather="user-plus"></i> <span class="align-middle">Questionario salvo</span>
                </a>
            </li>
        </ul>
    </div>
</nav>
<?php
$requestPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$base = rtrim((string)$BASE_para_URL, '/');
if ($base !== '' && str_starts_with($requestPath, $base)) {
    $requestPath = substr($requestPath, strlen($base));
}
$requestPath = '/' . trim($requestPath, '/');
$activeId = '';
if ($requestPath === '/quizz/avaliacao-professor') {
    $activeId = 'quizz_avaliacao';
} elseif ($requestPath === '/quizz/avaliacao-professor/sucesso') {
    $activeId = 'quizz_sucesso';
}
echo '<script>
    var elemento = document.getElementById("' . $activeId . '");
    if (elemento) {
        elemento.classList.add("active");
    }
</script>';
?>


