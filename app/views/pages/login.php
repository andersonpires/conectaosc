<?php
$ROOT_PATH = dirname(__DIR__, 3);
$runtime = require $ROOT_PATH . '/bootstrap/runtime.php';
$BASE_para_PATH = (string) ($runtime['base_para_path'] ?? $runtime['base_para_path'] ?? $ROOT_PATH);
$BASE_para_URL = (string) ($runtime['base_para_url'] ?? $runtime['base_para_url'] ?? '');
if ($BASE_para_URL === '' || $BASE_para_URL === '/') {
    $requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    if (preg_match('#^(.*)/login/?$#', $requestPath, $matches)) {
        $BASE_para_URL = rtrim((string) ($matches[1] ?? ''), '/');
    }
    if ($BASE_para_URL === '' || $BASE_para_URL === '/') {
        $fallbackPath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
        if (str_ends_with($fallbackPath, '/public')) {
            $fallbackPath = substr($fallbackPath, 0, -7);
        }
        $BASE_para_URL = ($fallbackPath !== '' && $fallbackPath !== '/') ? $fallbackPath : '/' . basename(__DIR__);
    }
}

$authCookieName = bootstrap_auth_cookie_name($ROOT_PATH);

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
    'auth_cookie_name',
];

function clearAuthState($keys, $cookieName, $baseUrl = '') {
    foreach ($keys as $key) {
        unset($_SESSION[$key]);
    }
    $paths = ['/'];
    $baseUrl = rtrim((string) $baseUrl, '/');
    if ($baseUrl !== '' && !in_array($baseUrl, $paths, true)) {
        $paths[] = $baseUrl;
    }
    foreach ($paths as $path) {
        setcookie($cookieName, '', time() - 3600, $path);
        setcookie($cookieName, '', time() - 3600, $path, '', false, false);
        setcookie($cookieName, '', time() - 3600, $path, '', true, true);
    }
    unset($_COOKIE[$cookieName]);
}

function redirectWithError($message, $keys, $cookieName, $baseUrl = '') {
    clearAuthState($keys, $cookieName, $baseUrl);
    $requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    $resolvedBaseUrl = $baseUrl;
    if ($resolvedBaseUrl === '' && preg_match('#^(.*)/login/?$#', $requestPath, $matches)) {
        $resolvedBaseUrl = rtrim((string) ($matches[1] ?? ''), '/');
    }
    if ($resolvedBaseUrl === '') {
        $scriptDir = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
        if (str_ends_with($scriptDir, '/public')) {
            $scriptDir = substr($scriptDir, 0, -7);
        }
        $resolvedBaseUrl = $scriptDir;
    }
    if ($resolvedBaseUrl === '') {
        $resolvedBaseUrl = '/' . basename(__DIR__);
    }
    $target = ($resolvedBaseUrl !== '' ? $resolvedBaseUrl : '') . '/login/?erro=' . urlencode($message);
    header("Location: " . $target);
    exit();
}

function loginProfileMissingFields(array $colaborador): array
{
    $missing = [];
    $required = [
        'Nascimento' => 'Data de nascimento',
        'Sobrenome' => 'Sobrenome',
        'Cargo' => 'Cargo',
        'WhatsApp' => 'WhatsApp',
    ];

    foreach ($required as $field => $label) {
        if (trim((string)($colaborador[$field] ?? '')) === '') {
            $missing[] = $label;
        }
    }

    $foto = strtolower(trim((string)($colaborador['Foto'] ?? '')));
    if ($foto === '' || in_array($foto, ['padrao.jfif', 'padrao.jpg', 'padrao.png'], true)) {
        $missing[] = 'Foto';
    }

    return $missing;
}

function loginProfileMissingFieldNames(array $colaborador): array
{
    $missing = [];
    foreach ([
        'Nascimento',
        'Sobrenome',
        'Cargo',
        'WhatsApp',
    ] as $field) {
        if (trim((string)($colaborador[$field] ?? '')) === '') {
            $missing[] = $field;
        }
    }

    $foto = strtolower(trim((string)($colaborador['Foto'] ?? '')));
    if ($foto === '' || in_array($foto, ['padrao.jfif', 'padrao.jpg', 'padrao.png'], true)) {
        $missing[] = 'Foto';
    }

    return $missing;
}

function loginNormalizeDate(?string $value): ?string
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    $date = DateTime::createFromFormat('Y-m-d', $value);
    if ($date && $date->format('Y-m-d') === $value) {
        return $value;
    }

    return null;
}

function loginUploadProfilePhoto(?array $file, string $currentPhoto): string
{
    if (!is_array($file) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $currentPhoto;
    }

    if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Não foi possível enviar a foto.');
    }

    if ((int)($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('A foto deve ter no máximo 5 MB.');
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    $mime = '';
    if (is_file($tmpName) && function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = (string)finfo_file($finfo, $tmpName);
            finfo_close($finfo);
        }
    }

    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Envie uma foto em JPG ou PNG.');
    }

    $dir = bootstrap_assets_img_path() . DIRECTORY_SEPARATOR . 'fotos';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }

    $filename = md5(uniqid((string)time(), true)) . '.' . $extensions[$mime];
    $target = $dir . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($tmpName, $target)) {
        throw new RuntimeException('Não foi possível salvar a foto.');
    }

    $currentPhoto = trim($currentPhoto);
    if ($currentPhoto !== '' && !in_array(strtolower($currentPhoto), ['padrao.jfif', 'padrao.jpg', 'padrao.png'], true)) {
        $oldPath = $dir . DIRECTORY_SEPARATOR . basename($currentPhoto);
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    return $filename;
}

function loginPreparePendingProfile(array $colaborador, array $missing, string $redirectUrl = ''): void
{
    $_SESSION['pending_profile_user_id'] = (int)$colaborador['IdColaborador'];
    $_SESSION['pending_profile_token'] = bin2hex(random_bytes(16));
    $_SESSION['pending_profile_redirect'] = $redirectUrl;
    unset($_SESSION['pending_profile_photo']);
}

function loginLoadPendingProfile(PDO $pdo): ?array
{
    $id = (int)($_SESSION['pending_profile_user_id'] ?? 0);
    if ($id <= 0) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT IdColaborador, Nome, Sobrenome, Nascimento, Cargo, WhatsApp, Foto FROM tbUser WHERE IdColaborador = ? AND Habilitado = 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

if (isset($_GET['erro']) && $_GET['erro'] !== '') {
    clearAuthState($authSessionKeys, $authCookieName, $BASE_para_URL);
}

if (isset($_GET['logout'])) {
    clearAuthState($authSessionKeys, $authCookieName, $BASE_para_URL);
}

$development_hosts = [
    'localhost',
    '127.0.0.1'
];

// Comentário ajustado para UTF-8.
$is_production = !in_array($_SERVER['HTTP_HOST'], $development_hosts);

// Comentário ajustado para UTF-8.
$is_http = (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off');

// Comentário ajustado para UTF-8.
if ($is_http && $is_production) {

    // Comentário ajustado para UTF-8.
    $url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

    // Envia o header de redirecionamento permanente (301)
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $url);
    exit();
}

require_once $ROOT_PATH . '/app/config/legacy_config.php';
require_once $ROOT_PATH . '/api/legacy/funcoes.php';
require_once $ROOT_PATH . '/api/conectabd/conexao.php';
$appJsVersion = @filemtime($ROOT_PATH . '/app/assets/js/app.js') ?: time();
$showProfileModal = false;
$profileModalData = null;
$profileModalMissing = [];
$profileModalErrors = [];
$config = $pdo->query("SELECT * FROM tbConfig LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$shortcutIcon = (string) ($config['ShortcutIcon'] ?? 'icon-48x48.png');
$shortcutIcon = trim($shortcutIcon);
if ($shortcutIcon === '') {
    $shortcutIcon = 'icon-48x48.png';
}
$shortcutIcon = ltrim(str_replace('\\', '/', $shortcutIcon), '/');
$iconFullPath = $BASE_para_PATH . '/app/assets/img/icons/' . $shortcutIcon;
if (!is_file($iconFullPath)) {
    $shortcutIcon = basename($shortcutIcon);
    $iconFullPath = $BASE_para_PATH . '/app/assets/img/icons/' . $shortcutIcon;
}
if (!is_file($iconFullPath)) {
    $shortcutIcon = 'icon-48x48.png';
}
bootstrap_apply_php_runtime();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'complete_profile') {
    $profileModalData = loginLoadPendingProfile($pdo);
    $sessionToken = (string)($_SESSION['pending_profile_token'] ?? '');
    $postedToken = (string)($_POST['profile_token'] ?? '');

    if (!$profileModalData || $sessionToken === '' || !hash_equals($sessionToken, $postedToken)) {
        redirectWithError('Não foi possível validar a atualização cadastral. Faça login novamente.', $authSessionKeys, $authCookieName, $BASE_para_URL);
    }

    $missingFieldNames = loginProfileMissingFieldNames($profileModalData);
    $sobrenome = in_array('Sobrenome', $missingFieldNames, true)
        ? trim((string)($_POST['Sobrenome'] ?? ''))
        : trim((string)($profileModalData['Sobrenome'] ?? ''));
    $cargo = in_array('Cargo', $missingFieldNames, true)
        ? trim((string)($_POST['Cargo'] ?? ''))
        : trim((string)($profileModalData['Cargo'] ?? ''));
    $whatsApp = in_array('WhatsApp', $missingFieldNames, true)
        ? trim((string)($_POST['WhatsApp'] ?? ''))
        : trim((string)($profileModalData['WhatsApp'] ?? ''));
    $nascimento = in_array('Nascimento', $missingFieldNames, true)
        ? loginNormalizeDate((string)($_POST['Nascimento'] ?? ''))
        : loginNormalizeDate((string)($profileModalData['Nascimento'] ?? ''));
    $foto = (string)($_SESSION['pending_profile_photo'] ?? ($profileModalData['Foto'] ?? ''));

    if ($sobrenome === '') {
        $profileModalErrors[] = 'Informe o sobrenome.';
    }
    if ($cargo === '') {
        $profileModalErrors[] = 'Informe o cargo.';
    }
    if ($whatsApp === '') {
        $profileModalErrors[] = 'Informe o WhatsApp.';
    }
    if ($nascimento === null) {
        $profileModalErrors[] = 'Informe a data de nascimento.';
    }

    if (in_array('Foto', $missingFieldNames, true)) {
        try {
            $foto = loginUploadProfilePhoto($_FILES['foto'] ?? null, $foto);
            $_SESSION['pending_profile_photo'] = $foto;
        } catch (RuntimeException $e) {
            $profileModalErrors[] = $e->getMessage();
        }
    }

    $candidate = [
        'Sobrenome' => $sobrenome,
        'Cargo' => $cargo,
        'WhatsApp' => $whatsApp,
        'Nascimento' => $nascimento,
        'Foto' => $foto,
    ];
    $profileModalMissing = loginProfileMissingFields($candidate);
    if ($profileModalMissing !== []) {
        $profileModalErrors[] = 'Preencha todos os dados obrigatórios para continuar.';
    }

    if ($profileModalErrors === []) {
        $stmt = $pdo->prepare(
            "UPDATE tbUser
                SET Sobrenome = ?, Nascimento = ?, Cargo = ?, WhatsApp = ?, Foto = ?, IdColaboradorAlt = ?, TimeAlterado = ?
              WHERE IdColaborador = ?"
        );
        $stmt->execute([
            $sobrenome,
            $nascimento,
            $cargo,
            $whatsApp,
            $foto,
            (int)$profileModalData['IdColaborador'],
            date('Y-m-d H:i:s'),
            (int)$profileModalData['IdColaborador'],
        ]);

        unset($_SESSION['pending_profile_user_id'], $_SESSION['pending_profile_token'], $_SESSION['pending_profile_redirect'], $_SESSION['pending_profile_photo']);
        clearAuthState($authSessionKeys, $authCookieName, $BASE_para_URL);
        header('Location: ' . rtrim((string)$BASE_para_URL, '/') . '/login/?sucesso=' . urlencode('Cadastro atualizado. Faça login novamente para continuar.'));
        exit();
    }

    $profileModalData = array_merge($profileModalData, $candidate);
    $showProfileModal = true;
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['confirma'])) {

    // Decodifica o cookie para obter os dados
    $confirma = json_decode(base64_decode($_GET['confirma']), true);

    // Recupera o IdColaborador e o token
    $email = $confirma['Email'];
    $senhaDigitada = $confirma['token'];

    // Prepara a consulta SQL para obter o hash da senha
    $sql = "SELECT u.IdColaborador, u.IdPermissao, u.Foto, u.Nome, u.Sobrenome, u.Nascimento, u.Cargo, u.WhatsApp, u.Senha, p.NomePermissao
        FROM tbUser u 
        JOIN tbPermissao p ON u.IdPermissao = p.IdPermissao 
        WHERE u.Email = ? AND u.Habilitado = 1";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(1, $email, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $hashed_password = $result['Senha'];
        $IdColaborador = $result['IdColaborador'];

        // Verifica se a senha inserida corresponde ao hash armazenado
        if (password_verify($senhaDigitada, $hashed_password)) {
            // Comentário ajustado para UTF-8.
            $hora_atual = time() * 10;
            $mes_atual = date("M");
            $token = $hora_atual . $mes_atual;

            // Comentário ajustado para UTF-8.
            if (!verificaHorarioPermissao($pdo, $result['IdPermissao'])) {
                redirectWithError('Você não está autorizado a acessar o sistema neste horário.', $authSessionKeys, $authCookieName, $BASE_para_URL);
                exit;
            }

            $profileModalMissing = loginProfileMissingFields($result);
            if ($profileModalMissing !== []) {
                $redirectUrl = isset($_GET['redirect']) ? urldecode((string)$_GET['redirect']) : $BASE_para_URL . "/dashboard/";
                loginPreparePendingProfile($result, $profileModalMissing, $redirectUrl);
                clearAuthState($authSessionKeys, $authCookieName, $BASE_para_URL);
                $profileModalData = $result;
                $showProfileModal = true;
            } else {
            $_SESSION['token'] = $token;
            $_SESSION['Cod'] = $IdColaborador;
            $_SESSION['Foto'] = $result['Foto'];
            $_SESSION['Nome'] = $result['Nome'];
            $_SESSION['Sobrenome'] = $result['Sobrenome'];
            $_SESSION['Tipo'] = $result['NomePermissao'];
            $_SESSION['IdPermissao'] = $result['IdPermissao'];


            // Comentário ajustado para UTF-8.
            $sqlPerms = "SELECT Pagina FROM tbPermissaoPagina WHERE IdPermissao = ?";
            $stmtPerms = $pdo->prepare($sqlPerms);
            $stmtPerms->execute([$result['IdPermissao']]);
            $paginasPermitidas = $stmtPerms->fetchAll(PDO::FETCH_COLUMN);

            // Comentário ajustado para UTF-8.
            $_SESSION['PaginasPermitidas'] = $paginasPermitidas;

            // Comentário ajustado para UTF-8.
            $_SESSION['ultimoAcessoData'] = "Agora";
            $_SESSION['auth_cookie_name'] = $authCookieName;

            setcookie($authCookieName, '', time() - 3600, "/");
            setcookie($authCookieName, '', time() - 3600, "/", "", false, false); // Sem secure e httponly

            // Comentário ajustado para UTF-8.
            if (isset($_POST['lembrar']) && $_POST['lembrar'] == 'lembrar') {
                // Configura o cookie para expirar em 30 dias
                $cookie_value = base64_encode(json_encode([
                    'token' => $token,
                    'id' => $IdColaborador
                ]));
                setcookie($authCookieName, $cookie_value, time() + (30 * 24 * 60 * 60), "/", "", true, true);
            }

            // Atualiza o campo 'Habilitado' para conformar login por e-mail
            $update_sql = "UPDATE tbUser SET Email_confere = 1 WHERE IdColaborador = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->bindParam(1, $IdColaborador, PDO::PARAM_STR);
            $update_stmt->execute();

            // Comentário ajustado para UTF-8.
            $redirect_url = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : $BASE_para_URL . "/dashboard/";
            header("Location: " . $redirect_url);
            exit();
            }
        } else {
            // Comentário ajustado para UTF-8.
            redirectWithError('E-mail ou senha incorretos. Tente novamente.', $authSessionKeys, $authCookieName, $BASE_para_URL);
            die;
        }
    } else {
        // Comentário ajustado para UTF-8.
        redirectWithError('E-mail ou senha não conferem.', $authSessionKeys, $authCookieName, $BASE_para_URL);
        die;
    }
}

// Verifica se o cookie de login existe
if (!$showProfileModal && isset($_COOKIE[$authCookieName]) && !isset($_GET['logout'])) {
    // Decodifica o cookie para obter os dados
    $cookie_data = json_decode(base64_decode($_COOKIE[$authCookieName]), true);

    // Recupera o IdColaborador e o token
    if (!is_array($cookie_data) || !isset($cookie_data['id'], $cookie_data['token'])) {
        redirectWithError('Sessão expirada. Faça login novamente.', $authSessionKeys, $authCookieName, $BASE_para_URL);
    }
    $IdColaborador = $cookie_data['id'];
    $token = $cookie_data['token'];

    // Comentário ajustado para UTF-8.
    $sql = "SELECT u.IdColaborador, u.IdPermissao, u.Foto, u.Nome, u.Sobrenome, u.Nascimento, u.Cargo, u.WhatsApp, u.Senha, p.NomePermissao
        FROM tbUser u 
        JOIN tbPermissao p ON u.IdPermissao = p.IdPermissao 
        WHERE IdColaborador = ? AND u.Habilitado = 1";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(1, $IdColaborador, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $profileModalMissing = loginProfileMissingFields($result);
        if ($profileModalMissing !== []) {
            $redirectUrl = isset($_GET['redirect']) ? urldecode((string)$_GET['redirect']) : $BASE_para_URL . '/dashboard/';
            loginPreparePendingProfile($result, $profileModalMissing, $redirectUrl);
            clearAuthState($authSessionKeys, $authCookieName, $BASE_para_URL);
            $profileModalData = $result;
            $showProfileModal = true;
        } else {
        // Comentário ajustado para UTF-8.
        // Comentário ajustado para UTF-8.
        $_SESSION['token'] = $token;
        $_SESSION['Cod'] = $result['IdColaborador'];
        $_SESSION['Foto'] = $result['Foto'];
        $_SESSION['Nome'] = $result['Nome'];
        $_SESSION['Sobrenome'] = $result['Sobrenome'];
        $_SESSION['Tipo'] = $result['NomePermissao'];
        $_SESSION['IdPermissao'] = $result['IdPermissao'];


        // Comentário ajustado para UTF-8.
        $sqlPerms = "SELECT Pagina FROM tbPermissaoPagina WHERE IdPermissao = ?";
        $stmtPerms = $pdo->prepare($sqlPerms);
        $stmtPerms->execute([$result['IdPermissao']]);
        $paginasPermitidas = $stmtPerms->fetchAll(PDO::FETCH_COLUMN);

        // Comentário ajustado para UTF-8.
        $_SESSION['PaginasPermitidas'] = $paginasPermitidas;

        // Comentário ajustado para UTF-8.
        $_SESSION['ultimoAcessoData'] = "Agora";
        $_SESSION['auth_cookie_name'] = $authCookieName;

        // Comentário ajustado para UTF-8.
        if (!verificaHorarioPermissao($pdo, $result['IdPermissao'])) {
                redirectWithError('Você não está autorizado a acessar o sistema neste horário.', $authSessionKeys, $authCookieName, $BASE_para_URL);
            exit;
        }

        // Comentário ajustado para UTF-8.
        $erro = isset($_GET['erro']) ? '?erro=' . $_GET['erro'] : '';
        $redirect_url = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : $BASE_para_URL . '/dashboard/';
        header("Location: " . $redirect_url . $erro);
        exit();
        }
    } else {
        redirectWithError('Sessão expirada. Faça login novamente.', $authSessionKeys, $authCookieName, $BASE_para_URL);
    }
}

// Verifica se os dados foram submetidos via POST
if (!$showProfileModal && $_SERVER["REQUEST_METHOD"] == "POST") {

    // Verifica se os campos de e-mail e senha foram preenchidos
    if (!empty($_POST['email']) && !empty($_POST['password'])) {

        $email = $_POST['email'];
        $senhaDigitada = $_POST['password'];

        // Prepara a consulta SQL para obter o hash da senha
        $sql = "SELECT u.IdColaborador, u.IdPermissao, u.Foto, u.Nome, u.Sobrenome, u.Nascimento, u.Cargo, u.WhatsApp, u.Senha, p.NomePermissao
        FROM tbUser u 
        JOIN tbPermissao p ON u.IdPermissao = p.IdPermissao 
        WHERE u.Email = ? AND u.Habilitado = 1";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(1, $email, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $hashed_password = $result['Senha'];
            $IdColaborador = $result['IdColaborador'];

            // Verifica se a senha inserida corresponde ao hash armazenado
            if (password_verify($senhaDigitada, $hashed_password)) {
                // Comentário ajustado para UTF-8.
                $hora_atual = time() * 10;
                $mes_atual = date("M");
                $token = $hora_atual . $mes_atual;

                // Comentário ajustado para UTF-8.
                if (!verificaHorarioPermissao($pdo, $result['IdPermissao'])) {
                redirectWithError('Você não está autorizado a acessar o sistema neste horário.', $authSessionKeys, $authCookieName, $BASE_para_URL);
                    exit;
                }

                $profileModalMissing = loginProfileMissingFields($result);
                if ($profileModalMissing !== []) {
                    $redirectUrl = isset($_GET['redirect']) ? urldecode((string)$_GET['redirect']) : $BASE_para_URL . "/dashboard/";
                    loginPreparePendingProfile($result, $profileModalMissing, $redirectUrl);
                    clearAuthState($authSessionKeys, $authCookieName, $BASE_para_URL);
                    $profileModalData = $result;
                    $showProfileModal = true;
                } else {
                $_SESSION['token'] = $token;
                $_SESSION['Cod'] = $IdColaborador;
                $_SESSION['Foto'] = $result['Foto'];
                $_SESSION['Nome'] = $result['Nome'];
                $_SESSION['Sobrenome'] = $result['Sobrenome'];
                $_SESSION['Tipo'] = $result['NomePermissao'];
                $_SESSION['IdPermissao'] = $result['IdPermissao'];


                // Comentário ajustado para UTF-8.
                $sqlPerms = "SELECT Pagina FROM tbPermissaoPagina WHERE IdPermissao = ?";
                $stmtPerms = $pdo->prepare($sqlPerms);
                $stmtPerms->execute([$result['IdPermissao']]);
                $paginasPermitidas = $stmtPerms->fetchAll(PDO::FETCH_COLUMN);

                // Comentário ajustado para UTF-8.
                $_SESSION['PaginasPermitidas'] = $paginasPermitidas;

                // Comentário ajustado para UTF-8.
                $_SESSION['ultimoAcessoData'] = "Agora";
                $_SESSION['auth_cookie_name'] = $authCookieName;

                setcookie($authCookieName, '', time() - 3600, "/");
                setcookie($authCookieName, '', time() - 3600, "/", "", false, false); // Sem secure e httponly

                // Comentário ajustado para UTF-8.
                if (isset($_POST['lembrar']) && $_POST['lembrar'] == 'lembrar') {
                    // Configura o cookie para expirar em 30 dias
                    $cookie_value = base64_encode(json_encode([
                        'token' => $token,
                        'id' => $IdColaborador
                    ]));
                    setcookie($authCookieName, $cookie_value, time() + (30 * 24 * 60 * 60), "/", "", true, true);
                }

                // Comentário ajustado para UTF-8.
                $redirect_url = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : $BASE_para_URL . "/dashboard/";
                header("Location: " . $redirect_url);
                exit();
                }
            } else {
                // Comentário ajustado para UTF-8.
                redirectWithError('E-mail ou senha incorretos. Tente novamente.', $authSessionKeys, $authCookieName, $BASE_para_URL);
                die;
            }
        } else {
            // Comentário ajustado para UTF-8.
            redirectWithError('E-mail ou senha não conferem.', $authSessionKeys, $authCookieName, $BASE_para_URL);
            die;
        }
    } else {
        // Comentário ajustado para UTF-8.
        redirectWithError('Por favor, preencha todos os campos.', $authSessionKeys, $authCookieName, $BASE_para_URL);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Responsive Admin &amp; Dashboard Template based on Bootstrap 5">

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="shortcut icon" href="<?php echo $BASE_para_URL; ?>/assets/img/icons/<?php echo htmlspecialchars($shortcutIcon); ?>" />

    <title>Login | ITEVA - Gestão de OSCs</title>

    <link href="<?php echo $BASE_para_URL; ?>/assets/css/app.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js?v=<?php echo $appJsVersion; ?>"></script>
    <style>
        .input-group {
            position: relative;
        }

        #passwordInput {
            padding-right: 2.5rem;
        }

        #togglePassword {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            outline: none;
        }

        @keyframes fadeInRight {
            0% {
                opacity: 0;
                transform: translateX(-50px);
            }

            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeInTop {
            0% {
                opacity: 0;
                transform: translateY(-50px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInBotton {
            0% {
                opacity: 0;
                transform: translateY(+50px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .texto {
            opacity: 0;
            animation: fadeInTop 0.5s ease-in-out forwards;
        }

        .caixa {
            opacity: 0;
            animation: fadeInBotton 0.5s ease-in-out forwards;
        }

        img {
            opacity: 0;
            animation: fadeInRight 1s ease-in-out forwards;
        }

        .login-alert-danger {
            width: 100%;
            color: #842029;
            background-color: #f8d7da;
            border: 1px solid #f5c2c7;
            border-radius: 0.25rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .login-alert-danger .bi {
            fill: currentColor;
        }

        .login-alert-success {
            width: 100%;
            color: #0f5132;
            background-color: #d1e7dd;
            border: 1px solid #badbcc;
            border-radius: 0.25rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .login-alert-success .bi {
            fill: currentColor;
        }

        body.profile-modal-open {
            overflow: hidden;
        }

        .profile-completion-overlay {
            position: fixed;
            inset: 0;
            z-index: 1050;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(15, 23, 42, 0.82);
        }

        .profile-completion-panel {
            width: min(100%, 560px);
            max-height: calc(100vh - 2rem);
            overflow-y: auto;
            background: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.32);
        }

        .profile-completion-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #dee2e6;
        }

        .profile-completion-body {
            padding: 1.5rem;
        }

        .profile-completion-footer {
            padding: 1.25rem 1.5rem;
            border-top: 1px solid #dee2e6;
            background: #f8f9fa;
        }

        .profile-completion-title {
            margin: 0;
            font-size: 1.125rem;
            font-weight: 600;
            color: #212529;
        }

        .profile-completion-text {
            color: #495057;
        }

        .profile-completion-required {
            color: #dc3545;
        }
    </style>
</head>

<body class="<?php echo $showProfileModal ? 'profile-modal-open' : ''; ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="d-none">
        <symbol id="check-circle-fill" viewBox="0 0 16 16">
            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM6.97 11.03a.75.75 0 0 0 1.08.022l3.992-4.99a.75.75 0 1 0-1.17-.94L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06l2.646 2.647z"/>
        </symbol>
        <symbol id="exclamation-triangle-fill" viewBox="0 0 16 16">
            <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.71c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
        </symbol>
    </svg>
    <main class="d-flex w-100">
        <div class="container d-flex flex-column">
            <div class="row vh-100">
                <div class="col-sm-9 col-md-7 col-lg-5 mx-auto d-table h-100">
                    <div class="d-table-cell align-middle">
                        <div class="text-center mt-4">
                            <h1 class="h2 texto">Bem-vindo de volta</h1>
                            <p class="lead texto">Faça login em sua conta para continuar</p>
                        </div>

                        <?php if (isset($_GET['sucesso']) && trim((string)$_GET['sucesso']) !== '') { ?>
                            <div class="alert alert-success login-alert-success d-flex align-items-center caixa" role="alert">
                                <svg class="bi flex-shrink-0 me-2" role="img" aria-label="Sucesso: " width="20" height="20">
                                    <use xlink:href="#check-circle-fill"></use>
                                </svg>
                                <div>
                                    <?php echo htmlspecialchars((string)$_GET['sucesso'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </div>
                        <?php } ?>
                        <?php if (isset($_GET['erro']) && trim((string)$_GET['erro']) !== '') { ?>
                            <div class="alert alert-danger login-alert-danger d-flex align-items-center caixa" role="alert">
                                <svg class="bi flex-shrink-0 me-2" role="img" aria-label="Erro: " width="20" height="20">
                                    <use xlink:href="#exclamation-triangle-fill"></use>
                                </svg>
                                <div>
                                    <?php echo htmlspecialchars((string)$_GET['erro'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </div>
                        <?php } ?>
                        <div class="card caixa">
                            <div class="card-body caixa">
                                <div class="m-sm-4">
                                    <div class="text-center mb-5">
                                        <img src="<?php echo $BASE_para_URL; ?>/assets/img/sistema/logo-bgbranco.png" alt="ITEVA LOGO" class="img-fluid fadeInRight" width="200" />
                                    </div>
                                    <form method="post" action="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/login/">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">E-mail</label>
                                            <input class="form-control form-control-lg" type="email" id="email" name="email" placeholder="Digite seu e-mail" autocomplete="on" />
                                        </div>
                                        <div class="input-group">
                                            <input class="form-control form-control-lg" type="password" name="password" id="passwordInput" placeholder="Digite sua senha" />
                                            <button class="btn" type="button" id="togglePassword">
                                                <i id="eyeIcon" class="align-middle" data-feather="eye"></i>
                                            </button>
                                        </div>
                                        <div class="mb-3">
                                            <small><a href="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/esqueci-senha/">Esqueceu a senha?</a></small>
                                        </div>
                                        <div>
                                            <label class="form-check">
                                                <input class="form-check-input" type="checkbox" value="lembrar" name="lembrar" checked>
                                                <span class="form-check-label">Lembre-se de mim da próxima vez</span>
                                                <p><br></p>
                                            </label>
                                        </div>
                                        <div class="text-center mt-3">
                                            <button type="submit" class="btn btn-lg btn-primary botao" id="conectar">Conecte-se</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php if ($showProfileModal && is_array($profileModalData)) {
        $camposPendentesPerfil = loginProfileMissingFieldNames($profileModalData);
        $fotoAtualPerfil = (string)($profileModalData['Foto'] ?? '');
        $fotoObrigatoria = in_array(strtolower(trim($fotoAtualPerfil)), ['', 'padrao.jfif', 'padrao.jpg', 'padrao.png'], true);
        $nascimentoPerfil = '';
        if (!empty($profileModalData['Nascimento'])) {
            $dtPerfil = DateTime::createFromFormat('Y-m-d', (string)$profileModalData['Nascimento']);
            $nascimentoPerfil = $dtPerfil ? $dtPerfil->format('Y-m-d') : '';
        }
    ?>
        <div class="profile-completion-overlay" id="completeProfileModal" role="dialog" aria-modal="true" aria-labelledby="completeProfileModalLabel">
            <div class="profile-completion-panel">
                    <form method="post" action="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/login/" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="action" value="complete_profile">
                        <input type="hidden" name="profile_token" value="<?php echo htmlspecialchars((string)($_SESSION['pending_profile_token'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="profile-completion-header">
                            <h5 class="profile-completion-title" id="completeProfileModalLabel">Conclua seu cadastro</h5>
                        </div>
                        <div class="profile-completion-body">
                            <p class="profile-completion-text mb-3">Para acessar o sistema, atualize seus dados pessoais obrigatórios.</p>
                            <?php if ($profileModalMissing !== []) { ?>
                                <div class="alert alert-warning py-2">
                                    Pendências: <?php echo htmlspecialchars(implode(', ', $profileModalMissing), ENT_QUOTES, 'UTF-8'); ?>.
                                </div>
                            <?php } ?>
                            <?php if ($profileModalErrors !== []) { ?>
                                <div class="alert alert-danger py-2">
                                    <?php echo htmlspecialchars(implode(' ', $profileModalErrors), ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php } ?>
                            <?php if (in_array('Nascimento', $camposPendentesPerfil, true)) { ?>
                            <div class="mb-3">
                                <label for="profileNascimento" class="form-label">Data de nascimento <span class="profile-completion-required">*</span></label>
                                <input type="date" class="form-control" id="profileNascimento" name="Nascimento" value="<?php echo htmlspecialchars($nascimentoPerfil, ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <?php } ?>
                            <?php if (in_array('Sobrenome', $camposPendentesPerfil, true)) { ?>
                            <div class="mb-3">
                                <label for="profileSobrenome" class="form-label">Sobrenome <span class="profile-completion-required">*</span></label>
                                <input type="text" class="form-control" id="profileSobrenome" name="Sobrenome" value="<?php echo htmlspecialchars((string)($profileModalData['Sobrenome'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="family-name">
                            </div>
                            <?php } ?>
                            <?php if (in_array('Cargo', $camposPendentesPerfil, true)) { ?>
                            <div class="mb-3">
                                <label for="profileCargo" class="form-label">Cargo <span class="profile-completion-required">*</span></label>
                                <input type="text" class="form-control" id="profileCargo" name="Cargo" value="<?php echo htmlspecialchars((string)($profileModalData['Cargo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required maxlength="120" autocomplete="organization-title">
                            </div>
                            <?php } ?>
                            <?php if (in_array('WhatsApp', $camposPendentesPerfil, true)) { ?>
                            <div class="mb-3">
                                <label for="profileWhatsApp" class="form-label">WhatsApp <span class="profile-completion-required">*</span></label>
                                <input type="text" class="form-control" id="profileWhatsApp" name="WhatsApp" value="<?php echo htmlspecialchars((string)($profileModalData['WhatsApp'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="tel">
                            </div>
                            <?php } ?>
                            <?php if (in_array('Foto', $camposPendentesPerfil, true)) { ?>
                            <div class="mb-0">
                                <label for="profileFoto" class="form-label">Foto <?php echo $fotoObrigatoria ? '<span class="profile-completion-required">*</span>' : ''; ?></label>
                                <input type="file" class="form-control" id="profileFoto" name="foto" accept="image/png,image/jpeg" <?php echo $fotoObrigatoria ? 'required' : ''; ?>>
                            </div>
                            <?php } ?>
                        </div>
                        <div class="profile-completion-footer">
                            <button type="submit" class="btn btn-primary w-100">Salvar dados obrigatórios</button>
                        </div>
                    </form>
            </div>
        </div>
    <?php } ?>
    <footer class="footer">
        <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php' ?>
    </footer>

</body>
<script>
    const togglePasswordBtn = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('passwordInput');

    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);

        const eyeIcon = document.getElementById('eyeIcon');
        eyeIcon.setAttribute('data-feather', type === 'password' ? 'eye' : 'eye-off');
        if (typeof feather !== "undefined" && typeof feather.replace === "function") {
            feather.replace();
        }

        const conectarBtn = document.getElementById('conectar');
        if (conectarBtn) {
            conectarBtn.focus();
        }
    });
    }
</script>
<?php if ($showProfileModal) { ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const firstInvalidOrEmpty = document.querySelector('#completeProfileModal input:invalid, #completeProfileModal input:not([type="hidden"])');
        if (firstInvalidOrEmpty) {
            firstInvalidOrEmpty.focus();
        }
    });
</script>
<?php } ?>

</html>














