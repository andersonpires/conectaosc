<?php
session_set_cookie_params(['httponly' => true]);
ini_set('session.gc_maxlifetime', 86400); // 24 horas
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');
$BASE_PATH = __DIR__;
$BASE_URL = dirname($_SERVER['SCRIPT_NAME']);

require_once __DIR__ . '/../conectabd/conexao.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verifica se os dados foram submetidos via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Verifica se os campos de e-mail e senha foram preenchidos
    if (!empty($_POST['email']) && !empty($_POST['password'])) {

        $email = $_POST['email'];
        $senhaDigitada = $_POST['password'];

        // Prepara a consulta SQL para obter o hash da senha
        $sql = "SELECT IdColaborador, Tipo, Foto, Nome, Senha FROM tbUser WHERE Email = ?";
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

                $_SESSION['token'] = $token;
                $_SESSION['Cod'] = $IdColaborador;
                $_SESSION['Foto'] = $result['Foto'];
                $_SESSION['Nome'] = $result['Nome'];
                $_SESSION['Tipo'] = $result['Tipo'];

                // Dados do último acesso
                $_SESSION['ultimoAcessoData'] = "Agora";

                // Atualiza o campo 'Habilitado' para conformar login por e-mail
                $update_sql = "UPDATE tbUser SET Email_confere = 1 WHERE IdColaborador = ?";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->bindParam(1, $IdColaborador, PDO::PARAM_STR);
                $update_stmt->execute();

                // Redireciona para a página principal após o login
                header("Location: /conectaosc/index.php");
                exit();
            } else {
                // Credenciais inválidas, exibe um alerta de erro
                echo "<script>alert('E-mail ou senha incorretos. Tente novamente.');</script>";
                die;
            }
        } else {
            // Usuário não encontrado, exibe um alerta de erro
            echo "<script>alert('E-mail ou senha não conferem.');</script>";
            die;
        }
    } else {
        // Caso os campos não tenham sido preenchidos, exibe um alerta de erro
        echo "<script>alert('Por favor, preencha todos os campos.');</script>";
    }
}


if (isset($_GET['confirma'])) {
    // Decodifica o cookie para obter os dados
    $confirma = json_decode(base64_decode($_GET['confirma']), true);

    // Recupera o IdColaborador e o token
    $IdColaborador = $confirma['id'];
    $hashSenha = $confirma['token'];


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
    <link rel="shortcut icon" href="https://www.iteva.com.br/conectaosc/assets/img/icons/icon-48x48.png" />

    <title>Login | ITEVA - Gestão de OSCs</title>

    <link href="https://www.iteva.com.br/conectaosc/assets/css/app.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://www.iteva.com.br/conectaosc/assets/js/app.js"></script>
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
                                        <img src="https://www.iteva.com.br/conectaosc/assets/img/sistema/logo-bgbranco.png" alt="ITEVA LOGO" class="img-fluid fadeInRight" width="200" />
                                    </div>
                                    <form method="post" action="index.php">
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

                    </div>
                </div>
            </div>
        </div>
    </main>
    <footer class="footer">
        <?php require_once $BASE_PATH . '/../template/footer.php' ?>
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