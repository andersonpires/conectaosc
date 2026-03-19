<?php
session_set_cookie_params(['httponly' => true]);
ini_set('session.gc_maxlifetime', 86400);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?redirect=$redirect_url");
    exit();
}

require_once $_SESSION['BASE_PATH'] . '/checa-token.php';
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';
require_once $_SESSION['BASE_PATH'] . '/app/colaborador/especialidadeProfissionalModel.php';
require_once 'perfilModel.php';

$authCookieName = 'login_v3';
$cookieConfigPath = $_SESSION['BASE_PATH'] . '/temp/setCookie.env';
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

$authSessionKeys = [
    'token',
    'Cod',
    'Foto',
    'Nome',
    'Sobrenome',
    'Tipo',
    'IdPermissao',
    'PaginasPermitidas',
    'ultimoAcessoData',
];

function normalizarEspecialidadesInput($input): array
{
    if (!is_array($input)) {
        return [];
    }

    $itens = [];
    foreach ($input as $item) {
        if (!is_array($item)) {
            continue;
        }

        $especialidadeId = isset($item['especialidade_id']) && $item['especialidade_id'] !== '' ? (int) $item['especialidade_id'] : null;
        $conselho = trim((string) ($item['conselho'] ?? ''));
        $uf = strtoupper(trim((string) ($item['uf'] ?? '')));
        $registro = trim((string) ($item['registro'] ?? ''));

        if ($especialidadeId === null && $conselho === '' && $uf === '' && $registro === '') {
            continue;
        }

        $itens[] = [
            'especialidade_id' => $especialidadeId,
            'Conselho' => $conselho !== '' ? $conselho : null,
            'UF' => $uf !== '' ? $uf : null,
            'NumeroRegistro' => $registro !== '' ? $registro : null
        ];
    }

    return $itens;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idColaborador = isset($_POST['IdColaborador']) ? (int) $_POST['IdColaborador'] : 0;

    $profissionalSaude = isset($_POST['profissional_saude']) && $_POST['profissional_saude'] === '1' ? 1 : 0;
    $especialidadesInput = normalizarEspecialidadesInput($_POST['especialidades'] ?? []);
    if ($profissionalSaude !== 1) {
        $especialidadesInput = [];
    }
    $_POST['especialidade_id'] = $especialidadesInput[0]['especialidade_id'] ?? null;

    $ok = PerfilModel::update($_POST, $_FILES);
    $okEspecialidades = true;
    if ($idColaborador > 0) {
        $okEspecialidades = EspecialidadeProfissionalModel::replaceForColaborador($idColaborador, $especialidadesInput);
    }

    if ($ok && $okEspecialidades) {
        $resultado = "Registro%20alterado%20com%20sucesso";
        $baseUrl = rtrim((string) ($_SESSION['BASE_URL'] ?? ''), '/');
        $loginUrl = $baseUrl !== '' ? $baseUrl . "/login.php?msg={$resultado}" : "login.php?msg={$resultado}";
        foreach ($authSessionKeys as $key) {
            unset($_SESSION[$key]);
        }
        setcookie($authCookieName, '', time() - 3600, "/");
        setcookie($authCookieName, '', time() - 3600, "/", "", false, false);
        header("Location: {$loginUrl}");
        exit;
    }

    $resultado = "N%C3%A3o%20foi%20poss%C3%ADvel%20alterar%20o%20registro!";
    header("Location: perfilController.php?erro=$resultado");
    exit;
}

if (isset($_GET['msg'])) $_POST['msg'] = urldecode($_GET['msg']);
if (isset($_GET['erro'])) $_POST['erro'] = urldecode($_GET['erro']);

$IdColaborador = $_SESSION['Cod'];
$colaborador = PerfilModel::getById((int) $IdColaborador);

$ufs = [
    'AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'
];

$especialidades = [];
try {
    $stmtEsp = $pdo->query("SELECT id, nome FROM tb_especialidade WHERE ativo = 1 ORDER BY nome");
    if ($stmtEsp) $especialidades = $stmtEsp->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $especialidades = [];
}

$especialidadesProfissionais = [];
try {
    $especialidadesProfissionais = EspecialidadeProfissionalModel::listByColaborador((int) $IdColaborador);
} catch (Throwable $e) {
    $especialidadesProfissionais = [];
}

require 'formPerfil.php';
exit;
