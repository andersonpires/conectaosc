<?php
session_set_cookie_params(['httponly' => true]);
ini_set('session.gc_maxlifetime', 86400); // 24 horas
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

$authCookieName = 'login_v3';
$cookieConfigPath = __DIR__ . '/temp/setCookie.env';
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
    header("Location: login.php?erro=" . urlencode($message));
    exit();
}

if (isset($_GET['erro']) && $_GET['erro'] !== '') {
    clearAuthState($authSessionKeys, $authCookieName);
}

$development_hosts = [
    'localhost',
    '127.0.0.1'
];

// 1. Verifica se o host atual NÃO está na lista de desenvolvimento
$is_production = !in_array($_SERVER['HTTP_HOST'], $development_hosts);

// 2. Verifica se a conexão NÃO é HTTPS (está em 'http')
$is_http = (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off');

// 3. SÓ redireciona se estiver em HTTP E for produção
if ($is_http && $is_production) {

    // Constrói a URL de destino com "https"
    $url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

    // Envia o header de redirecionamento permanente (301)
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $url);
    exit();
}

if (
    !isset($_SESSION['BASE_PATH']) || $_SESSION['BASE_PATH'] === '' ||
    !isset($_SESSION['BASE_URL']) || $_SESSION['BASE_URL'] === ''
) {

    $_SESSION['BASE_PATH'] = __DIR__;
    $_SESSION['BASE_URL'] = dirname($_SERVER['SCRIPT_NAME']);
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/funcoes.php';
require_once __DIR__ . '/conectabd/conexao.php';
$config = $pdo->query("SELECT * FROM tbConfig LIMIT 1")->fetch(PDO::FETCH_ASSOC);
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
            // Credenciais válidas, armazena os detalhes do usuário na sessão
            $hora_atual = time() * 10;
            $mes_atual = date("M");
            $token = $hora_atual . $mes_atual;

            // VERIFICA SE O USUÁRIO PODE ACESSAR NESTE DIA E HORA
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


            // Agora buscar as permissões de página:
            $sqlPerms = "SELECT Pagina FROM tbPermissaoPagina WHERE IdPermissao = ?";
            $stmtPerms = $pdo->prepare($sqlPerms);
            $stmtPerms->execute([$result['IdPermissao']]);
            $paginasPermitidas = $stmtPerms->fetchAll(PDO::FETCH_COLUMN);

            // Armazena o array com as páginas permitidas na sessão
            $_SESSION['PaginasPermitidas'] = $paginasPermitidas;

            // Dados do último acesso
            $_SESSION['ultimoAcessoData'] = "Agora";
            $_SESSION['BASE_PATH'] = BASE_PATH;
            $_SESSION['BASE_URL'] = BASE_URL;

            setcookie($authCookieName, '', time() - 3600, "/");
            setcookie($authCookieName, '', time() - 3600, "/", "", false, false); // Sem secure e httponly

            // Se o usuário marcar "lembrar", cria um cookie para manter o login ativo
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

            // Redireciona para a página principal após o login
            $redirect_url = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : BASE_URL . "/index.php";
            header("Location: " . $redirect_url);
            exit();
        } else {
            // Credenciais inválidas, exibe um alerta de erro
            redirectWithError('E-mail ou senha incorretos. Tente novamente.', $authSessionKeys, $authCookieName);
            die;
        }
    } else {
        // Usuário não encontrado, exibe um alerta de erro
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

    // Verifica se o IdColaborador é válido no banco de dados
    $sql = "SELECT u.IdColaborador, u.IdPermissao, u.Foto, u.Nome, u.Sobrenome, u.Senha, p.NomePermissao 
        FROM tbUser u 
        JOIN tbPermissao p ON u.IdPermissao = p.IdPermissao 
        WHERE IdColaborador = ? AND u.Habilitado = 1";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(1, $IdColaborador, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // A senha não é necessária, pois estamos utilizando o token do cookie
        // Armazenando os dados na sessão
        $_SESSION['token'] = $token;
        $_SESSION['Cod'] = $result['IdColaborador'];
        $_SESSION['Foto'] = $result['Foto'];
        $_SESSION['Nome'] = $result['Nome'];
        $_SESSION['Sobrenome'] = $result['Sobrenome'];
        $_SESSION['Tipo'] = $result['NomePermissao'];
        $_SESSION['IdPermissao'] = $result['IdPermissao'];


        // Agora buscar as permissões de página:
        $sqlPerms = "SELECT Pagina FROM tbPermissaoPagina WHERE IdPermissao = ?";
        $stmtPerms = $pdo->prepare($sqlPerms);
        $stmtPerms->execute([$result['IdPermissao']]);
        $paginasPermitidas = $stmtPerms->fetchAll(PDO::FETCH_COLUMN);

        // Armazena o array com as páginas permitidas na sessão
        $_SESSION['PaginasPermitidas'] = $paginasPermitidas;

        // Dados do último acesso
        $_SESSION['ultimoAcessoData'] = "Agora";
        $_SESSION['BASE_PATH'] = BASE_PATH;
        $_SESSION['BASE_URL'] = BASE_URL;

        // VERIFICA SE O USUÁRIO PODE ACESSAR NESTE DIA E HORA
        if (!verificaHorarioPermissao($pdo, $result['IdPermissao'])) {
                redirectWithError('Voce nao esta autorizado a acessar o sistema neste horario.', $authSessionKeys, $authCookieName);
            exit;
        }

        // Redireciona para a página de destino (se houver) ou para a página principal
        $erro = isset($_GET['erro']) ? '?erro=' . $_GET['erro'] : '';
        $redirect_url = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : BASE_URL . '/index.php';
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
                // Credenciais válidas, armazena os detalhes do usuário na sessão
                $hora_atual = time() * 10;
                $mes_atual = date("M");
                $token = $hora_atual . $mes_atual;

                // VERIFICA SE O USUÁRIO PODE ACESSAR NESTE DIA E HORA
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


                // Agora buscar as permissões de página:
                $sqlPerms = "SELECT Pagina FROM tbPermissaoPagina WHERE IdPermissao = ?";
                $stmtPerms = $pdo->prepare($sqlPerms);
                $stmtPerms->execute([$result['IdPermissao']]);
                $paginasPermitidas = $stmtPerms->fetchAll(PDO::FETCH_COLUMN);

                // Armazena o array com as páginas permitidas na sessão
                $_SESSION['PaginasPermitidas'] = $paginasPermitidas;

                // Dados do último acesso
                $_SESSION['ultimoAcessoData'] = "Agora";
                $_SESSION['BASE_PATH'] = BASE_PATH;
                $_SESSION['BASE_URL'] = BASE_URL;

                setcookie($authCookieName, '', time() - 3600, "/");
                setcookie($authCookieName, '', time() - 3600, "/", "", false, false); // Sem secure e httponly

                // Se o usuário marcar "lembrar", cria um cookie para manter o login ativo
                if (isset($_POST['lembrar']) && $_POST['lembrar'] == 'lembrar') {
                    // Configura o cookie para expirar em 30 dias
                    $cookie_value = base64_encode(json_encode([
                        'token' => $token,
                        'id' => $IdColaborador
                    ]));
                    setcookie($authCookieName, $cookie_value, time() + (30 * 24 * 60 * 60), "/", "", true, true);
                }

                // Redireciona para a página principal após o login
                $redirect_url = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : BASE_URL . "/index.php";
                header("Location: " . $redirect_url);
                exit();
            } else {
                // Credenciais inválidas, exibe um alerta de erro
                redirectWithError('E-mail ou senha incorretos. Tente novamente.', $authSessionKeys, $authCookieName);
                die;
            }
        } else {
            // Usuário não encontrado, exibe um alerta de erro
            redirectWithError('E-mail ou senha nao conferem.', $authSessionKeys, $authCookieName);
            die;
        }
    } else {
        // Caso os campos não tenham sido preenchidos, exibe um alerta de erro
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
    <link rel="shortcut icon" href="<?php echo $_SESSION['BASE_URL']; ?>/assets/img/icons/<?php echo $config['ShortcutIcon']; ?>" />

    <title>Login | ITEVA - Gestão de OSCs</title>

    <link href="<?php echo BASE_URL; ?>/assets/css/app.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>
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
                            <p class="lead texto">Faça login em sua conta para continuar</p>
                        </div>

                        <div class="card caixa">
                            <div class="card-body caixa">
                                <div class="m-sm-4">
                                    <div class="text-center mb-5">
                                        <img src="<?php echo BASE_URL; ?>/assets/img/sistema/logo-bgbranco.png" alt="ITEVA LOGO" class="img-fluid fadeInRight" width="200" />
                                    </div>
                                    <form method="post" action="login.php">
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
        <?php require_once BASE_PATH . '/template/footer.php' ?>
    </footer>

</body>
<script>
    const togglePasswordBtn = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('passwordInput');

    togglePasswordBtn.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);

        const eyeIcon = document.getElementById('eyeIcon');
        eyeIcon.setAttribute('data-feather', type === 'password' ? 'eye' : 'eye-off');
        feather.replace();

        conectar.focus();
    });
</script>

</html>








