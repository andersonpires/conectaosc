<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];

require_once $BASE_para_PATH . '/api/conectabd/conexao.php';

bootstrap_apply_php_runtime();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['email']) && !empty($_POST['password'])) {
        $email = $_POST['email'];
        $senhaDigitada = $_POST['password'];

        $sql = "SELECT IdColaborador, Tipo, Foto, Nome, Senha FROM tbUser WHERE Email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(1, $email, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $hashed_password = $result['Senha'];
            $IdColaborador = $result['IdColaborador'];

            if (password_verify($senhaDigitada, $hashed_password)) {
                $hora_atual = time() * 10;
                $mes_atual = date('M');
                $token = $hora_atual . $mes_atual;

                $_SESSION['token'] = $token;
                $_SESSION['Cod'] = $IdColaborador;
                $_SESSION['Foto'] = $result['Foto'];
                $_SESSION['Nome'] = $result['Nome'];
                $_SESSION['Tipo'] = $result['Tipo'];
                $_SESSION['ultimoAcessoData'] = 'Agora';

                $update_sql = "UPDATE tbUser SET Email_confere = 1 WHERE IdColaborador = ?";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->bindParam(1, $IdColaborador, PDO::PARAM_STR);
                $update_stmt->execute();

                header('Location: ' . rtrim((string) $BASE_para_URL, '/') . '/dashboard/');
                exit();
            }

            echo "<script>alert('E-mail ou senha incorretos. Tente novamente.');</script>";
            die;
        }

        echo "<script>alert('E-mail ou senha não conferem.');</script>";
        die;
    }

    echo "<script>alert('Por favor, preencha todos os campos.');</script>";
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
    <link rel="shortcut icon" href="<?php echo $BASE_para_URL; ?>/assets/img/icons/icon-48x48.png" />
    <title>Login | ITEVA - Gestão de OSCs</title>
    <link href="<?php echo $BASE_para_URL; ?>/assets/css/app.css" rel="stylesheet">
    <link href="https:
    <script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js"></script>
</head>
<body>
    <main class="d-flex w-100">
        <div class="container d-flex flex-column">
            <div class="row vh-100">
                <div class="col-sm-9 col-md-7 col-lg-5 mx-auto d-table h-100">
                    <div class="d-table-cell align-middle">
                        <div class="text-center mt-4">
                            <h1 class="h2">Bem-vindo de volta</h1>
                            <p class="lead">Faça login em sua conta para continuar</p>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <div class="m-sm-4">
                                    <div class="text-center mb-5">
                                        <img src="<?php echo $BASE_para_URL; ?>/assets/img/sistema/logo-bgbranco.png" alt="ITEVA LOGO" class="img-fluid" width="200" />
                                    </div>
                                    <form method="post" action="">
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
                                            </label>
                                        </div>
                                        <div class="text-center mt-3">
                                            <button type="submit" class="btn btn-lg btn-primary" id="conectar">Conecte-se</button>
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
        <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
    </footer>
</body>
<script>
const togglePasswordBtn = document.getElementById('togglePassword');
const passwordInput = document.getElementById('passwordInput');

togglePasswordBtn.addEventListener('click', function () {
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    const eyeIcon = document.getElementById('eyeIcon');
    eyeIcon.setAttribute('data-feather', type === 'password' ? 'eye' : 'eye-off');
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
});
</script>
</html>

