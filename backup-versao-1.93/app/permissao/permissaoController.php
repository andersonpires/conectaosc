<?php
session_set_cookie_params(['httponly' => true]);
ini_set('session.gc_maxlifetime', 86400);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?redirect=$redirect_url");
    exit();
}

require_once $_SESSION['BASE_PATH'] . '/checa-token.php';
require_once 'permissaoModel.php';

$view = $_GET['view'] ?? 'list';

if ($view === 'form') {
    $paginas = PermissaoModel::listarPaginas();
    $grupos = [];
    foreach ($paginas as $pagina) {
        $tipoApp = $pagina['TipoApp'] ?: 'Outros';
        $grupos[$tipoApp][] = $pagina;
    }
    ksort($grupos);
    if (isset($grupos['Outros'])) {
        $outros = $grupos['Outros'];
        unset($grupos['Outros']);
        $grupos['Outros'] = $outros;
    }

    require 'formPermissaoView.php';
    exit;
}

require 'listPermissaoView.php';
exit;
