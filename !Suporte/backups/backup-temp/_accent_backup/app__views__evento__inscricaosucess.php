<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_PATH = $runtime['base_path'];
$BASE_URL = $runtime['base_url'];

// Verifica se as vari?f?????T?f??s?,?veis de sess?f?????T?f??s?,?o BASE_PATH e BASE_URL est?f?????T?f??s?,?o definidas
if (!isset($BASE_PATH) || !isset($BASE_URL)) {
    // Salva a URL atual para redirecionar o usu?f?????T?f??s?,?rio ap?f?????T?f??s?,?s o login
    $redirect_url = urlencode($_SERVER['REQUEST_URI']); // Codifica o endere?f?????T?f??s?,?o atual
    header("Location: " . rtrim((string)$BASE_URL, '/') . "/login/?erro=Ocorreu%20um%20erro!%20Talvez%20voce%20tenha%20perdido%20sua%20ultima%20acao.%20Verifique.");
    exit(); // Garante que o c?f?????T?f??s?,?digo abaixo n?f?????T?f??s?,?o ser?f?????T?f??s?,? executado
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $BASE_PATH . '/app/template/header.php'; ?>
    <style>
        p {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .success-container {
            text-align: center;
            background: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .success-container h1 {
            font-size: 3rem;
            color: #4caf50;
        }

        .success-container p {
            font-size: 1.5rem;
            color: #555;
        }

        .success-icon {
            font-size: 4rem;
            color: #4caf50;
            margin-bottom: 20px;
        }
    </style>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

</head>

<body>
    <div class="wrapper">
        <?php require_once $BASE_PATH . '/app/template/menu.php'; ?>

        <div class="main">
            <?php require_once $BASE_PATH . '/app/template/topo.php'; ?>
            <script src="<?php echo $BASE_URL; ?>/assets/js/app.js"></script>

            <main class="content">
                <div class="success-container">
                    <i class="fas fa-check-circle success-icon"></i>
                    <h1>Parab?f?????T?f??s?,?ns!</h1>
                    <p>Seu cadastro foi feito com sucesso!</p>
                    <!-- <p><strong>Te aguardamos no dia 30/01, ?f?????T?f??s?,?s 8h30.</strong></p> -->
                </div>
            </main>

            <footer class="footer">
            <?php require_once $BASE_PATH . '/app/template/footer.php'; ?>
            </footer>
        </div>
    </div>

</body>

</html>




