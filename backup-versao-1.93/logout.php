<?php
session_start();

$authCookieName = 'login_v3';
$cookieConfigPath = __DIR__ . '/temp/setCookie.env';
if (file_exists($cookieConfigPath)) {
    $rawCookieName = trim((string) file_get_contents($cookieConfigPath));
    if ($rawCookieName !== '') {
        if (strpos($rawCookieName, '=') !== false) {
            $parts = explode('=', $rawCookieName, 2);
            $rawCookieName = trim($parts[1]);
        }
        if ($rawCookieName !== '') {
            $authCookieName = $rawCookieName;
        }
    }
}

// Destroi a sessão existente
session_destroy();

// Excluir o cookie de sessão
$params = session_get_cookie_params();
setcookie(session_name(), '', time() - 42000,
    $params["path"], $params["domain"],
    $params["secure"], $params["httponly"]
);

// Excluir o cookie de login com todos os parâmetros possíveis
setcookie($authCookieName, '', time() - 3600, "/");
setcookie($authCookieName, '', time() - 3600, "/", "", false, false); // Sem secure e httponly

// Redireciona para a página de login
header("Location: login.php");
exit();
?>
