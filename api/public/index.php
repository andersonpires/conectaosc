<?php
declare(strict_types=1);

use BackEnd\Core\ErrorHandler;
use BackEnd\Core\Request;
use BackEnd\Core\Router;

$errorHandlerNew = __DIR__ . '/../core/ErrorHandler.php';
$errorHandlerLegacy = __DIR__ . '/../src/Core/ErrorHandler.php';
if (is_file($errorHandlerNew)) {
    require_once $errorHandlerNew;
} else {
    require_once $errorHandlerLegacy;
}

ErrorHandler::register();

spl_autoload_register(static function (string $class): void {
    $prefix = 'BackEnd\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $relativePath = str_replace('\\', '/', $relativeClass) . '.php';
    $newFile = __DIR__ . '/../' . strtolower(dirname($relativePath)) . '/' . basename($relativePath);
    $legacyFile = __DIR__ . '/../src/' . $relativePath;

    if (is_file($newFile)) {
        require_once $newFile;
        return;
    }

    if (is_file($legacyFile)) {
        require_once $legacyFile;
    }
});

function apiRestoreLegacyAuthFromCookie(): void
{
    if (!empty($_SESSION['Cod'])) {
        return;
    }

    $rootPath = dirname(__DIR__);
    $authCookieName = 'login_v43';
    $cookieConfigPath = $rootPath . '/temp/setCookie.env';
    if (is_file($cookieConfigPath)) {
        $rawCookieName = trim((string) file_get_contents($cookieConfigPath));
        if ($rawCookieName !== '') {
            if (str_contains($rawCookieName, '=')) {
                $parts = explode('=', $rawCookieName, 2);
                $rawCookieName = trim((string) ($parts[1] ?? ''));
            }
            if ($rawCookieName !== '') {
                $authCookieName = $rawCookieName;
            }
        }
    }

    if (empty($_COOKIE[$authCookieName])) {
        return;
    }

    $cookieData = json_decode(base64_decode((string) $_COOKIE[$authCookieName], true) ?: '', true);
    if (!is_array($cookieData) || !isset($cookieData['id'], $cookieData['token'])) {
        return;
    }

    require_once $rootPath . '/legacy/funcoes.php';
    require_once $rootPath . '/conectabd/conexao.php';
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        return;
    }

    $stmt = $pdo->prepare(
        "SELECT u.IdColaborador, u.IdPermissao, u.Foto, u.Nome, u.Sobrenome, p.NomePermissao
         FROM tbUser u
         JOIN tbPermissao p ON u.IdPermissao = p.IdPermissao
         WHERE u.IdColaborador = ? AND u.Habilitado = 1"
    );
    $stmt->execute([(int) $cookieData['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!is_array($result)) {
        return;
    }

    if (!verificaHorarioPermissao($pdo, (int) $result['IdPermissao'])) {
        return;
    }

    $stmtPerms = $pdo->prepare("SELECT Pagina FROM tbPermissaoPagina WHERE IdPermissao = ?");
    $stmtPerms->execute([(int) $result['IdPermissao']]);
    $paginasPermitidas = $stmtPerms->fetchAll(PDO::FETCH_COLUMN);

    $_SESSION['token'] = (string) $cookieData['token'];
    $_SESSION['Cod'] = (int) $result['IdColaborador'];
    $_SESSION['Foto'] = (string) ($result['Foto'] ?? '');
    $_SESSION['Nome'] = (string) ($result['Nome'] ?? '');
    $_SESSION['Sobrenome'] = (string) ($result['Sobrenome'] ?? '');
    $_SESSION['Tipo'] = (string) ($result['NomePermissao'] ?? '');
    $_SESSION['IdPermissao'] = (int) ($result['IdPermissao'] ?? 0);
    $_SESSION['PaginasPermitidas'] = is_array($paginasPermitidas) ? $paginasPermitidas : [];
    $_SESSION['ultimoAcessoData'] = 'Agora';
}

if (session_status() === PHP_SESSION_NONE) {
    session_name('PHPSESSID3');
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/conecta/api/v1'));
    $projectBasePath = preg_match('#^(.*?)/api(?:/|$)#', $scriptName, $matches) ? rtrim((string)($matches[1] ?? ''), '/') : '';
    $sessionCookiePath = $projectBasePath !== '' ? $projectBasePath : '/';
    session_set_cookie_params([
        'path' => $sessionCookiePath,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

apiRestoreLegacyAuthFromCookie();

$requestPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$apiPrefix = '/api/v1';
$prefixPos = strpos($requestPath, $apiPrefix);
$normalizedPath = $prefixPos === false ? '/' : substr($requestPath, $prefixPos + strlen($apiPrefix));
$normalizedPath = $normalizedPath === '' ? '/' : $normalizedPath;

$request = new Request($normalizedPath);
$router = new Router($request);

require __DIR__ . '/../routes/api.php';

$router->dispatch();
