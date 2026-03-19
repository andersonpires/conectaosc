<?php
require_once dirname(__DIR__, 2) . '/bootstrap/runtime.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('PHPSESSID3');
    session_start();
}

$authCookieName = 'login_v43';
$cookieConfigPath = dirname(__DIR__, 2) . '/temp/setCookie.env';
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

session_destroy();

$params = session_get_cookie_params();
setcookie(
    session_name(),
    '',
    time() - 42000,
    $params['path'],
    $params['domain'],
    $params['secure'],
    $params['httponly']
);

$runtime = bootstrap_runtime();
$baseUrl = rtrim((string) ($runtime['base_para_url'] ?? $runtime['base_para_url'] ?? ''), '/');
if ($baseUrl === '') {
    $baseUrl = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
}

foreach (['/', $baseUrl] as $path) {
    if ($path === '') {
        continue;
    }
    setcookie($authCookieName, '', time() - 3600, $path);
    setcookie($authCookieName, '', time() - 3600, $path, '', false, false);
    setcookie($authCookieName, '', time() - 3600, $path, '', true, true);
}
unset($_COOKIE[$authCookieName]);

header('Location: ' . $baseUrl . '/login/?logout=1');
exit;
