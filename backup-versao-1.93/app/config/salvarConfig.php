<?php
session_set_cookie_params(['httponly' => true]);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?redirect=$redirect_url");
    exit();
}

require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

try {
    $stmt = $pdo->prepare("UPDATE tbConfig SET 
        NomeSistema = ?, 
        CorPrimaria = ?, 
        CorSecundaria = ?, 
        LogoSistema = ?, 
        LogoImpressao = ?,
        ShortcutIcon = ?, 
        TituloPagina = ?, 
        MetaDescription = ?, 
        MetaAuthor = ?, 
        MetaKeywords = ?, 
        APIzap = ?, 
        HeaderEmail = ?, 
        TermosUso = ?
        WHERE IdConfig = 1");

    $stmt->execute([
        $_POST['NomeSistema'] ?? '',
        $_POST['CorPrimaria'] ?? '',
        $_POST['CorSecundaria'] ?? '',
        $_POST['LogoSistema'] ?? '',
        $_POST['LogoImpressao'] ?? '',
        $_POST['ShortcutIcon'] ?? '',
        $_POST['TituloPagina'] ?? '',
        $_POST['MetaDescription'] ?? '',
        $_POST['MetaAuthor'] ?? '',
        $_POST['MetaKeywords'] ?? '',
        $_POST['APIzap'] ?? '',
        $_POST['HeaderEmail'] ?? '',
        $_POST['TermosUso'] ?? ''
    ]);

    header("Location: formConfig.php?msg=" . urlencode("Configurações atualizadas com sucesso!"));
    exit();
} catch (PDOException $e) {
    header("Location: formConfig.php?erro=" . urlencode("Erro ao atualizar configurações: " . $e->getMessage()));
    exit();
}
