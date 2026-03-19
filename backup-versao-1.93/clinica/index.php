<?php
/**
 * Bootstrap do App Clínica - garante sessão antes de carregar o SPA
 * Acesse /conectaosc/clinica/ (com ou sem index.php)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

$conectaoscPath = dirname(__DIR__);
if (empty($_SESSION['BASE_PATH'])) {
    $_SESSION['BASE_PATH'] = $conectaoscPath;
}
if (empty($_SESSION['BASE_URL'])) {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $_SESSION['BASE_URL'] = ($isHttps ? 'https' : 'http') . '://' . $host . '/conectaosc';
}

if (empty($_SESSION['Cod'])) {
    $redirect = urlencode('/conectaosc/clinica/');
    header('Location: ' . $_SESSION['BASE_URL'] . '/login.php?redirect=' . $redirect);
    exit;
}

// Apenas profissionais de saúde podem acessar o App Clínica (sempre consulta o banco, evita sessão desatualizada)
$profissionalSaudeAtual = 0;
try {
    require_once $conectaoscPath . '/conectabd/conexao.php';
    $stmt = $pdo->prepare("SELECT COALESCE(profissional_saude, 0) FROM tbUser WHERE IdColaborador = ?");
    $stmt->execute([$_SESSION['Cod']]);
    $profissionalSaudeAtual = (int)($stmt->fetchColumn() ?: 0);
    $_SESSION['profissional_saude'] = $profissionalSaudeAtual;
} catch (Throwable $e) {
    $_SESSION['profissional_saude'] = 0;
}
if ($profissionalSaudeAtual !== 1) {
    header('Content-Type: text/html; charset=utf-8');
    die('<div class="alert alert-danger m-4">Acesso restrito a profissionais de saúde. Entre em contato com o administrador se você deveria ter acesso.</div>');
}

header('Content-Type: text/html; charset=utf-8');
readfile(__DIR__ . '/index.html');
