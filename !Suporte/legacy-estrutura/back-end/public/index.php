<?php
declare(strict_types=1);

use BackEnd\Core\ErrorHandler;
use BackEnd\Core\Request;
use BackEnd\Core\Router;

require_once __DIR__ . '/../src/Core/ErrorHandler.php';

ErrorHandler::register();

spl_autoload_register(static function (string $class): void {
    $prefix = 'BackEnd\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = __DIR__ . '/../src/' . str_replace('\\', '/', $relativeClass) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

$requestPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$apiPrefix = '/api/v1';
$prefixPos = strpos($requestPath, $apiPrefix);
$normalizedPath = $prefixPos === false ? '/' : substr($requestPath, $prefixPos + strlen($apiPrefix));
$normalizedPath = $normalizedPath === '' ? '/' : $normalizedPath;

$request = new Request($normalizedPath);
$router = new Router($request);

require __DIR__ . '/../routes/api.php';

$router->dispatch();

