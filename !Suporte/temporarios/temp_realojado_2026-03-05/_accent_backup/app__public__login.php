<?php
$ROOT_PATH = dirname(__DIR__, 2);
$runtime = require $ROOT_PATH . '/bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'] ?? $runtime['base_path'];
$BASE_para_URL = $runtime['base_para_url'] ?? $runtime['base_url'];
if ($BASE_para_URL === '' || $BASE_para_URL === '/') {
    $requestPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (preg_match('#^(.*)/login/?$#', $requestPath, $matches)) {
        $BASE_para_URL = rtrim((string)($matches[1] ?? ''), '/');
    }
    if ($BASE_para_URL === '' || $BASE_para_URL === '/') {
        $fallbackPath = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
        if (str_ends_with($fallbackPath, '/public')) {
            $fallbackPath = substr($fallbackPath, 0, -7);
        }
        $BASE_para_URL = ($fallbackPath !== '' && $fallbackPath !== '/') ? $fallbackPath : '/' . basename(__DIR__);
    }
}

$authCookieName = 'login_v3';
$cookieConfigPath = $ROOT_PATH . '/temp/setCookie.env';
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

function clearAuthState($keys, $cookieName) {
    foreach ($keys as $key) {
        unset($_SESSION[$key]);
    }
    setcookie($cookieName, '', time() - 3600, "/");
    setcookie($cookieName, '', time() - 3600, "/", "", false, false);
}

function redirectWithError($message, $keys, $cookieName) {
    clearAuthState($keys, $cookieName);
    $requestPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $baseUrl = '';
    if (preg_match('#^(.*)/login/?$#', $requestPath, $matches)) {
        $baseUrl = rtrim((string)($matches[1] ?? ''), '/');
    }
    if ($baseUrl === '') {
        $scriptDir = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
        if (str_ends_with($scriptDir, '/public')) {
            $scriptDir = substr($scriptDir, 0, -7);
        }
        $baseUrl = $scriptDir;
    }
    if ($baseUrl === '') {
        $baseUrl = '/' . basename(__DIR__);
    }
    $target = ($baseUrl !== '' ? $baseUrl : '') . '/login/?erro=' . urlencode($message);
    header("Location: " . $target);
    exit();
}

if (isset($_GET['erro']) && $_GET['erro'] !== '') {
    clearAuthState($authSessionKeys, $authCookieName);
}

$development_hosts = [
    'localhost',
    '127.0.0.1'
];

// 1. Verifica se o host atual N?f?????T?f?????,???O est?f?????T?f??s?,? na lista de desenvolvimento
$is_production = !in_array($_SERVER['HTTP_HOST'], $development_hosts);

// 2. Verifica se a conex?f?????T?f??s?,?o N?f?????T?f?????,???O ?f?????T?f??s?,? HTTPS (est?f?????T?f??s?,? em 'http')
$is_http = (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off');

// 3. S?f?????T?f??s?,? redireciona se estiver em HTTP E for produ?f?????T?f??s?,??f?????T?f??s?,?o
if ($is_http && $is_production) {

    // Constr?f?????T?f??s?,?i a URL de destino com "https"
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
$config = $pdo->query("SELECT * FROM tbConfig LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$shortcutIcon = (string)($config['ShortcutIcon'] ?? 'icon-48x48.png');
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
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['confirma'])) {

    // Decodifica o cookie para obter os dados
    $confirma = json_decode(base64_decode($_GET['confirma']), true);

    // Recupera o IdColaborador e o token
    $email = $confirma['Email'];
    $senhaDigitada = $confirma['token'];

    // Prepara a consulta SQL para obter o hash da senha
    $sql = "SELECT u.IdColaborador, u.IdPermissao, u.Foto, u.Nome, u.Sobrenome, u.Senha, p.NomePermissao 
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
            // Credenciais v?f?????T?f??s?,?lidas, armazena os detalhes do usu?f?????T?f??s?,?rio na sess?f?????T?f??s?,?o
            $hora_atual = time() * 10;
            $mes_atual = date("M");
            $token = $hora_atual . $mes_atual;

            // VERIFICA SE O USU?f?????T?f??s?,?RIO PODE ACESSAR NESTE DIA E HORA
            if (!verificaHorarioPermissao($pdo, $result['IdPermissao'])) {
                redirectWithError('Voce nao esta autorizado a acessar o sistema neste horario.', $authSessionKeys, $authCookieName);
                exit;
            }

            $_SESSION['token'] = $token;
            $_SESSION['Cod'] = $IdColaborador;
            $_SESSION['Foto'] = $result['Foto'];
            $_SESSION['Nome'] = $result['Nome'];
            $_SESSION['Sobrenome'] = $result['Sobrenome'];
            $_SESSION['Tipo'] = $result['NomePermissao'];
            $_SESSION['IdPermissao'] = $result['IdPermissao'];


            // Agora buscar as permiss?f?????T?f??s?,?es de p?f?????T?f??s?,?gina:
            $sqlPerms = "SELECT Pagina FROM tbPermissaoPagina WHERE IdPermissao = ?";
            $stmtPerms = $pdo->prepare($sqlPerms);
            $stmtPerms->execute([$result['IdPermissao']]);
            $paginasPermitidas = $stmtPerms->fetchAll(PDO::FETCH_COLUMN);

            // Armazena o array com as p?f?????T?f??s?,?ginas permitidas na sess?f?????T?f??s?,?o
            $_SESSION['PaginasPermitidas'] = $paginasPermitidas;

            // Dados do ?f?????T?f??s?,?ltimo acesso
            $_SESSION['ultimoAcessoData'] = "Agora";

            setcookie($authCookieName, '', time() - 3600, "/");
            setcookie($authCookieName, '', time() - 3600, "/", "", false, false); // Sem secure e httponly

            // Se o usu?f?????T?f??s?,?rio marcar "lembrar", cria um cookie para manter o login ativo
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

            // Redireciona para a p?f?????T?f??s?,?gina principal ap?f?????T?f??s?,?s o login
            $redirect_url = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : $BASE_para_URL . "/dashboard/";
            header("Location: " . $redirect_url);
            exit();
        } else {
            // Credenciais inv?f?????T?f??s?,?lidas, exibe um alerta de erro
            redirectWithError('E-mail ou senha incorretos. Tente novamente.', $authSessionKeys, $authCookieName);
            die;
        }
    } else {
        // usu?f?????T?f??s?,?rio n?f?????T?f??s?,?o encontrado, exibe um alerta de erro
        redirectWithError('E-mail ou senha nao conferem.', $authSessionKeys, $authCookieName);
        die;
    }
}

// Verifica se o cookie de login existe
if (isset($_COOKIE[$authCookieName])) {
    // Decodifica o cookie para obter os dados
    $cookie_data = json_decode(base64_decode($_COOKIE[$authCookieName]), true);

    // Recupera o IdColaborador e o token
    if (!is_array($cookie_data) || !isset($cookie_data['id'], $cookie_data['token'])) {
        redirectWithError('Sessao expirada. Faca login novamente.', $authSessionKeys, $authCookieName);
    }
    $IdColaborador = $cookie_data['id'];
    $token = $cookie_data['token'];

    // Verifica se o IdColaborador ?f?????T?f??s?,? v?f?????T?f??s?,?lido no banco de dados
    $sql = "SELECT u.IdColaborador, u.IdPermissao, u.Foto, u.Nome, u.Sobrenome, u.Senha, p.NomePermissao 
        FROM tbUser u 
        JOIN tbPermissao p ON u.IdPermissao = p.IdPermissao 
        WHERE IdColaborador = ? AND u.Habilitado = 1";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(1, $IdColaborador, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // A senha n?f?????T?f??s?,?o ?f?????T?f??s?,? necess?f?????T?f??s?,?ria, pois estamos utilizando o token do cookie
        // Armazenando os dados na sess?f?????T?f??s?,?o
        $_SESSION['token'] = $token;
        $_SESSION['Cod'] = $result['IdColaborador'];
        $_SESSION['Foto'] = $result['Foto'];
        $_SESSION['Nome'] = $result['Nome'];
        $_SESSION['Sobrenome'] = $result['Sobrenome'];
        $_SESSION['Tipo'] = $result['NomePermissao'];
        $_SESSION['IdPermissao'] = $result['IdPermissao'];


        // Agora buscar as permiss?f?????T?f??s?,?es de p?f?????T?f??s?,?gina:
        $sqlPerms = "SELECT Pagina FROM tbPermissaoPagina WHERE IdPermissao = ?";
        $stmtPerms = $pdo->prepare($sqlPerms);
        $stmtPerms->execute([$result['IdPermissao']]);
        $paginasPermitidas = $stmtPerms->fetchAll(PDO::FETCH_COLUMN);

        // Armazena o array com as p?f?????T?f??s?,?ginas permitidas na sess?f?????T?f??s?,?o
        $_SESSION['PaginasPermitidas'] = $paginasPermitidas;

        // Dados do ?f?????T?f??s?,?ltimo acesso
        $_SESSION['ultimoAcessoData'] = "Agora";

        // VERIFICA SE O USU?f?????T?f??s?,?RIO PODE ACESSAR NESTE DIA E HORA
        if (!verificaHorarioPermissao($pdo, $result['IdPermissao'])) {
                redirectWithError('Voce nao esta autorizado a acessar o sistema neste horario.', $authSessionKeys, $authCookieName);
            exit;
        }

        // Redireciona para a p?f?????T?f??s?,?gina de destino (se houver) ou para a p?f?????T?f??s?,?gina principal
        $erro = isset($_GET['erro']) ? '?erro=' . $_GET['erro'] : '';
        $redirect_url = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : $BASE_para_URL . '/dashboard/';
        header("Location: " . $redirect_url . $erro);
        exit();
    } else {
        redirectWithError('Sessao expirada. Faca login novamente.', $authSessionKeys, $authCookieName);
    }
}

// Verifica se os dados foram submetidos via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Verifica se os campos de e-mail e senha foram preenchidos
    if (!empty($_POST['email']) && !empty($_POST['password'])) {

        $email = $_POST['email'];
        $senhaDigitada = $_POST['password'];

        // Prepara a consulta SQL para obter o hash da senha
        $sql = "SELECT u.IdColaborador, u.IdPermissao, u.Foto, u.Nome, u.Sobrenome, u.Senha, p.NomePermissao 
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
                // Credenciais v?f?????T?f??s?,?lidas, armazena os detalhes do usu?f?????T?f??s?,?rio na sess?f?????T?f??s?,?o
                $hora_atual = time() * 10;
                $mes_atual = date("M");
                $token = $hora_atual . $mes_atual;

                // VERIFICA SE O USU?f?????T?f??s?,?RIO PODE ACESSAR NESTE DIA E HORA
                if (!verificaHorarioPermissao($pdo, $result['IdPermissao'])) {
                redirectWithError('Voce nao esta autorizado a acessar o sistema neste horario.', $authSessionKeys, $authCookieName);
                    exit;
                }

                $_SESSION['token'] = $token;
                $_SESSION['Cod'] = $IdColaborador;
                $_SESSION['Foto'] = $result['Foto'];
                $_SESSION['Nome'] = $result['Nome'];
                $_SESSION['Sobrenome'] = $result['Sobrenome'];
                $_SESSION['Tipo'] = $result['NomePermissao'];
                $_SESSION['IdPermissao'] = $result['IdPermissao'];


                // Agora buscar as permiss?f?????T?f??s?,?es de p?f?????T?f??s?,?gina:
                $sqlPerms = "SELECT Pagina FROM tbPermissaoPagina WHERE IdPermissao = ?";
                $stmtPerms = $pdo->prepare($sqlPerms);
                $stmtPerms->execute([$result['IdPermissao']]);
                $paginasPermitidas = $stmtPerms->fetchAll(PDO::FETCH_COLUMN);

                // Armazena o array com as p?f?????T?f??s?,?ginas permitidas na sess?f?????T?f??s?,?o
                $_SESSION['PaginasPermitidas'] = $paginasPermitidas;

                // Dados do ?f?????T?f??s?,?ltimo acesso
                $_SESSION['ultimoAcessoData'] = "Agora";

                setcookie($authCookieName, '', time() - 3600, "/");
                setcookie($authCookieName, '', time() - 3600, "/", "", false, false); // Sem secure e httponly

                // Se o usu?f?????T?f??s?,?rio marcar "lembrar", cria um cookie para manter o login ativo
                if (isset($_POST['lembrar']) && $_POST['lembrar'] == 'lembrar') {
                    // Configura o cookie para expirar em 30 dias
                    $cookie_value = base64_encode(json_encode([
                        'token' => $token,
                        'id' => $IdColaborador
                    ]));
                    setcookie($authCookieName, $cookie_value, time() + (30 * 24 * 60 * 60), "/", "", true, true);
                }

                // Redireciona para a p?f?????T?f??s?,?gina principal ap?f?????T?f??s?,?s o login
                $redirect_url = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : $BASE_para_URL . "/dashboard/";
                header("Location: " . $redirect_url);
                exit();
            } else {
                // Credenciais inv?f?????T?f??s?,?lidas, exibe um alerta de erro
                redirectWithError('E-mail ou senha incorretos. Tente novamente.', $authSessionKeys, $authCookieName);
                die;
            }
        } else {
            // usu?f?????T?f??s?,?rio n?f?????T?f??s?,?o encontrado, exibe um alerta de erro
            redirectWithError('E-mail ou senha nao conferem.', $authSessionKeys, $authCookieName);
            die;
        }
    } else {
        // Caso os campos n?f?????T?f??s?,?o tenham sido preenchidos, exibe um alerta de erro
        redirectWithError('Por favor, preencha todos os campos.', $authSessionKeys, $authCookieName);
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

    <title>Login | ITEVA - Gest?o de OSCs</title>

    <link href="<?php echo $BASE_para_URL; ?>/assets/css/app.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
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
    </style>
</head>

<body>
    <main class="d-flex w-100">
        <div class="container d-flex flex-column">
            <div class="row vh-100">
                <div class="col-sm-9 col-md-7 col-lg-5 mx-auto d-table h-100">
                    <div class="d-table-cell align-middle">
                        <div class="text-center mt-4">
                            <h1 class="h2 texto">Bem-vindo de volta</h1>
                            <p class="lead texto">Fa?a login em sua conta para continuar</p>
                        </div>

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
                                            <small><a href="#">Esqueceu a senha?</a></small>
                                        </div>
                                        <div>
                                            <label class="form-check">
                                                <input class="form-check-input" type="checkbox" value="lembrar" name="lembrar" checked>
                                                <span class="form-check-label">Lembre-se de mim da pr?xima vez</span>
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
                        <?php if (isset($_GET['erro']) && $_GET['erro'] !== '') { ?>
                            <div class="text-danger mt-3 text-center">
                                <?php echo htmlspecialchars($_GET['erro']); ?>
                            </div>
                        <?php } ?>

                    </div>
                </div>
            </div>
        </div>
    </main>
    <footer class="footer">
        <?php require_once $BASE_para_PATH . '/app/template/footer.php' ?>
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

</html>











