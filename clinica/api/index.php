<?php
/**
 * App Clinica - API REST
 * /{raiz-do-projeto}/clinica/api/
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
date_default_timezone_set('America/Sao_Paulo');
require_once dirname(__DIR__, 2) . '/bootstrap/runtime.php';

$apiErrorHandler = function (Throwable $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    $isDev = (stripos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || stripos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false);
    echo json_encode([
        'success' => false,
        'message' => $isDev ? $e->getMessage() : 'Erro interno do servidor',
        'data' => (object)[],
        'errors' => $isDev ? [$e->getFile() . ':' . $e->getLine()] : [],
    ], JSON_UNESCAPED_UNICODE);
    exit;
};
set_exception_handler($apiErrorHandler);
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (headers_sent() === false) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
        }
        echo json_encode([
            'success' => false,
            'message' => $e['message'],
            'data' => (object)[],
            'errors' => [$e['file'] . ':' . $e['line']],
        ], JSON_UNESCAPED_UNICODE);
    }
});

if (session_status() === PHP_SESSION_NONE) {
    session_name('PHPSESSID3');
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/conecta/clinica/api/index.php'));
    $projectBasePath = preg_match('#^(.*?)/clinica/api(?:/|$)#', $scriptName, $matches) ? rtrim((string)($matches[1] ?? ''), '/') : '';
    $sessionCookiePath = $projectBasePath !== '' ? $projectBasePath : '/';
    session_set_cookie_params([
        'path' => $sessionCookiePath,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

bootstrap_validate_auth_session_cookie_name(dirname(__DIR__, 2));

function clinicaApiProjectBasePath(): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/clinica/api/index.php');
    $apiPrefix = '/clinica/api';
    $pos = strpos($scriptName, $apiPrefix);
    if ($pos === false) {
        return '';
    }
    return rtrim(substr($scriptName, 0, $pos), '/');
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

function clinicaApiMeData(): array
{
    $baseUrl = $_SESSION['BASE_para_URL'] ?? '';
    if (!is_string($baseUrl) || trim($baseUrl) === '') {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $baseUrl = ($isHttps ? 'https' : 'http') . '://' . $host . clinicaApiProjectBasePath();
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

$conectaoscPath = dirname(dirname(__DIR__));
if (empty($_SESSION['BASE_para_PATH'])) {
    $_SESSION['BASE_para_PATH'] = $conectaoscPath;
}
if (empty($_SESSION['BASE_para_URL'])) {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $_SESSION['BASE_para_URL'] = ($isHttps ? 'https' : 'http') . '://' . $host . clinicaApiProjectBasePath();
}

if (isset($_SESSION['Cod'])) {
    try {
        $me = clinicaApiMeData();
        if ($me !== []) {
            $_SESSION['profissional_saude'] = (int) ($me['profissional_saude'] ?? 0);
            $_SESSION['licenca_administrativa'] = (int) ($me['licenca_administrativa'] ?? 0);
            $_SESSION['especialidade_id'] = isset($me['especialidade_id']) && $me['especialidade_id'] !== null ? (int)$me['especialidade_id'] : null;
        } else {
            $basePathSession = rtrim((string) ($_SESSION['BASE_para_PATH'] ?? ''), '/\\');
            $conexaoPath = $basePathSession . '/api/conectabd/conexao.php';
            if (!is_file($conexaoPath)) {
                $conexaoPath = $basePathSession . '/conectabd/conexao.php';
            }
            require_once $conexaoPath;
            $stmt = $pdo->prepare("SELECT COALESCE(profissional_saude, 0), COALESCE(licenca_administrativa, 0), COALESCE(especialidade_id, 0) FROM tbUser WHERE IdColaborador = ?");
            $stmt->execute([$_SESSION['Cod']]);
            $row = $stmt->fetch(\PDO::FETCH_NUM);
            $_SESSION['profissional_saude'] = (int) ($row[0] ?? 0);
            $_SESSION['licenca_administrativa'] = (int) ($row[1] ?? 0);
            $_SESSION['especialidade_id'] = ($row[2] ?? 0) ? (int)$row[2] : null;
        }
    } catch (Throwable $e) {
        $_SESSION['profissional_saude'] = 0;
        $_SESSION['licenca_administrativa'] = 0;
        $_SESSION['especialidade_id'] = null;
    }
}

try {
spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\') !== 0) return;
    $path = str_replace('\\', '/', substr($class, 4));
    $file = __DIR__ . '/app/' . $path . '.php';
    if (file_exists($file)) require_once $file;
});

require_once __DIR__ . '/app/Core/Env.php';
require_once __DIR__ . '/app/Core/Database.php';
require_once __DIR__ . '/app/Core/JsonResponse.php';
require_once __DIR__ . '/app/Core/Router.php';
require_once __DIR__ . '/app/Middlewares/AuthMiddleware.php';

\App\Core\Env::init();

\App\Middlewares\RateLimitMiddleware::check();

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/clinica/api/index.php')), '/');
$router = new \App\Core\Router($basePath);

$router->get('/', function () {
    \App\Core\JsonResponse::success([
        'ok' => true,
        'session' => isset($_SESSION['Cod']),
        'msg' => isset($_SESSION['Cod']) ? 'Sessao OK' : 'Faca login no ConectaOSC',
    ]);
});

$router->get('/debug/database', function () {
    \App\Middlewares\AuthMiddleware::requireAcessoClinica();

    if (!\App\Middlewares\AuthMiddleware::isSuperAdmin() && (int)($_SESSION['licenca_administrativa'] ?? 0) !== 1) {
        \App\Core\JsonResponse::error('Acesso restrito ao diagnostico do banco', [], 403);
    }

    $pdo = \App\Core\Database::getConnection();
    $info = $pdo->query("
        SELECT
            DATABASE() AS banco_atual,
            @@hostname AS servidor_mysql,
            @@port AS porta_mysql,
            @@version AS versao_mysql
    ")->fetch(\PDO::FETCH_ASSOC) ?: [];

    $consultas = $pdo->query("
        SELECT id, aluno_id, profissional_id, data_consulta, hora_inicio_prevista, status, created_at
          FROM tb_consulta
         ORDER BY id DESC
         LIMIT 10
    ")->fetchAll(\PDO::FETCH_ASSOC);

    $prontuarios = $pdo->query("
        SELECT id, consulta_id, aluno_id, profissional_id, status, created_at
          FROM tb_prontuario
         ORDER BY id DESC
         LIMIT 10
    ")->fetchAll(\PDO::FETCH_ASSOC);

    $consultasAguardandoProntuario = $pdo->query("
        SELECT c.id, c.aluno_id, c.profissional_id, c.data_consulta, c.hora_inicio_prevista, c.status, c.created_at,
               (
                   SELECT COUNT(*)
                     FROM tb_prontuario p2
                    WHERE p2.aluno_id = c.aluno_id
               ) AS total_prontuarios_mesmo_aluno,
               (
                   SELECT p2.id
                     FROM tb_prontuario p2
                    WHERE p2.aluno_id = c.aluno_id
                    ORDER BY p2.created_at DESC, p2.id DESC
                    LIMIT 1
               ) AS ultimo_prontuario_mesmo_aluno_id,
               (
                   SELECT p2.consulta_id
                     FROM tb_prontuario p2
                    WHERE p2.aluno_id = c.aluno_id
                    ORDER BY p2.created_at DESC, p2.id DESC
                    LIMIT 1
               ) AS ultimo_prontuario_mesmo_aluno_consulta_id,
               (
                   SELECT p2.created_at
                     FROM tb_prontuario p2
                    WHERE p2.aluno_id = c.aluno_id
                    ORDER BY p2.created_at DESC, p2.id DESC
                    LIMIT 1
               ) AS ultimo_prontuario_mesmo_aluno_created_at
          FROM tb_consulta c
         WHERE c.status IN ('concluida', 'em_atendimento')
           AND NOT EXISTS (SELECT 1 FROM tb_prontuario p WHERE p.consulta_id = c.id)
         ORDER BY c.data_consulta DESC, c.hora_inicio_prevista DESC
         LIMIT 10
    ")->fetchAll(\PDO::FETCH_ASSOC);

    $prontuariosSemConsultaValida = $pdo->query("
        SELECT p.id, p.consulta_id, p.aluno_id, p.profissional_id, p.status, p.created_at
          FROM tb_prontuario p
          LEFT JOIN tb_consulta c ON c.id = p.consulta_id
         WHERE p.consulta_id IS NULL
            OR p.consulta_id = 0
            OR c.id IS NULL
         ORDER BY p.id DESC
         LIMIT 10
    ")->fetchAll(\PDO::FETCH_ASSOC);

    \App\Core\JsonResponse::success([
        'database' => $info,
        'env' => [
            'DB_HOST' => bootstrap_env('DB_HOST', ''),
            'DB_NAME' => bootstrap_env('DB_NAME', ''),
            'DB_PORT' => bootstrap_env('DB_PORT', ''),
            'APP_ENV' => bootstrap_env('APP_ENV', ''),
            'APP_DEBUG' => bootstrap_env('APP_DEBUG', ''),
        ],
        'ultimas_consultas' => $consultas,
        'ultimos_prontuarios' => $prontuarios,
        'consultas_aguardando_prontuario' => $consultasAguardandoProntuario,
        'prontuarios_sem_consulta_valida' => $prontuariosSemConsultaValida,
    ]);
});

$pac = new \App\Controllers\PacientesController();
$esp = new \App\Controllers\EspecialidadesController();
$tip = new \App\Controllers\TiposConsultaController();
$profissionais = new \App\Controllers\ProfissionaisController();
$con = new \App\Controllers\ConsultasController();
$pront = new \App\Controllers\ProntuariosController();
$evol = new \App\Controllers\EvolucoesController();

$router->get('/pacientes', [$pac, 'index']);
$router->get('/pacientes/{id}', [$pac, 'show']);
$router->get('/especialidades', [$esp, 'index']);
$router->get('/tipos-consulta', [$tip, 'index']);
$router->post('/tipos-consulta', [$tip, 'store']);
$router->put('/tipos-consulta/{id}', [$tip, 'update']);
$router->post('/tipos-consulta/{id}/toggle', [$tip, 'toggle']);
$router->get('/profissionais', [$profissionais, 'index']);
$router->get('/consultas/{id}', [$con, 'show']);
$router->post('/consultas', [$con, 'store']);
$router->put('/consultas/{id}', [$con, 'update']);
$router->post('/consultas/{id}/confirmacao', [$con, 'confirmacao']);
$router->post('/consultas/{id}/cancelar', [$con, 'cancelar']);
$router->post('/consultas/{id}/excluir', [$con, 'excluir']);
$router->post('/consultas/{id}/iniciar-atendimento', [$con, 'iniciarAtendimento']);
$router->post('/consultas/{id}/concluir-atendimento', [$con, 'concluirAtendimento']);
$router->post('/consultas/{id}/reverter-atendimento', [$con, 'reverterAtendimento']);
$router->post('/consultas/{id}/excluir-atendimento', [$con, 'excluirAtendimento']);
$router->post('/consultas/{id}/reverter-atendimento-completo', [$con, 'reverterAtendimentoCompleto']);
$router->get('/agenda', [new \App\Controllers\AgendaController(), 'index']);
$router->get('/agenda/dias-com-agendamento', [new \App\Controllers\AgendaController(), 'diasComAgendamento']);
$router->get('/agenda/em-atendimento', [new \App\Controllers\AgendaController(), 'emAtendimento']);
$router->get('/agenda/consultas-atendimento', [new \App\Controllers\AgendaController(), 'consultasParaAtendimento']);
$router->get('/agenda/consultas-aguardando-prontuario', [new \App\Controllers\AgendaController(), 'consultasAguardandoProntuario']);
$router->get('/feriados/verificar', [new \App\Controllers\FeriadosController(), 'verificar']);
$router->get('/prontuarios', [$pront, 'index']);
$router->get('/prontuarios/pacientes', [$pront, 'pacientesComProntuario']);
$router->get('/prontuarios/{id}', [$pront, 'show']);
$router->post('/prontuarios/gerar-ia', [$pront, 'gerarIa']);
$router->post('/prontuarios/stream-ia', [$pront, 'streamIa']);
$router->post('/prontuarios', [$pront, 'store']);
$router->post('/prontuarios/pdf', [$pront, 'pdfPost']);
$router->put('/prontuarios/{id}', [$pront, 'update']);
$router->delete('/prontuarios/{id}', [$pront, 'destroy']);

$router->get('/evolucoes', [$evol, 'index']);
$router->get('/evolucoes/paciente/{id}', [$evol, 'paciente']);
$router->get('/evolucoes/{id}', [$evol, 'show']);
$router->post('/evolucoes', [$evol, 'store']);
$router->put('/evolucoes/{id}', [$evol, 'update']);

$anamnese = new \App\Controllers\AnamneseController();
$router->get('/anamnese', [$anamnese, 'index']);
$router->get('/anamnese/{id}', [$anamnese, 'show']);
$router->post('/anamnese', [$anamnese, 'store']);
$router->put('/anamnese/{id}', [$anamnese, 'update']);

$rot = new \App\Controllers\AnamneseRoteiroController();
$router->get('/anamnese-roteiro', [$rot, 'index']);
$router->get('/anamnese-roteiro/{id}', [$rot, 'show']);
$router->post('/anamnese-roteiro', [$rot, 'store']);
$router->put('/anamnese-roteiro/{id}', [$rot, 'update']);

$router->dispatch();
} catch (Throwable $e) {
    $apiErrorHandler($e);
}
