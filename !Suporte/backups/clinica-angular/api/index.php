<?php
/**
 * App Clínica - API REST
 * /{raiz-do-projeto}/clinica/api/
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
date_default_timezone_set('America/Sao_Paulo');

// Captura erros fatais e exceções para retornar JSON
$apiErrorHandler = function (Throwable $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    $isDev = (stripos($_SERVER['HTTP_HOST'] ? '', 'localhost') !== false || stripos($_SERVER['HTTP_HOST'] ? '', '127.0.0.1') !== false);
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
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function clinicaApiProjectBasePath(): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ? '/clinica/api/index.php');
    $apiPrefix = '/clinica/api';
    $pos = strpos($scriptName, $apiPrefix);
    if ($pos === false) {
        return '';
    }
    return rtrim(substr($scriptName, 0, $pos), '/');
}

// Garantir BASE_para_PATH quando acessado via API (session do login principal)
$conectaoscPath = dirname(dirname(__DIR__));
if (empty($_SESSION['BASE_para_PATH'])) {
    $_SESSION['BASE_para_PATH'] = $conectaoscPath;
}
if (empty($_SESSION['BASE_para_URL'])) {
    $host = $_SERVER['HTTP_HOST'] ? 'localhost';
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $_SESSION['BASE_para_URL'] = ($isHttps ? 'https' : 'http') . '://' . $host . clinicaApiProjectBasePath();
}

// Sempre atualizar profissional_saude e especialidade_id na sessão (evita cache desatualizado quando admin altera perfil)
if (isset($_SESSION['Cod'])) {
    try {
        $conexaoPath = rtrim((string) $_SESSION['BASE_para_PATH'], '/\\') . '/api/conectabd/conexao.php';
        if (!is_file($conexaoPath)) {
            $conexaoPath = rtrim((string) $_SESSION['BASE_para_PATH'], '/\\') . '/conectabd/conexao.php';
        }
        require_once $conexaoPath;
        $stmt = $pdo->prepare("SELECT COALESCE(profissional_saude, 0), COALESCE(especialidade_id, 0) FROM tbUser WHERE IdColaborador = ?");
        $stmt->execute([$_SESSION['Cod']]);
        $row = $stmt->fetch(\PDO::FETCH_NUM);
        $_SESSION['profissional_saude'] = (int) ($row[0] ? 0);
        $_SESSION['especialidade_id'] = ($row[1] ? 0) ? (int)$row[1] : null;
    } catch (Throwable $e) {
        $_SESSION['profissional_saude'] = 0;
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

// Base path flexível (localhost pode usar /clinica/api)
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ? '/clinica/api/index.php')), '/');
$router = new \App\Core\Router($basePath);

// Endpoint de diagnóstico (sem auth) - acesse /clinica/api/ para testar
$router->get('/', function () {
    \App\Core\JsonResponse::success([
        'ok' => true,
        'session' => isset($_SESSION['Cod']),
        'msg' => isset($_SESSION['Cod']) ? 'Sessão OK' : 'Faça login no ConectaOSC',
    ]);
});

$pac = new \App\Controllers\PacientesController();
$esp = new \App\Controllers\EspecialidadesController();
$tip = new \App\Controllers\TiposConsultaController();
$profissionais = new \App\Controllers\ProfissionaisController();
$con = new \App\Controllers\ConsultasController();
$pront = new \App\Controllers\ProntuariosController();

$router->get('/pacientes', [$pac, 'index']);
$router->get('/pacientes/{id}', [$pac, 'show']);
$router->get('/especialidades', [$esp, 'index']);
$router->get('/tipos-consulta', [$tip, 'index']);
$router->get('/profissionais', [$profissionais, 'index']);
$router->post('/consultas', [$con, 'store']);
$router->put('/consultas/{id}', [$con, 'update']);
$router->post('/consultas/{id}/confirmacao', [$con, 'confirmacao']);
$router->post('/consultas/{id}/cancelar', [$con, 'cancelar']);
$router->post('/consultas/{id}/excluir', [$con, 'excluir']);
$router->post('/consultas/{id}/iniciar-atendimento', [$con, 'iniciarAtendimento']);
$router->post('/consultas/{id}/reverter-atendimento', [$con, 'reverterAtendimento']);
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
$router->put('/prontuarios/{id}', [$pront, 'update']);
$router->delete('/prontuarios/{id}', [$pront, 'destroy']);
$router->get('/prontuarios/{id}/pdf', [$pront, 'pdf']);

$anamnese = new \App\Controllers\AnamneseController();
$router->get('/anamnese', [$anamnese, 'index']);
$router->get('/anamnese/{id}', [$anamnese, 'show']);
$router->post('/anamnese', [$anamnese, 'store']);
$router->put('/anamnese/{id}', [$anamnese, 'update']);

$router->dispatch();
} catch (Throwable $e) {
    $apiErrorHandler($e);
}
