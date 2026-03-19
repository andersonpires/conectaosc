<?php
/**
 * Bootstrap do App Clinica - garante sessao antes de carregar o SPA
 * Acesse /{raiz-do-projeto}/clinica/ (com ou sem index.php)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_name('PHPSESSID3');
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/conecta/clinica/index.php'));
    $projectBasePath = preg_match('#^(.*?)/clinica(?:/|$)#', $scriptName, $matches) ? rtrim((string)($matches[1] ?? ''), '/') : '';
    $sessionCookiePath = $projectBasePath !== '' ? $projectBasePath : '/';
    session_set_cookie_params([
        'path' => $sessionCookiePath,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function clinicaProjectBasePath(): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/clinica/index.php');
    $clinicaPrefix = '/clinica';
    $pos = strpos($scriptName, $clinicaPrefix);
    if ($pos === false) {
        return '';
    }
    return rtrim(substr($scriptName, 0, $pos), '/');
}

function clinicaAssetsImgUrl(): string
{
    if (
        !empty($_SESSION['BASE_ASSETS_IMG_URL']) &&
        is_string($_SESSION['BASE_ASSETS_IMG_URL']) &&
        !empty($_SESSION['BASE_ASSETS_IMG_PATH']) &&
        is_string($_SESSION['BASE_ASSETS_IMG_PATH']) &&
        is_dir((string) $_SESSION['BASE_ASSETS_IMG_PATH'])
    ) {
        return rtrim((string) $_SESSION['BASE_ASSETS_IMG_URL'], '/');
    }

    $projectBasePath = rtrim(clinicaProjectBasePath(), '/');
    if ($projectBasePath === '') {
        return '/assets/img';
    }

    return $projectBasePath . '/assets/img';
}

function clinicaShortcutIconUrl(): string
{
    $projectBasePath = rtrim(clinicaProjectBasePath(), '/');
    $baseUrl = $_SESSION['BASE_para_URL'] ?? '';
    if (!is_string($baseUrl)) {
        $baseUrl = '';
    }

    $shortcutIcon = 'icon-48x48.png';
    try {
        $conectaoscPath = dirname(__DIR__);
        $conexaoPath = $conectaoscPath . '/api/conectabd/conexao.php';
        if (!is_file($conexaoPath)) {
            $conexaoPath = $conectaoscPath . '/conectabd/conexao.php';
        }
        require $conexaoPath;
        $stmt = $pdo->query("SELECT ShortcutIcon FROM tbConfig LIMIT 1");
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        $valor = trim((string)($row['ShortcutIcon'] ?? ''));
        if ($valor !== '') {
            $shortcutIcon = ltrim(str_replace('\\', '/', $valor), '/');
        }
    } catch (Throwable $e) {
    }

    $iconName = basename($shortcutIcon);
    if ($baseUrl !== '') {
        return rtrim($baseUrl, '/') . '/assets/img/icons/' . rawurlencode($iconName);
    }

    return ($projectBasePath !== '' ? $projectBasePath : '') . '/assets/img/icons/' . rawurlencode($iconName);
}

function clinicaApiCookieHeader(): string
{
    $pairs = [];
    foreach ($_COOKIE as $name => $value) {
        if (!is_string($name) || $name === '' || !is_scalar($value)) {
            continue;
        }
        $pairs[] = rawurlencode($name) . '=' . rawurlencode((string) $value);
    }

    $sessionName = session_name();
    $sessionId = session_id();
    if ($sessionName !== '' && $sessionId !== '') {
        $needle = rawurlencode($sessionName) . '=';
        $found = false;
        foreach ($pairs as $pair) {
            if (str_starts_with($pair, $needle)) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $pairs[] = rawurlencode($sessionName) . '=' . rawurlencode($sessionId);
        }
    }

    return implode('; ', $pairs);
}

function clinicaApiMe(): array
{
    $baseUrl = $_SESSION['BASE_para_URL'] ?? '';
    if (!is_string($baseUrl) || trim($baseUrl) === '') {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $baseUrl = ($isHttps ? 'https' : 'http') . '://' . $host . clinicaProjectBasePath();
    }

    $url = rtrim((string) $baseUrl, '/') . '/api/v1/me';
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'Cookie: ' . clinicaApiCookieHeader(),
    ];
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $headers),
            'timeout' => 15,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    if (!is_string($response) || trim($response) === '') {
        return [];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded) || !($decoded['success'] ?? false) || !is_array($decoded['data'] ?? null)) {
        return [];
    }

    return $decoded['data'];
}

$conectaoscPath = dirname(__DIR__);
if (empty($_SESSION['BASE_para_PATH'])) {
    $_SESSION['BASE_para_PATH'] = $conectaoscPath;
}
if (empty($_SESSION['BASE_para_URL'])) {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $_SESSION['BASE_para_URL'] = ($isHttps ? 'https' : 'http') . '://' . $host . clinicaProjectBasePath();
}

if (empty($_SESSION['Cod'])) {
    $redirect = urlencode(clinicaProjectBasePath() . '/clinica/');
    header('Location: ' . $_SESSION['BASE_para_URL'] . '/login/?redirect=' . $redirect);
    exit;
}

$profissionalSaudeAtual = 0;
try {
    $me = clinicaApiMe();
    if ($me !== []) {
        $profissionalSaudeAtual = (int)($me['profissional_saude'] ?? 0);
        $_SESSION['profissional_saude'] = $profissionalSaudeAtual;
        $_SESSION['especialidade_id'] = isset($me['especialidade_id']) && $me['especialidade_id'] !== null ? (int)$me['especialidade_id'] : null;
    } else {
        $conexaoPath = $conectaoscPath . '/api/conectabd/conexao.php';
        if (!is_file($conexaoPath)) {
            $conexaoPath = $conectaoscPath . '/conectabd/conexao.php';
        }
        require_once $conexaoPath;
        $stmt = $pdo->prepare("SELECT COALESCE(profissional_saude, 0), especialidade_id FROM tbUser WHERE IdColaborador = ?");
        $stmt->execute([$_SESSION['Cod']]);
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $profissionalSaudeAtual = (int)($row[0] ?? 0);
        $_SESSION['profissional_saude'] = $profissionalSaudeAtual;
        $_SESSION['especialidade_id'] = ($row[1] ?? 0) ? (int)$row[1] : null;
    }
} catch (Throwable $e) {
    $_SESSION['profissional_saude'] = 0;
    $_SESSION['especialidade_id'] = null;
}
if ($profissionalSaudeAtual !== 1) {
    header('Content-Type: text/html; charset=utf-8');
    die('<div class="alert alert-danger m-4">Acesso restrito a profissionais de saude. Entre em contato com o administrador se voce deveria ter acesso.</div>');
}

header('Content-Type: text/html; charset=utf-8');
$clinicaJsVersion = (string)(@filemtime(__DIR__ . '/src/app.js') ?: time());
$html = file_get_contents(__DIR__ . '/index.html');
if ($html === false) {
    http_response_code(500);
    exit('Erro ao carregar o front da clinica.');
}
$logoUrl = clinicaAssetsImgUrl() . '/sistema/logo.png';
$projectBasePath = clinicaProjectBasePath();
$fallbackPrimary = rtrim($projectBasePath, '/') . '/assets/img/sistema/logo.png';
$fallbackSecondary = rtrim($projectBasePath, '/') . '/app/assets/img/sistema/logo.png';

$logoTag = '<img src="' . htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') . '" alt="Logo" class="h-8 md:h-9 object-contain" onerror="if(!this.dataset.fallback1){this.dataset.fallback1=1;this.src=\'' . htmlspecialchars($fallbackPrimary, ENT_QUOTES, 'UTF-8') . '\';return;} if(!this.dataset.fallback2){this.dataset.fallback2=1;this.src=\'' . htmlspecialchars($fallbackSecondary, ENT_QUOTES, 'UTF-8') . '\';return;} this.style.display=\'none\';">';
$faviconUrl = clinicaShortcutIconUrl();
$faviconTag = '<link rel="shortcut icon" href="' . htmlspecialchars($faviconUrl, ENT_QUOTES, 'UTF-8') . '">';
$fotoColaborador = trim((string) ($_SESSION['Foto'] ?? ''));
$clinicaBootstrap = [
    'usuario' => [
        'nome' => (string) ($_SESSION['Nome'] ?? ''),
        'fotoUrl' => $fotoColaborador !== ''
            ? clinicaAssetsImgUrl() . '/fotos/' . rawurlencode(basename($fotoColaborador))
            : '',
    ],
];
$bootstrapTag = '<script>window.__CLINICA_BOOTSTRAP__ = ' . json_encode($clinicaBootstrap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';</script>';
$html = str_replace('__CLINICA_LOGO_TAG__', $logoTag, $html);
$html = str_replace('__CLINICA_FAVICON_TAG__', $faviconTag, $html);
$html = str_replace('</head>', $bootstrapTag . "\n</head>", $html);
$html = str_replace('./src/app.js', './src/app.js?v=' . rawurlencode($clinicaJsVersion), $html);
echo $html;

