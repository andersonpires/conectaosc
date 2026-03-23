<?php
/**
 * Bootstrap do App Clinica - garante sessao antes de carregar o front
 * Acesse /{raiz-do-projeto}/clinica/ (com ou sem index.php)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
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

    $projectBasePath = clinicaProjectBasePath();
    $projectRootFs = dirname(__DIR__);
    $sharedFs = dirname($projectRootFs) . DIRECTORY_SEPARATOR . 'conectaosc' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img';

    if (is_dir($sharedFs)) {
        $parentUrl = rtrim(str_replace('\\', '/', dirname($projectBasePath)), '/');
        if ($parentUrl === '/' || $parentUrl === '.') {
            $parentUrl = '';
        }
        return $parentUrl . '/conectaosc/assets/img';
    }

    return rtrim($projectBasePath, '/') . '/assets/img';
}

function clinicaShouldUseLegacyFront(): bool
{
    $legacyParam = $_GET['legacy'] ?? '';
    return $legacyParam === '1' || strtolower((string) $legacyParam) === 'true';
}

function clinicaFrontMode(): string
{
    $mode = getenv('CLINICA_FRONT_MODE');
    if (!is_string($mode) || $mode === '') {
        return 'auto';
    }
    $mode = strtolower(trim($mode));
    if (in_array($mode, ['legacy', 'angular', 'auto'], true)) {
        return $mode;
    }
    return 'auto';
}

function clinicaLoadAngularFront(): ?string
{
    $angularIndexPath = __DIR__ . '/frontend-angular/dist/frontend-angular/browser/index.html';
    if (!is_file($angularIndexPath)) {
        return null;
    }
    $html = file_get_contents($angularIndexPath);
    if ($html === false) {
        return null;
    }

    $projectBasePath = clinicaProjectBasePath();
    $angularBaseHref = rtrim($projectBasePath, '/') . '/clinica/frontend-angular/';
    if ($angularBaseHref === '/clinica/frontend-angular/') {
        $angularBaseHref = '/clinica/frontend-angular/';
    }

    return preg_replace(
        '#<base\s+href="[^"]*"\s*/?>#i',
        '<base href="' . htmlspecialchars($angularBaseHref, ENT_QUOTES, 'UTF-8') . '">',
        $html,
        1
    ) ?: $html;
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

setcookie(
    'CLINICA_PROJECT_BASE',
    clinicaProjectBasePath(),
    [
        'expires' => time() + (60 * 60 * 24 * 30),
        'path' => '/',
        'httponly' => false,
        'samesite' => 'Lax',
    ]
);

if (empty($_SESSION['Cod'])) {
    $redirect = urlencode(clinicaProjectBasePath() . '/clinica/');
    header('Location: ' . $_SESSION['BASE_para_URL'] . '/login/?redirect=' . $redirect);
    exit;
}

// Apenas profissionais de saude podem acessar o App Clinica (sempre consulta o banco, evita sessao desatualizada)
$profissionalSaudeAtual = 0;
try {
    $conexaoPath = $conectaoscPath . '/api/conectabd/conexao.php';
    if (!is_file($conexaoPath)) {
        $conexaoPath = $conectaoscPath . '/conectabd/conexao.php';
    }
    require_once $conexaoPath;
    $stmt = $pdo->prepare("SELECT COALESCE(profissional_saude, 0) FROM tbUser WHERE IdColaborador = ?");
    $stmt->execute([$_SESSION['Cod']]);
    $profissionalSaudeAtual = (int)($stmt->fetchColumn() ?: 0);
    $_SESSION['profissional_saude'] = $profissionalSaudeAtual;
} catch (Throwable $e) {
    $_SESSION['profissional_saude'] = 0;
}
if ($profissionalSaudeAtual !== 1) {
    header('Content-Type: text/html; charset=utf-8');
    die('<div class="alert alert-danger m-4">Acesso restrito a profissionais de saude. Entre em contato com o administrador se voce deveria ter acesso.</div>');
}

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$frontMode = clinicaFrontMode();
$forceLegacy = ($frontMode === 'legacy') || clinicaShouldUseLegacyFront();
$forceAngular = ($frontMode === 'angular');

if (!$forceLegacy) {
    $angularHtml = clinicaLoadAngularFront();
    if ($angularHtml !== null) {
        echo $angularHtml;
        exit;
    }
    if ($forceAngular) {
        http_response_code(503);
        exit('Front Angular indisponivel: build nao encontrado.');
    }
}

// Fallback legado: front antigo
$legacyHtml = file_get_contents(__DIR__ . '/index.html');
if ($legacyHtml === false) {
    http_response_code(500);
    exit('Erro ao carregar o front da clinica.');
}
$logoUrl = clinicaAssetsImgUrl() . '/sistema/logo.png';
$projectBasePath = clinicaProjectBasePath();
$parentBasePath = rtrim(str_replace('\\', '/', dirname($projectBasePath)), '/');
if ($parentBasePath === '/' || $parentBasePath === '.') {
    $parentBasePath = '';
}
$fallbackPrimary = $parentBasePath . '/conectaosc/assets/img/sistema/logo.png';
$fallbackSecondary = rtrim($projectBasePath, '/') . '/assets/img/sistema/logo.png';

$logoTag = '<img src="' . htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') . '" alt="Logo" class="h-8 md:h-9 object-contain" onerror="if(!this.dataset.fallback1){this.dataset.fallback1=1;this.src=\'' . htmlspecialchars($fallbackPrimary, ENT_QUOTES, 'UTF-8') . '\';return;} if(!this.dataset.fallback2){this.dataset.fallback2=1;this.src=\'' . htmlspecialchars($fallbackSecondary, ENT_QUOTES, 'UTF-8') . '\';return;} this.style.display=\'none\';">';
echo str_replace('__CLINICA_LOGO_TAG__', $logoTag, $legacyHtml);
