<?php
session_set_cookie_params(['httponly' => true]);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['BASE_para_PATH']) || !isset($_SESSION['BASE_para_URL'])) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?redirect=$redirect_url");
    exit();
}

require_once $_SESSION['BASE_para_PATH'] . '/api/conectabd/conexao.php';

if (!isset($_POST['id']) || empty($_POST['id'])) {
    header("Location: formPaginas.php?erro=ID invÒ¡lido");
    exit();
}

$id = $_POST['id'];

try {
    $stmt = $pdo->prepare("DELETE FROM tbPaginas WHERE IdPagina = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount()) {
        header("Location: formPaginas.php?msg=PÒ¡gina excluÒ­da com sucesso!");
    } else {
        header("Location: formPaginas.php?erro=NÒ£o foi possÒ­vel excluir a pÒ¡gina.");
    }
} catch (Exception $e) {
    header("Location: formPaginas.php?erro=Erro ao excluir: " . $e->getMessage());
}
exit();
?>
